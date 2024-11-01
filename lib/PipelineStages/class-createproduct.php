<?php
/**
 * Product creation pipeline step.
 *
 * @link       https://snapplify.com
 * @since      1.0.0
 *
 * @package    Snapplify_ECommerce
 * @subpackage Snapplify_ECommerce/lib
 */

namespace Snapplify\PipelineStages;

use DateTime;
use Snapplify\WooMetaHandler;

/**
 * As part of the product creation pipeline this is the final stage
 * This stage will return a product and not the original JSON payload.
 */
class CreateProduct
{

    /**
     * @throws \Exception
     */
    public function __invoke($data)
    {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $data = json_decode(json_encode($data), true);
        return $this->saveProduct($data);
    }

    /**
     * @param \WC_Product_Simple $product
     * @param array $data
     * @return \WC_Product_Simple
     * @throws \Exception
     */
    private function saveProductImage(\WC_Product_Simple $product, array $data): \WC_Product_Simple
    {
        global $wpdb, $snapplifyEcommerce;
        $loggingController = $snapplifyEcommerce->getLoggingController();
        $productPostId = $product->get_id();
        $existingImageHash = '';
        $existingImageId = 0;
        /*
        * @todo Typed query & output parameters not allowed in PHP 7.2, type must be cast when upgrading code base to higher PHP version.
        */
        $existingImageData = $wpdb->get_results(
            $wpdb->prepare(/** @lang text */ "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %s AND (meta_key ='_snap_imageMD5Hash' OR meta_key = '_thumbnail_id') LIMIT 2", $productPostId),
            ARRAY_A
        );
        if (
            (true === isset($existingImageData[0]))
            && (true === isset($existingImageData[0]['meta_value']))
            && (true === isset($existingImageData[1]))
            && (true === isset($existingImageData[1]['meta_value']))
        ) {
            $existingImageHash = $existingImageData[0]['meta_value'];
            $existingImageId = $existingImageData[1]['meta_value'];
        }
        $isSupportsImageDownload = false;
        if ('yes' === get_option('wcsnapplify_download_product')) {
            $isSupportsImageDownload = true;
        }
        $newImageHash = $data['imageMd5Hash'];
        if (true === $isSupportsImageDownload) {
            if (
                (null !== $newImageHash)
                && ($existingImageHash !== $newImageHash)
            ) {
                if (0 !== $existingImageId) {
                    $loggingController->addSystemLog(sprintf('Removing existing image: Product Id: %s', $productPostId), 'product-scheduled-task');
                    $loggingController->addDebugLog(sprintf('Removing image: Product Id: %s, Existing Image Hash: %s, New Image Hash: %s', $productPostId, $existingImageHash, $newImageHash), 'product-scheduled-task');
                    wp_delete_attachment($existingImageId, true);
                }
                try {
                    $loggingController->addSystemLog(sprintf('Updating image: Product Id: %s', $productPostId), 'product-scheduled-task');
                    $imageId = media_sideload_image($data['imageUrl'], $productPostId, $data['identifier'], 'id');
                    if (true === is_wp_error($imageId)) {
                        throw new \Exception($imageId->get_error_message());
                    }
                    $product->set_image_id($imageId);
                } catch (\Throwable $th) {
                    $loggingController->addErrorLog(sprintf('Error fetching image: Product Id:%s, Message: %s', $productPostId, $th->getMessage()), 'product-scheduled-task');
                }
            }
        } else if (0 !== $existingImageId) {
            wp_delete_attachment($existingImageId, true);
            $loggingController->addSystemLog(sprintf('Removing existing image due to image served from cloud configuration: Product Id: %s Existing Image Hash: %s', $productPostId, $existingImageHash), 'product-scheduled-task');
            $loggingController->addDebugLog(sprintf('Removing image: Product Id: %s, Existing Image Hash: %s', $productPostId, $existingImageHash), 'product-scheduled-task');
        }
        return $product;
    }

    /**
     * @param \WC_Product_Simple $product
     * @param array $data
     * @return \WC_Product_Simple
     */
    private function setDefaultProductPostData(\WC_Product_Simple $product, array $data): \WC_Product_Simple
    {
        $postStatus = 'draft';
        if (true === $product->exists()) {
            $postStatus = $product->get_status('edit');
        }
        if (
            ('yes' === get_option('wcsnapplify_auto_publish'))
            && (true === $this->isProductPublishable($data))
        ) {
            $postStatus = 'publish';
        }
        if (
            ('yes' === get_option('wcsnapplify_auto_unpublish_unavailable'))
            && (false === $this->isProductPublishable($data))
        ) {
            $postStatus = 'draft';
        }
        $product->set_status($postStatus);
        $product->set_name($data['title']);
        $product->set_description((null === $data['description']) ? '' : $data['description']);
        if (!empty($data->title)) {
            $product->set_slug(sanitize_title($data['title']));
        }
        $product->set_virtual(true);
        $product->set_status($postStatus);
        return $product;
    }

