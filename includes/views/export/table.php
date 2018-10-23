<?php

	if (! is_admin() || ! current_user_can( self::$config->getSetting('admin_access_capability') ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
?>
	<br>
<?php

	$sel_yearmonth = isset($_POST['yearmonth']) ? htmlspecialchars( stripslashes( $_POST['yearmonth'] ) )   : 0;
	$export_filepath = '';
	$export_fileurl = '';
	$zip_filepath = '';
	$zip_fileurl = '';

	# Set export folder (and create it, if not existing)
	$export_dir = WP_CONTENT_DIR . '/extensions/';
	if (! file_exists( $export_dir ) ) {
		if (! mkdir( $export_dir ) ) {
			echo '<br><div class="wpvf_warning">' . sprintf(__('Cannot create folder %s.', 'wp-visitorflow'), $export_dir) . '<br>' . __('Please check folder permissions.', 'wp-visitorflow') . "</div><br>\n";
			return;
		}
	}
	$export_dir .= 'wp-visitorflow/';
	if (! file_exists( $export_dir ) ) {
		if (! mkdir( $export_dir ) ) {
			echo '<br><div class="wpvf_warning">' . sprintf(__('Cannot create folder %s.', 'wp-visitorflow'), $export_dir) . '<br>' . __('Please check folder permissions.', 'wp-visitorflow') . "</div><br>\n";
			return;
		}
	}
	$export_dir .= 'export/';
	if (! file_exists( $export_dir ) ) {
		if (! mkdir( $export_dir ) ) {
			echo '<br><div class="wpvf_warning">' . sprintf(__('Cannot create folder %s.', 'wp-visitorflow'), $export_dir) . '<br>' . __('Please check folder permissions.', 'wp-visitorflow') . "</div><br>\n";
			return;
		}
	}

	# Clean-up folder "exports"
	$dirFiles = array();
	if ($handle = opendir($export_dir)) {
		while (false !== ($file = readdir($handle))) {
			if (is_file($export_dir . $file) && $file != '.' && $file != '..')  {
				unlink ($export_dir . $file);
			}
		}
		@closedir($handle);
	}

	if ($sel_yearmonth) {

		// Get DB object and table names from visitorflow class
		$db = WP_VisitorFlow_Database::getDB();
		$visits_table = WP_VisitorFlow_Database::getTableName('visits');
		$flow_table = WP_VisitorFlow_Database::getTableName('flow');
		$pages_table = WP_VisitorFlow_Database::getTableName('pages');

		$startdate = $sel_yearmonth . '-01';
		$enddate = new DateTime($startdate);
		$enddate->modify('+1 month');
		$enddate = $enddate->format('Y-m-d');

		$sql = $db->prepare(
			"SELECT $flow_table.datetime AS datetime,
					$flow_table.step AS step,
					$visits_table.agent_name AS agent_name,
					$visits_table.agent_version AS agent_version,
					$visits_table.agent_engine AS agent_engine,
					$visits_table.os_name AS os_name,
					$visits_table.os_version AS os_version,
					$visits_table.os_platform AS os_platform,
					$visits_table.ip AS ip,
					$pages_table.f_post_id AS post_id,
					$pages_table.title AS post_title
			   FROM $flow_table
		  LEFT JOIN $visits_table ON $visits_table.id=$flow_table.f_visit_id
		  LEFT JOIN $pages_table ON $pages_table.id=$flow_table.f_page_id
		      WHERE $flow_table.datetime BETWEEN '%s' AND '%s'
		   ORDER BY $visits_table.id, $flow_table.datetime;",
			$startdate, $enddate
		);
		$results = $db->get_results( $sql );

		$csv_table = array();
		array_push($csv_table,
			array(
				__('Date/Time', 'wp-visitorflow'),
				__('Agent', 'wp-visitorflow'),
				__('Agent Version', 'wp-visitorflow'),
				__('Agent Engine', 'wp-visitorflow'),
				__('OS', 'wp-visitorflow'),
				__('OS Version', 'wp-visitorflow'),
				__('OS Platform', 'wp-visitorflow'),
				__('IP Address', 'wp-visitorflow'),
				__('Visit Step', 'wp-visitorflow'),
				__('Post/Page ID', 'wp-visitorflow'),
				__('Post/Page Title', 'wp-visitorflow')
			)
		);

		$table_data = array();
		foreach ($results as $res) {
			$ip = $res->ip;
			if (! preg_match('/\./',  $ip) ) {
				$ip = __('encrypted', 'wp-visitorflow');
			}

			$entry = array(
				'datetime'		=> $res->datetime,
				'agent_name'    => $res->agent_name,
				'agent_version' => $res->agent_version,
				'agent_engine'  => $res->agent_engine,
				'os_name'  		=> $res->os_name,
				'os_version' 	=> $res->os_version,
				'os_platform'  	=> $res->os_platform,
				'ip'        	=> $ip,
				'step'        	=> ($res->step > 1 ? $res->step -1 : 'referrer'),
				'post_id'       => $res->post_id,
				'post_title'    => $res->post_title,
			);


			array_push($table_data, $entry);

			array_push($csv_table,
				array(
					$res->datetime,
					$res->agent_name,
					$res->agent_version,
					$res->agent_engine,
					$res->os_name,
					$res->os_version,
					$res->os_platform,
					$ip,
					($res->step > 1 ? $res->step -1 : 'referrer'),
					$res->post_id,
					$res->post_title
				)
			);
		}

		$export_filename  = 'VisitorFlow-DataExport-' . $sel_yearmonth . '_' . time() . '_' . substr( md5( rand() ), 0, 12);
		$export_filepath = $export_dir . $export_filename . '.csv';
		$export_fileurl = content_url( 'extensions/wp-visitorflow/export/' ) . $export_filename . '.csv';
		$file = fopen($export_filepath, "w");
			foreach ($csv_table as $line) {
				if (! fputcsv($file, $line) ) {
					echo '<br><div class="wpvf_warning">' . sprintf(__('Cannot write file %s.', 'wp-visitorflow'), $file) . '<br>' . __('Please check folder permissions.', 'wp-visitorflow') . "</div><br>\n";
					return;
				}
			}
		fclose($file);

		// create ziparchive:
		$zip = new ZipArchive();
		$zip_filepath = $export_dir . $export_filename . '.zip';
		$zip_fileurl = content_url( 'extensions/wp-visitorflow/export/' ) . $export_filename . '.zip';
		if ($zip->open($zip_filepath, ZipArchive::CREATE) == true) {
			$zip->addfile($export_filepath, $export_filename);
			$zip->close();
		}


	} // if ($sel_yearmonth)

?>
	<div class="wpvf-background">

		<h3><?php _e('Data Export', 'wp-visitorflow'); ?></h3>

		<br />
		<?php echo  __('Data export for the recorded page view statistics as a CSV raw data table.', 'wp-visitorflow'); ?><br />
		<br />
		<?php echo  __('Select year and month for data export:', 'wp-visitorflow'); ?>
		<form method="POST">
			<input type="month" name="yearmonth" value="<?php echo $sel_yearmonth ? $sel_yearmonth : date("Y-m", time()); ?>">
			<button type="submit" name="todo" value="selectmonth"><?php echo  __('Create data table', 'wp-visitorflow'); ?></button>
		</form>
		(<?php echo  __('Format "YYYY-MM" for older browsers', 'wp-visitorflow'); ?>)<br />
		<br />
	</div>

<?php
	if ($zip_fileurl) {
?>
		<br />
		<h4><?php echo  __('Result', 'wp-visitorflow'); ?>:</h4>
		<a href="<?php echo $zip_fileurl; ?>"><?php echo  __('Download ZIP File', 'wp-visitorflow'); ?></a>
		(<?php echo number_format_i18n( filesize($zip_filepath)/1024 ); ?> kB)<br />
<?php
	}
	if ($export_fileurl) {
?>
		<br />
		<a href="<?php echo $export_fileurl; ?>"><?php echo __('Download CSV File', 'wp-visitorflow'); ?></a>
		(<?php echo number_format_i18n( filesize($export_filepath)/1024 ); ?> kB)<br />
<?php
	}

	// Draw table with all visitors in the selected timeframe
	// include_once dirname( __FILE__ ) . '/../../includes/classes/wp_visitorflow-table.class.php';

	// $columns = array('datetime'      => __('Date/Time', 'wp-visitorflow'),
						// 'agent_name'    => __('Agent', 'wp-visitorflow'),
						// 'agent_version' => __('Agent Version', 'wp-visitorflow'),
						// 'agent_engine'  => __('Agent Engine', 'wp-visitorflow'),
						// 'os_name'  	 => __('OS', 'wp-visitorflow'),
						// 'os_version'  	 => __('OS Version', 'wp-visitorflow'),
						// 'os_platform'   => __('OS Platform', 'wp-visitorflow'),
						// 'ip'        	 => __('IP Address', 'wp-visitorflow'),
						// 'step'        	 => __('Visit Step', 'wp-visitorflow'),
						// 'post_id'     	 => __('Post/Page ID', 'wp-visitorflow'),
						// 'post_title'  	 => __('Post/Page Title', 'wp-visitorflow'),

						// );
	// $sortable_columns = array( 'datetime' => array('datetime', false),
								// 'count' => array('count', false),
								// );

	// $myTable = new Visitor_Table( $columns, $sortable_columns, $table_data);
	// $myTable->prepare_items();

	// $myTable->display();
