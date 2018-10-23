<?php
/**
 * Load frontend JavaScript
 *
 * @package WP-VisitorFlow
 */

?>
<div id="wpvf-info" style="display:none" data-home-url="<?php echo esc_url( home_url( '/', 'relative' ) ); ?>"></div>
<script src="<?php echo esc_attr( WP_VISITORFLOW_PLUGIN_URL . 'assets/js/wp-visitorflow-frontend.js' ); ?>"></script>
<?php