    /**
     * @param \WC_Product_Simple $product
     * @param array $data
     * @param bool $isExistingProduct
     * @return void
     * @throws \Exception
     */
    private function updateCustomMetaData(\WC_Product_Simple $product, array $data, bool $isExistingProduct): void
    {
        try {

            $customData = [];
            foreach ($data as $key => $value) {
                $customData['_snap_' . $key] = $value;
            }

            $customData['_price'] = $data['price'];
            $customData['_regular_price'] = $data['price'];

            if (true === $isExistingProduct) {
                unset($customData['_snap_id']);
            }

            /** @noinspection PhpParamsInspection */
            WooMetaHandler::update_post_metas($product->get_id(), $customData);
        } catch (\Throwable $th) {
            global $snapplifyEcommerce;
            $errorMessage = 'Error updating product meta data: Product Id %s, Snap Id: %s, Snap Identifier: %s, Message: %s';
            $snapplifyEcommerce->getLoggingController()->addErrorLog(sprintf($errorMessage, $product->get_id(), $data['id'], $data['identifier'], $th->getMessage()), 'product-scheduled-task');
            wp_die(sprintf($errorMessage, $product->get_id(), $data['id'], $data['identifier'], esc_html($th->getMessage())));
        }
    }

    /**
     * @param array $data
     * @return \WP_Error|\WC_Product_Simple
     * @throws \Exception
     * @todo Typed or return values not allowed in PHP 7.2, type must be cast when upgrading code base to higher PHP version.
     */
    public function saveProduct(array $data)
    {
        $snapplifyExternalProductId = $data['id'];
        $snapplifyExternalProductIdentifier = $data['identifier'];
        $productPostId = $this->get_product_id_by_snap_id($snapplifyExternalProductId);
        global $snapplifyEcommerce;
        $loggingController = $snapplifyEcommerce->getLoggingController();
        if (
            (0 === $productPostId)
            || (true === $this->should_product_be_updated($productPostId, $data['updatedDate']))
        ) {
            try {
                $isExistingProduct = false;
                if (0 !== $productPostId) {
                    $isExistingProduct = true;
                }
                $loggingController->addSystemLog(sprintf('Processing product API scheduled task: Snap Id: %s, Snap Identifier: %s', $snapplifyExternalProductId, $snapplifyExternalProductIdentifier), 'product-scheduled-task');
                $loggingController->addDebugLog(sprintf('Product payload: %s', json_encode($data)), 'product-scheduled-task');
                $product = new \WC_Product_Simple($productPostId);
                $product = $this->setDefaultProductPostData($product, $data);
                $product->save();
                $productPostId = $product->get_id();
                if (0 >= $productPostId) {
                    $loggingController->addErrorLog(sprintf('Error instantiating product: Snap Id: %s, Snap Identifier: %s', $snapplifyExternalProductId, $snapplifyExternalProductIdentifier), 'product-scheduled-task');
                    throw new \Exception('snapplify_cannot_create_product', $productPostId, 400);
                }
                $this->updateCustomMetaData($product, $data, $isExistingProduct);
                $customAttributes = $this->make_custom_attributes($data);
                $this->save_wc_custom_attributes($productPostId, $customAttributes);
                if (get_option('wcsnapplify_manage_categories') === 'yes') {
                    $categories = $this->make_categories($data['categories']);
                    $product->set_category_ids($categories);
                }
                $product = $this->saveProductImage($product, $data);
                $product->save();
                $loggingController->addSystemLog(sprintf('Processed product API scheduled task: Product Id: %s, Snap Id: %s, Snap Identifier: %s', $productPostId, $snapplifyExternalProductId, $snapplifyExternalProductIdentifier), 'product-scheduled-task');
                return $product;
            } catch (\Exception $e) {
                $loggingController->addErrorLog(sprintf('Error processing product API scheduled task: Product Id: %s, Message: %s', $productPostId, $e->getMessage()), 'product-scheduled-task');
                return new \WP_Error($e->getErrorCode(), $e->getMessage(), ['status' => $e->getCode()]);
            }
        } else {
            $loggingController->addSystemLog(sprintf('Bypassing out-dated product API scheduled task: Snap Id: %s, Snap Identifier: %s', $snapplifyExternalProductId, $snapplifyExternalProductIdentifier), 'product-scheduled-task');
            $loggingController->addDebugLog(sprintf('Product payload: %s', json_encode($data)), 'product-scheduled-task');
            $product = new \WC_Product_Simple($productPostId);
        }
        return $product;
    }

