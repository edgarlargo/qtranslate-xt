<?php

namespace QTX\Core\Rest;

final class EditorMergeResult {
    private string $status;
    private string $raw;
    private string $revision;

    public function __construct( string $status, string $raw, string $revision ) {
        $this->status = $status;
        $this->raw = $raw;
        $this->revision = $revision;
    }

    public function status(): string { return $this->status; }
    public function raw(): string { return $this->raw; }
    public function revision(): string { return $this->revision; }
    public function isMerged(): bool { return $this->status === 'merged'; }
}
