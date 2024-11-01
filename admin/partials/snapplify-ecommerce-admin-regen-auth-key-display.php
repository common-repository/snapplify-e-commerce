<?php
/**
 * View for Authentication key regeneration
 *
 * @link       https://snapplify.com
 * @since      1.0.0
 *
 * @package    Snapplify_ECommerce
 * @subpackage Snapplify_ECommerce/admin/partials
 */

defined( 'ABSPATH' ) || exit;

$auth_token = get_option( 'wcsnapplify_token' );
?>
<script>
	function wcSnapClipboardCopier() {
		var tokenField = document.getElementById("authentication-token");

		tokenField.select();
		tokenField.setSelectionRange(0, 99999); /* For mobile devices */

		document.execCommand("copy");

		var copyButton = document.getElementById('wcSnapCopyButton');
		var copiedText = copyButton.getAttribute('data-copiedText');
		var copyText = copyButton.getAttribute('data-copyText');
		copyButton.innerText = copiedText;
		setInterval(function() {
			copyButton.innerText = copyText;
		}, 3000);
	}
</script>

<div id="key-fields" class="settings-panel">
	<h2><?php esc_html_e( 'New key details', 'snapplify-ecommerce' ); ?></h2>

	<p><?php esc_html_e( 'This key will only be displayed once. Make sure you copy and store it safely.', 'snapplify-ecommerce' ); ?></p>
	<table class="form-table">
		<tbody>
			<tr valign="top">
				<th scope="row" class="titledesc">
					<?php esc_html_e( 'Authentication Token', 'snapplify-ecommerce' ); ?>
				</th>
				<td class="forminp">
					<input id="authentication-token" type="text" value="<?php echo ( esc_html( $auth_token ) ); ?>" size="55" readonly="readonly"> <button id="wcSnapCopyButton" type="button" class="button-secondary copy-authentication-token" data-copyText="<?php esc_attr_e( 'Copy', 'snapplify-ecommerce' ); ?>" data-copiedText="<?php esc_attr_e( 'Copied!', 'snapplify-ecommerce' ); ?>" onclick="wcSnapClipboardCopier()"><?php esc_html_e( 'Copy', 'snapplify-ecommerce' ); ?></button>
				</td>
			</tr>
		</tbody>
	</table>
	<p></p>
	<a href="<?php echo esc_url( $settings_link ); ?>" class="button-primary"><?php esc_html_e( "I've copied the token", 'snapplify-ecommerce' ); ?></a>
</div>


<?php
unset( $auth_token );
