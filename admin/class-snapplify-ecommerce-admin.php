<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://snapplify.com
 * @since      1.0.0
 *
 * @package    Snapplify_ECommerce
 * @subpackage Snapplify_ECommerce/admin
 */


/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Snapplify_ECommerce
 * @subpackage Snapplify_ECommerce/admin
 */
class Snapplify_ECommerce_Admin
{


    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @var      string $version The current version of this plugin.
     */
    private $version;
    const SNAP_FULFILLMENT_KEY = '_snap_fulfillment_status';
    const SNAP_FULFILLMENT_COMPLETE = 'complete';
    const SNAP_FULFILLMENT_FAILED = 'failed';
    const SNAP_FULFILLMENT_VOUCHER_KEY = '_snap_voucher';
    const SNAP_VOUCHER_URL = 'https://api.snapplify.com/vouchers?apiKey=';

    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name The name of this plugin.
     * @param string $version The version of this plugin.
     * @since    1.0.0
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/snapplify-ecommerce-admin.css', [], $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/snapplify-ecommerce-admin.js', ['jquery'], $this->version, false);
    }

    /**
     * Add settings link.
     *
     * @param array $links Current links.
     */
    public function wcsnapplify_settings_link($links)
    {
        // Build and escape the URL.
        $url = esc_url($this->wcsnapplify_ecommerce_settings_link());
        // Create the link.
        $settings_link = "<a href='$url'>" . __('Settings', 'snapplify-ecommerce') . '</a>';
        // Adds the link to the end of the array.
        array_push(
            $links,
            $settings_link
        );
        return $links;
    }

    public function wcsnapplify_ecommerce_settings_link()
    {
        $url = add_query_arg(
            'section',
            'wcsnapplify',
            add_query_arg(
                'tab',
                'products',
                add_query_arg(
                    'page',
                    'wc-settings',
                    get_admin_url() . 'admin.php'
                )
            )
        );
        return $url;
    }

    public function wcsnapplify_ecommerce_failed_orders_link()
    {
        $url = add_query_arg(
            'post_type',
            'shop_order',
            add_query_arg(
                'post_status',
                'wc-snapp-failed',
                get_admin_url() . 'edit.php'
            )
        );
        return $url;
    }

    public function get_admin_image_html($product_id, $product_name, $size = 'woocommerce_thumbnail')
    {
        $snap_imageUrl = get_post_meta($product_id, '_snap_imageUrl', true);
        if ($snap_imageUrl !== '') {
            $dimensions = wc_get_image_size($size);

            $default_attr = [
                'class' => 'woocommerce-placeholder wp-post-image',
                'alt' => __($product_name, 'woocommerce'),
            ];

            $attr = wp_parse_args('', $default_attr);

            $image = $snap_imageUrl;
            $hwstring = image_hwstring($dimensions['width'], $dimensions['height']);
            $attributes = [];

            foreach ($attr as $name => $value) {
                $attribute[] = esc_attr($name) . '="' . esc_attr($value) . '"';
            }

            return '<img src="' . esc_url($image) . '" ' . $hwstring . implode(' ', $attribute) . '/>';
        }
        return false;
    }

    public function wcsnapplify_admin_order_item_image_from_cloud($image_html, $item_id, $item)
    {
        $newImageHtml = false;
        if (get_option('wcsnapplify_download_product') !== 'yes') {
            $product = $item->get_product();
            $newImageHtml = $this->get_admin_image_html($product->get_id(), $product->get_title());
        }
        if (!$newImageHtml) {
            $newImageHtml = $image_html;
        }
        return $newImageHtml;
    }


    public function wcsnapplify_add_section($sections)
    {

        $sections['wcsnapplify'] = __('Snapplify E-Commerce Management', 'snapplify-ecommerce');
        return $sections;
    }

    /**
     * Add settings to the specific section we created before
     */
    public function wcsnapplify_all_settings($settings, $current_section)
    {
        /**
         * Check the current section is what we want
         */
        if ('wcsnapplify' === $current_section && isset($_GET['regen-key'])) {
            $this->wcsnapplify_generate_new_auth_token();
            $settings_link = esc_url($this->wcsnapplify_ecommerce_settings_link());

            require_once 'partials/' . $this->plugin_name . '-admin-regen-auth-key-display.php';
            exit;
        }


        if ('wcsnapplify' === $current_section && isset($_GET['delete-product'])) {
            $settings_link = esc_url($this->wcsnapplify_ecommerce_settings_link());
            require_once 'partials/' . $this->plugin_name . '-admin-delete-product.php';
            exit;
        }

        if ('wcsnapplify' === $current_section) {

            $settingConfiguration = [];

            $settingConfiguration[] = [
                'name' => __('Snapplify E-Commerce Management Settings', 'snapplify-ecommerce'),
                'type' => 'title',
                'desc' => __('The following options are used to configure Snapplify E-Commerce Products<br>For more information, please visit the <a href="https://help.snapplify.com/support/home">Snapplify Knowledgebase</a>', 'snapplify-ecommerce'),
                'id' => 'wcsnapplify',
            ];

            $settingConfiguration[] = [
                'name' => __('Publish Products Immediately', 'snapplify-ecommerce'),
                'desc_tip' => __('Immediately publish imported products', 'snapplify-ecommerce'),
                'id' => 'wcsnapplify_auto_publish',
                'type' => 'checkbox',
                'css' => 'min-width:300px;',
                'desc' => __('Enable Auto-Publish', 'snapplify-ecommerce'),
                'default' => 'yes',
            ];

            $settingConfiguration[] = [
                'name' => __('Unpublish Unavailable Products', 'snapplify-ecommerce'),
                'desc_tip' => __('Automatically unpublish products marked as unavailable', 'snapplify-ecommerce'),
                'id' => 'wcsnapplify_auto_unpublish_unavailable',
                'type' => 'checkbox',
                'css' => 'min-width:300px;',
                'desc' => __('Enable Auto-Unpublish', 'snapplify-ecommerce'),
                'default' => 'yes',
            ];

            $settingConfiguration[] = [
                'name' => __('Manage Product Categories', 'snapplify-ecommerce'),
                'desc_tip' => __('Manage categories of imported products', 'snapplify-ecommerce'),
                'id' => 'wcsnapplify_manage_categories',
                'type' => 'checkbox',
                'css' => 'min-width:300px;',
                'desc' => __('Enable Category Management. This setting will create categories and assign products to them automatically.', 'snapplify-ecommerce'),
                'default' => 'yes',
            ];

            $settingConfiguration[] = [
                'name' => __('Manage Product Images', 'snapplify-ecommerce'),
                'desc_tip' => __('Download product images onto your server from the cloud, or display them directly from the cloud server.', 'snapplify-ecommerce'),
                'id' => 'wcsnapplify_download_product',
                'type' => 'checkbox',
                'css' => 'min-width:300px;',
                'desc' => __('Enable Auto-Download from the cloud server', 'snapplify-ecommerce'),
                'default' => '',
            ];

            $settingConfiguration[] = [
                'name' => __('Strict Product Validation', 'snapplify-ecommerce'),
                'desc_tip' => __('Validate the Product Feed fields strictly', 'snapplify-ecommerce'),
                'id' => 'wcsnapplify_strict_validation',
                'type' => 'checkbox',
                'css' => 'min-width:300px;',
                'desc' => __('Enable Strict Product Validation', 'snapplify-ecommerce'),
                'default' => 'no',
            ];

            $auth_url = esc_url(
                wp_nonce_url(
                    add_query_arg(
                        'regen-key',
                        '1',
                        $this->wcsnapplify_ecommerce_settings_link()
                    )
                ),
                'regen-auth-token'
            );

            $settingConfiguration[] = [
                'name' => __('Authentication Token', 'snapplify-ecommerce'),
                'desc_tip' => __('The bearer token to be used for authenticating requests', 'snapplify-ecommerce'),
                'id' => 'wcsnapplify_authtokenbutton',
                'type' => 'auth-token',
                'value' => 'wcsnapplify_token',
                /* translators: %s is replaced by a link */
                'desc' => sprintf(__('You can generate a new Authentication Token by <a href="%s">clicking here</a>.', 'snapplify-ecommerce'), $auth_url),
            ];
            global $snapplifyEcommerce;
            $settingConfiguration[] = [
                'name' => __('Product Feed Webhook URL', 'snapplify-ecommerce'),
                'desc_tip' => __('The URL to give Snapplify for product feed configuration', 'snapplify-ecommerce'),
                'id' => 'wcsnapplify_api_url',
                'type' => 'api-url',
                'value' => get_rest_url(null, $snapplifyEcommerce->geProductFeedApiController()->getApiProductFeedEndpoint()),
                'desc' => __('You should give Snapplify this URL along with the authentication token.', 'snapplify-ecommerce'),
            ];

            $settingConfiguration[] = [
                'name' => __('Snapplify API Key', 'snapplify-ecommerce'),
                'desc_tip' => __('The API Key assigned to you by Snapplify', 'snapplify-ecommerce'),
                'id' => 'wcsnapplify_ext_api_key',
                'type' => 'password',
                'css' => 'min-width:300px;',
                'desc' => __('The "API Key" assigned to you by Snapplify, used for authentication.', 'snapplify-ecommerce'),
            ];

            $settingConfiguration[] = [
                'name' => __('Debug Logging', 'snapplify-ecommerce'),
                'desc_tip' => __('If enabled, there will be additional logging used for debug purposes, this should not be enabled in working production environment.', 'snapplify-ecommerce'),
                'id' => 'wcsnapplify_debug_logging',
                'type' => 'checkbox',
                'css' => 'min-width:300px;',
                'desc' => __('Enable/Disable Debug Logging ', 'snapplify-ecommerce'),
                'default' => 'no',
            ];

            $product_delete_url = esc_url(
                wp_nonce_url(
                    add_query_arg(
                        'delete-product',
                        '1',
                        $this->wcsnapplify_ecommerce_settings_link()
                    )
                ),
                'all-product-delete'
            );

            $settingConfiguration[] = [
                'name' => __('Manually Flushing Pending Queue', 'snapplify-ecommerce'),
                'desc_tip' => '',
                'id' => 'wcsnapplify_deleteproductbutton',
                'type' => 'api-url',
                'value' => 'You can manually flush the pending products queue.',
                'desc' => sprintf(__('By <a href="%s">Click here</a> to flush the pending products queue.', 'snapplify-ecommerce'), $product_delete_url),
            ];

            $settingConfiguration[] = [
                'type' => 'sectionend',
                'id' => 'wcsnapplify',
            ];
            return $settingConfiguration;

            /**
             * If not, return the standard settings.
             */
        } else {
            return $settings;
        }
    }

    /**
     * Custom field for showing a masked version of the Bearer Token used for authentication.
     */
    public function wcsnapplify_add_admin_field_auth_token($saved_token)
    {
        $saved_token_name = $saved_token['value'];
        $description = WC_Admin_Settings::get_field_description($saved_token);
        $truncated_auth_token = substr(get_option($saved_token_name), -7);

        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr($saved_token['id']); ?>"><?php echo esc_html($saved_token['title']); ?></label>
                <?php _e($description['tooltip_html']); ?>
            </th>

            <td class="forminp forminp-<?php echo esc_html(sanitize_title($saved_token['type'])); ?>">
                <?php if ('' !== $truncated_auth_token) : ?>
                    <p>Current authentication token ends with <code>&hellip;<?php echo esc_html($truncated_auth_token); ?></code></p>
                <?php else : ?>
                    <p style="color:red;">Warning! There is no authentication token set!</p>
                <?php endif ?>
                <?php _e($description['description']); ?>

            </td>
        </tr>

        <?php
    }

    /**
     * Custom field for showing the url used for receiving push data from Snapplify
     */
    public function wcsnapplify_add_admin_field_api_url($field_data)
    {
        $description = WC_Admin_Settings::get_field_description($field_data);
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr($field_data['id']); ?>"><?php echo esc_html($field_data['title']); ?></label>
                <?php _e($description['tooltip_html']); ?>
            </th>

            <td class="forminp forminp-<?php echo esc_html(sanitize_title($field_data['type'])); ?>">
                <p><code><?php echo esc_html($field_data['value']); ?></code></p>
                <?php _e($description['description']); ?>

            </td>
        </tr>

        <?php
    }

    public function wcsnapplify_custom_product_tab($default_tabs)
    {
        global $post;

        $snap_id = get_post_meta($post->ID, '_snap_id', true);

        if ('' !== $snap_id) {
            $default_tabs['custom_tab'] = [
                'label' => __('Snapplify', 'snapplify-ecommerce'),
                'target' => 'wcsnapplify_custom_tab_data',
                'priority' => 60,
                'class' => [],
            ];
        }
        return $default_tabs;
    }

    public function wcsnapplify_custom_tab_data()
    {
        ?>
        <div id="wcsnapplify_custom_tab_data" class="panel woocommerce_options_panel hidden">
            <div class="options_group">
                <?php
                $this->wcsnapplify_fill_custom_tab();
                ?>
            </div>
        </div>
        <?php
    }

    public function wcsnapplify_enqueue_scripts()
    {
        if (isset($_GET['wcsnapplify'])) {
            if ($wc_screen_id . '_page_wc-settings' === $screen_id && isset($_GET['section']) && 'keys' === $_GET['wcsnapplify']) {
                wp_register_script('snap-api-key', WC()->plugin_url() . '/admin/js/api-key.js', ['jquery', 'woocommerce_admin', 'underscore', 'backbone', 'wp-util', 'qrcode', 'wc-clipboard'], $version, true);
                wp_enqueue_script('snap-api-key');
                wp_localize_script(
                    'snap-api-key',
                    'woocommerce_admin_api_keys',
                    [
                        'ajax_url' => admin_url('admin-ajax.php'),
                        'update_api_nonce' => wp_create_nonce('update-snap-api-key'),
                        'clipboard_failed' => esc_html__('Copying to clipboard failed. Please press Ctrl/Cmd+C to copy.', 'snapplify-ecommerce'),
                    ]
                );
            }
        }
    }


    public function wcsnapplify_fill_custom_tab()
    {
        $this->wcsnapplify_wp_read_only_text_value(
            [
                'id' => '_snap_id',
                'label' => __('ID', 'snapplify-ecommerce'),
                'placeholder' => '',
                'description' => __('The Snapplify ID of the product.', 'snapplify-ecommerce'),
            ]
        );
        $this->wcsnapplify_wp_read_only_text_value(
            [
                'id' => '_snap_title',
                'label' => __('Title', 'snapplify-ecommerce'),
                'placeholder' => '',
                'description' => __('The Snapplify Title of the product.', 'snapplify-ecommerce'),
            ]
        );
        $this->wcsnapplify_wp_read_only_text_value(
            [
                'id' => '_snap_subTitle',
                'label' => __('Subtitle', 'snapplify-ecommerce'),
                'placeholder' => '',
                'description' => __('The Subtitle of the product.', 'snapplify-ecommerce'),
            ]
        );
        $this->wcsnapplify_wp_read_only_text_value(
            [
                'id' => '_snap_identifier',
                'label' => __('ISBN', 'snapplify-ecommerce'),
                'placeholder' => '',
                'description' => __('The ISBN of the product.', 'snapplify-ecommerce'),
            ]
        );
        $this->wcsnapplify_wp_read_only_text_value(
            [
                'id' => '_snap_availability',
                'label' => __('Availability', 'snapplify-ecommerce'),
                'placeholder' => '',
                'description' => __('Availability of the product.', 'snapplify-ecommerce'),
            ]
        );
        $this->wcsnapplify_wp_read_only_text_value(
            [
                'id' => '_snap_price',
                'label' => __('Price', 'snapplify-ecommerce'),
                'placeholder' => '',
                'description' => __('The base price of the product.', 'snapplify-ecommerce'),
            ]
        );
        $this->wcsnapplify_wp_read_only_text_value(
            [
                'id' => '_snap_currency',
                'label' => __('Currency', 'snapplify-ecommerce'),
                'placeholder' => '',
                'description' => __('Currency of the price of the product.', 'snapplify-ecommerce'),
            ]
        );
        $this->wcsnapplify_wp_read_only_text_value(
            [
                'id' => '_snap_embargoDate',
                'label' => __('Embargo Date', 'snapplify-ecommerce'),
                'placeholder' => '',
                'description' => __('Embargo date of the product.', 'snapplify-ecommerce'),
            ]
        );
        $this->wcsnapplify_wp_read_only_text_value(
            [
                'id' => '_snap_bicCode',
                'label' => __('BIC Code', 'snapplify-ecommerce'),
                'placeholder' => '',
                'description' => __('BIC code of the product.', 'snapplify-ecommerce'),
            ]
        );
        $this->wcsnapplify_wp_read_only_text_value(
            [
                'id' => '_snap_bisacCode',
                'label' => __('BISAC Code', 'snapplify-ecommerce'),
                'placeholder' => '',
                'description' => __('BISAC code of the product.', 'snapplify-ecommerce'),
            ]
        );
        $this->wcsnapplify_wp_read_only_text_value(
            [
                'id' => '_snap_publisher',
                'label' => __('Publisher', 'snapplify-ecommerce'),
                'placeholder' => '',
                'description' => __('Product publisher.', 'snapplify-ecommerce'),
            ]
        );
        $this->wcsnapplify_wp_read_only_text_value(
            [
                'id' => '_snap_includedForSaleInCountries',
                'label' => __('Allowed Countries', 'snapplify-ecommerce'),
                'placeholder' => '',
                'description' => __('Countries the product may be sold in.', 'snapplify-ecommerce'),
            ]
        );
        $this->wcsnapplify_wp_read_only_text_value(
            [
                'id' => '_snap_excludedForSaleInCountries',
                'label' => __('Excluded Countries', 'snapplify-ecommerce'),
                'placeholder' => '',
                'description' => __('Countries the product is barred from.', 'snapplify-ecommerce'),
            ]
        );
    }

    public function wcsnapplify_wp_read_only_text_value($field)
    {
        global $thepostid, $post;

        $thepostid = empty($thepostid) ? $post->ID : $thepostid;
        $field['placeholder'] = isset($field['placeholder']) ? $field['placeholder'] : '';
        $field['class'] = isset($field['class']) ? $field['class'] : 'short';
        $field['style'] = isset($field['style']) ? $field['style'] : '';
        $field['wrapper_class'] = isset($field['wrapper_class']) ? $field['wrapper_class'] : '';
        $field['value'] = isset($field['value']) ? $field['value'] : (string)get_post_meta($thepostid, $field['id'], true);
        $field['name'] = isset($field['name']) ? $field['name'] : $field['id'];
        $field['type'] = isset($field['type']) ? $field['type'] : 'text';
        $field['desc_tip'] = isset($field['desc_tip']) ? $field['desc_tip'] : false;
        $data_type = empty($field['data_type']) ? '' : $field['data_type'];

        echo '<p class="form-field ' . esc_attr($field['id']) . '_field ' . esc_attr($field['wrapper_class']) . '">
		<label for="' . esc_attr($field['id']) . '">' . wp_kses_post($field['label']) . '</label>';

        if (!empty($field['description']) && false !== $field['desc_tip']) {
            echo wc_help_tip($field['description']);
        }

        echo '<input type="' . esc_attr($field['type']) . '" class="' . esc_attr($field['class']) . '" style="' . esc_attr($field['style']) . '" name="' . esc_attr($field['name']) . '" id="' . esc_attr($field['id']) . '" value="' . esc_attr($field['value']) . '" placeholder="' . esc_attr($field['placeholder']) . '"  readonly> ';

        if (!empty($field['description']) && false === $field['desc_tip']) {
            echo '<span class="description">' . wp_kses_post($field['description']) . '</span>';
        }

        echo '</p>';
    }

    public function wcsnapplify_menu()
    {
        $page_title = 'Snapplify';
        $menu_title = 'Snapplify';
        $capability = 'manage_woocommerce';
        $menu_slug = 'wcsnapplify-info';
        $function = [$this, 'wcsnapplify_render_info_page'];
        $icon_url = 'dashicons-text-page';
        $position = 76;

        add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position);

        $parent_menu = $menu_slug;
        $dashboard_title = __('Dashboard', 'snapplify-ecommerce');
        $dashboard_slug = 'wcsnapplify-info';
        add_submenu_page($parent_menu, $dashboard_title, $dashboard_title, $capability, $dashboard_slug, $function);

        $parent_menu = $menu_slug;
        $dashboard_title = __('Settings', 'snapplify-ecommerce');
        $dashboard_slug = 'wc-settings&tab=products&section=wcsnapplify';
        add_submenu_page($parent_menu, $dashboard_title, $dashboard_title, $capability, $dashboard_slug, $function);

        $parent_menu = $menu_slug;
        $dashboard_title = __('Logs', 'snapplify-ecommerce');
        $dashboard_slug = 'wc-status&tab=logs';
        add_submenu_page($parent_menu, $dashboard_title, $dashboard_title, $capability, $dashboard_slug, $function);
    }

    public function wcsnapplify_render_info_page()
    {
        global $wpdb;
        $total_products = $wpdb->get_var("select count(distinct post_id) as num_rows from {$wpdb->postmeta} where meta_key='_snap_id'");
        $total_actions_pending = $wpdb->get_var('select count(action_id) as pending from ' . $wpdb->prefix . "actionscheduler_actions where hook = 'wc_snap_process_product_exec' and status = 'pending'");
        $total_actions_complete = $wpdb->get_var('select count(action_id) as pending from ' . $wpdb->prefix . "actionscheduler_actions where hook = 'wc_snap_process_product_exec' and status = 'complete'");

        $last_feed_push_time = get_option('wcsnapplify_last_incoming_push_time');
        $last_product_update_time = get_option('wcsnapplify_last_processed_time');

        $total_snapplify_orders = $wpdb->get_var(
            $wpdb->prepare(
                "select count(distinct post_id) as num_rows from {$wpdb->postmeta} where meta_key=%s",
                self::SNAP_FULFILLMENT_KEY
            )
        );
        $total_successful_order_fulfillments = $wpdb->get_var(
            $wpdb->prepare(
                "select count(distinct post_id) as num_rows from {$wpdb->postmeta} where meta_key=%s and meta_value=%s",
                self::SNAP_FULFILLMENT_KEY,
                self::SNAP_FULFILLMENT_COMPLETE
            )
        );
        $total_failed_order_fulfillments = $wpdb->get_var(
            $wpdb->prepare(
                "select count(distinct post_id) as num_rows from {$wpdb->postmeta} where meta_key=%s and meta_value=%s",
                self::SNAP_FULFILLMENT_KEY,
                self::SNAP_FULFILLMENT_FAILED
            )
        );

        $go_to_settings_line = __('Manage product import settings', 'snapplify-ecommerce');
        $go_to_logs_line = __('View the import Logs', 'snapplify-ecommerce');
        /* translators: %s is replaced by a total count */
        $total_products_line = sprintf(__('There are currently <b>%s Snapplify products</b> in your store.', 'snapplify-ecommerce'), $total_products);
        /* translators: %s is replaced by a total count */
        $total_actions_complete_line = sprintf(__('<b>%s requests</b> from the Snapplify product feed have been processed.', 'snapplify-ecommerce'), $total_actions_complete);
        /* translators: %s is replaced by a total count */
        $total_actions_pending_line = sprintf(__('There are currently <b>%s pending requests</b> from the Snapplify product feed awaiting processing.', 'snapplify-ecommerce'), $total_actions_pending);

        $failed_orders_link = esc_url($this->wcsnapplify_ecommerce_failed_orders_link());

        if (false !== $last_feed_push_time) {
            /* translators: %s is replaced by a date and time string */
            $last_feed_push_line = sprintf(__('The last update received from the Snapplify Product Feed was at <b>%s</b>', 'snapplify-ecommerce'), $last_feed_push_time);
        } else {
            $last_feed_push_line = __('No updates have been received from the Snapplify Product Feed.', 'snapplify-ecommerce');
        }

        if (false !== $last_product_update_time) {
            /* translators: %s is replaced by a date and time string */
            $last_product_update_line = sprintf(__('The most recent product update occurred at <b>%s</b>', 'snapplify-ecommerce'), $last_product_update_time);
        } else {
            $last_product_update_line = '';
        }

        /* translators: %s is replaced by a total count */
        $total_snapplify_orders_line = sprintf(__('%s orders include a Snapplify product.', 'snapplify-ecommerce'), $total_snapplify_orders);
        /* translators: %s is replaced by a total count */
        $total_successful_order_fullfilments_line = sprintf(__('%s orders have been successfully fulfilled.', 'snapplify-ecommerce'), $total_successful_order_fulfillments);
        /* translators: %s is replaced by a total count */
        $total_failed_order_fullfilments_line = sprintf(__('%1$s orders experienced an error during fulfillment. <a href="%2$s">View them here</a>', 'snapplify-ecommerce'), $total_failed_order_fulfillments, $failed_orders_link);

        ?>
        <div class="wrap">
            <h1>Snapplify E-Commerce</h1>
            <p><?php echo esc_html($total_products_line); ?></p>
            <p><?php echo esc_html($total_actions_complete_line); ?></p>
            <p><?php echo esc_html($total_actions_pending_line); ?></p>
            <br>
            <p><?php echo esc_html($last_feed_push_line); ?></p>
            <p><?php echo esc_html($last_product_update_line); ?></p>
            <br>
            <p><?php echo esc_html($total_snapplify_orders_line); ?></p>
            <p><?php echo esc_html($total_successful_order_fullfilments_line); ?></p>
            <p><?php echo esc_html($total_failed_order_fullfilments_line); ?></p>
            <br>
            <p><a href='admin.php?page=wc-settings&tab=products&section=wcsnapplify'><?php echo esc_html($go_to_settings_line); ?></a></p>
            <p><a href='admin.php?page=wc-status&tab=logs'><?php echo esc_html($go_to_logs_line); ?></a></p>
        </div>
        <?php
    }

    public function add_snapplify_log_admin_menu_item()
    {
        add_submenu_page(
            'admin.php?page=wc-status&tab=logs',
            'View Logs',
            'View Logs',
            'manage_woocommerce',
            'wcsnapplify-log',
            '',
            1
        );
    }

    /**
     * TODO: FIX page specific take down notice
     */
    public function wcsnapplify_product_takedown_admin_notice()
    {
        global $pagenow;
        global $post;
        // if ($pagenow == 'post.php' && current_user_can('manage_woocommerce')) {
        $is_taken_down = get_post_meta($post->ID, '_snap_takeDown', true);
        if (true == $is_taken_down) {
            $class = 'notice notice-error';
            $message = __('WARNING: This product has been marked with a takedown notice by Snapplify', 'snapplify-ecommerce');
            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
            echo 'WARNING: This product has been taken down';
        }
    }

    /**
     * Add "drafts" option to the posts interface allowing for posts to be filtered by draft status
     */
    public function add_drafts_admin_menu_item()
    {
        add_posts_page(__('Drafts', 'snapplify-ecommerce'), __('Drafts', 'snapplify-ecommerce'), 'read', 'edit.php?post_status=draft&post_type=post');
    }

    public function true_permission()
    {
        return true;
    }

    /**
     * @param $request
     * @return bool
     * @throws \Exception
     */
    public function wcsnapplify_check_permission($request)
    {
        global $snapplifyEcommerce;
        $gate = new WC_REST_Authentication();
        $gate->authenticate(false);
        $snapplifyEcommerce->getLoggingController()->addSystemLog(sprintf('Attempted Push: auth-%s', current_user_can('manage_woocommerce')), 'auth');
        return current_user_can('manage_woocommerce');
    }

    public function save_wc_custom_attributes($post_id, $custom_attributes)
    {
        $i = 0;
        // Loop through the attributes array
        foreach ($custom_attributes as $name => $value) {
            // Relate post to a custom attribute, add term if it does not exist
            wp_set_object_terms($post_id, $value, $name, false);
            $is_taxonomy = false;
            if ('pa_language' === $name || 'pa_grade-level' === $name) {
                $is_taxonomy = true;
            }
            // Create product attributes array
            $product_attributes[$i] = [
                'name' => $name,
                'value' => $value,
                'is_visible' => 1,
                'is_variation' => 0,
                'is_taxonomy' => $is_taxonomy,
            ];
            $i++;
        }
        // Now update the post with its new attributes
        update_post_meta($post_id, '_product_attributes', $product_attributes);
    }

    public function get_product_id_by_snap_id($snap_id)
    {
        global $wpdb;

        $id = $wpdb->get_var(
            $wpdb->prepare(
                "
				SELECT posts.ID
				FROM {$wpdb->posts} as posts
				INNER JOIN {$wpdb->wc_product_meta_lookup} AS lookup ON posts.ID = lookup.product_id
				WHERE
				posts.post_type IN ( 'product', 'product_variation' )
				AND posts.post_status != 'trash'
				AND lookup.snap_id = %s
				LIMIT 1
				",
                $snap_id
            )
        );

        return (int)$id;
    }

    public function snap_verbose_trace()
    {
        $backtrace = debug_backtrace();
        $limit = 8;
        foreach ($backtrace as $index => $trace) {
            if (false !== $limit && $index == $limit) {
                break;
            }

            error_log(print_r($trace['line'], true));
            error_log(print_r($trace['file'], true));
        }
    }

    private function wcsnapplify_generate_new_auth_token()
    {
        $token = wc_rand_hash();
        update_option('wcsnapplify_token', $token);
    }

    private function process_add_attribute($attribute)
    {
        global $wpdb;

        if (empty($attribute['attribute_type'])) {
            $attribute['attribute_type'] = 'text';
        }

        if (empty($attribute['attribute_orderby'])) {
            $attribute['attribute_orderby'] = 'menu_order';
        }
        if (empty($attribute['attribute_public'])) {
            $attribute['attribute_public'] = 0;
        }

        if (empty($attribute['attribute_name']) || empty($attribute['attribute_label'])) {
            return new WP_Error('error', __('Please, provide an attribute name and slug.', 'snapplify-ecommerce'));
        } elseif (($valid_attribute_name === $this->valid_attribute_name($attribute['attribute_name'])) && is_wp_error($valid_attribute_name)) {
            return $valid_attribute_name;
        } elseif (taxonomy_exists(wc_attribute_taxonomy_name($attribute['attribute_name']))) {
            /* translators: %s is replaced by an attribute name */
            return new WP_Error('error', sprintf(__('Slug "%s" is already in use. Change it, please.', 'snapplify-ecommerce'), sanitize_title($attribute['attribute_name'])));
        }

        $wpdb->insert($wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute);

        do_action('woocommerce_attribute_added', $wpdb->insert_id, $attribute);

        flush_rewrite_rules();
        delete_transient('wc_attribute_taxonomies');

        return true;
    }

    private function valid_attribute_name($attribute_name)
    {
        if (strlen($attribute_name) >= 28) {
            /* translators: %s is replaced by an attribute name */
            return new WP_Error('error', sprintf(__('Slug "%s" is too long (28 characters max). Shorten it, please.', 'snapplify-ecommerce'), sanitize_title($attribute_name)));
        } elseif (wc_check_if_attribute_name_is_reserved($attribute_name)) {
            /* translators: %s is replaced by an attribute name */
            return new WP_Error('error', sprintf(__('Slug "%s" is not allowed because it is a reserved term. Change it, please.', 'snapplify-ecommerce'), sanitize_title($attribute_name)));
        }

        return true;
    }


    // Fulfillments
    public function register_snapplify_processing_status()
    {
        register_post_status(
            'wc-snapp-process',
            [
                'label' => 'Snapplify Processing',
                'public' => true,
                'exclude_from_search' => false,
                'show_in_admin_all_list' => true,
                'show_in_admin_status_list' => true,
                /* translators: %s is replaced by a total count */
                'label_count' => _n_noop('Snapplify Processing (%s)', 'Snapplify Processing (%s)', 'snapplify-ecommerce'),
            ]
        );
    }

    public function register_snapplify_failed_status()
    {
        register_post_status(
            'wc-snapp-failed',
            [
                'label' => 'Snapplify Error',
                'public' => true,
                'exclude_from_search' => false,
                'show_in_admin_all_list' => true,
                'show_in_admin_status_list' => true,
                /* translators: %s is replaced by a total count */
                'label_count' => _n_noop('Snapplify Errors (%s)', 'Snapplify Errors (%s)', 'snapplify-ecommerce'),
            ]
        );
    }

    public function add_snapplify_processing_to_order_statuses($order_statuses)
    {
        $new_order_statuses = [];

        foreach ($order_statuses as $key => $status) {

            $new_order_statuses[$key] = $status;

            if ('wc-processing' === $key) {
                $new_order_statuses['wc-snapp-process'] = 'Processing via Snapplify';
            }
            if ('wc-failed' === $key) {
                $new_order_statuses['wc-snapp-failed'] = 'Snapplify Error';
            }
        }

        return $new_order_statuses;
    }

    public function add_snapplify_processing_color_to_admin_order_list()
    {
        global $pagenow, $post;

        if (null === $post) {
            return; // Exit.
        }
        if ('edit.php' !== $pagenow) {
            return; // Exit.
        }
        if ('shop_order' !== get_post_type($post->ID)) {
            return; // Exit.
        }

        $order_status = 'snapp-process';
        ?>
        <style>
            .order-status.status-<?php echo esc_html(sanitize_title($order_status)); ?> {
                background: #bacf47;
                color: #617300;
            }
        </style>
        <?php
    }

    public function add_snapplify_error_color_to_admin_order_list()
    {
        global $pagenow, $post;

        if ('edit.php' !== $pagenow) {
            return; // Exit.
        }
        if ('shop_order' !== get_post_type($post->ID)) {
            return; // Exit.
        }

        $order_status = 'snapp-failed';
        ?>
        <style>
            .order-status.status-<?php echo esc_html(sanitize_title($order_status)); ?> {
                background: #eba3a3;
                color: #761919;
            }
        </style>
        <?php
    }

    public function should_order_be_fulfilled($order_id)
    {
        $paid_status = metadata_exists('post', $order_id, '_date_paid');
        if (false === $paid_status) {
            return false;
        }

        $fulfillment_attempted = metadata_exists('post', $order_id, self::SNAP_FULFILLMENT_KEY);
        if (false === $fulfillment_attempted) {
            return true;
        }

        $fulfillment_status = get_post_meta($order_id, self::SNAP_FULFILLMENT_KEY, true);
        if (self::SNAP_FULFILLMENT_COMPLETE !== $fulfillment_status) {
            return true;
        }

        return false;
    }

    /**
     * @param $order_id
     * @param $checkout
     * @return void
     * @throws \Exception
     */
    public function trigger_snapplify_fulfillment($order_id, $checkout = null)
    {
        if ($this->should_order_be_fulfilled($order_id) === false) {
            return;
        }
        global $snapplifyEcommerce;
        $loggingController = $snapplifyEcommerce->getLoggingController();
        $loggingController->addSystemLog(sprintf('Attempting fulfillment of order: %s', $order_id), 'order-fulfilment');

        $order = new WC_Order($order_id);
        $original_order_status = $order->get_status();
        $trigger_fulfillment = false;
        $user_id = $order->get_user_id(); // or $order->get_customer_id();
        $snapplifyUserId = '';
        $redeemVoucher = false;
        // Check and get a snapplify sso id user meta
        if (metadata_exists('user', $user_id, 'snplfy_sso_id')) {
            $snapplifyUserId = get_user_meta($user_id, 'snplfy_sso_id', true);
            $redeemVoucher = true;
        }

        $payload = [
            'order_id' => $order_id,
            'reference' => $order_id . gmdate('YmdHis'),
            'customer_email' => $order->get_billing_email(),
            'snapplifyUserId' => $snapplifyUserId,
            'redeemVoucher' => $redeemVoucher,
        ];

        foreach ($order->get_items() as $item_id => $item) {

            $product_id = $item->get_product_id();
            $fulfill_product = metadata_exists('post', $product_id, '_snap_id');

            if (true === $fulfill_product) {
                $trigger_fulfillment = true;
                $order->update_status('snapp-process');

                $product = $item->get_product();
                $snapplify_id = $product->get_meta('_snap_id', true);
                $isbn = $product->get_meta('_snap_identifier', true);
                $quantity = $item->get_quantity();

                $payload['snapplify'][] = [
                    'product_id' => $product_id,
                    'snapplify_id' => $snapplify_id,
                    'snapplify_asset_id' => $isbn,
                    'quantity' => $quantity,
                ];
                $loggingController->addSystemLog(sprintf('Order %s has a Snapplify Product with Snap ID: %s', $order_id, $snapplify_id), 'order-fulfilment');
            } else {
                $loggingController->addSystemLog(sprintf('Order: %s, Item: %s has a non Snapplify Product', $order_id, $item_id), 'order-fulfilment');
            }
        }

        if (true === $trigger_fulfillment) {
            $success = $this->snapplify_voucher_fulfillment($payload);
            if (true === $success) {
                $order->update_status($original_order_status);
            }
            if (false === $success) {
                $order->update_status('snap-failed');
            }
        } else {
            $loggingController->addSystemLog(sprintf('Order %s has no Snapplify Product, no processing via Snapplify required.', $order_id), 'order-fulfilment');
        }
    }

    /**
     * @param $payload
     * @return bool
     * @throws \Exception
     */
    public function snapplify_voucher_fulfillment($payload)
    {
        global $snapplifyEcommerce;
        $loggingController = $snapplifyEcommerce->getLoggingController();
        $success = false;
        $snapplify_api_key = get_option('wcsnapplify_ext_api_key');

        $request_url = self::SNAP_VOUCHER_URL . $snapplify_api_key;

        $asset_ids = wp_list_pluck($payload['snapplify'], 'snapplify_asset_id');

        $request_body = [
            'reference' => $payload['reference'],
            'quantity' => 1,
            'recipientEmail' => $payload['customer_email'],
            'assetIds' => $asset_ids,
            'user' => $payload['snapplifyUserId'],
            'redeem' => $payload['redeemVoucher'],
        ];

        $request_body = wp_json_encode($request_body);

        $request_args = [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => $request_body,
        ];

        try {
            $voucher_attempt_note = __('Trying to obtain Voucher from Snapplify', 'snapplify-ecommerce');
            $voucher_attempt_error = __('An error was experienced, while obtaining a voucher from Snapplify', 'snapplify-ecommerce');
            $voucher_attempt_product_error = __('An error was experienced obtaining a voucher for this order. The product cannot be found.', 'snapplify-ecommerce');
            /* translators: %1$s is replaced by a voucher code, %2$s is replaced by a reference code */
            $voucher_attempt_success = __('A voucher was obtained from Snapplify with code: "%1$s" & reference code "%2$s"', 'snapplify-ecommerce');
            /* translators: %s is replaced by an order number */
            $logger_voucher_attempt_success = __('A voucher was obtained from Snapplify for Order: "%s"', 'snapplify-ecommerce');
            /* translators: %s is replaced by an order number */
            $customer_voucher_note = __('A voucher code redeemable at http://redeem.snapplify.com/ was generated for your order: "%s"', 'snapplify-ecommerce');
            $customer_voucher_note_error = __('An error was experienced, while obtaining a voucher for your order. Please contact support for help.', 'snapplify-ecommerce');

            $response = wp_remote_post($request_url, $request_args);

            $order = new WC_Order($payload['order_id']);
            $response_code = $response['response']['code'];
            $response_body = json_decode($response['body'], true);

            if (201 === $response_code) {
                update_post_meta($order->get_id(), self::SNAP_FULFILLMENT_KEY, self::SNAP_FULFILLMENT_COMPLETE);
                update_post_meta($order->get_id(), self::SNAP_FULFILLMENT_VOUCHER_KEY, $response_body['code']);
                $order->add_order_note(sprintf($voucher_attempt_success, $response_body['code'], $payload['reference']));
                $order->add_order_note(sprintf($customer_voucher_note, $response_body['code']), true);
                $loggingController->addSystemLog(sprintf($logger_voucher_attempt_success, $payload['order_id']), 'voucher-fulfilment');
                $success = true;
            }
            if (409 === $response_code) {
                if (400 === $response_body['status']) {
                    update_post_meta($order->get_id(), self::SNAP_FULFILLMENT_KEY, self::SNAP_FULFILLMENT_FAILED);
                    $order->add_order_note($voucher_attempt_product_error);
                    $order->add_order_note(sprintf($customer_voucher_note_error, $response_body['code']), true);
                    $loggingController->addErrorLog(sprintf('%s for Order ID: %s - %s', $voucher_attempt_error, $payload['order_id'], $response_body['message']), 'voucher-fulfilment');
                }
                if (401 === $response_body['status']) {
                    update_post_meta($order->get_id(), self::SNAP_FULFILLMENT_KEY, self::SNAP_FULFILLMENT_FAILED);
                    $order->add_order_note($voucher_attempt_error);
                    $order->add_order_note(sprintf($customer_voucher_note_error, $response_body['code']), true);
                    $loggingController->addErrorLog(sprintf('%s for Order ID: %s - %s', $voucher_attempt_error, $payload['order_id'], $response_body['message']), 'voucher-fulfilment');
                }
            }
            if (400 === $response_code) {
                update_post_meta($order->get_id(), self::SNAP_FULFILLMENT_KEY, self::SNAP_FULFILLMENT_FAILED);
                $order->add_order_note($voucher_attempt_error);
                $order->add_order_note($customer_voucher_note_error, true);
                $loggingController->addErrorLog(sprintf('%s for Order ID: %s - %s', $voucher_attempt_error, $payload['order_id'], $response_body['message']), 'voucher-fulfilment');
            }
        } catch (\Throwable $th) {
            $order = new WC_Order($payload['order_id']);
            $order->add_order_note($voucher_attempt_error);
            $order->add_order_note(sprintf($customer_voucher_note_error, $response_body['code']), true);
            $loggingController->addErrorLog(sprintf('%s Order ID: %s', $voucher_attempt_error, $payload['order_id']), 'voucher-fulfilment');
            update_post_meta($order->get_id(), self::SNAP_FULFILLMENT_KEY, self::SNAP_FULFILLMENT_FAILED);
        }
        return $success;
    }
}
