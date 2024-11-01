<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://snapplify.com
 * @since      1.0.0
 *
 * @package    Snapplify_ECommerce
 * @subpackage Snapplify_ECommerce/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Snapplify_ECommerce
 * @subpackage Snapplify_ECommerce/public
 */
class Snapplify_ECommerce_Public
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

    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name The name of the plugin.
     * @param string $version The version of this plugin.
     * @since    1.0.0
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Snapplify_ECommerce_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Snapplify_ECommerce_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/snapplify-ecommerce-public.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Snapplify_ECommerce_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Snapplify_ECommerce_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/snapplify-ecommerce-public.js', array('jquery'), $this->version, false);
    }

    public function wcsnapplify_product_image_from_cloud($html, $post_thumbnail_id)
    {
        global $post;
        if (get_option('wcsnapplify_download_product') !== 'yes') {
            if (!$post_thumbnail_id) {
                $pID = $post->ID;
                $snap_imageUrl = get_post_meta($pID, '_snap_imageUrl', true);
                if ($snap_imageUrl !== '') {
                    $html = '<div class="woocommerce-product-gallery__image--placeholder">';
                    $html .= sprintf('<img src="%s" alt="%s" class="wp-post-image" />', esc_url($snap_imageUrl), esc_html__($post->post_title, 'snapplify-ecommerce'));
                    $html .= '</div>';
                }
            }
        }
        return $html;
    }

    public function get_image_html($product_id, $product_name, $size = 'woocommerce_thumbnail')
    {
        $snap_imageUrl = get_post_meta($product_id, '_snap_imageUrl', true);
        if ($snap_imageUrl !== '') {
            $dimensions = wc_get_image_size($size);

            $default_attr = array(
                'class' => 'woocommerce-placeholder wp-post-image',
                'alt' => __($product_name, 'woocommerce'),
            );

            $attr = wp_parse_args('', $default_attr);

            $image = $snap_imageUrl;
            $hwstring = image_hwstring($dimensions['width'], $dimensions['height']);
            $attributes = array();

            foreach ($attr as $name => $value) {
                $attribute[] = esc_attr($name) . '="' . esc_attr($value) . '"';
            }

            return '<img src="' . esc_url($image) . '" ' . $hwstring . implode(' ', $attribute) . '/>';
        }
        return false;
    }

    public function wcsnapplify_product_placeholder_override_image_from_cloud($imageHtml, $size, $dimensions)
    {
        global $product;
        $snapImageHtml = $this->get_image_html($product->post->ID, $product->post->post_title, $size);
        if (false !== $snapImageHtml) {
            $imageHtml = $snapImageHtml;
        }
        return $imageHtml;
    }

    public function wcsnapplify_product_loop_image_from_cloud()
    {
        $newImageHtml = false;
        $size = 'woocommerce_thumbnail';
        if (get_option('wcsnapplify_download_product') !== 'yes') {
            global $product;
            $newImageHtml = $this->get_image_html($product->post->ID, $product->post->post_title, $size);
        }
        if (!$newImageHtml) {
            if (has_post_thumbnail()) {
                global $product;
                $image_html = get_the_post_thumbnail($product->post->ID, $size);
            } else {
                $image_html = wc_placeholder_img($size);
            }
            $newImageHtml = $image_html;
        }
        return $newImageHtml;
    }

    public function wcsnapplify_cart_product_image_from_cloud($image_html, $cart_item, $cart_item_key)
    {
        $newImageHtml = false;
        if (get_option('wcsnapplify_download_product') !== 'yes') {
            $product = $cart_item['data'];
            $newImageHtml = $this->get_image_html($product->get_id(), $product->get_title());
        }
        if (!$newImageHtml) {
            $newImageHtml = $image_html;
        }
        return $newImageHtml;
    }

    public function wcsnapplify_order_item_image_from_cloud($image_html, $item)
    {
        $newImageHtml = false;
        if (get_option('wcsnapplify_download_product') !== 'yes') {
            $product = $item->get_product();
            $newImageHtml = $this->get_image_html($product->get_id(), $product->get_title());
        }
        if (!$newImageHtml) {
            $newImageHtml = $image_html;
        }
        return $newImageHtml;
    }
}
