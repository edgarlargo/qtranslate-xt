<?php

namespace QTX\Core\Integration;

final class BuiltinModuleProvider {
    private string $moduleId;
    private string $loader;

    public function __construct( string $moduleId, string $canonicalLoader ) {
        $this->moduleId = $moduleId;
        $this->loader = $canonicalLoader;
    }

    public function moduleId(): string {
        return $this->moduleId;
    }

    public function loader(): string {
        return $this->loader;
    }

    public function load(): void {
        require_once $this->loader;
    }
}
