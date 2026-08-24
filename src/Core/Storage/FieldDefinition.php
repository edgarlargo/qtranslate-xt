<?php

namespace QTX\Core\Storage;

use InvalidArgumentException;

final class FieldDefinition {
    public const STORAGE_OPTION = 'option';
    public const STORAGE_POST_META = 'post';
    public const STORAGE_TERM_META = 'term';
    public const STORAGE_USER_META = 'user';

    public const VALUE_TEXT = 'text';
    public const VALUE_HTML = 'html';

    private string $storage;
    private string $key;
    private string $valueType;

    public function __construct( string $storage, string $key, string $valueType = self::VALUE_TEXT ) {
        if ( ! in_array( $storage, self::storages(), true ) ) {
            throw new InvalidArgumentException( 'Unsupported multilingual storage type: ' . $storage );
        }
        if ( $key === '' || preg_match( '/[\x00-\x1F\x7F]/', $key ) ) {
            throw new InvalidArgumentException( 'A non-empty storage key without control characters is required.' );
        }
        if ( ! in_array( $valueType, self::valueTypes(), true ) ) {
            throw new InvalidArgumentException( 'Unsupported multilingual value type: ' . $valueType );
        }

        $this->storage = $storage;
        $this->key = $key;
        $this->valueType = $valueType;
    }

    public function storage(): string {
        return $this->storage;
    }

    public function key(): string {
        return $this->key;
    }

    public function valueType(): string {
        return $this->valueType;
    }

    public function identifier(): string {
        return $this->storage . ':' . $this->key;
    }

    public static function storages(): array {
        return array(
            self::STORAGE_OPTION,
            self::STORAGE_POST_META,
            self::STORAGE_TERM_META,
            self::STORAGE_USER_META,
        );
    }

    public static function valueTypes(): array {
        return array( self::VALUE_TEXT, self::VALUE_HTML );
    }
}
