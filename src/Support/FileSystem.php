<?php

namespace TLTSuite\TLTSearch\Support;

class FileSystem {

	public static function delete_directory( string $directory ): void {

		if ( ! is_dir( $directory ) ) {
			return;
		}

		$items = scandir( $directory );

		foreach ( $items as $item ) {

			if ( '.' === $item || '..' === $item ) {
				continue;
			}

			$path = $directory . DIRECTORY_SEPARATOR . $item;

			if ( is_dir( $path ) ) {
				self::delete_directory( $path );
			} else {
				unlink( $path );
			}
		}

		rmdir( $directory );
	}
}