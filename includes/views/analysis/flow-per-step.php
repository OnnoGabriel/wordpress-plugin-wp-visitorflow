<?php


/*******************************************************************************
 * GET USER SETTINGS AND USER MENU INTERACTION
 *******************************************************************************/

// Show all or limited amounts of nodes?
$max_nodes  = self::$config->getUserSetting('flowchart_max_nodes');
if (array_key_exists('flowchart_max_nodes', $_GET)) {
	$max_nodes = htmlspecialchars( stripslashes( $_GET['flowchart_max_nodes'] ) );
	if ($max_nodes != 999 && $max_nodes != 2 && $max_nodes != 10  && $max_nodes != 20  && $max_nodes != 30
			&& $max_nodes != 40  && $max_nodes != 50) {
		$max_nodes = 10;
	}
	self::$config->setUserSetting('flowchart_max_nodes', $max_nodes);
}

// Filter first step?
$filter_data = self::$config->getUserSetting('flowchart_filter_data');
if (array_key_exists('filter_data', $_GET)) {
	$filter_data = $_GET['filter_data'] == 'on' ? 1 : 0;
	self::$config->setUserSetting('flowchart_filter_data', $filter_data);
}
$filter_data_selected = array();
if ($filter_data) {
	if (array_key_exists('filter_data_selected', $_POST)) {
		$steps = $_POST['filter_data_selected'];
		foreach ($steps as $name) {
			array_push($filter_data_selected, htmlspecialchars( stripslashes( $name ) ) );
		}
		self::$config->setUserSetting('flowchart_filter_data_selected', $filter_data_selected);
	}
	$filter_data_selected = self::$config->getUserSetting('flowchart_filter_data_selected');
}

$max_distance_between_steps = 1150;
$distance_between_steps  = self::$config->getUserSetting('flowchart_distance_between_steps');
if (array_key_exists('dimension', $_GET)) {
	$distance_between_steps = htmlspecialchars( stripslashes( $_GET['dimension'] ) );
	if ($distance_between_steps < 150) {
		$distance_between_steps =  150;
	}
	if ($distance_between_steps > $max_distance_between_steps) {
		$distance_between_steps =  $max_distance_between_steps;
	}
	self::$config->setUserSetting('flowchart_distance_between_steps', $distance_between_steps);
}

// Get selected minimum and maxmimum flow steps
$start_step  = self::$config->getUserSetting('flowchart_start_step');

$min_step = 0;
$max_step  = self::$config->getUserSetting('flowchart_max_step');
if (array_key_exists('flowchart_save_settings', $_POST)) {
	$min_step = 0;
	if (array_key_exists('flowchart_start_step', $_POST)) {
		$start_step = htmlspecialchars( stripslashes( $_POST['flowchart_start_step'] ) );
		if ( ( $start_step < 0 || $start_step > 19 )
			&& $start_step != 'agent_name' && $start_step != 'os_name'
			&& $start_step != 'agent_version' && $start_step != 'os_version'
			&& $start_step != 'agent_os') {
			$start_step = 0;
		}
		if ($start_step != self::$config->getUserSetting('flowchart_start_step')) {
			self::$config->setUserSetting('flowchart_start_step', $start_step);
			self::$config->setUserSetting('flowchart_filter_data_selected', array() ); // clear filter (selected data)
			$filter_data_selected = array();
		}
	}
	self::$config->setUserSetting('flowchart_min_step', $min_step);

	$max_step = 0;
	if (array_key_exists('flowchart_max_step', $_POST)) {
		$max_step = htmlspecialchars( stripslashes( $_POST['flowchart_max_step'] ) );
		if ($max_step < 1 || $max_step > 20) {
			$max_step = 3;
		}
		if ($max_step <= $min_step) {
			$max_step = $min_step+1;
		}
		self::$config->setUserSetting('flowchart_max_step', $max_step);
	}
}
if ($start_step > 0) {
	$min_step = $start_step;
}


/*******************************************************************************
 * Page Menu
 *******************************************************************************/

$queries = array('page' => 'wpvf_mode_website',
					'tab' => 'flow',
					'max_nodes' => $max_nodes);

$timeframePage->setTimeframe($datetimestart, $datetimestop);
$timeframePage->setQueries($queries);

// Print Timeframe Menu
$timeframePage->printTimeframeMenu( self::$config->getSetting('flow-startdatetime') );

// Print Tab Menu
$timeframePage->printTabsMenu();


/*******************************************************************************
 * Calculate Sankey Nodes => Pages visited on each flow step
 *******************************************************************************/

// Initialize $flow_data => array containing parameters and results of the Sankey diagram
$flow_data = array();
$flow_data['nodes'] = array();					// Nodes array of the Sankey diagram
$flow_data['links'] = array();					// Links array of the Sankey diagram
$flow_data['datetimestart'] = $datetimestart;	// minimum datetime for the data selection
$flow_data['datetimestop'] = $datetimestop;		// maximum datetime for the data selection

$node_id = array();								// Node ID array by node name (page title)
$node_color = array();							// Node color array by node name (page title)
$color_index = 0;								// Node color index used in sankey diagram (0 .. 31 different colos)
$db_max_step = 0;								// Maximum step found in flow table
$nodes_count_max = 0;   						// Maximum nodes per step (needed to calculate the chart height. The more nodes, the heigher the chart)

