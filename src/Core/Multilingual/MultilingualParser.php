<?php

namespace QTX\Core\Multilingual;

final class MultilingualParser {
    /** @var string[] */
    private $enabledLanguages;
    /** @var string */
    private $currentLanguage;
    /** @var string */
    private $languageCodePattern;
    /** @var MultilingualDetector */
    private $detector;
    /** @var int */
    private $cacheCapacity;
    /** @var MultilingualValue[] */
    private $rawCache = array();
    /** @var MultilingualValue[] */
    private $blocksCache = array();
    /** @var string|null */
    private $lastRaw;
    /** @var MultilingualValue|null */
    private $lastRawValue;
    /** @var string[]|null */
    private $lastBlocks;
    /** @var MultilingualValue|null */
    private $lastBlocksValue;

    /** @param string[] $enabledLanguages */
    public function __construct(
        array $enabledLanguages,
        string $currentLanguage,
        string $languageCodePattern = '[a-z]{2,3}',
        int $cacheCapacity = 64
    ) {
        if ( $cacheCapacity < 0 ) {
            throw new \InvalidArgumentException( 'Cache capacity cannot be negative.' );
        }
        $this->enabledLanguages    = array_values( $enabledLanguages );
        $this->currentLanguage     = $currentLanguage;
        $this->languageCodePattern = $languageCodePattern;
        $this->detector            = new MultilingualDetector( $languageCodePattern );
        $this->cacheCapacity       = $cacheCapacity;
    }

    public function parse( string $raw ): MultilingualValue {
        if ( $this->cacheCapacity > 0 && $this->lastRawValue !== null && $this->lastRaw === $raw ) {
            return $this->lastRawValue;
        }
        $key = hash( 'sha256', $raw );
        if ( $this->cacheCapacity > 0 && isset( $this->rawCache[ $key ] ) && $this->rawCache[ $key ]->raw() === $raw ) {
            $value = $this->rawCache[ $key ];
        } else {
            $value = $this->parseTokenList( $raw, $this->tokenize( $raw ) );
            $this->remember( $this->rawCache, $key, $value );
        }
        $this->lastRaw      = $raw;
        $this->lastRawValue = $value;

        return $value;
    }

    /** @param string[] $blocks */
    public function parseBlocks( array $blocks ): MultilingualValue {
        if ( $this->cacheCapacity > 0 && $this->lastBlocksValue !== null && $this->lastBlocks === $blocks ) {
            return $this->lastBlocksValue;
        }
        $hash = hash_init( 'sha256' );
        foreach ( $blocks as $block ) {
            hash_update( $hash, strlen( $block ) . ':' );
            hash_update( $hash, $block );
        }
        $key = hash_final( $hash );
        if ( $this->cacheCapacity > 0 && isset( $this->blocksCache[ $key ] ) ) {
            $value = $this->blocksCache[ $key ];
        } else {
            $value = $this->parseTokenList( implode( '', $blocks ), $blocks );
            $this->remember( $this->blocksCache, $key, $value );
        }
        $this->lastBlocks      = $blocks;
        $this->lastBlocksValue = $value;

        return $value;
    }

    /** @param MultilingualValue[] $cache */
    private function remember( array &$cache, string $key, MultilingualValue $value ): void {
        if ( $this->cacheCapacity === 0 ) {
            return;
        }
        if ( count( $cache ) >= $this->cacheCapacity ) {
            array_shift( $cache );
        }
        $cache[ $key ] = $value;
    }

