<?php

namespace QTX\Core\Multilingual;

final class MultilingualBuilder {
    public function build( MultilingualValue $value, ?string $syntax = null, bool $canonicalize = false ): string {
        if ( ! $canonicalize && ! $value->isChanged() ) {
            return $value->raw();
        }

        return $this->buildTranslations( $value->translations(), $syntax ?? 'bracket' );
    }

    /** @param string[] $translations */
    public function buildTranslations( array $translations, string $syntax = 'bracket', bool $includeClosing = true ): string {
        $same = $this->allTheSame( $translations );
        if ( $same !== null ) {
            return $same;
        }

        $result = '';
        foreach ( $translations as $language => $text ) {
            if ( empty( $text ) ) {
                continue;
            }
            if ( $syntax === 'comment' ) {
                $result .= '<!--:' . $language . '-->' . $text . '<!--:-->';
            } elseif ( $syntax === 'curly' ) {
                $result .= '{:' . $language . '}' . $text;
            } else {
                $result .= '[:' . $language . ']' . $text;
            }
        }

        if ( $includeClosing && $result !== '' && $syntax === 'curly' ) {
            $result .= '{:}';
        } elseif ( $includeClosing && $result !== '' && $syntax !== 'comment' ) {
            $result .= '[:]';
        }

        return $result;
    }

    /** @param string[] $translations */
    public function buildBySeparator( array $translations, string $separatorPattern ): string {
        $same = $this->allTheSame( $translations );
        if ( $same !== null ) {
            return $same;
        }

        $lines   = array();
        $indexes = array();
        foreach ( $translations as $language => $text ) {
            $lines[ $language ]   = preg_split( $separatorPattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE );
            $indexes[ $language ] = 0;
        }

        $result = '';
        while ( true ) {
            $done   = true;
            $join   = array();
            $suffix = '';
            foreach ( $lines as $language => $parts ) {
                ++$indexes[ $language ];
                if ( ! isset( $parts[ $indexes[ $language ] ] ) ) {
                    continue;
                }
                $part = $parts[ $indexes[ $language ] ];
                if ( preg_match( $separatorPattern, $part ) ) {
                    $suffix = $part;
                    ++$indexes[ $language ];
                    $part = $parts[ $indexes[ $language ] ] ?? false;
                }
                $done              = false;
                $join[ $language ] = $part;
            }
            if ( $done ) {
                break;
            }
            $result .= $this->buildTranslations( $join, 'bracket' ) . $suffix;
        }

        return $result;
    }

    /** @param string[] $translations */
    public function buildByLine( array $translations, string $lineEnding = PHP_EOL ): string {
        $same = $this->allTheSame( $translations );
        if ( $same !== null ) {
            return $same;
        }

        $lines = array();
        foreach ( $translations as $language => $text ) {
            $lines[ $language ] = preg_split( '/\r?\n\r?/', $text );
        }

        $result = '';
        for ( $index = 0; true; ++$index ) {
            $done = true;
            $join = array();
            foreach ( $lines as $language => $languageLines ) {
                if ( count( $languageLines ) <= $index ) {
                    continue;
                }
                $done = false;
                $line = $languageLines[ $index ];
                if ( empty( $line ) ) {
                    continue;
                }
                $join[ $language ] = $line;
            }
            if ( $done ) {
                break;
            }
            $result .= $this->buildTranslations( $join, 'bracket' ) . $lineEnding;
        }

        return $result;
    }

    /** @param string[] $translations */
    private function allTheSame( array $translations ): ?string {
        $first = null;
        foreach ( $translations as $text ) {
            if ( empty( $text ) ) {
                continue;
            }
            $first = $text;
            break;
        }
        if ( empty( $first ) ) {
            return '';
        }
        foreach ( $translations as $text ) {
            if ( $text != $first ) {
                return null;
            }
        }

        return $first;
    }
}
