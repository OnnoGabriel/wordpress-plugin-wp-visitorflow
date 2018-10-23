<?php
/**
 *	Single Page Flow Analysis
 *
 * @package    WP-VisitorFlow
 * @author     Onno Gabriel
 **/

// Prevent calls from outside of WordPress
defined( 'ABSPATH' ) || exit;

if (! class_exists("WP_VisitorFlow_Admin_Single")) :	// Prevent multiple class definitions

class WP_VisitorFlow_Admin_Single
{

	private static $config = 0;

	/**
	 * Init
	 **/
	public static function init() {
		if ( ! self::$config ) {
			self::$config = WP_VisitorFlow_Config::getInstance();
		}

		// Enqueue Sankey stylesheet
		wp_enqueue_style('sankey-css', WP_VISITORFLOW_PLUGIN_URL . 'assets/css/sankey.css');
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

		// Load user settings
		self::$config->loadUserSettings();

		$db = WP_VisitorFlow_Database::getDB();
		$pages_table = WP_VisitorFlow_Database::getTableName('pages');
		$aggregation_table = WP_VisitorFlow_Database::getTableName('aggregation');


		// Minimum reader capability (can be set in "general settings" section):
		$reader_cap = self::$config->getSetting('read_access_capability');
		$hit_count = 20;

		// Include TimeframePage class and instantiate
		$timeframePage = new WP_VisitorFlow_Admin_Page_Timeframe($reader_cap);

		// Start and end of the time interval selection (permanently stored in options)
		$datetimestart = $timeframePage->getTimeframeStart();
		$datetimestop = $timeframePage->getTimeframeStop();
		if ( ! $timeframePage->getTimeframeStart() || ! $timeframePage->getTimeframeStop() ) {
			$datetimestart = self::$config->getUserSetting('datetimestart');
			$datetimestop  = self::$config->getUserSetting('datetimestop');
			$timeframePage->setTimeframe($datetimestart, $datetimestop);
		}

		// Get selected page:
		$selected_page_id = 3;
		$selected_title =  '';
		$selected_post_id =  '';
		$error_message = '';
		if (array_key_exists('select_page_id', $_GET)) {
			$selected_page_id = htmlspecialchars( stripslashes( $_GET['select_page_id'] ) );

			// Check if page exists:
			$result = $db->get_row(
				$db->prepare(
					"SELECT title, f_post_id
					FROM $pages_table
					WHERE id='%s' LIMIT 1;",
					$selected_page_id
				)
			);
			if (isset($result->title)) {
				$selected_title =  html_entity_decode( $result->title );
				$selected_title = preg_replace('/\\\\\'/', "", $selected_title);
				$selected_post_id =  $result->f_post_id;
			}
		}

		// Or is post id queried?
		elseif (array_key_exists('select_post_id', $_GET)) {
			$selected_post_id = htmlspecialchars( stripslashes( $_GET['select_post_id'] ) );
			$result = $db->get_row(
				$db->prepare(
					"SELECT id, title
					FROM $pages_table
					WHERE f_post_id='%s' LIMIT 1;",
					$selected_post_id
				)
			);

			if (isset($result->id)) {
				$selected_title =  html_entity_decode( $result->title );
				$selected_title = preg_replace('/\\\\\'/', "", $selected_title);
				$selected_page_id =  $result->id;
			}
			else {
				$error_message = '<br /><div class="wpvf_warning">';
				$error_message .= '<p>' . __('Selected post/page does not exist or has not been visited yet.', 'wp-visitorflow') . '<p>';
				$error_message .= '</div><br />';
			}
		}


		//No entry found? Get the primary page (id=3)
		if (! $selected_title) {
			$selected_page_id = 3;
			$result = $db->get_row("SELECT title, f_post_id
										FROM $pages_table
										WHERE id=3 LIMIT 1;");
			$selected_title =  html_entity_decode( $result->title );
			$selected_title = preg_replace('/\\\\\'/', "", $selected_title);
			$selected_post_id =  $result->f_post_id;
		}

		self::$config->setUserSetting('selected_page_id', $selected_page_id);

		// Set Tabs
		$timeframePage->setTabs(
			 array(
				'flow' 		=> array( 'title' => __('Visitor Flow', 'wp-visitorflow'),	'min_role' => $reader_cap),
				'timeline'  => array( 'title' => __('Timeline', 'wp-visitorflow'),		'min_role' => $reader_cap),
			)
		);


		$pagelink = $selected_title;
		if ($selected_post_id == -1) {
			$pagelink = '<a class="wpvfpage" href="' . site_url() . $selected_title . '">' . $selected_title . '</a> (404 error)';
		}
		elseif ($selected_post_id > 0) {
			$pagelink = '<a class="wpvfpage" href="' . site_url() . '?p=' . $selected_post_id . '">' . $selected_title . '</a>';
		}

		if ($timeframePage->get_current_tab() == 'timeline') {
			$timeframePage->printHeader(
				__('Single Page Timeline Plot', 'wp-visitorflow'),
				sprintf(__('Page views for page <strong>%s</strong>', 'wp-visitorflow'), $pagelink)
			);
			echo $error_message;
			include_once WP_VISITORFLOW_PLUGIN_PATH . 'includes/views/analysis/single-timeline.php';
		}
		else {
			$timeframePage->printHeader(
				__('Single Page Visitor Flow', 'wp-visitorflow'),
				sprintf(__('Visitor Flow towards and from page <strong>%s</strong>', 'wp-visitorflow'), $pagelink)
			);
			echo $error_message;
			include_once WP_VISITORFLOW_PLUGIN_PATH . 'includes/views/analysis/flow-per-page.php';
		}


		if (self::$config->getUserSetting('datetimestart') !=  $timeframePage->getTimeframeStart() ) {
			self::$config->setUserSetting('datetimestart', $timeframePage->getTimeframeStart(), 0);
		}
		if (self::$config->getUserSetting('datetimestop')  !=  $timeframePage->getTimeframeStop() ) {
			self::$config->setUserSetting('datetimestop',  $timeframePage->getTimeframeStop(), 0);
		}

		self::$config->saveUserSettings();

		$timeframePage->printFooter();

	}

}

endif;	// Prevent multiple class definitions
