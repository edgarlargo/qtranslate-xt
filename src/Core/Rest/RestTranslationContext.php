<?php

namespace QTX\Core\Rest;

final class RestTranslationContext {
    public const MODE_TRANSLATED = 'translated';
    public const MODE_RAW = 'raw';

    private bool $allowed;
    private ?string $language;
    private string $mode;
    private ?string $error;

    public function __construct( bool $allowed, ?string $language, string $mode, ?string $error = null ) {
        $this->allowed = $allowed;
        $this->language = $language;
        $this->mode = $mode;
        $this->error = $error;
    }

    public function isAllowed(): bool {
        return $this->allowed;
    }

    public function language(): ?string {
        return $this->language;
    }

    public function mode(): string {
        return $this->mode;
    }

    public function error(): ?string {
        return $this->error;
    }
}
