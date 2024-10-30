<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://TheWebDeveloper.io
 * @since             1.0.0
 * @package           Media_File_Organizer
 *
 * @wordpress-plugin
 * Plugin Name:       Media File Organizer
 * Description:       Organize files and folders inside the media library directory by moving them to sub directories or renaming them while also updating all references to them in pages and posts.
 * Version:           1.0.1
 * Author:            Sherif Mesallam
 * Author URI:        http://TheWebDeveloper.io
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       media-file-organizer
 * Domain Media_File_Organizer_Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


define( 'MEDIA_FILE_ORGANIZER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MEDIA_FILE_ORGANIZER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-media-file-organizer-activator.php
 */
function activate_media_file_organizer() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-media-file-organizer-activator.php';
	Media_File_Organizer_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-media-file-organizer-deactivator.php
 */
function deactivate_media_file_organizer() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-media-file-organizer-deactivator.php';
	Media_File_Organizer_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_media_file_organizer' );
register_deactivation_hook( __FILE__, 'deactivate_media_file_organizer' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-media-file-organizer.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_media_file_organizer() {

	$plugin = new Media_File_Organizer();
	$plugin->run();

}
run_media_file_organizer();