$min_post_id = 0;
if (! self::$config->getSetting('exclude_404') ) { $min_post_id = -1; }

// Filter data base?
$filter_data_available = array();
$sql_where_filter = '';
if ($filter_data) {

	$name_field = '';
	if (is_numeric($start_step)) {
		$name_field = 'pt.title';
	}
	else {
		switch ($start_step) {
			case 'agent_name':
				$name_field = 'vt.agent_name';
				break;
			case 'agent_version':
				$name_field = "CONCAT(vt.agent_name,' ',vt.agent_version)";
				break;
			case 'os_name':
				$name_field = 'vt.os_name';
				break;
			case 'os_version':
				$name_field = "CONCAT(vt.os_name,' ',vt.os_version)";
				break;
			case 'agent_os':
				$name_field = "CONCAT(vt.os_name,'/',vt.agent_name)";
				break;
		}
	}

	$sql = $db->prepare(  "SELECT DISTINCT $name_field AS name,
									pt.id AS page_id
								FROM $flow_table as ft
								JOIN $pages_table as pt
								ON pt.id=ft.f_page_id AND pt.f_post_id>='%d'
								JOIN $visits_table as vt
								ON vt.id=ft.f_visit_id
							WHERE ft.datetime>='%s' AND ft.datetime<=date_add('%s', interval 1 day)
								AND ft.step='1';",
							$min_post_id, $datetimestart, $datetimestop
						);
	$results =  $db->get_results( $sql );
	foreach ($results as $res) {
		$key = $res->page_id;
		if (! is_numeric($start_step)) {
			$key = $res->name;
		}
		if ($key && ! array_key_exists($key, $filter_data_available)) {
			$filter_data_available[$key] = $res->name;
		}
	}
	ksort($filter_data_available);

	foreach ($filter_data_selected as $filter_item) {
		$string = '';
		if (is_numeric($start_step)) {
		}
		else {
			switch ($start_step) {
				case 'agent_name':
					$string = "vt.agent_name='$filter_item'";;
					break;
				case 'agent_version':
					$string = "CONCAT(vt.agent_name,' ',vt.agent_version)='$filter_item'";;
					break;
				case 'os_name':
					$string = "vt.os_name='$filter_item'";;
					break;
				case 'os_version':
					$string = "CONCAT(vt.os_name,' ',vt.os_version)='$filter_item'";;
					break;
				case 'agent_os':
					$string = "CONCAT(vt.os_name,'/',vt.agent_name)='$filter_item'";;
					break;
			}
		}
		if ($string) {
			if ($sql_where_filter) { $sql_where_filter .= 'OR '; }
			$sql_where_filter .= $string . ' ';
		}
	}

	if ($sql_where_filter) {
		$sql_where_filter = 'AND (' . $sql_where_filter . ') ';
	}
}

// echo $sql_where_filter;

