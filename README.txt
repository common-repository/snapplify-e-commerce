=== Snapplify E-Commerce ===
Contributors: snapplify
Tags: snapplify, woocommerce, products
Requires at least: 5.6
Tested up to: 6.6.1
Stable tag: 1.1.4
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The Snapplify E-Commerce plugin imports Snapplify products into your WooCommerce Store.

== Description ==

Manage your catalogue and enable digital content sales with Snapplify.<br><br> 

<strong>Full catalogue search and management:</strong><br><br>

Sell from a catalogue of products from thousands of content and service partners. Seamlessly manage your digital catalogue and all of your metadata, in one place.<br><br>

<strong>Curate products from a vast catalogue for your branded ebook store:</strong><br><br>

Booksellers, create your own branded ebook store or integrate with your existing e-commerce website to offer a vast ebook and audiobook catalogue to your customers.<br><br>

<strong>Launch your own, personalised turnkey store:</strong><br><br>

Creating your own content? Publishers, filter Snapplify's catalogue of products to only show your titles. Launch your branded turnkey store and sell digital content directly to customers around the world.

<strong>Instant access to content:</strong><br><br>

Use the Snapplify E-commerce plugin and gain immediate access to a highly valuable and relevant catalogue.

<strong>Direct retail for education:</strong><br><br>

Set up your store quickly and easily without having to spend hours on development, and sell a vast ebook and audiobook catalogue to your customers, globally.

== Installation ==

The plugin may be installed manually or automatically (once published to the WordPress directory) as with any other WordPress plugin.<br><br>

Additional setup is required in conjunction with Snapplify in order to register your Store with them and supply Snapplify with your E Commerce API Endpoint as well as your Authentication token.<br>

= Minimum Requirements =

In line with the requirements for WooCommerce:<br>
* PHP 7.4 or greater is recommended
* MySQL 5.6 or greater is recommended
* The WooCommerce plugin must be installed

= Optional but Highly Recommended =

* [Action Scheduler - Disable Default Queue Runner](https://github.com/woocommerce/action-scheduler-disable-default-runner)
* [WP-CLI](https://wp-cli.org)
* Ability to set up and run cron jobs on your server.
** See recommended additional setup steps below under "Background Processing"

= Manual Installation Steps =

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

= Snapplify Feed Setup =
3. Go to the plugin settings by clicking "Snapplify" in the Admin navigation menu and following
the link on the Snapplify information page to the plugin's "import settings"
4. Generate an Authentication token, keep this token and pass this token on to Snapplify
as it will be used to configure the Snapplify Feed allowing it to send you data

= Performance Notes =

It is recommended that this plugin be used in cases where the supporting server infrastructure is capable of high throughput.
Infrastructure more capable than shared hosting is recommended.

= Background Processing =

This plugin makes use of background processing via the [action scheduler](https://actionscheduler.org/) in order to process incoming data in a performant manner.
[WP-CLI](http://wp-cli.org/) is included with this plugin and can be used to run the import of products in the background with higher than normal performance.

== Screenshots ==

1. screenshot-1.png

== Changelog ==
= 1.1.4 =
* Product JSON validation.
= 1.1.3 =
* Fix issue with versioning.
= 1.1.2 =
* Fix issues with nullable fields in the product data.
= 1.1.1 =
* Product API performance updates.
* Product saving scheduled tasks performance updates.
* Prioritising take-down product scheduled tasks functionality.
* Bug fixes & refactoring of product image processing.
* Logging refactoring, including standardising the logging context's source & functionality to enable/disable debug logs.
* Logging messages clean up.
= 1.1.0 =
* Plugin documentation and readme updates.
= 1.0.9 =
* Product image download vs render from cloud refactoring & bug fixes.
= 1.0.8 =
* Bug Fixing
= 1.0.7 =
* Bug Fixing
= 1.0.6 =
* Image Bug Fixing for Woocommorce Mini cart & Shop Page
= 1.0.5 =
* Image Bug Fixing for Woocommorce Order
= 1.0.4 =
* Some Image Bug Fixing
= 1.0.3 =
* Add option for manually flush the pending products queue.
* Add option for download product images.
= 1.0.2 =
* Some Bug Fixing
* Updated Json feed data file.
= 1.0.1 =
* Bug Fixing
= 1.0.0 =
* Initial Release