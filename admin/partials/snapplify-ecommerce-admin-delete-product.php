<?php
/**
 * View for product delete
 *
 * @link       https://snapplify.com
 * @since      1.0.0
 *
 * @package    Snapplify_ECommerce
 * @subpackage Snapplify_ECommerce/admin/partials
 */

defined( 'ABSPATH' ) || exit;

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

$product_flush_url = esc_url(
	wp_nonce_url(
		add_query_arg(
			array('delete-product' => '1', 'flush-product' => '1'),
			$url
		)
	),
	'all-product-delete'
);

if ( 'wcsnapplify' === $current_section && isset( $_GET['delete-product'] ) && isset( $_GET['flush-product'] ) ) {
	global $wpdb;
	$table_name = 'wp_actionscheduler_actions';
	$column_name = 'wc_snap_process_product_exec';
	
	if($table_name != '' && $column_name != ''){
		try{
			$sql = "DELETE FROM {$table_name} WHERE hook = 'wc_snap_process_product_exec' AND status = 'pending';";
			$wpdb->query($sql);            
		}catch(Exception $e){
			print_r($e);
		}
	}
	if ( wp_redirect( $url ) ) {
		exit;
	}
}


?>
<div id="key-fields" class="settings-panel">
	<h2><?php esc_html_e( 'Delete all pending product queue', 'snapplify-ecommerce' ); ?></h2>

	<p><?php esc_html_e( 'This will delete all the pending products in the processing queue.', 'snapplify-ecommerce' ); ?></p>
	<p></p>
	<a href="<?php echo esc_url( $product_flush_url )?>" class="button-primary"><?php esc_html_e( "Flush all queues", 'snapplify-ecommerce' ); ?></a>
</div>
