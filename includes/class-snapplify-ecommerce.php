<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://snapplify.com
 * @since      1.0.0
 *
 * @package    Snapplify_ECommerce
 * @subpackage Snapplify_ECommerce/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Snapplify_ECommerce
 * @subpackage Snapplify_ECommerce/includes
 */
class Snapplify_ECommerce
{


	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @var      Snapplify_ECommerce_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		if (defined('SNAPPLIFY_CATAlog_VERSION')) {
			$this->version = SNAPPLIFY_CATAlog_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'snapplify-ecommerce';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Snapplify_ECommerce_Loader. Orchestrates the hooks of the plugin.
	 * - Snapplify_ECommerce_i18n. Defines internationalization functionality.
	 * - Snapplify_ECommerce_Admin. Defines all hooks for the admin area.
	 * - Snapplify_ECommerce_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 */
	private function load_dependencies()
	{
		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-snapplify-ecommerce-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-snapplify-ecommerce-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-snapplify-ecommerce-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-snapplify-ecommerce-public.php';

		$this->loader = new Snapplify_ECommerce_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Snapplify_ECommerce_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 */
	private function set_locale()
	{
		$plugin_i18n = new Snapplify_ECommerce_I18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 */
	private function define_admin_hooks()
	{
		$plugin_admin = new Snapplify_ECommerce_Admin($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
		$this->loader->add_action('woocommerce_admin_field_auth-token', $plugin_admin, 'wcsnapplify_add_admin_field_auth_token');
		$this->loader->add_action('woocommerce_admin_field_api-url', $plugin_admin, 'wcsnapplify_add_admin_field_api_url');
		$this->loader->add_filter('woocommerce_get_sections_products', $plugin_admin, 'wcsnapplify_add_section');
		$this->loader->add_filter('woocommerce_get_settings_products', $plugin_admin, 'wcsnapplify_all_settings', 10, 2);
		$this->loader->add_filter('woocommerce_product_data_tabs', $plugin_admin, 'wcsnapplify_custom_product_tab');
		$this->loader->add_action('woocommerce_product_data_panels', $plugin_admin, 'wcsnapplify_custom_tab_data');
		$this->loader->add_filter('plugin_action_links_snapplify-ecommerce/snapplify-ecommerce.php', $plugin_admin, 'wcsnapplify_settings_link');
		$this->loader->add_action('admin_menu', $plugin_admin, 'wcsnapplify_menu');
		$this->loader->add_action('admin_menu', $plugin_admin, 'add_drafts_admin_menu_item');

		// Fulfillments
		$this->loader->add_action('init', $plugin_admin, 'register_snapplify_processing_status');
		$this->loader->add_filter('wc_order_statuses', $plugin_admin, 'add_snapplify_processing_to_order_statuses');
		$this->loader->add_action('admin_head', $plugin_admin, 'add_snapplify_processing_color_to_admin_order_list');
		$this->loader->add_action('woocommerce_order_payment_status_changed', $plugin_admin, 'trigger_snapplify_fulfillment');

		$this->loader->add_action('wc_snap_log_exec', $plugin_admin, 'wcsnapplify_eg_log_action_data');

		$this->loader->add_action('doing_it_wrong_run', $plugin_admin, 'snap_verbose_trace');

		// display product image from cloud server
		$this->loader->add_filter('woocommerce_admin_order_item_thumbnail', $plugin_admin, 'wcsnapplify_admin_order_item_image_from_cloud', 10, 3);
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 */
	private function define_public_hooks()
	{
		$plugin_public = new Snapplify_ECommerce_Public($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

		// display product image from cloud server
		$this->loader->add_filter('woocommerce_single_product_image_thumbnail_html', $plugin_public, 'wcsnapplify_product_image_from_cloud', 10, 2);
		$this->loader->add_filter('woocommerce_placeholder_img', $plugin_public, 'wcsnapplify_product_placeholder_override_image_from_cloud', 10, 3);
		$this->loader->add_filter('woocommerce_before_shop_loop_item_title', $plugin_public, 'wcsnapplify_product_loop_image_from_cloud', 10);
		$this->loader->add_filter('woocommerce_cart_item_thumbnail', $plugin_public, 'wcsnapplify_cart_product_image_from_cloud', 10, 3);
		$this->loader->add_filter('woocommerce_order_item_thumbnail', $plugin_public, 'wcsnapplify_order_item_image_from_cloud', 10, 2);
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Snapplify_ECommerce_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}
}
