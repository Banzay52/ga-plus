<?php
namespace sf\gaplus\includes;

use sf\gaplus\includes\Loader as Loader;
use sf\gaplus\admin\Admin as Admin;
use sf\gaplus\frontend\Frontend as Frontend;

class Gaplus {
	protected $loader;
	protected $plugin_slug = '';
	protected $activated;
	protected $version;
	protected $plugin_admin, $plugin_public;

	public function __construct() {
		if ( 1 !== GAPLUS_DEBUG ) {
			if ( defined( 'GAPLUS_VERSION' ) ) {
				$this->version = GAPLUS_VERSION;
			} else {
				$this->version = $wp_version;
			}
		} else {
			$this->version = time();
		}
	}

	public function init() {
		$this->loader =  new Loader();
		$this->plugin_slug = 'gaplus';
		$this->plugin_admin = new Admin( $this->get_slug(), $this->get_version() );
		$this->define_admin_hooks();
		$this->plugin_public = new Frontend( $this->get_slug(), $this->get_version() );
		$this->define_public_hooks();
		$this->loader->run();
	}

	private function define_admin_hooks() {
		$plugin_admin = $this->plugin_admin;
		$this->loader->g_add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->g_add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->g_add_action( 'cmb2_admin_init', $plugin_admin, 'add_notification_filters', 91 );
		$this->loader->g_add_action( 'cmb2_admin_init', $plugin_admin, 'gaplus_ga_services_add_location_field', 90);
		$this->loader->g_add_action( 'cmb2_admin_init', $plugin_admin, 'gaplus_ga_services_add_custom_fields_metabox', 89);
		$this->loader->g_add_action( 'cmb2_admin_init', $plugin_admin, 'ga_service_notifications_metabox', 88);
		$this->loader->g_add_action( 'cmb2_render_email_msg_field', $plugin_admin, 'draw_email_msg_field', 10, 5);
		$this->loader->g_add_action( 'wp_ajax_update_cf_template', $plugin_admin, 'update_cft_field', 10);
	}

	private function define_public_hooks() {
		$plugin_public = $this->plugin_public;
		$this->loader->g_add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->g_add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->g_add_action( 'wp_enqueue_scripts', $plugin_public, 'gaplus_add_cft_to_appointments');
		$this->loader->g_add_action( 'wp_ajax_save_app_cf_data', $plugin_public, 'save_app_cf_data', 10);
	}

	public function run() {
		$this->init();
	}

	public function get_slug() {
		return $this->plugin_slug;
	}

	public function get_loader() {
		return $this->loader;
	}

	public function get_version() {
		return $this->version;
	}
}