// Initialize node colums
// ------------------------
// We go step by step through the data (steps determined by user settings)
for ($step = $min_step; $step <= $max_step; $step++) {

	$page_title = array();						// Page title array by page id

	$sql = $db->prepare(  "SELECT pt.id AS page_id,
									pt.f_post_id AS post_id,
									ft.step AS step,
									ft.f_visit_id AS visit_id,
									title,
									COUNT(ft.id) AS hit_count
								FROM $flow_table as ft
								JOIN $pages_table as pt
								ON pt.id=ft.f_page_id AND pt.f_post_id>='%d'
								JOIN $visits_table as vt
								ON vt.id=ft.f_visit_id
							WHERE ft.datetime>='%s' AND ft.datetime<=date_add('%s', interval 1 day)
								AND ft.step='%d'
							$sql_where_filter
							GROUP BY pt.id;",
							$min_post_id, $datetimestart, $datetimestop, $step+1
						);

	$flow_data['special_start_step'][$step] = 0;

	// in case of first step (step=0): special selection for start step?
	if ($step == 0 && ! is_numeric($start_step)) {
		$flow_data['special_start_step'][$step] =  $start_step;
// echo "special_start_step: $step => ". $flow_data['special_start_step'][$step] ."<br />";
		// SQL-Modification in case of special selection for start step
		$select_visits = '';
		$index_field = '';
		switch ($flow_data['special_start_step'][$step]) {
			case 'agent_name':
				$select_visits = 'vt.agent_name AS agent_name,';
				$index_field = 'vt.agent_name';
				break;
			case 'agent_version':
				$select_visits = 'vt.agent_name AS agent_name, vt.agent_version AS agent_version, ';
				$index_field = "CONCAT(vt.agent_name,' ',vt.agent_version)";
				break;
			case 'os_name':
				$select_visits = 'vt.os_name AS os_name,';
				$index_field = 'vt.os_name';
				$groupby = 'vt.os_name';
				break;
			case 'os_version':
				$select_visits = 'vt.os_name AS os_name, vt.os_version AS os_version,';
				$index_field = "CONCAT(vt.os_name,' ',vt.os_version)";
				break;
			case 'agent_os':
				$select_visits = 'vt.agent_name AS agent_name, vt.os_name AS os_name,';
				$index_field = "CONCAT(vt.os_name,' ',vt.agent_name)";
				break;
		}

		$sql = $db->prepare(  "SELECT $select_visits
									$index_field as page_id,
									pt.f_post_id AS post_id,
									ft.step AS step,
									ft.f_visit_id AS visit_id,
									title,
									COUNT(ft.id) AS hit_count
								FROM $flow_table as ft
								JOIN $pages_table as pt
								ON pt.id=ft.f_page_id AND pt.f_post_id>='%d'
							JOIN $visits_table as vt
								ON vt.id=ft.f_visit_id
							WHERE ft.datetime>='%s' AND ft.datetime<=date_add('%s', interval 1 day)
								AND ft.step='%d'
							$sql_where_filter
							GROUP BY $index_field",
							$min_post_id, $datetimestart, $datetimestop, $step+1
						);

	}


	// Get data from flow table
	$results =  $db->get_results( $sql );

	// Get hits (page views) and page titles from db results
	$hits = array();	// number of page views array by page id
	$total_page_views = 0;

	foreach ($results as $res) {
// echo "$step $res->page_id<br />";
		if (! array_key_exists($res->page_id, $page_title)) {
			if ($flow_data['special_start_step'][$step]) {
				switch ($flow_data['special_start_step'][$step]) {
					case 'agent_name':
						$page_title[$res->page_id] = html_entity_decode($res->agent_name);
						break;
					case 'agent_version':
						$page_title[$res->page_id] = html_entity_decode($res->agent_name);
						if (html_entity_decode($res->agent_version)) {
							$page_title[$res->page_id] .= ' ' . html_entity_decode($res->agent_version);
						}
						break;
					case 'os_name':
						$page_title[$res->page_id] = html_entity_decode($res->os_name);
						break;
					case 'os_version':
						$page_title[$res->page_id] = html_entity_decode($res->os_name);
						if (html_entity_decode($res->os_version)) {
							$page_title[$res->page_id] .= ' ' . html_entity_decode($res->os_version);
						}
						break;
					case 'agent_os':
						$page_title[$res->page_id] = html_entity_decode($res->os_name) . '/' . html_entity_decode($res->agent_name);
						break;
				}
			}
			else {
				$page_title[$res->page_id] =  html_entity_decode($res->title);
				$page_title[$res->page_id] = preg_replace('/\\\\\'/', "", $page_title[$res->page_id]);
				if ($res->post_id == -1) {
					$page_title[$res->page_id] =  '404 error: ' . $page_title[$res->page_id];
				}
			}
		}

		$hits[$res->page_id] = $res->hit_count;

		$total_page_views += $res->hit_count;

		if ($db_max_step < $res->step) {
			$db_max_step = $res->step;
		}
	}

	$flow_data[$step]['page_views'] = $total_page_views;

	// Create an array with all found hit values
	$hit_values = array();
	foreach ($hits as $page_id => $hit_count) {
		if (! in_array($hit_count, $hit_values)) {
			array_push($hit_values, $hit_count);
		}
	}

	$nodes_count = count($hits);				// number of different nodes
	$flow_data['others_node_id'][$step] = -1;	// "Other pages" node
	$flow_data['threshold'][$step] = 0;			// Filter threshold (0 = no filter)

	// If output should be filtered by setting max_nodes to a certain value
	// find threshold for filtering
	if ($max_nodes < 999) {
		sort($hit_values);
		$value_index = 0;
		while($nodes_count >= $max_nodes && $value_index < count($hit_values)) {
			$flow_data['threshold'][$step] = $hit_values[$value_index];
			$nodes_count = 0;
			foreach ($hits as $page_id => $hit_count) {
				if ($hit_count >= $flow_data['threshold'][$step]) {
					$nodes_count++;
				}
			}
			$value_index++;
		}
	}

	if ($nodes_count_max < $nodes_count) { $nodes_count_max = $nodes_count; }

	$node_id = array(); // node id array by node name

	// Create nodes
	foreach ($hits as $page_id => $hit_count) {
		$node_label = $page_title[$page_id];
		$node_name = "$step-$page_id";
// echo "node: $step $page_id	$page_title[$page_id]<br />";
		if ($flow_data['special_start_step'][$step]) {
			$node_name = "$step-".$node_label;
		}

		if ($hit_count >= $flow_data['threshold'][$step]) {
			if (! array_key_exists($node_name, $node_id) ) {
				if (! array_key_exists($page_id, $node_color) ) {
					$node_color[$page_id] = $color_index;
					$color_index++;
					if ($color_index > 31) { $color_index = 0; }
				}
				$node = array("name"  => $node_name,
								"label" => $node_label,
								// "title" => "$node_label ($hit_count page views at step $step)",
"title" => "$node_label / $node_name ($hit_count page views at step $step)",
								"color" => $node_color[$page_id] );
				if (! $flow_data['special_start_step'][$step]) {
					$node['url'] = '?page=wpvf_mode_singlepage&select_page_id=' . $page_id;
				}
				array_push($flow_data['nodes'], $node);
				$node_id[$node_name] = count($flow_data['nodes']) - 1;
			}
			$flow_data['node_id'][$step][$page_id] = $node_id[$node_name];
		}
		else {
			$node_label = __('Other pages', 'wp-visitorflow');
			if ($flow_data['special_start_step'][$step]) {
				switch ($flow_data['special_start_step'][$step]) {
					case 'agent_name':
						$node_label = __('Other User Agents', 'wp-visitorflow');
						break;
					case 'agent_version':
						$node_label = __('Other User Agents/Versions', 'wp-visitorflow');
						break;
					case 'os_name':
						$node_label = __('Other Operation Systems', 'wp-visitorflow');
						break;
					case 'os_version':
						$node_label = __('Other Operation Systems/Versions', 'wp-visitorflow');
						break;
					case 'agent_os':
						$node_label = __('Other Operation Systems/User Agents', 'wp-visitorflow');
						break;
				}
			}
			$node_name = "$step-others";
			if (! array_key_exists($node_name, $node_id) ) {
				if (! array_key_exists(0, $node_color) ) {
					$node_color[0] = $color_index;
					$color_index++;
					if ($color_index > 31) { $color_index = 0; }
				}
				$node = array("name" => $node_name,
								"label" => $node_label,
								"title" => "Step $step: $node_label",
								"color" => $node_color[0]);
				array_push($flow_data['nodes'], $node);
				$node_id[$node_name] = count($flow_data['nodes']) - 1;
			}
			$flow_data['node_id'][$step][$page_id] = $node_id[$node_name];
			$flow_data['others_node_id'][$step] = $node_id[$node_name];
		}
	}
}
// echo var_dump($flow_data);

