<?php

use PHPUnit\Framework\TestCase;

final class BlockEditorRevisionContractTest extends TestCase {
    public function testRuntimeRequiresRouteScopedRevisionChecksAndClientSendsThem(): void {
        $root = dirname( __DIR__, 2 );
        $php = file_get_contents( $root . '/src/admin/block_editor.php' );
        $js = file_get_contents( $root . '/js/block-editor.js' );

        self::assertStringContainsString( 'request_targets_registered_post( $request )', $php );
        self::assertStringContainsString( 'hash_equals( hash( \'sha256\', $current_raw ), $revisions[ $field ] )', $php );
        self::assertStringContainsString( "'qtx_editor_conflict'", $php );
        self::assertStringContainsString( "'status' => 409", $php );
        self::assertStringContainsString( "'(?:/autosaves)?$#'", $php );
        self::assertStringContainsString( "'qtx_editor_revisions': post.qtx_editor_revisions", $js );
    }
}
