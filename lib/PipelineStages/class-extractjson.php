<?php
/**
 * JSON extraction pipeline step.
 *
 * @link       https://snapplify.com
 * @since      1.0.0
 *
 * @package    Snapplify_ECommerce
 * @subpackage Snapplify_ECommerce/lib
 */

namespace Snapplify\PipelineStages;

/**
 * This class invokes JSON extraction.
 *
 * @link       https://snapplify.com
 * @since      1.0.0
 *
 * @package    Snapplify_ECommerce
 * @subpackage Snapplify_ECommerce/lib
 */
class ExtractJson {

	public function __invoke( $request ) {
		return json_decode( $request->get_body() );
	}
}
