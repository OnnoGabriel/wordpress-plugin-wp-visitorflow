<?php
/**
 *	Admin Settings for WP VisitorFlow.
 *
 * @package    WP-VisitorFlow
 * @author     Onno Gabriel
 **/

// Prevent calls from outside of WordPress
defined( 'ABSPATH' ) || exit;

if (! class_exists("WP_VisitorFlow_Admin_Settings")) :	// Prevent multiple class definitions

class WP_VisitorFlow_Admin_Settings
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
			'storage'  	 	=> array( 'title' => __('Storage', 'wp-visitorflow'),			 	'min_role' => 'moderate_comments'),
			'privacy'  	 	=> array( 'title' => __('Privacy', 'wp-visitorflow'),	    	 	'min_role' => 'moderate_comments'),
			'maintenance' 	=> array( 'title' => __('Maintenance', 'wp-visitorflow'),		 	'min_role' => 'moderate_comments'),
			'logfile'		=> array( 'title' => __('Logfile', 'wp-visitorflow'),			 	'min_role' => 'moderate_comments'),
		);


		$settingsPage = new WP_VisitorFlow_Admin_Page(
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
			<h1>WP VisitorFlow &ndash; <?php echo __('Settings', 'wp-visitorflow') ?></h1>
			<div style="clear:both;"></div>
	<?php

		// Print Tab Menu
	?>
			<div style="clear:both;"></div>

			<nav class="nav-tab-wrapper">

	<?php
			foreach ($admin_tabs as $tab => $props) {
				if (current_user_can($props['min_role']) ) {
					if ($settingsPage->get_current_tab() == $tab){
						$class = ' nav-tab-active';
					}
					else {
						$class = '';
					}
					echo '<a class="nav-tab'.$class.'" href="?page=wpvf_admin_settings&amp;tab=' . $tab . '">'.$props['title'].'</a>';
				}
			}
	?>
			</nav>
			<div style="clear:both;"></div>
	<?php


		if ($settingsPage->get_current_tab() == 'storage') {
			include_once WP_VISITORFLOW_PLUGIN_PATH . 'includes/views/settings/storage.php';
		}
		elseif ($settingsPage->get_current_tab() == 'privacy') {
			include_once WP_VISITORFLOW_PLUGIN_PATH . 'includes/views/settings/privacy.php';
		}
		elseif ($settingsPage->get_current_tab() == 'maintenance') {
			include_once WP_VISITORFLOW_PLUGIN_PATH . 'includes/views/settings/maintenance.php';
		}
		elseif ($settingsPage->get_current_tab() == 'logfile') {
			include_once WP_VISITORFLOW_PLUGIN_PATH . 'includes/views/settings/logfile.php';
		}

		$settingsPage->printFooter();

	}
}

endif;	// Prevent multiple class definitions
