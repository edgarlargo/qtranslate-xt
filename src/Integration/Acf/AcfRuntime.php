<?php

namespace QTX\Integration\Acf;

final class AcfRuntime {
    private bool $available;
    private ?string $version;
    private bool $pro;

    public function __construct( bool $available, ?string $version, bool $pro ) {
        $this->available = $available;
        $this->version = $version;
        $this->pro = $pro;
    }

    public function isAvailable(): bool {
        return $this->available;
    }

    public function version(): ?string {
        return $this->version;
    }

    public function isPro(): bool {
        return $this->pro;
    }
}
