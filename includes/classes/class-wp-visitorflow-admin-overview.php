<?php
/**
 *	This is admin class for WP VisitorFlow.
 *
 * @package    WP-VisitorFlow
 * @author     Onno Gabriel
 **/

// Prevent calls from outside of WordPress
defined( 'ABSPATH' ) || exit;

if (! class_exists("WP_VisitorFlow_Admin_Overview")) :	// Prevent multiple class definitions

class WP_VisitorFlow_Admin_Overview
{

	public static $config = 0;

	/**
	 * Init
	 **/
	public static function init() {
		if ( is_admin() && ! self::$config ) {
			self::$config = WP_VisitorFlow_Config::getInstance();
		}
	}

	/**
	 * Overview page: Header
	 **/
	public static function overviewHeader() {
		// No data yet? Print some information
		$db_info = self::$config->getSetting('db_info');
		if ($db_info['visits_count'] == 0) {
	?>
		<br><br>
		<div class="wpvf_warning">
			<?php
			echo __('No data yet.', 'wp-visitorflow') . '<br>';
			echo '<br>';
			echo '<span style="font-weight:normal">' . __('This is probably due to a fresh installation.', 'wp-visitorflow') . '</span><br>';
			echo '<br>';
			echo '<span style="font-weight:normal">' . __('Please note that by default no visitor data is stored originating from admin pages or from administrators visiting your website. You can change this settings in the <a class="wpvf" href="?page=wpvf_admin_settings&tab=storing">settings section</a>.', 'wp-visitorflow') . '</span><br>';
			?>
		</div><br>
		<br><br>
	<?php
		}
		else {
	?>
		<div style="text-align:right;">
			<a href="//wordpress.org/support/view/plugin-reviews/wp-visitorflow" target="_blank" class="add-new-h2">
				<?php echo __('Rate us', 'wp-visitorflow'); ?> &#9733;&#9733;&#9733;&#9733;&#9733;
			</a>
			<a href="//wordpress.org/support/plugin/wp-visitorflow" target="_blank" class="add-new-h2">
				<?php echo __('Support', 'wp-visitorflow'); ?>
			</a>
		</div>
	<?php
		}
	}


	/**
	 * Overview page: Metaboxes
	 **/
	public static function overviewMetaboxes() {

		$current_screen = get_current_screen();
		switch ( $current_screen->id) {
			case 'toplevel_page_wpvf_menu':
				self::content();
				break;
			default:
				add_meta_box('example1','Example 1','sh_example_metabox', get_current_screen(), 'side','high');
				add_meta_box('example2','Example 2','sh_example_metabox', get_current_screen(), 'advanced','high');
		}
	}


	/**
	 * Content of the "Overview" Page (in form of WordPress Metaboxes)
	 **/
	public static function content() {

		WP_VisitorFlow_Analysis::init();
		WP_VisitorFlow_Analysis::updateDBInfo();

		add_meta_box('quickanswers',  		__('Quick Answers to Routine Inquiries', 'wp-visitorflow'),				array('WP_VisitorFlow_Admin_Overview', 'quickAnswers'), 		get_current_screen(), 'normal','high');
		add_meta_box('shortsummary',  		__('Short Summary', 'wp-visitorflow'),   								array('WP_VisitorFlow_Admin_Overview', 'summary'), 				get_current_screen(), 'normal','high');
		add_meta_box('lastmonthplot', 		__('Page Views and Visits within the Last 30 Days', 'wp-visitorflow'),	array('WP_VisitorFlow_Admin_Overview', 'lastMonthPlot'), 		get_current_screen(), 'normal','high');
		add_meta_box('latestkeywords',		__('Latest Search Keys Words', 'wp-visitorflow'), 						array('WP_VisitorFlow_Admin_Overview', 'latestKeywords'), 		get_current_screen(), 'normal','low');
		add_meta_box('botcounts', 			__('Recorded Bot Visits', 'wp-visitorflow'), 							array('WP_VisitorFlow_Admin_Overview', 'botCounts'), 			get_current_screen(), 'side','high');
		add_meta_box('exclusioncounts', 	__('Excluded Page Views', 'wp-visitorflow'), 							array('WP_VisitorFlow_Admin_Overview', 'exclusionCounts'), 		get_current_screen(), 'side','low');
		add_meta_box('dbinfo',				__('Database Summary', 'wp-visitorflow'), 								array('WP_VisitorFlow_Admin_Overview', 'dbInfo'), 				get_current_screen(), 'side','low');
		add_meta_box('mostfrequentpages', 	__('Most Visited Pages within the last 7 Days', 'wp-visitorflow'), 		array('WP_VisitorFlow_Admin_Overview', 'mostFrequentPages'),	get_current_screen(), 'normal','low');
		add_meta_box('latestuastrings', 	__('Latest HTTP User-Agent Strings', 'wp-visitorflow'), 				array('WP_VisitorFlow_Admin_Overview', 'latestUaStrings'), 		get_current_screen(), 'normal','low');

	}


