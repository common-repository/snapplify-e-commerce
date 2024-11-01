<?php
/**
 * JSON validation pipeline step.
 *
 * @link       https://snapplify.com
 * @since      1.0.0
 *
 * @package    Snapplify_ECommerce
 * @subpackage Snapplify_ECommerce/lib
 */

namespace Snapplify\PipelineStages;

use JsonSchema\Validator;

/**
 * This class invokes JSON validation.
 *
 * @link       https://snapplify.com
 * @since      1.0.0
 *
 * @package    Snapplify_ECommerce
 * @subpackage Snapplify_ECommerce/lib
 */
class ValidateJson {

	public function __invoke( $json ) {
		$schema_path = realpath( __DIR__ . '/../schema_with_null.json' );
		$validator   = new Validator();
		$validator->validate( $json, (object) array( '$ref' => 'file://' . $schema_path ) );

		return $validator;
	}
}