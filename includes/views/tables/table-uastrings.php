<?php
	if (! is_admin() || ! current_user_can( self::$config->getSetting('read_access_capability') ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	$db = WP_VisitorFlow_Database::getDB();
	$meta_table = WP_VisitorFlow_Database::getTableName('meta');
	$flow_table = WP_VisitorFlow_Database::getTableName('flow');


	$results = $db->get_results(
		"SELECT $meta_table.value AS ua_string,
				MAX($flow_table.datetime) AS datetime,
				$meta_table.label AS visit_id,
				COUNT($flow_table.id) AS page_hits
			FROM $meta_table
			JOIN $flow_table
				ON $flow_table.f_visit_id=$meta_table.label
			WHERE type='useragent'
		GROUP BY $flow_table.f_visit_id
		ORDER BY $meta_table.id DESC LIMIT 50;"
	);

	if ( ! self::$config->getSetting('store_useragent') ) {
?>
		<p>
			<em><?php _e('Storage of User-Agent Strings has been disabled in settings.', 'wp-visitorflow'); ?></em>
		</p>
<?php
	}

?>
	<table class="wpvftable">
	<tr>
		<th><?php echo __('HTTP User-Agent String', 'wp-visitorflow'); ?></th>
		<th><?php echo __('Page Views', 'wp-visitorflow'); ?></th>
		<th><?php echo __('Last Visit', 'wp-visitorflow'); ?></th>
	</tr>
<?php
	$count = 1;
	foreach ($results as $result) {

		if ($count % 2 != 0) {
			echo '<tr>';
		}
		else {
			echo '<tr class="darker">';
		}
		$count++;

		$url = '?page=wpvf_mode_website&amp;tab=visitors&amp;visit_id=' . $result->visit_id;
		$agentlink = '<a class="wpvf" href="' . $url . '">'. $result->ua_string . '</a>';
		echo '<td>' . $agentlink . ' </td>';
		$nice_datetime = sprintf(
			__('%s ago', 'wp-visitorflow'),
			WP_VisitorFlow_Admin::getNiceTimeDifference(
				$result->datetime,
				self::$config->getDatetime()
			)
		);
		$nice_datetime = str_replace(' ' ,'&nbsp;',$nice_datetime);
		echo '<td class="right">' . ($result->page_hits - 1) . '</td>';
		echo '<td>' . $nice_datetime . '</td>';
		echo '</tr>';
	}

	if (! is_array($results) || count($results) == 0) {
		echo '<tr><td colspan="3"><em>' . __('Table still empty.', 'wp-visitorflow') . '</em></td></tr>';
	}
?>
	</table>
