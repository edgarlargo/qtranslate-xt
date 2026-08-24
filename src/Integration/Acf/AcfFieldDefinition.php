<?php

namespace QTX\Integration\Acf;

final class AcfFieldDefinition {
    private string $key;
    private string $type;
    private string $valueType;

    public function __construct( string $key, string $type, string $valueType ) {
        $this->key = $key;
        $this->type = $type;
        $this->valueType = $valueType;
    }

    public function key(): string {
        return $this->key;
    }

    public function type(): string {
        return $this->type;
    }

    public function valueType(): string {
        return $this->valueType;
    }
}
