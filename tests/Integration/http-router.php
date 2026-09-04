<?php

/**
 * Router for the PHP built-in server used by release HTTP smoke tests.
 *
 * Existing files are served directly. Every other request is routed through
 * WordPress' front controller, matching the effective result of the standard
 * Apache/Nginx permalink rules without modifying the disposable installation.
 */
$document_root = $_SERVER['DOCUMENT_ROOT'] ?? '';
$request_path  = parse_url( $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH );

if ( ! is_string( $request_path ) ) {
    $request_path = '/';
}

$candidate = rtrim( $document_root, '/\\' ) . DIRECTORY_SEPARATOR
    . ltrim( rawurldecode( $request_path ), '/\\' );

if ( $request_path !== '/' && is_file( $candidate ) ) {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF']    = '/index.php';
require rtrim( $document_root, '/\\' ) . '/index.php';