    /**
     * @param string[] $tokens
     */
    private function parseTokenList( string $raw, array $tokens ): MultilingualValue {
        $entries      = array();
        $diagnostics  = array();
        $syntaxes     = array();
        $openingCount = 0;
        $closingCount = 0;
        $previousKind = null;

        foreach ( $tokens as $token ) {
            $opening = $this->opening( $token );
            if ( $opening !== null ) {
                if ( $previousKind === MultilingualEntry::OPENING ) {
                    $diagnostics[] = 'adjacent-language-markers';
                }
                $entries[]    = new MultilingualEntry( MultilingualEntry::OPENING, $token, $opening['language'], $opening['syntax'] );
                $syntaxes[]   = $opening['syntax'];
                $openingCount++;
                $previousKind = MultilingualEntry::OPENING;
                continue;
            }

            $closingSyntax = $this->closingSyntax( $token );
            if ( $closingSyntax !== null ) {
                $entries[]    = new MultilingualEntry( MultilingualEntry::CLOSING, $token, null, $closingSyntax );
                $syntaxes[]   = $closingSyntax;
                $closingCount++;
                $previousKind = MultilingualEntry::CLOSING;
                continue;
            }

            $entries[]    = new MultilingualEntry( MultilingualEntry::TEXT, $token );
            $previousKind = MultilingualEntry::TEXT;
        }

        if ( $openingCount > 0 && $closingCount === 0 ) {
            $diagnostics[] = 'missing-closing-marker';
        }
        if ( $openingCount === 0 && $closingCount > 0 ) {
            $diagnostics[] = 'closing-marker-without-language-marker';
        }

        $uniqueSyntaxes = array_values( array_unique( $syntaxes ) );
        $syntax         = count( $uniqueSyntaxes ) === 0 ? 'plain' : ( count( $uniqueSyntaxes ) === 1 ? $uniqueSyntaxes[0] : 'mixed' );
        if ( $syntax === 'mixed' ) {
            $diagnostics[] = 'mixed-syntax';
        }

        $found        = array();
        $translations = $this->splitEntries( $entries, true, $found );
        $encoded      = $this->splitEntries( $entries, false );
        $available    = array();

        if ( count( $tokens ) > 1 ) {
            foreach ( $encoded as $language => $text ) {
                if ( ! empty( trim( $text ) ) ) {
                    $available[] = $language;
                }
            }
            if ( count( $available ) === 0 ) {
                $available[] = $this->currentLanguage;
            }
        }

        return new MultilingualValue(
            $raw,
            $syntax,
            $entries,
            $translations,
            $encoded,
            $available,
            $diagnostics,
            $this->detector->isMultilingual( $raw )
        );
    }

    /** @return string[] */
    private function tokenize( string $raw ): array {
        $language = $this->languageCodePattern;
        $regex    = "#(<!--:$language-->|<!--:-->|\[:$language\]|\[:\]|\{:$language\}|\{:\})#ism";
        $tokens   = preg_split( $regex, $raw, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );

        return $tokens === false ? array( $raw ) : $tokens;
    }

    /** @return array{language:string,syntax:string}|null */
    private function opening( string $token ): ?array {
        $language = $this->languageCodePattern;
        $patterns = array(
            'comment' => "#^<!--:($language)-->$#ism",
            'bracket' => "#^\[:($language)]$#ism",
            'curly'   => "#^{:($language)}$#ism",
        );
        foreach ( $patterns as $syntax => $pattern ) {
            if ( preg_match( $pattern, $token, $matches ) === 1 ) {
                return array( 'language' => $matches[1], 'syntax' => $syntax );
            }
        }

        return null;
    }

    private function closingSyntax( string $token ): ?string {
        if ( $token === '<!--:-->' ) {
            return 'comment';
        }
        if ( $token === '[:]' ) {
            return 'bracket';
        }
        if ( $token === '{:}' ) {
            return 'curly';
        }

        return null;
    }

    /**
     * @param MultilingualEntry[] $entries
     * @param bool[]              $found
     * @return string[]
     */
    private function splitEntries( array $entries, bool $includeNeutral, array &$found = array() ): array {
        $result = array();
        if ( $includeNeutral ) {
            foreach ( $this->enabledLanguages as $language ) {
                $result[ $language ] = '';
            }
        }

        $currentLanguage = null;
        foreach ( $entries as $entry ) {
            if ( $entry->kind() === MultilingualEntry::OPENING ) {
                $currentLanguage = $entry->language();
                continue;
            }
            if ( $entry->kind() === MultilingualEntry::CLOSING ) {
                $currentLanguage = null;
                continue;
            }

            if ( $currentLanguage !== null ) {
                if ( ! isset( $result[ $currentLanguage ] ) ) {
                    $result[ $currentLanguage ] = '';
                }
                $result[ $currentLanguage ] .= $entry->raw();
                $found[ $currentLanguage ] = true;
                $currentLanguage           = null;
            } elseif ( $includeNeutral ) {
                foreach ( $this->enabledLanguages as $language ) {
                    $result[ $language ] .= $entry->raw();
                }
            }
        }

        foreach ( $result as $language => $text ) {
            $result[ $language ] = trim( $text );
        }

        return $result;
    }
}
