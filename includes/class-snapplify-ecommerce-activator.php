<?php
/**
 * Fired during plugin activation
 *
 * @link       https://snapplify.com
 * @since      1.0.0
 *
 * @package    Snapplify_ECommerce
 * @subpackage Snapplify_ECommerce/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Snapplify_ECommerce
 * @subpackage Snapplify_ECommerce/includes
 */
class Snapplify_ECommerce_Activator {


	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		 self::initialise_attributes();
	}

	/**
	 * Initialises attributes for Snapplify meta data
	 */
	private static function initialise_attributes() {
		$attribute_keys          = array(
			'contributors'  => 'Authors',
			'gradeLevel'    => 'Grade Level',
			'language'      => 'Language',
			'copyright'     => 'Copyright',
			'publisher'     => 'Publisher',
			'identifier'    => 'ISBN',
			'numberOfPages' => 'Number Of Pages',
			'fileSize'      => 'File Size',
			'format'        => 'Format',
			'edition'       => 'Edition',
			'publishedDate' => 'Published',
		);
		$attribute_taxonomy_keys = array(
			'contributors' => 'Authors',
			'gradeLevel'   => 'Grade Level',
			'language'     => 'Language',
			'copyright'    => 'Copyright',
			'publisher'    => 'Publisher',
		);

		$current_attributes = wc_get_attribute_taxonomy_labels();

		foreach ( $attribute_keys as $key => $value ) {

			$attribute_slug = strtolower( $key );
			// only add the attribute if it doesn't exist.
			if ( false === array_key_exists( $attribute_slug, $current_attributes ) ) {

				$type   = 'select';
				$public = true;
				if ( false === array_key_exists( $key, $attribute_taxonomy_keys ) ) {
					$type   = 'text';
					$public = false;
				}

				wc_create_attribute(
					array(
						'name'         => $value,
						'slug'         => $attribute_slug,
						'type'         => $type,
						'order_by'     => 'name',
						'has_archives' => $public,

					)
				);
			}
		}
	}
}
