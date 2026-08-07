<?php

/**
 * Fired during plugin activation
 *
 * @link       https://trileotech.com/
 * @since      1.0.0
 *
 * @package    Tlt_Search
 * @subpackage Tlt_Search/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Tlt_Search
 * @subpackage Tlt_Search/includes
 * @author     TLT Suite <info@trileotech.com>
 */
class Tlt_Search_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		\TLTSuite\TLTSearch\Analytics\SearchLogger::create_table();
		\TLTSuite\TLTSearch\Merchandising\PinManager::create_table();

		if ( ! wp_next_scheduled( 'tlt_search_cleanup_logs' ) ) {
			wp_schedule_event( time(), 'daily', 'tlt_search_cleanup_logs' );
		}
	}

}