if ($max_step > $db_max_step-1) {
	$max_step = $db_max_step-1;
}

// Get Sankey Diagram links between nodes for each step
for ($step = $min_step+1; $step <= $max_step; $step++) {
	$flow_data = wpvf_getSankeyLinks($flow_data, $step, $sql_where_filter);
}

// Create $sankeydata array with combined nodes and linkes
$sankeydata = array("nodes" => $flow_data['nodes'], "links" => $flow_data['links']);



/*******************************************************************************
 * Sankey Diagram Menu
 *******************************************************************************/
?>
<table class="wpvfmenutable">
<tr>
	<form method="post">
	<input type="hidden" name="flowchart_save_settings">
	<td class="nohover">
		<label for="selectfirststep"><?php echo __('First step', 'wp-visitorflow'); ?>:</label>
		<select id="selectfirststep" name="flowchart_start_step">
<?php
		echo '<option value="agent_name"' . (is_string($start_step) && $start_step == 'agent_name' ? ' selected' : '') . '>User Agents</option>';
		echo '<option value="agent_version"' . (is_string($start_step) && $start_step == 'agent_version' ? ' selected' : '') . '>User Agent Versions</option>';
		echo '<option value="os_name"' . (is_string($start_step) && $start_step == 'os_name' ? ' selected' : '') . '>Operation Systems</option>';
		echo '<option value="os_version"' . (is_string($start_step) && $start_step == 'os_version' ? ' selected' : '') . '>Operation System Versions</option>';
		echo '<option value="agent_os"' . (is_string($start_step) && $start_step == 'agent_os' ? ' selected' : '') . '>Operation Systems/User Agents</option>';
		for ($step = 0; $step <= 19; $step++) {
			echo '<option value="'.$step.'"' . (is_numeric($start_step) && $start_step == $step ? ' selected' : '') . '>';
			echo $step == 0 ? 'Referrer' : "Step " . $step;
			echo '</option>';
		}
?>
		</select>
	</td>
	<td class="nohover">
		<label for="selectlaststep"><?php echo __('Last step', 'wp-visitorflow'); ?>:</label>
		<select id="selectlaststep" name="flowchart_max_step">
<?php
		for ($step = 1; $step <= 20; $step++) {
			echo '<option value="'.$step.'"' . ($max_step == $step ? ' selected' : '') . '>';
			echo $step == 0 ? 'Referrer' : "Step " . $step;
			echo '</option>';
		}
?>
		</select>
	</td>
	<td class="nohover">
		<input type="submit" name="submit" value="<?php echo __('Go', 'wp-visitorflow'); ?>" />
	</td>
	</form>

