<?php
	$sql_where = "WHERE $flow_table.datetime>='%s' AND $flow_table.datetime<=date_add('%s', interval 1 day)";
	$sql_prepare_array = array($datetimestart, $datetimestop);
	if (array_key_exists('visit_id', $_GET)) {
		$sql_where = "WHERE $flow_table.f_visit_id='%s'";
		$selected_visit_id = htmlspecialchars( stripslashes( $_GET['visit_id'] ) );
		$sql_prepare_array = array($selected_visit_id);

		$ua_string = $db->get_var(
			$db->prepare(
				"SELECT $meta_table.value
				FROM $meta_table
				WHERE type='useragent'
				AND label='%s';",
			$selected_visit_id
			)
		);

		echo '<h3>' . __('Flow of a Single Visitor', 'wp-visitorflow') . '</h3>';
		echo '<p><strong>HTTP User Agent String:</strong> ' . $ua_string . '</p>';
	}

	$results = $db->get_results(
		$db->prepare(
			"SELECT f_page_id, f_visit_id, step, datetime,
					$pages_table.id AS page_id,
					$pages_table.internal AS internal,
					$pages_table.title AS title,
					$pages_table.f_post_id AS post_id,
					$visits_table.agent_name AS agent_name,
					$visits_table.agent_version AS agent_version,
					$visits_table.os_name AS os_name,
					$visits_table.os_version AS os_version,
					$visits_table.ip AS ip
				FROM $flow_table
				JOIN $pages_table
				ON $pages_table.id=$flow_table.f_page_id
				JOIN $visits_table
				ON $visits_table.id=$flow_table.f_visit_id
				$sql_where
				ORDER BY $flow_table.f_visit_id DESC, $flow_table.step DESC LIMIT 1000;
				",
			$sql_prepare_array
		)
	);

?>
	<table class="wpvftable">
	<tr>
		<th>Agent</th>
		<th>Agent Version</th>
		<th>OS</th>
		<th>OS Version</th>
		<th>IP</th>
		<th>Step</th>
		<th colspan="2">Page</th>
		<th>Date/Time</th>

	</tr>
<?php
	$old_visit_id = 0;
	$col = 1;
	foreach ($results as $res) {
		if ($old_visit_id != $res->f_visit_id) {
			$old_visit_id = $res->f_visit_id;
			$col++;
			if ($col > 1) { $col = 0; }
		}
		if ($col == 0) {
			echo '<tr>';
		}
		else {
			echo '<tr class="darker">';
		}
		echo '<td>' . $res->agent_name . '</td>';
		echo '<td>' . $res->agent_version . '</td>';
		echo '<td>' . $res->os_name . '</td>';
		echo '<td>' . $res->os_version . '</td>';
		$ip = $res->ip;
		if (! preg_match('/\./',  $ip) ) {
			$ip = __('encrypted', 'wp-visitorflow');
		}
		echo '<td>' . $ip . '</td>';
		echo '<td>' . $res->step . '</td>';

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

		if ($res->step > 1 || ($res->page_id > 2)) {
			echo '<td><a class="wpvf wpvfpage" href="'. $pagelink . '">' . $title . '</a></td>';
		}
		else {
			echo '<td>' . $title . '</td>';
		}
		echo '<td><a class="wpvf wpvfflow" href="?page=wpvf_mode_singlepage&amp;select_page_id=' .  $res->page_id . '">' . __('Flow', 'wp-visitorflow') . '</a>';
		echo '</td>';
		echo '<td>' . $res->datetime . '</td>';
		echo '</tr>';
	}
?>
	</table>
<?php