    /**
     * @param array $data
     * @return bool
     */
    private function isProductPublishable(array $data): bool
    {
        return ('AVAILABLE' === $data['availability']);
    }

    private function category_exists($cat_name, $parent = null)
    {
        $categoryId = term_exists($cat_name, 'product_cat', $parent);
        if (is_array($categoryId)) {
            $categoryId = $categoryId['term_id'];
        }
        return $categoryId;
    }

    /**
     * @param $categories
     * @return array
     * @throws \Exception
     */
    private function make_categories($categories)
    {
        require_once ABSPATH . 'wp-admin/includes/taxonomy.php';
        global $snapplifyEcommerce;
        $loggingController = $snapplifyEcommerce->getLoggingController();
        $cat_ids = array();
        foreach ($categories as $cat) {
            $snap_cat_id = $cat->id;

            $cat = array(
                'taxonomy' => 'product_cat',
                'cat_name' => $cat->label,
                'category_description' => '',
                'category_nicename' => strtolower($cat->label),
                'category_parent' => '',
            );

            try {
                $term = term_exists($cat['cat_name'], 'product_cat');
                if (is_array($term)) {
                    $cat_ids[] = (int)$term['term_id'];
                } else {
                    $term = wp_insert_category($cat);
                    $cat_ids[] = $term;
                    $term_meta_result = update_term_meta($term, '_snap_cat_id', $snap_cat_id);
                }
            } catch (\Throwable $th) {
                $loggingController->addErrorLog(sprintf('Error creating categories. %s', $th->getMessage()), 'product-scheduled-task');
                wp_die('Error creating categories. ' . esc_html($th->getMessage()));
            }
        }
        return $cat_ids;
    }

    private function make_custom_attributes($data)
    {
        $attribute_keys = array(
            'contributors' => 'Authors',
            'gradeLevel' => 'Grade Level',
            'language' => 'Language',
            'copyright' => 'Copyright',
            'publisher' => 'Publisher',
            'identifier' => 'ISBN',
            'numberOfPages' => 'Number Of Pages',
            'fileSize' => 'File Size',
            'format' => 'Format',
            'edition' => 'Edition',
            'publishedDate' => 'Published',
        );
        $attribute_taxonomy_keys = array(
            'contributors' => 'Authors',
            'gradeLevel' => 'Grade Level',
            'language' => 'Language',
            'copyright' => 'Copyright',
            'publisher' => 'Publisher',
        );

        $temp_attributes = array_intersect_key($data, $attribute_keys);

        $attributes = array();

        $current_attributes = wc_get_attribute_taxonomy_labels();

        foreach ($attribute_keys as $key => $value) {
            if ('' === $value || null === $value) {
                // skip iteration if value string is empty or null.
                continue;
            }
            if ('fileSize' === $key) {
                // format file size into something human readable.
                $temp_attributes[$key] = number_format(($temp_attributes[$key] / 1048576), 2) . ' mb';
            }
            if ('contributors' === $key) {
                $temp_author = array();
                foreach ($temp_attributes[$key] as $loop_author) {
                    $temp_author[] = $loop_author['firstName'] . ' ' . $loop_author['lastName'];
                }
                $temp_attributes[$key] = $temp_author;
            }
            $attribute_slug = strtolower($key);
            // only add the attribute taxonomy if it doesn't exist.
            if (false === array_key_exists($attribute_slug, $current_attributes)) {

                $type = 'select';
                $public = true;
                if (false === array_key_exists($key, $attribute_taxonomy_keys)) {
                    $type = 'text';
                    $public = false;
                }

                wc_create_attribute(
                    array(
                        'name' => $value,
                        'slug' => $attribute_slug,
                        'type' => $type,
                        'order_by' => 'name',
                        'has_archives' => $public,

                    )
                );
            }

            if ('' !== $temp_attributes[$key] && null !== $temp_attributes[$key]) {
                $attributes[wc_attribute_taxonomy_name($attribute_slug)] = $temp_attributes[$key];
            }
        }

        return $attributes;
    }

    private function make_basic_attributes($data)
    {
        $attribute_keys = array(
            'identifier' => 'ISBN',
            'numberOfPages' => 'Number Of Pages',
            'fileSize' => 'File Size',
            'format' => 'Format',
            'edition' => 'Edition',
            'publishedDate' => 'Published',
        );

        $temp_attributes = array_intersect_key($data, $attribute_keys);

        $attributes = array();

        foreach ($attribute_keys as $key => $value) {
            if ('' === $value || null === $value) {
                // skip iteration if value string is empty or null.
                continue;
            }
            if ('fileSize' === $key) {
                // format file size into something human readable.
                $temp_attributes[$key] = number_format(($temp_attributes[$key] / 1048576), 2) . ' mb';
            }

            $attribute_object = new \WC_Product_Attribute();
            $attribute_object->set_name($attribute_keys[$key]);
            $attribute_object->set_options(array((string)$temp_attributes[$key]));
            $attribute_object->set_position(0);
            $attribute_object->set_visible(true);
            $attribute_object->set_variation(false);
            $attributes[] = $attribute_object;
        }

        return $attributes;
    }