	/**
	 * Metabox content: Latest Search Keywords
	 **/
	public static function quickAnswers() {
		if (! is_admin() || ! current_user_can( self::$config->getSetting('read_access_capability') ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		// Calculate number of days since overall database start
		$data_startdate = new DateTime( self::$config->getSetting('db-startdatetime') );
		$today = new DateTime();
		$date_diff = $today->diff($data_startdate);
		$max_days_back_db = $date_diff->format('%a');

		// Calculate number of days since flow database start
		$data_startdate = new DateTime( self::$config->getSetting('flow-startdatetime') );
		$today = new DateTime();
		$date_diff = $today->diff($data_startdate);
		$max_days_back_flow = $date_diff->format('%a');

		$periods = array(
			array('days_back' => 0, 'label' => __('Today', 'wp-visitorflow'), 'start' => '+0', 'end' => '+0'),
			array('days_back' => 1, 'label' => __('Yesterday', 'wp-visitorflow'), 'start' => '-1', 'end' => '-1'),
			array('days_back' => 1, 'label' => __('Last 7 days', 'wp-visitorflow'), 'start' => '-7', 'end' => '+0'),
			array('days_back' => 7, 'label' => __('Last 14 days', 'wp-visitorflow'), 'start' => '-14', 'end' => '+0'),
			array('days_back' => 14, 'label' => __('Last 30 days', 'wp-visitorflow'), 'start' => '-30', 'end' => '+0'),
			array('days_back' => 30, 'label' => __('Last 60 days', 'wp-visitorflow'), 'start' => '-60', 'end' => '+0'),
			array('days_back' => 60, 'label' => __('Last 365 days', 'wp-visitorflow'), 'start' => '-365', 'end' => '+0')
		);

		echo '<ul>';

		echo '<li><strong>' . __('Visitor Flow', 'wp-visitorflow') . ':</strong> ';
		echo __('Show me the Visitor Flow for', 'wp-visitorflow') . ' ' ;
		for($i = 0; $i < count($periods); $i++) {
			$period = $periods[$i];
			if ($period['days_back'] <= $max_days_back_flow) {
				if ($i > 0) { echo ' | '; }
				$link = htmlspecialchars('?page=wpvf_mode_website&tab=flow&datetimestart=' . date( 'Y-m-d',  strtotime( $period['start'] . ' days') ) . '&datetimestop=' . date( 'Y-m-d',  strtotime( $period['end'] . ' days') ) );
				echo '<a class="wpvf" href="' . $link . '">';
				echo $period['label'];
				echo '</a>';
			}
		}
		echo '.</li>';

		echo '<li><strong>' . __('Referrers', 'wp-visitorflow') . ':</strong> ';
		echo __('Where are my visitors coming from? ', 'wp-visitorflow') . ' ' ;
		for($i = 0; $i < count($periods); $i++) {
			$period = $periods[$i];
			if ($period['days_back'] <= $max_days_back_db) {
				if ($i > 0) { echo ' | '; }
				$link = htmlspecialchars('?page=wpvf_mode_website&tab=referrers&datetimestart=' . date( 'Y-m-d',  strtotime( $period['start'] . ' days') ) . '&datetimestop=' . date( 'Y-m-d',  strtotime( $period['end'] . ' days') ) );
				echo '<a class="wpvf" href="' . $link . '">';
				echo $period['label'];
				echo '</a>';
			}
		}
		echo '.</li>';

		echo '<li><strong>' . __('Page Views', 'wp-visitorflow') . ':</strong> ';
		echo __('What are my most viewed posts or pages? ', 'wp-visitorflow') . ' ' ;
		for($i = 0; $i < count($periods); $i++) {
			$period = $periods[$i];
			if ($period['days_back'] <= $max_days_back_db) {
				if ($i > 0) { echo ' | '; }
				$link = htmlspecialchars('?page=wpvf_mode_website&tab=pages&datetimestart=' . date( 'Y-m-d',  strtotime( $period['start'] . ' days') ) . '&datetimestop=' . date( 'Y-m-d',  strtotime( $period['end'] . ' days') ) );
				echo '<a class="wpvf" href="' . $link . '">';
				echo $period['label'];
				echo '</a>';
			}
		}
		echo '.</li>';

		echo '</ul>';
	}


	/**
	 * Metabox content: WP VisitorFlow Short Summary (Visits, Page Views etc.)
	 **/
	public static function summary() {
		WP_VisitorFlow_Admin_Tables::summary();
	}



	/**
	 * Metabox content: Plot with Visits and Page Views in the Last 30 Days
	 **/
	public static function lastMonthPlot() {
		if (! is_admin() || ! current_user_can( self::$config->getSetting('read_access_capability') ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		// Get DB object and table names from visitorflow class
		$db = WP_VisitorFlow_Database::getDB();
		$aggregation_table  = WP_VisitorFlow_Database::getTableName('aggregation');

		//Get data for today
		$today = self::$config->getDatetime('Y-m-d');
		$todays_data = WP_VisitorFlow_Database::getData( $today );

		// Draw Visits/Page Views Timeline Diagram
		$chart_data = array();
		$results = $db->get_results(
			$db->prepare(
				"SELECT date, value AS count
				   FROM $aggregation_table
				  WHERE type='visits'
				    AND date>=subdate('%s', interval 30 day)
			    ORDER BY date ASC;",
				self::$config->getDatetime()
			)
		);
		$data = array();
		foreach ($results as $res) {
			$data[$res->date] = $res->count;
		}
		if ( isset($todays_data['visits']) ) {
			$data[$today] = $todays_data['visits'];
		}
		array_push($chart_data, array('label' => __('Visits', 'wp-visitorflow'),
									  'data' => $data) );

		$results = $db->get_results(
			$db->prepare(
				"SELECT date, value AS count
				   FROM $aggregation_table
				  WHERE type='views-all'
				    AND date>=subdate('%s', interval 30 day)
			   ORDER BY date ASC;",
				self::$config->getDatetime()
				)
    	);
		$data = array();
		foreach ($results as $res) {
			$data[$res->date] = $res->count;
		}
		if ( isset($todays_data['views-all']) ) {
			$data[$today] = $todays_data['views-all'];
		}
		array_push($chart_data, array('label' => __('Page views', 'wp-visitorflow'),
									  'data' => $data) );

		echo '<br>';

		WP_VisitorFlow_Admin_Plots::lineChart(
			'',
			$chart_data,
			array(
				'id' => 'chart_overview',
				'width' => '98%',
				'height' => '300px'
			)
		);
	}

	/**
	 * Metabox content: Latest Search Keywords
	 **/
	public static function latestKeywords() {
		WP_VisitorFlow_Admin_Tables::latestKeywordsTable();
	}

	/**
	 * Metabox content: Crawlers/Bots/Spiders Counts
	 **/
	public static function botCounts() {
		WP_VisitorFlow_Admin_Tables::botCountsTable();
	}

	/**
	 * Metabox content: Page View Exclusions
	 **/
	public static function exclusionCounts() {
		WP_VisitorFlow_Admin_Tables::exclusionCountsTable();
	}

	/**
	 * Metabox content: Database Status
	 */
	public static function dbInfo() {
		WP_VisitorFlow_Admin_Tables::dbInfoTable();
	}


	/**
	 * Metabox content: Latest Recorded User-Agent Strings
	 **/
	public static function latestUaStrings() {
		WP_VisitorFlow_Admin_Tables::latestUAStringsTable();
	}

	/**
	 * Metabox content: Most Frequented Pages within the Last 7 Days
	 **/
	public static function mostFrequentPages() {
		if (! is_admin() || ! current_user_can(self::$config->getSetting('read_access_capability') ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		// Get DB object and table names from visitorflow class
		$db = WP_VisitorFlow_Database::getDB();
		$flow_table  = WP_VisitorFlow_Database::getTableName('flow');
		$pages_table  = WP_VisitorFlow_Database::getTableName('pages');

		$datetimestart = date( 'Y-m-d', strtotime( '-7 days') );
		$datetimestop =  date( 'Y-m-d' );
		$hit_count = 20;

		$min_post_id = 0;
		if (!self::$config->getSetting('exclude_404') ) { $min_post_id = -1; }

		$sql = $db->prepare(
			"SELECT COUNT($flow_table.id) AS pcount,
					$pages_table.id AS page_id,
					$pages_table.title AS title,
					$pages_table.f_post_id AS post_id,
					MAX($flow_table.datetime) AS last_datetime
			   FROM $flow_table
			   JOIN $pages_table
			     ON $pages_table.id=$flow_table.f_page_id
			  WHERE $flow_table.datetime>='%s' AND $flow_table.datetime<=date_add('%s', interval 1 day)
			    AND $flow_table.step>'1'
			    AND $pages_table.f_post_id>='$min_post_id'
		   GROUP BY $flow_table.f_page_id
		   ORDER BY pcount DESC LIMIT %d;",
			$datetimestart, $datetimestop, $hit_count
		);

		$results = $db->get_results( $sql );
?>
		<table class="wpvftable">
		<tr>
			<th><?php echo __('Page Views', 'wp-visitorflow'); ?></th>
			<th colspan="2"><?php echo __('Page', 'wp-visitorflow'); ?></th>
			<th><?php echo __('Last Visit', 'wp-visitorflow'); ?></th>
		</tr>
<?php
		$step = 0;
		foreach ($results as $res) {

			if ($step % 2 == 0) {
				echo '<tr>';
			}
			else {
				echo '<tr class="darker">';
			}
			$step++;

			echo '<td class="right">' . $res->pcount . '</td>';

			$title = $res->title;
			if ($res->post_id == -1) {
				$title = '<font color="red"><em>404 error:</em></font> ' . $title;
			}

			$pagelink = $res->title;
			if ($res->post_id > 0) {
				$pagelink = site_url() . '?p=' . $res->post_id;
			}
			else {
				$pagelink = site_url() . $res->title;;
			}

			echo '<td><a class="wpvf wpvfpage" href="'. $pagelink . '">' .  $title . '</a></td>';
			echo '<td><a class="wpvf wpvfflow" href="?page=wpvf_mode_singlepage&amp;select_page_id=' .  $res->page_id . '">' . __('Flow', 'wp-visitorflow') . '</a></td>';
			echo '<td>' . sprintf(
				__('%s ago', 'wp-visitorflow'),
				WP_VisitorFlow_Admin::getNiceTimeDifference(
					$res->last_datetime,
					self::$config->getDatetime()
				)
			) . '</td>';
			echo '</tr>';

		}
?>
		</table>
		<br>
<?php
	}

}

endif;	// Prevent multiple class definitions
