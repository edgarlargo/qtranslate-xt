<?php

namespace QTX\Core\Rest;

final class EditorFieldState {
    private string $raw;
    /** @var string[] */
    private array $translations;
    private string $syntax;
    private string $revision;
    /** @var string[] */
    private array $diagnostics;

    /** @param string[] $translations
     *  @param string[] $diagnostics
     */
    public function __construct( string $raw, array $translations, string $syntax, string $revision, array $diagnostics ) {
        $this->raw = $raw;
        $this->translations = $translations;
        $this->syntax = $syntax;
        $this->revision = $revision;
        $this->diagnostics = $diagnostics;
    }

    public function raw(): string { return $this->raw; }
    /** @return string[] */
    public function translations(): array { return $this->translations; }
    public function syntax(): string { return $this->syntax; }
    public function revision(): string { return $this->revision; }
    /** @return string[] */
    public function diagnostics(): array { return $this->diagnostics; }
}