</tr>
<table>
<table class="wpvfmenutable">
<tr>
	<td class="nohover">
		<div class="wpvf-menu-div">
			<?php echo __('Filter data', 'wp-visitorflow'); ?>:
		</div>
		<a class="wpvf" href="?page=<?php echo $queries['page']; ?>&amp;filter_data=on" title="<?php echo __('on', 'wp-visitorflow'); ?>">
			<div class="wpvf-menu-div <?php echo $filter_data ? 'wpvf-menu-active' : ''; ?>">
				&nbsp;on&nbsp;
			</div>
		</a>
		<a class="wpvf" href="?page=<?php echo $queries['page']; ?>&amp;filter_data=off" title="<?php echo __('off', 'wp-visitorflow'); ?>">
			<div class="wpvf-menu-div <?php echo $filter_data ? '' : ' wpvf-menu-active'; ?>">
				&nbsp;off&nbsp;
			</div>
		</a>
	</td>
	<td class="nohover"><?php echo  __('Pages per step', 'wp-visitorflow'); ?>:</td>
	<td<?php echo $max_nodes == 999? ' class="active"' : ''; ?>>
		<a href="?page=<?php echo $queries['page']; ?>&amp;flowchart_max_nodes=999" title="<?php echo __('Show all pages', 'wp-visitorflow'); ?>">
			<img src="<?php echo  WP_VISITORFLOW_PLUGIN_URL . 'assets/images/tabicon_all.png'; ?>" align="left" width="32" height="32" alt="Show all pages">
		</a>
	</td>
	<td<?php echo $max_nodes == 50? ' class="active"' : ''; ?>>
		<a href="?page=<?php echo $queries['page']; ?>&amp;flowchart_max_nodes=50" title="<?php echo __('Show Top 50 only', 'wp-visitorflow'); ?>">
			<img src="<?php echo  WP_VISITORFLOW_PLUGIN_URL . 'assets/images/tabicon_top50.png'; ?>" align="left" width="32" height="32" alt="Show Top 50">
		</a>
	</td>
	<td<?php echo $max_nodes ==40? ' class="active"' : ''; ?>>
		<a href="?page=<?php echo $queries['page']; ?>&amp;flowchart_max_nodes=40" title="<?php echo __('Show Top 40 only', 'wp-visitorflow'); ?>">
			<img src="<?php echo  WP_VISITORFLOW_PLUGIN_URL . 'assets/images/tabicon_top40.png'; ?>" align="left" width="32" height="32" alt="Show Top 40">
		</a>
	</td>
	<td<?php echo $max_nodes == 30? ' class="active"' : ''; ?>>
		<a href="?page=<?php echo $queries['page']; ?>&amp;flowchart_max_nodes=30" title="<?php echo __('Show Top 30 only', 'wp-visitorflow'); ?>">
			<img src="<?php echo  WP_VISITORFLOW_PLUGIN_URL . 'assets/images/tabicon_top30.png'; ?>" align="left" width="32" height="32" alt="Show Top 30">
		</a>
	</td>
	<td<?php echo $max_nodes == 20? ' class="active"' : ''; ?>>
		<a href="?page=<?php echo $queries['page']; ?>&amp;flowchart_max_nodes=20" title="<?php echo __('Show Top 20 only', 'wp-visitorflow'); ?>">
			<img src="<?php echo  WP_VISITORFLOW_PLUGIN_URL . 'assets/images/tabicon_top20.png'; ?>" align="left" width="32" height="32" alt="Show Top 20">
		</a>
	</td>
	<td<?php echo $max_nodes == 10? ' class="active"' : ''; ?>>
		<a href="?page=<?php echo $queries['page']; ?>&amp;flowchart_max_nodes=10" title="<?php echo __('Show Top 10 only', 'wp-visitorflow'); ?>">
			<img src="<?php echo  WP_VISITORFLOW_PLUGIN_URL . 'assets/images/tabicon_top10.png'; ?>" align="left" width="32" height="32" alt="Show Top 10">
		</a>
	</td>
	<td>
		<a href="?page=<?php echo $queries['page']; ?>&amp;dimension=<?php echo $distance_between_steps < $max_distance_between_steps ? $distance_between_steps + 200 : $distance_between_steps; ?>" title="<?php echo __('Zoom in', 'wp-visitorflow'); ?>">
			<img src="<?php echo  WP_VISITORFLOW_PLUGIN_URL . 'assets/images/tabicon_zoom_in.png'; ?>" align="left" width="32" height="32" alt="Zoom in">
		</a>
	</td>
	<td>
		<a href="?page=<?php echo $queries['page']; ?>&amp;dimension=<?php echo $distance_between_steps > 0 ? $distance_between_steps - 200 : $distance_between_steps; ?>" title="<?php echo __('Zoom out', 'wp-visitorflow'); ?>">
			<img src="<?php echo  WP_VISITORFLOW_PLUGIN_URL . 'assets/images/tabicon_zoom_out.png'; ?>" align="left" width="32" height="32" alt="Zoom in">
		</a>
	</td>
</tr>
</table>

<div style="clear:both;"></div>
<?php
if ($filter_data) {
	$steps_in = array();
?>
	<table class="wpvfmenutable">
	<tr>
		<td class="nohover">
			<form method="post">
				<label for="filterlist"><?php echo __('Show only:', 'wp-visitorflow'); ?></label>
				<select id="filterlist" name="filter_data_selected[]" size="6" multiple>
<?php
	$step_to_be_filtered = 0;
	if ($start_step > 0) {
		$step_to_be_filtered = $start_step;
	}

	foreach ($filter_data_available as $key => $name) {
		if (! in_array($key, $steps_in)) {
			echo '<option value="' . $key . '"';
			if (count($filter_data_selected) == 0 || in_array($key, $filter_data_selected)) {
				echo ' selected';
			}
			echo  ">$name</option>\n";
			array_push($steps_in, $key);
		}
	}

?>					<input type="submit" name="submit" value="<?php echo __('Go', 'wp-visitorflow'); ?>" />
				</select>
			</form>
		</td>
	</tr>
	</table>
<?php
}
?>
<br />
<?php


