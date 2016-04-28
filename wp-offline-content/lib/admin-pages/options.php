<?php
/**
 * The template for the options page to configure caching content.
 *
 * @package OfflineContent
 */

?>
<div class="wrap">
	<h1><?php esc_html_e( 'Offline Content', 'offline-content' ); ?></h1>
	<form method="post" action="options.php">
	<?php settings_fields( self::$options_group ); ?>
	<?php do_settings_sections( self::$options_page_id ); ?>
	<?php submit_button( __( 'Save Changes', 'offline-content' ), 'primary' ); ?>
	</form>
</div>
