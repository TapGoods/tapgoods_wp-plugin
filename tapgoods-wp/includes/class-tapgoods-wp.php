<?php

class Tapgoods {

	private static $instance = null;
	protected $loader;
	protected $plugin_name;
	protected $version;
	protected $shortcodes;
	private $plugin_admin;
	private $tapgoods_taxonomy;

	private function __construct() {
		$this->version     = TAPGOODSWP_VERSION;
		$this->plugin_name = 'tapgoods';
		$this->load_dependencies();
		$this->set_locale( $this->plugin_name );
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_general_hooks();
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __clone() {}
	private function __wakeup() {}

	private function load_dependencies() {
		$includes = [
			'includes/class-tapgoods-loader.php',         // Class for adding actions and filers
			'admin/class-tapgoods-admin.php',             // Class for WP Admin features
			'includes/class-tapgoods-i18n.php',           // Loads text domain for localization
			'includes/tapgoods-core-functions.php',       // Core functions for admin and public
			'includes/tapgoods-formatting-functions.php', // Core functions for admin and public
			'includes/class-tapgoods-shortcodes.php',     // Registers Shortcodes
			'includes/class-tapgoods-post-types.php',     // Regusters Taxonomies and Post Types
			'public/class-tapgoods-public.php',           // Class for frontend features
			'includes/class-tapgoods-encryption.php',     // Class for encryption/decryption methods
			// 'includes/class-tapgoods-connection.php',     // API Connection Controller
			// 'includes/class-tapgoods-api-exception.php',  // API Exception Classes
			// 'includes/class-tapgoods-api-request.php',    // API Request Class
			// 'includes/class-tapgoods-api-response.php',   // API Response Class
			'includes/class-tapgoods-filesystem.php',     // Filesystem utility class
			'includes/class-tapgoods-helpers.php',        // Filesystem utility class
		];

		foreach ( $includes as $file ) {
			require_once TAPGOODS_PLUGIN_PATH . $file;
		}

		$this->loader = new Tapgoods_Loader();
	}

	private function set_locale( $domain ) {
		$tapgoods_i18n = new Tapgoods_i18n( $domain );
		$this->loader->add_action( 'plugins_loaded', $tapgoods_i18n, 'load_plugin_textdomain' );
	}

	private function define_admin_hooks() {
		$this->plugin_admin = new Tapgoods_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'current_screen', $this->plugin_admin, 'conditional_includes', 10, 0 );
		$this->loader->add_action( 'admin_enqueue_scripts', $this->plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $this->plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $this->plugin_admin, 'tapgoods_admin_menu' );
		$this->loader->add_action( 'load-edit-tags.php', $this->plugin_admin, 'taxonomy_intercept' );

		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_name . '.php' );
		$this->loader->add_filter( 'plugin_action_links_' . $plugin_basename, $this->plugin_admin, 'add_action_links' );
		$this->loader->add_filter( 'register_taxonomy_args', $this->plugin_admin, 'tax_args_filter', 10, 2 );

		$this->loader->add_filter( 'available_permalink_structure_tags', $this, 'tg_add_available_tags', 10, 1 );

		$this->loader->add_action( 'wp_ajax_tg_update_connection', $this->plugin_admin, 'tg_update_connection', 10, 1 );
		$this->loader->add_action( 'tg_save_custom_css', $this->plugin_admin, 'tg_save_styles', 10, 1 );
		$this->loader->add_action( 'tg_save_advanced', $this->plugin_admin, 'tg_save_advanced', 10, 0 );
		$this->loader->add_action( 'tg_save_dev', $this->plugin_admin, 'tg_save_dev', 10, 0 );
	}

	private function define_public_hooks() {

		$plugin_public = new Tapgoods_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		add_action( 'parse_request', 'tg_parse_request', 10, 1 );
	}

	private function define_general_hooks() {
		if ( defined( 'TAPGOODS_DEV' ) && TAPGOODS_DEV ) {
			// development environement hooks
		}
	}

	public function tg_add_available_tags( $available_tags ) {

		$tg_tags = array(
			'tg_inventory_base' => '',
			'tg_category'       => '',
			'tg_tags'           => '',
			'tg_location'       => '',
		);

		$new_tags = array_merge( $available_tags, $tg_tags );
		return $new_tags;
	}

	public function get_admin() {
		return $this->plugin_admin;
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	// The reference to the class that orchestrates the hooks with the plugin.
	public function get_loader() {
		return $this->loader;
	}

	public function get_version() {
		return $this->version;
	}

	public function init() {
		$this->loader->run();
		$this->shortcodes = Tapgoods_Shortcodes::get_instance();
	}
}