/*******************************************************************************
 * Sankey Diagram Output
 *******************************************************************************/

// Set Sankey diagram dimensions
$node_width = 20;
$node_padding = 10;
$chart_width  = ($max_step - $min_step + 1) * $distance_between_steps;
$chart_height  = $nodes_count_max * 30;
if ($chart_height < 800) { $chart_height = 800; }

$warning = '';
if (count($flow_data['nodes']) == 0) {
?>
	<br />
	<div class="wpvf_warning"><?php echo  __('No data found in the selected timeframe.', 'wp-visitorflow'); ?></div><br />
<?php
}

?>
<div id="flowbox" style="height:<?php echo $chart_height; ?>px;">
	<div id="chart" style="width:<?php echo $chart_width; ?>px;height:<?php echo ($chart_height-40); ?>px;"> </div>
</div>

<script type="text/javascript">
jQuery(function($) {
	$(document).ready(function(){
		$('#flowbox').doubleScroll({
			scrollCss: {
				'overflow-x': 'auto',
				'overflow-y': 'hidden'
			},
			contentCss: {
				'overflow-x': 'auto',
				'overflow-y': 'hidden'
			},
			onlyIfScroll: true,
			resetOnWindowResize: true}
		);
	});
});
</script>

<script src="<?php echo  WP_VISITORFLOW_PLUGIN_URL . 'assets/js/d3.v2.min.js'; ?>"></script>
<script src="<?php echo  WP_VISITORFLOW_PLUGIN_URL . 'assets/js/sankey.js'; ?>"></script>
<script>

	var margin = {top: 10, right: <?php echo 3*$distance_between_steps/4; ?>, bottom: 40, left: 10},
		width = <?php echo $chart_width; ?> - margin.left - margin.right;
		height = <?php echo ($chart_height-30); ?> - margin.top - margin.bottom;

	var formatNumber0 = d3.format(",.0f"),
		formatCount = function(d) { if (formatNumber0(d) == 1) { return formatNumber0(d) + " click"; }
									else { return formatNumber0(d) + " clicks"; } },
		color = d3.scale.category20();

	var formatNumber1 = d3.format(",.1f"),
		formatPercent = function(d) { return formatNumber1(d) + " %"; },
		color = d3.scale.category20();

	var svg = d3.select("#chart").append("svg")
			.attr("width", width + margin.left + margin.right)
			.attr("height", height + margin.top + margin.bottom)
		.append("g")
			.attr("transform", "translate(" + margin.left + "," + margin.top + ")")

	var sankey = d3.sankey()
		.nodePadding(<?php echo $node_padding; ?>)
		.nodeWidth(<?php echo $node_width; ?> )
		.size([width-36, height]);

	var path = sankey.link();

	var datajson = '<?php echo json_encode($sankeydata); ?>';

	sankeydata = JSON.parse(datajson);

	sankey
		.nodes(sankeydata.nodes)
		.links(sankeydata.links)
		.layout(32);


	var link = svg.append("g").selectAll(".link")
		.data(sankeydata.links)
		.enter().append("path")
		.attr("class", function(d) { return d.class; })
		.attr("d", path)
		.style("stroke-width", function(d) { return Math.max(1, d.dy); })
		.sort(function(a, b) { return b.dy - a.dy; });

	link.append("title")
		.text(function(d) { return d.label1 + "\n" + d.label2 + "\n" + d.label3; });

	var node = svg.append("g").selectAll(".node")
		.data(sankeydata.nodes)

		node.enter().append("g")
			.attr("class", "node")
		.attr("transform", function(d) { return "translate(" + d.x + "," + d.y + ")"; })

		node.append("a")
			.attr("xlink:href", function (d) { return d.url; })
			.append("rect")
				.attr("height", function(d) { return d.dy; })
				.attr("width", function(d) { return sankey.nodeWidth(); })
				.style("fill", function(d) { return color(d.color); })
				.style("stroke", function(d) { return d3.rgb(d.color).darker(2); })
			.append("title")
				.text(function(d) { return d.title; });

		node.append("text")
			.attr("class", "node textsmall")
			.attr("x", 6 + sankey.nodeWidth())
			.attr("y", function(d) { return d.dy / 2; })
			.attr("dy", ".35em")
			.attr("text-anchor", "start")
			.attr("transform", null)
			.text(function(d) { return d.label; })

<?php
for ($step = $min_step; $step <= $max_step; $step++) {
	$label = $step;
	if ($step == 0) {
		switch ($start_step) {
			case 'agent_name':
				$label = __('User Agents', 'wp-visitorflow');
				break;
			case 'agent_version':
				$label = __('User Agent Versions', 'wp-visitorflow');
				break;
			case 'os_name':
				$label = __('Operation Systems', 'wp-visitorflow');
				break;
			case 'os_version':
				$label = __('Operation System Versions', 'wp-visitorflow');
				break;
			case 'agent_os':
				$label = __('Operation Systems/User Agents', 'wp-visitorflow');
				break;
		}
	}
?>
	var text =	svg.append('text').text('<?php echo $label; ?>')
					.attr('x', <?php echo ($step - $min_step) * ($distance_between_steps + $node_padding-3); ?>)
					.attr('y', <?php echo $chart_height-58; ?>)
					.attr('font-size', 20);

<?php
}
?>
	function dragmove(d) {
		d3.select(this).attr("transform", "translate(" + d.x + "," + (d.y = Math.max(0, Math.min(height - d.dy, d3.event.y))) + ")");
		sankey.relayout();
		link.attr("d", path);
	}
	// });
