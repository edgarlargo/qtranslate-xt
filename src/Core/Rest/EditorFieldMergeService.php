<?php

namespace QTX\Core\Rest;

use QTX\Core\Multilingual\LanguageCatalog;
use QTX\Core\Multilingual\MultilingualBuilder;
use QTX\Core\Multilingual\MultilingualEntry;
use QTX\Core\Multilingual\MultilingualParser;

final class EditorFieldMergeService {
    private LanguageCatalog $catalog;
    private MultilingualParser $parser;
    private MultilingualBuilder $builder;

    public function __construct( LanguageCatalog $catalog, MultilingualParser $parser, ?MultilingualBuilder $builder = null ) {
        $this->catalog = $catalog;
        $this->parser = $parser;
        $this->builder = $builder ?? new MultilingualBuilder();
    }

    public function project( string $raw ): EditorFieldState {
        $value = $this->parser->parse( $raw );

        return new EditorFieldState(
            $raw,
            $value->translations(),
            $value->syntax(),
            $this->revision( $raw ),
            $value->diagnostics()
        );
    }

    public function merge( string $currentRaw, string $expectedRevision, string $language, string $newValue ): EditorMergeResult {
        $currentRevision = $this->revision( $currentRaw );
        if ( ! hash_equals( $currentRevision, $expectedRevision ) ) {
            return new EditorMergeResult( 'conflict', $currentRaw, $currentRevision );
        }
        if ( ! $this->catalog->contains( $language ) ) {
            return new EditorMergeResult( 'invalid_language', $currentRaw, $currentRevision );
        }
        $parsed = $this->parser->parse( $currentRaw );
        if ( $parsed->diagnostics() !== array() || $this->hasDuplicateLanguageBlocks( $parsed->entries() ) ) {
            return new EditorMergeResult( 'unsupported_source', $currentRaw, $currentRevision );
        }
        $translations = $parsed->translations();
        $translations[ $language ] = $newValue;
        $ordered = array();
        foreach ( $this->catalog->codes() as $code ) {
            if ( array_key_exists( $code, $translations ) ) {
                $ordered[ $code ] = $translations[ $code ];
            }
        }
        $syntax = in_array( $parsed->syntax(), array( 'bracket', 'comment', 'curly' ), true )
            ? $parsed->syntax()
            : 'bracket';
        $merged = $this->builder->buildTranslations( $ordered, $syntax );

        return new EditorMergeResult( 'merged', $merged, $this->revision( $merged ) );
    }

    /** @param MultilingualEntry[] $entries */
    private function hasDuplicateLanguageBlocks( array $entries ): bool {
        $seen = array();
        foreach ( $entries as $entry ) {
            if ( $entry->kind() !== MultilingualEntry::OPENING || $entry->language() === null ) {
                continue;
            }
            $language = strtolower( $entry->language() );
            if ( isset( $seen[ $language ] ) ) {
                return true;
            }
            $seen[ $language ] = true;
        }

        return false;
    }

    private function revision( string $raw ): string {
        return hash( 'sha256', $raw );
    }
}
