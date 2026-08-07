<?php

namespace TLTSuite\TLTSearch\Support;

class Logger {

	public static function error( string $message, array $context = [] ): void {
		error_log( sprintf( 'TLT Search: %s %s', $message, wp_json_encode( $context ) ) );
	}
}