<?php
/**
 * This file manages metadata on products.
 *
 * @link       https:// snapplify.com
 * @since      1.0.0
 *
 * @package    Snapplify_ECommerce
 * @subpackage Snapplify_ECommerce / lib
 */

namespace Snapplify;

/**
 * This class provides functionality to manage metadata on products.
 *
 * @link       https:// snapplify.com
 * @since      1.0.0
 *
 * @package    Snapplify_ECommerce
 * @subpackage Snapplify_ECommerce / lib
 */
class WooMetaHandler {

	/**
	 * Add metadatas to a post.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id    Post ID.
	 * @param string $meta_data  Metadata as an key/value pair array
	 *
	 * @return bool  Was the data inserted
	 */
	public static function add_post_metas( $post_id, $meta_data ) {

		return self::add_metadatas( 'post', $post_id, $meta_data );
	}

	/**
	 * Update metadatas on a post.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id    Post ID.
	 * @param string $meta_data  Metadata as an key/value pair array
	 *
	 * @return bool  Was the data inserted
	 */
	public static function update_post_metas( $post_id, $meta_data ) {

		return self::add_post_metas( $post_id, $meta_data );
	}

	/**
	 * Add or Update multiple metadata for the specified object.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string $meta_type  Type of object metadata is for (e.g., comment, post, or user)
	 * @param int    $object_id  ID of the object metadata is for
	 * @param array  $meta_data  Metadata as an key/value pair array
	 *
	 * @return bool  If the metadata was stored successfully.
	 */
	public static function add_metadatas( $meta_type, $object_id, $meta_data ) {
		global $wpdb;

		if ( ! $meta_type || ! is_array( $meta_data ) || ! is_numeric( $object_id ) ) {
			return false;
		}

		$object_id = absint( $object_id );
		if ( ! $object_id ) {
			return false;
		}

		$_meta_data = array();
		foreach ( $meta_data as $key => $value ) {
			update_post_meta( $object_id, $key, $value );
		}

		wp_cache_delete( $object_id, $meta_type . '_meta' );

		return true;
	}
}