</script>
<br />
<?php


/*******************************************************************************
 * Table with Summary: Page Views per Step
 *******************************************************************************/

?>
<table class="wpvftable wpvftable-background">
<tr>
	<th class="wpvftable-title" colspan="2"><?php echo __('Summary', 'wp-visitorflow'); ?></th>
</tr>
<tr>
	<th><?php echo  __('Step on your Website', 'wp-visitorflow'); ?></th>
	<th><?php echo  __('Page Views', 'wp-visitorflow') . '<br />'; ?></th>
	<th><?php echo  __('Exit Rate', 'wp-visitorflow') . '<br />' . __(' (in %)', 'wp-visitorflow'); ?></th>
</tr>
<?php
for ($step = $min_step; $step <= $max_step; $step++) {
	if ($step > 0) {
		echo '<tr><td>' . ($step) . '</td><td class="right">';
		echo isset($flow_data[$step]['page_views']) ? $flow_data[$step]['page_views'] : '?';
		echo '</td><td class="right">';
		if ( isset($flow_data[$step]['page_views']) && isset($flow_data[$step+1]['page_views']) && $flow_data[$step]['page_views'] > 0) {
			$diff = 100 * ( $flow_data[$step]['page_views'] - $flow_data[$step+1]['page_views']) / $flow_data[$step]['page_views'];
			if ($diff < 0) { $diff = 0; }
			echo round($diff, 0) . ' %';
		}
		else { echo '...'; }
		echo '</td>';
		echo '</tr>';
	}
}
echo '</table>';

/*******************************************************************************
 * Get Sankey Diagram Links between $step and $step-1
 * Input: $flow_data array: includes Sankey diagram nodes and settings
 *        $step: the number of the right flow step (1 = first visited page etc.)
 * Output: $flow_data array: now also including Sankey diagram links
 *******************************************************************************/