    private function generate_snapplify_sku($snapplify_id)
    {
        return 'snap-' . $snapplify_id;
    }

    private function get_product_id_by_snap_id($snap_id)
    {
        global $wpdb;

        // phpcs:ignore WordPress.VIP.DirectDatabaseQuery.DirectQuery
        $productPostId = $wpdb->get_var(
            $wpdb->prepare(
                "
    			SELECT post_id
    			FROM {$wpdb->postmeta}
    			WHERE meta_key ='_snap_id'
    			AND meta_value = %s
    			LIMIT 1
    			",
                $snap_id
            )
        );

        return (int)$productPostId;
    }

    private function should_product_be_updated($product_id, $new_updated_date)
    {
        global $wpdb;

        // phpcs:ignore WordPress.VIP.DirectDatabaseQuery.DirectQuery
        $old_updated_date = $wpdb->get_var(
            $wpdb->prepare(
                "
    			SELECT meta_value
    			FROM {$wpdb->postmeta}
    			WHERE meta_key = '_snap_updatedDate'
    			AND post_id = %s
    			LIMIT 1
    			",
                $product_id
            )
        );

        $old_updated_date = new DateTime($old_updated_date);
        $new_updated_date = new DateTime($new_updated_date);

        return ($new_updated_date > $old_updated_date) ? true : false;
    }

    public function process_add_attribute($attribute)
    {
        global $wpdb;

        if (empty($attribute['attribute_type'])) {
            $attribute['attribute_type'] = 'select';
        }

        if (empty($attribute['attribute_orderby'])) {
            $attribute['attribute_orderby'] = 'menu_order';
        }
        if (empty($attribute['attribute_public'])) {
            $attribute['attribute_public'] = 0;
        }

        if (empty($attribute['attribute_name']) || empty($attribute['attribute_label'])) {
            return new \WP_Error('error', __('Please, provide an attribute name and slug.', 'snapplify-ecommerce'));
        }
        if (taxonomy_exists(wc_attribute_taxonomy_name($attribute['attribute_name']))) {
            /* translators: %s is replaced by the name of an attribute */
            return new \WP_Error('error', sprintf(__('Slug "%s" is already in use. Change it, please.', 'snapplify-ecommerce'), sanitize_title($attribute['attribute_name'])));
        }
        if (($valid_attribute_name == $this->valid_attribute_name($attribute['attribute_name'])) && is_wp_error($valid_attribute_name)) {
            return $valid_attribute_name;
        }

        $wpdb->replace($wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute);

        flush_rewrite_rules();
        delete_transient('wc_attribute_taxonomies');
        do_action('woocommerce_attribute_added', $wpdb->insert_id, $attribute);

        return true;
    }

    private function valid_attribute_name($attribute_name)
    {
        if (strlen($attribute_name) >= 28) {
            /* translators: %s is replaced by the name of an attribute */
            return new \WP_Error('error', sprintf(__('Slug "%s" is too long (28 characters max). Shorten it, please.', 'snapplify-ecommerce'), sanitize_title($attribute_name)));
        } elseif (wc_check_if_attribute_name_is_reserved($attribute_name)) {
            /* translators: %s is replaced by the name of an attribute */
            return new \WP_Error('error', sprintf(__('Slug "%s" is not allowed because it is a reserved term. Change it, please.', 'snapplify-ecommerce'), sanitize_title($attribute_name)));
        }

        return true;
    }

    private function save_wc_custom_attributes($post_id, $custom_attributes)
    {
        $i = 0;
        // Loop through the attributes array.
        foreach ($custom_attributes as $name => $value) {
            // Relate post to a custom attribute, add term if it does not exist.
            wp_set_object_terms($post_id, $value, $name, false);

            $is_taxonomy = false;
            $archive_taxonomies = array(
                'pa_contributors',
                'pa_language',
                'pa_gradelevel',
                'pa_copyright',
                'pa_publisher',
            );
            if (in_array($name, $archive_taxonomies)) {
                $is_taxonomy = true;
            }

            $product_attributes[$i] = array(
                'name' => $name,
                'value' => $value,
                'is_visible' => 1,
                'is_variation' => 0,
                'is_taxonomy' => $is_taxonomy,
            );
            $i++;
        }

        // Now update the post with its new attributes.
        update_post_meta($post_id, '_product_attributes', $product_attributes);
    }
}
