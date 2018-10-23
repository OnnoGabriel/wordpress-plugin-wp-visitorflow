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
			<p><?php echo __('Export recorded data to csv tables or to the WP VisitorFlow app.', 'wp-visitorflow'); ?></p>
			<div style="clear:both;"></div>
	<?php

		// Print Tab Menu
	?>
			<div style="clear:both;"></div>
			<nav class="nav-tab-wrapper">
	<?php
			foreach ($admin_tabs as $tab => $props) {
				if (current_user_can($props['min_role']) ) {
					if ($exportPage->get_current_tab() == $tab){
						$class = ' nav-tab-active';
					}
					else {
						$class = '';
					}
					echo '<a class="nav-tab'.$class.'" href="?page=wpvf_admin_export&amp;tab=' . $tab . '">'.$props['title'].'</a>';
				}
			}
	?>
			</nav>
			<div style="clear:both;"></div>
	<?php


		if ($exportPage->get_current_tab() == 'table') {
			include_once WP_VISITORFLOW_PLUGIN_PATH . 'includes/views/export/table.php';
		}
		elseif ($exportPage->get_current_tab() == 'app') {
			include_once WP_VISITORFLOW_PLUGIN_PATH . 'includes/views/export/app.php';
		}


	}
}

endif;	// Prevent multiple class definitions
