<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://trileotech.com/
 * @since             1.0.0
 * @package           Tlt_Search
 *
 * @wordpress-plugin
 * Plugin Name:       TLTSearch
 * Plugin URI:        https://trileotech.com/
 * Description:       This is a description of the plugin.
 * Version:           1.0.0
 * Author:            TLT Suite
 * Author URI:        https://trileotech.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       tlt-search
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}



/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'TLT_SEARCH_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-tlt-search-activator.php
 */
function activate_tlt_search() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-tlt-search-activator.php';
	Tlt_Search_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-tlt-search-deactivator.php
 */
function deactivate_tlt_search() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-tlt-search-deactivator.php';
	Tlt_Search_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_tlt_search' );
register_deactivation_hook( __FILE__, 'deactivate_tlt_search' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-tlt-search.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_tlt_search() {

	$plugin = new Tlt_Search();
	$plugin->run();
	
}
run_tlt_search();

