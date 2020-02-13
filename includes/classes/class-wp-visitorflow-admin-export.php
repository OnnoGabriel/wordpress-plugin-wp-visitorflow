<?php
/**
 *	Export class
 *
 * @package    WP-VisitorFlow
 * @author     Onno Gabriel
 **/

// Prevent calls from outside of WordPress
defined( 'ABSPATH' ) || exit;

if (! class_exists("WP_VisitorFlow_Admin_Export")) :	// Prevent multiple class definitions

class WP_VisitorFlow_Admin_Export
{

	private static $config = 0;

	/**
	 * Init
	 **/
	public static function init() {
		if ( ! self::$config ) {
			self::$config = WP_VisitorFlow_Config::getInstance();
		}
	}

	/**
	 * Main
	 **/
	public static function main() {
		if ( ! self::$config ) {
			self::init();
		}

		if (! is_admin() || ! current_user_can( self::$config->getSetting('admin_access_capability') ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		$admin_tabs = array(
			'table'  => array( 'title' => __('Data Export', 'wp-visitorflow'),		'min_role' => 'moderate_comments'),
			'app'  	 => array( 'title' => __('App', 'wp-visitorflow'), 				'min_role' => 'moderate_comments'),
		);

		$exportPage = new WP_VisitorFlow_Admin_Page(
			self::$config->getSetting('admin_access_capability'),
			false,
			$admin_tabs
		);

		// Print Page Header
	?>
		<div class="wrap">
			<div style="float:left;">
				<img src="<?php echo plugin_dir_url( __FILE__ ) . '../../assets/images/Logo_250.png'; ?>" align="left" width="80" height="80" alt="Logo" />
			</div>
			<h1>WP VisitorFlow &ndash; <?php echo __('Data Export', 'wp-visitorflow') ?></h1>
			<p><?php echo __('Export recorded data to CSV.', 'wp-visitorflow'); ?></p>
			<div style="clear:both;"></div>
	<?php

		include_once WP_VISITORFLOW_PLUGIN_PATH . 'includes/views/export/table.php';


	}
}

endif;	// Prevent multiple class definitions
