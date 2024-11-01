<?php

/**
 * The plugin bootstrap file
 *
 * @link              https://snapplify.com
 * @since             1.0.0
 * @package           Snapplify_ECommerce
 *
 * @wordpress-plugin
 * Plugin Name:       Snapplify E-Commerce
 * Plugin URI:        https://www.snapplify.com/snapplify-ecommerce
 * Description:       Offer Snapplify products in your WooCommerce Store that can be redeemed using a voucher.
 * Version:           1.1.4
 * Author:            Snapplify
 * Author URI:        https://snapplify.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       snapplify-ecommerce
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
require_once plugin_dir_path(__FILE__) . 'lib/class-woometahandler.php';
require_once plugin_dir_path(__FILE__) . 'lib/PipelineStages/class-createproduct.php';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-snapplify-ecommerce-activator.php
 */
function activate_snapplify_ecommerce()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-snapplify-ecommerce-activator.php';
	Snapplify_ECommerce_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-snapplify-ecommerce-deactivator.php
 */
function deactivate_snapplify_ecommerce()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-snapplify-ecommerce-deactivator.php';
	Snapplify_ECommerce_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_snapplify_ecommerce');
register_deactivation_hook(__FILE__, 'deactivate_snapplify_ecommerce');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-snapplify-ecommerce.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_snapplify_ecommerce()
{
	$plugin = new Snapplify_ECommerce();
	$plugin->run();
}
global $snapplifyEcommerce;
require_once(__DIR__ . '/Core/SnapplifyEcommerce.php');
$snapplifyEcommerce = new SnapplifyEcommerce(__FILE__);
$snapplifyEcommerce->initialize();
run_snapplify_ecommerce();