function wpvf_getSankeyLinks($flow_data, $step, $sql_filter = '')
{
	$db = WP_VisitorFlow_Database::getDB();
	$flow_table = WP_VisitorFlow_Database::getTableName('flow');
	$pages_table = WP_VisitorFlow_Database::getTableName('pages');
	$visits_table = WP_VisitorFlow_Database::getTableName('visits');

	$min_post_id = 0;

	$wpvfConfig = WP_VisitorFlow_Config::getInstance();

	if (! $wpvfConfig->getSetting('exclude_404') ) { $min_post_id = -1; }

	$sql = $db->prepare("SELECT CONCAT(p1.title,'->',p2.title) AS slink,
								t1.f_page_id AS page_id1,
								t2.f_page_id AS page_id2,
								COUNT(t1.id) AS flow_sum,
								SUM(ABS(TIME_TO_SEC(TIMEDIFF(t2.datetime, t1.datetime)))) AS time_sum,
								STDDEV_SAMP(ABS(TIME_TO_SEC(TIMEDIFF(t2.datetime, t1.datetime)))) AS time_sigma,
								p1.internal AS internal1,
								p2.internal AS internal2,
								p1.title AS title1,
								p2.title AS title2
							FROM $flow_table AS t2
							JOIN $flow_table AS t1
							ON t1.f_visit_id=t2.f_visit_id AND t1.step='%d'
							JOIN $pages_table as p2
							ON p2.id=t2.f_page_id AND p2.f_post_id>='%d'
							JOIN $pages_table as p1
							ON p1.id=t1.f_page_id AND p1.f_post_id>='%d'
							JOIN $visits_table as vt
							ON vt.id=t1.f_visit_id
						WHERE t2.step='%d'
							AND t2.datetime>='%s' AND t2.datetime<=date_add('%s', interval 1 day)
						$sql_filter
						GROUP BY slink;",
						$step, $min_post_id, $min_post_id, $step+1, $flow_data['datetimestart'], $flow_data['datetimestop']);

	if ($flow_data['special_start_step'][$step-1]) {

		switch ($flow_data['special_start_step'][$step-1]) {
			case 'agent_name':
				$index_field = 'vt.agent_name';
				break;
			case 'agent_version':
				$index_field = "CONCAT(vt.agent_name,' ',vt.agent_version)";
				break;
			case 'os_name':
				$index_field = 'vt.os_name';
				break;
			case 'os_version':
				$index_field = "CONCAT(vt.os_name,' ',vt.os_version)";
				break;
			case 'agent_os':
				$index_field = "CONCAT(vt.os_name,' ',vt.agent_name)";
		}


		$sql = $db->prepare("SELECT CONCAT($index_field,'->',p2.title) AS slink,
									$index_field AS page_id1,
									t2.f_page_id AS page_id2,
									COUNT(t1.id) AS flow_sum,
									SUM(ABS(TIME_TO_SEC(TIMEDIFF(t2.datetime, t1.datetime)))) AS time_sum,
									STDDEV_SAMP(ABS(TIME_TO_SEC(TIMEDIFF(t2.datetime, t1.datetime)))) AS time_sigma,
									p1.internal AS internal1,
									p2.internal AS internal2,
									p1.title AS title1,
									p2.title AS title2
								FROM $flow_table AS t2
								JOIN $flow_table AS t1
								ON t1.f_visit_id=t2.f_visit_id AND t1.step='%d'
								JOIN $pages_table as p2
								ON p2.id=t2.f_page_id AND p2.f_post_id>='%d'
								JOIN $pages_table as p1
								ON p1.id=t1.f_page_id AND p1.f_post_id>='%d'
								JOIN $visits_table as vt
								ON vt.id=t1.f_visit_id
								WHERE t2.step='%d'
								AND t2.datetime>='%s' AND t2.datetime<=date_add('%s', interval 1 day)
								$sql_filter
								GROUP BY slink;",
								$step, $min_post_id, $min_post_id, $step+1, $flow_data['datetimestart'], $flow_data['datetimestop']);

	}
// echo $flow_data['special_start_step'][$step-1] . ": $step => " . $sql .'<br /><br />';
	$results = $db->get_results( $sql );

	$total_flow_sum = 0;
	$total_nodes_count = 0;
	foreach ($results as $res) {
		if ($res->title1 != 'self' && $res->title2 != 'self') {
			$total_flow_sum += $res->flow_sum;
			$total_nodes_count++;
		}
	}

	$link_id = array();

	foreach ($results as $res) {

		$time_mean  = round($res->time_sum/$res->flow_sum, 0);
		$time_sigma = 0;
		if ($res->time_sigma) {
			$time_sigma = round($res->time_sigma, 0);
		}

		if (isset($flow_data['node_id'][$step-1][$res->page_id1]) && isset($flow_data['node_id'][$step][$res->page_id2])) { // Bugfix for rare occasions, where $flow_data for these page ids does not exist

			$node_id_1 = $flow_data['node_id'][$step-1][$res->page_id1];
			$node_id_2 = $flow_data['node_id'][$step][$res->page_id2];

			if (! isset($link_id[$node_id_1][$node_id_2])) {

				$label1 = 'Step ' . ($step-1) . ': ';
				if ($step == 1) {
					$label1 = 'Referrer: ';
					if ($flow_data['special_start_step'][$step-1]) {
						$label1 = '';
					}
				}
				$label1 .= $flow_data['nodes'][$node_id_1]['label'];
				$label1 .= ' → Step ' . ($step) . ': ' . $flow_data['nodes'][$node_id_2]['label'];

				$label2 = $res->flow_sum . ' ' . ($res->flow_sum == 1 ? __('click', 'wp-visitorflow') : __('clicks', 'wp-visitorflow'));
				if ($total_flow_sum > 0) {
					$label2 .= ' (= ' . round(100*$res->flow_sum/$total_flow_sum,1) . '% ' . __('of all clicks at this step', 'wp-visitorflow') . ')';
				}

				$label3 = $time_mean . ' ± '. $time_sigma . ' ' . __('seconds', 'wp-visitorflow') . ' ' . __('page view duration', 'wp-visitorflow');
				$link_class = 'link_intern';
				if ($node_id_1 == $flow_data['others_node_id'][$step-1]) {
					$link_class = 'link_other';
					$label3 = '';
				}
				elseif ($res->internal1 == 0) {
					$link_class = 'link_extern';
					$label3 = __('Coming from an external website.', 'wp-visitorflow');
					if ($res->title1 == 'unknown') {
						$label3 = __('Coming from an unknown external source, e.g. a bookmark or direct enter.', 'wp-visitorflow');
					}
				}
				if ($flow_data['special_start_step'][$step-1]) {
					$link_class = 'link_extern';
					$label3 = '';
				}

				$link = array("source" => $node_id_1,
								"target" => $node_id_2,
								"label1"  => $label1,
								"label2"  => $label2,
								"label3"  => $label3,
								"value"  => $res->flow_sum,
								"class" => $link_class
								);
				array_push($flow_data['links'], $link);
				$link_id[$node_id_1][$node_id_2] = count($flow_data['links']) - 1;
			}
			else {
				$id = $link_id[$node_id_1][$node_id_2];
				$flow_data['links'][$id]['value'] += $res->flow_sum;
				$label2 = $flow_data['links'][$id]['value'] . ($flow_data['links'][$id]['value'] == 1 ? ' click' : ' clicks') .' (= '
						. round(100*$flow_data['links'][$id]['value']/$total_flow_sum,1) . '% ' . __('of all clicks at this step', 'wp-visitorflow') . ')';
				$flow_data['links'][$id]['label2'] = $label2;
				$flow_data['links'][$id]['label3'] = '';
			}

		}
	}

	$flow_data[$step]['total_flow_sum'] = $total_flow_sum;
	$flow_data[$step]['total_nodes_count'] = $total_nodes_count;

	return $flow_data;
}

