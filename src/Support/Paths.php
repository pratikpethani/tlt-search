<?php

namespace TLTSuite\TLTSearch\Support;

class Paths {

	public static function index_directory(): string {

		$upload_dir = wp_upload_dir();

		$directory = trailingslashit( $upload_dir['basedir'] ) . 'tlt-search';

		if ( ! file_exists( $directory ) ) {
			wp_mkdir_p( $directory );
		}

		return $directory;
	}

}