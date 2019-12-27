<?php
/**
 * Plugin Name:       gAappointments Plus
 * Plugin URI:        https://github.com/Banzay52/ga-plus
 * Description:       Addon for gAppointments
 * Version:           1.0.4
 * Author:            Serhii Franchuk
 * Author URI:        https://github.com/Banzay52
 */

use sf\gaplus\includes\Gaplus as Gaplus;

if ( ! defined( 'WPINC' ) ) {
	die;
}
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
define( 'GAPLUS_PLUGIN', __FILE__ );
define( 'GAPLUS_PLUGIN_URL', plugin_dir_url(__FILE__) );
define( 'GAPLUS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'GAPLUS_VERSION', '1.0.4' );
define('GAPLUS_DEBUG', 0 );
define('GAPLUS_PARENT_PLUGIN_CLASS', 'ga_appointments_addon' );

require_once GAPLUS_PLUGIN_PATH . 'includes/functions.php';

if ( class_exists( GAPLUS_PARENT_PLUGIN_CLASS ) ) {
	require_once GAPLUS_PLUGIN_PATH . 'includes/Gaplus.php';
	require_once GAPLUS_PLUGIN_PATH . 'includes/Loader.php';
	require_once GAPLUS_PLUGIN_PATH . 'includes/Options.php';
	require_once GAPLUS_PLUGIN_PATH . 'admin/Admin.php';
	require_once GAPLUS_PLUGIN_PATH . 'frontend/Frontend.php';

	$gaplus_plugin = new Gaplus();
	$gaplus_plugin->run();
} else {
	add_action( 'admin_notices', 'parent_plugin_not_active_message' );
}
