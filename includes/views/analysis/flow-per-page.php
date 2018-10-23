<?php
/*******************************************************************************
 * GET USER SETTINGS AND USER MENU INTERACTION
 *******************************************************************************/

// Show all or limited amounts of nodes?
$max_nodes  = self::$config->getUserSetting('flowchart_max_nodes');
if (array_key_exists('max_nodes', $_GET)) {
	$max_nodes = htmlspecialchars( stripslashes( $_GET['max_nodes'] ) );
	if ($max_nodes != 999 && $max_nodes != 2 && $max_nodes != 10  && $max_nodes != 20  && $max_nodes != 30
		&& $max_nodes != 40  && $max_nodes != 50) {
		$max_nodes = 10;
	}
	self::$config->setUserSetting('flowchart_max_nodes', $max_nodes);
}
$max_chart_dimension = 6;
$chart_dimensions = array(array('width' => 300, 'height' => 200),
						array('width' => 600, 'height' => 400),
						array('width' => 900, 'height' => 600),
						array('width' => 1200, 'height' => 800),
						array('width' => 1500, 'height' => 1000),
						array('width' => 1800, 'height' => 1200),
						array('width' => 2100, 'height' => 1400));

// Get the chart dimensions:
$current_dimension  = self::$config->getUserSetting('flowchart_dimension');
if (array_key_exists('dimension', $_GET)) {
	$current_dimension = htmlspecialchars( stripslashes( $_GET['dimension'] ) );
	if ($current_dimension < 0 || $current_dimension > $max_chart_dimension) { $current_dimension = 3; }
}
self::$config->setUserSetting('flowchart_dimension', $current_dimension);


/*******************************************************************************
 * Page Menu
 *******************************************************************************/

$queries = array('page' => 'wpvf_mode_singlepage',
				'tab' => 'flow',
				'select_page_id' => $selected_page_id,
				'max_nodes' => $max_nodes);

$timeframePage->setTimeframe($datetimestart, $datetimestop);
$timeframePage->setQueries($queries);

// Print Timeframe Menu
$timeframePage->printTimeframeMenu( self::$config->getSetting('flow-startdatetime') );

// Print Tab Menu
$timeframePage->printTabsMenu();


/*******************************************************************************
 * Draw Sankey Diagramm
 *******************************************************************************/

// Initialize $flow_data => array containing parameters and results of the Sankey diagram
$flow_data = array();
$flow_data['nodes'] = array();					// Nodes array of the Sankey diagram
$flow_data['node_id'] = array();				// Node-ID array used to identify nodes by page_id
$flow_data['links'] = array();					// Links array of the Sankey diagram
$flow_data['datetimestart'] = $datetimestart;	// minimum datetime for the data selection
$flow_data['datetimestop'] = $datetimestop;		// maximum datetime for the data selection

// Initialize first and central sankey node:
$node = array("name" => $selected_title,
			"url" => '?page=wpvf_mode_singlepage&amp;select_page_id=' . $selected_page_id);
array_push($flow_data['nodes'], $node);

// Get Nodes and Links for visitors incoming to central Sanky node (= selected page)
$flow_data = wpvf_getNewNodesAndLinks($flow_data, -1);
// Get Nodes and Links for visitors outgoing from central Sanky node (= selected page)
$flow_data = wpvf_getNewNodesAndLinks($flow_data, +1);

// Prepare warning messages
$warning = '';
if (! $flow_data[-1]['total_flow_sum']) {
	$warning = __('No incoming flow to this page in the selected time interval.', 'wp-visitorflow');
}
if (! $flow_data[+1]['total_flow_sum']) {
	if ($warning) { $warning .= "<br />\n"; }
	$warning .= __('No outgoing flow from this page in the selected time interval.', 'wp-visitorflow');
}

// Prepare info messages
$message = '';
if ($flow_data[-1]['filtered_page_count'] > 0) {
	$message = sprintf( __('%s incoming pages with threshold below %s clicks aggregated to "Other pages".', 'wp-visitorflow'),
							$flow_data[-1]['filtered_page_count'], $flow_data[-1]['flow_threshold']);
}
if ($flow_data[+1]['filtered_page_count'] > 0) {
	if ($message) { $message .= "<br />\n"; }
	$message .= sprintf( __('%s outgoing pages with threshold below %s clicks aggregated to "Other pages".', 'wp-visitorflow'),
							$flow_data[+1]['filtered_page_count'], $flow_data[+1]['flow_threshold']);
}
if ($message) {
	$message .= '<br /><a class="wpvf" href="?page=wpvf_mode_singlepage&amp;select_page_id=' . $selected_page_id . '&amp;max_nodes=999">';
	$message .= '&rarr;' . __('Show all pages', 'wp-visitorflow') . '</a>';
}


/*******************************************************************************
 * Sankey Diagram Menu
 *******************************************************************************/
?>
<table class="wpvfmenutable">
<tr>
<?php
// Show part of the menu only if sankey diagramm can be filtered:
if ($flow_data[-1]['total_nodes_count'] > 10 || $flow_data[+1]['total_nodes_count'] > 10) {
?>
	<td<?php echo $max_nodes == 999? ' class="active"' : ''; ?>>
		<a href="?page=wpvf_mode_singlepage&amp;select_page_id=<?php echo  $selected_page_id; ?>&amp;max_nodes=999" title="<?php echo __('Show all pages', 'wp-visitorflow'); ?>">
			<img src="<?php echo WP_VISITORFLOW_PLUGIN_URL . 'assets/images/tabicon_all.png'; ?>" align="left" width="32" height="32" alt="Show all pages">
		</a>
	</td>
	<td<?php echo $max_nodes == 50? ' class="active"' : ''; ?>>
		<a href="?page=wpvf_mode_singlepage&amp;select_page_id=<?php echo  $selected_page_id; ?>&amp;max_nodes=50" title="<?php echo __('Show Top 50 only', 'wp-visitorflow'); ?>">
			<img src="<?php echo WP_VISITORFLOW_PLUGIN_URL . 'assets/images/tabicon_top50.png'; ?>" align="left" width="32" height="32" alt="Show Top 50">
		</a>
	</td>
	<td<?php echo $max_nodes ==40? ' class="active"' : ''; ?>>
		<a href="?page=wpvf_mode_singlepage&amp;select_page_id=<?php echo  $selected_page_id; ?>&amp;max_nodes=40" title="<?php echo __('Show Top 40 only', 'wp-visitorflow'); ?>">
			<img src="<?php echo WP_VISITORFLOW_PLUGIN_URL . 'assets/images/tabicon_top40.png'; ?>" align="left" width="32" height="32" alt="Show Top 40">
		</a>
	</td>
	<td<?php echo $max_nodes == 30? ' class="active"' : ''; ?>>
		<a href="?page=wpvf_mode_singlepage&amp;select_page_id=<?php echo  $selected_page_id; ?>&amp;max_nodes=30" title="<?php echo __('Show Top 30 only', 'wp-visitorflow'); ?>">
			<img src="<?php echo WP_VISITORFLOW_PLUGIN_URL . 'assets/images/tabicon_top30.png'; ?>" align="left" width="32" height="32" alt="Show Top 30">
		</a>
	</td>
	<td<?php echo $max_nodes == 20? ' class="active"' : ''; ?>>
		<a href="?page=wpvf_mode_singlepage&amp;select_page_id=<?php echo  $selected_page_id; ?>&amp;max_nodes=20" title="<?php echo __('Show Top 20 only', 'wp-visitorflow'); ?>">
			<img src="<?php echo WP_VISITORFLOW_PLUGIN_URL . 'assets/images/tabicon_top20.png'; ?>" align="left" width="32" height="32" alt="Show Top 20">
		</a>
	</td>
	<td<?php echo $max_nodes == 10? ' class="active"' : ''; ?>>
		<a href="?page=wpvf_mode_singlepage&amp;select_page_id=<?php echo  $selected_page_id; ?>&amp;max_nodes=10" title="<?php echo __('Show Top 10 only', 'wp-visitorflow'); ?>">
			<img src="<?php echo WP_VISITORFLOW_PLUGIN_URL . 'assets/images/tabicon_top10.png'; ?>" align="left" width="32" height="32" alt="Show Top 10">
		</a>
	</td>
<?php
}
else {
?>
	<td class="inactive">
		<img src="<?php echo WP_VISITORFLOW_PLUGIN_URL . 'assets/images/tabicon_all.png'; ?>" align="left" width="32" height="32" alt="Show all pages">
	</td>
	<td class="inactive">
		<img src="<?php echo WP_VISITORFLOW_PLUGIN_URL . 'assets/images/tabicon_top50.png'; ?>" align="left" width="32" height="32" alt="Show Top 50">
	</td>
	<td class="inactive">
		<img src="<?php echo WP_VISITORFLOW_PLUGIN_URL . 'assets/images/tabicon_top40.png'; ?>" align="left" width="32" height="32" alt="Show Top 40">
	</td>
	<td class="inactive">
		<img src="<?php echo WP_VISITORFLOW_PLUGIN_URL . 'assets/images/tabicon_top30.png'; ?>" align="left" width="32" height="32" alt="Show Top 30">
	</td>
	<td class="inactive">
		<img src="<?php echo WP_VISITORFLOW_PLUGIN_URL . 'assets/images/tabicon_top20.png'; ?>" align="left" width="32" height="32" alt="Show Top 20">
	</td>
	<td class="inactive">
		<img src="<?php echo WP_VISITORFLOW_PLUGIN_URL . 'assets/images/tabicon_top10.png'; ?>" align="left" width="32" height="32" alt="Show Top 10">
	</td>
<?php
}
?>
	<td>
		<a href="?page=wpvf_mode_singlepage&amp;dimension=<?php echo $current_dimension < $max_chart_dimension? $current_dimension + 1 : $current_dimension; ?>&amp;select_page_id=<?php echo  $selected_page_id; ?>" title="<?php echo __('Zoom in', 'wp-visitorflow'); ?>">
			<img src="<?php echo WP_VISITORFLOW_PLUGIN_URL . 'assets/images/tabicon_zoom_in.png'; ?>" align="left" width="32" height="32" alt="Zoom in">
		</a>
	</td>
	<td>
		<a href="?page=wpvf_mode_singlepage&amp;dimension=<?php echo $current_dimension > 0? $current_dimension - 1 : $current_dimension; ?>&amp;select_page_id=<?php echo  $selected_page_id; ?>" title="<?php echo __('Zoom out', 'wp-visitorflow'); ?>">
			<img src="<?php echo WP_VISITORFLOW_PLUGIN_URL . 'assets/images/tabicon_zoom_out.png'; ?>" align="left" width="32" height="32" alt="Zoom in">
		</a>
	</td>
</tr>
</table>

<div style="clear:both;"></div>
<?php
//Show warning meassages
if ($warning) {
?>
<br />
<div class="wpvf_warning"><?php echo  $warning; ?></div><br />
<?php
}


/*******************************************************************************
 * Sankey Diagram
 *******************************************************************************/

if (! $flow_data[-1]['total_flow_sum'] && ! $flow_data[+1]['total_flow_sum']) {
	return;
}

$sankeydata = array("nodes" => $flow_data['nodes'], "links" => $flow_data['links']);

$chart_width  = $chart_dimensions[$current_dimension]['width'];
$chart_height = $chart_dimensions[$current_dimension]['height'];
if ($chart_height < $flow_data[-1]['nodes_count'] * 20) {
	$chart_height = $flow_data[-1]['nodes_count'] * 20;
}
if ($chart_height < $flow_data[+1]['nodes_count'] * 20) {
	$chart_height = $flow_data[+1]['nodes_count'] * 20;
}
if ($chart_height > 5000) { $chart_height = 5000; }

$db_info = self::$config->getSetting('db_info');


// Sankey-Diagram
?>
<p id="chart" style="width:<?php echo $chart_width; ?>px;height:<?php echo $chart_height; ?>px;"> </p>
<?php

//Show info meassages
if ($message) {
?>
<br />
<div class="wpvf_message"><?php echo  $message; ?></div><br />
<?php

}
// JS to create Sankey-Diagram
?>
<script src="<?php echo WP_VISITORFLOW_PLUGIN_URL . 'assets/js/d3.v2.min.js'; ?>"></script>
<script src="<?php echo WP_VISITORFLOW_PLUGIN_URL . 'assets/js/sankey.js'; ?>"></script>
<script>

	var margin = {top: 10, right: 0, bottom: 10, left: 10},
		width = <?php echo $chart_width; ?> - margin.left - margin.right + 25,
		height = <?php echo $chart_height; ?> - margin.top - margin.bottom;

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
		.attr("transform", "translate(" + margin.left + "," + margin.top + ")");

	var sankey = d3.sankey()
		.nodePadding(10)
		.nodeWidth(50)
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
		.text(function(d) { return d.label; });

	var node = svg.append("g").selectAll(".node")
		.data(sankeydata.nodes)

		node.enter().append("g")
			// .attr("class", "node")
		.attr("transform", function(d) { return "translate(" + d.x + "," + d.y + ")"; })
		// Drag (deactivated in favour of href-links)
		// .call(d3.behavior.drag()
		// .origin(function(d) { return d; })
		// .on("dragstart", function() { this.parentNode.appendChild(this); })
		// .on("drag", dragmove));

		node.append("a")
			.attr("xlink:href", function (d) { return d.url; })
			.append("rect")
				.attr("height", function(d) { return d.dy; })
		// .attr("width", sankey.nodeWidth())
				.attr("width", function(d) { return sankey.nodeWidth(); })
				// .style("fill", function(d) { return d.color = color(d.name.replace(/ .*/, "")); })
				.style("fill", function(d,i) { return d.color = color(i); })
				.style("stroke", function(d) { return d3.rgb(d.color).darker(2); })
			.append("title")
				.text(function(d) { return d.name; });

		node.append("text")
			.attr("x", -6)
			.attr("y", function(d) { return d.dy / 2; })
			.attr("dy", ".35em")
			.attr("text-anchor", "end")
			.attr("transform", null)
			.text(function(d) { return d.name; })

		.filter(function(d) { return d.x < width / 2; })
			.attr("x", 6 + sankey.nodeWidth())
			.attr("text-anchor", "start")

		.filter(function(d,i) { return i == 0; })
			.attr("x",0)
			.attr("y",0)
			.style('fill', 'white')
			.attr("text-anchor", "middle")
			.attr("transform", function (d) {
				return "rotate(-90) translate(" + (-d.dy / 2) + ", " + (sankey.nodeWidth() / 2) + ")";
			})

	function dragmove(d) {
		d3.select(this).attr("transform", "translate(" + d.x + "," + (d.y = Math.max(0, Math.min(height - d.dy, d3.event.y))) + ")");
		sankey.relayout();
		link.attr("d", path);
	}
</script>

<br />

<table class="wpvftable wpvftable-background">
<tr>
	<th class="wpvftable-title" colspan="2"><?php echo sprintf(__('Summary for Page "%s"', 'wp-visitorflow'), $selected_title); ?></th>
</tr>
<tr>
	<td class="right"><?php echo  __('Incoming page hits', 'wp-visitorflow'); ?>:</td>
	<td><?php echo  $flow_data[-1]['total_flow_sum']; ?></td>
</tr>
<tr>
	<td class="right"><?php echo  __('Outgoing page hits', 'wp-visitorflow'); ?>:</td>
	<td><?php echo  $flow_data[+1]['total_flow_sum']; ?></td>
</tr>
<tr>
	<td class="right"><?php echo  __('Page views per day', 'wp-visitorflow'); ?>:</td>
	<td><?php echo  sprintf("%3.1f", $flow_data[-1]['total_flow_sum'] * $db_info['perdayfactor']); ?></td>
</tr>
<tr>
	<td class="right"><?php echo  __('Page view duration', 'wp-visitorflow'); ?>:</td>
	<td><?php echo  $flow_data[+1]['staytime_mean'] . ' ± '. $flow_data[+1]['staytime_sigma'] . ' sec'; ?></td>
</tr>
<tr>
	<td class="right"><?php echo  __('Exit rate', 'wp-visitorflow'); ?>:</td>
	<td><?php 	if ( $flow_data[-1]['total_flow_sum'] > 0) {
					$diff = 100 * ( $flow_data[-1]['total_flow_sum'] - $flow_data[+1]['total_flow_sum']) / $flow_data[-1]['total_flow_sum'];
					if ($diff < 0) { $diff = 0; }
					echo round($diff, 0) . ' %';
				}
				else { echo '&minus;'; }
		?></td>
</tr>
</table>
<?php


/**
 * Get all incoming and outgoing nodes (pages) and links between them for the selected WP page
 *
 **/
function wpvf_getNewNodesAndLinks($flow_data, $delta)
{
	$db = WP_VisitorFlow_Database::getDB();
	$flow_table = WP_VisitorFlow_Database::getTableName('flow');
	$pages_table = WP_VisitorFlow_Database::getTableName('pages');

	$wpvfConfig = WP_VisitorFlow_Config::getInstance();

	$max_nodes = $wpvfConfig->getUserSetting('flowchart_max_nodes');
	$selected_page_id = $wpvfConfig->getUserSetting('selected_page_id');

	$sql_join_on = 'ON t2.f_visit_id=t1.f_visit_id AND t2.step=t1.step-1';
	if ($delta == +1) {
		$sql_join_on = 'ON t2.f_visit_id=t1.f_visit_id AND t2.step=t1.step+1';
	}

	$min_post_id = 0;
	if (! $wpvfConfig->getSetting('exclude_404') ) { $min_post_id = -1; }

	// Get Flow
	$results = $db->get_results(
				$db->prepare("SELECT t2.f_page_id AS page_id,
									COUNT(t1.id) AS flow_sum,
									SUM(ABS(TIME_TO_SEC(TIMEDIFF(t1.datetime, t2.datetime)))) AS time_sum,
									STDDEV_SAMP(ABS(TIME_TO_SEC(TIMEDIFF(t1.datetime, t2.datetime)))) AS time_sigma,
									$pages_table.title AS title,
									$pages_table.f_post_id AS post_id,
									$pages_table.internal AS internal
								FROM $flow_table AS t1
								JOIN $flow_table AS t2
									$sql_join_on
								JOIN $pages_table
									ON $pages_table.id=t2.f_page_id AND $pages_table.f_post_id>='%d'
								WHERE t1.f_page_id='%d'
								AND t1.datetime>='%s' AND t1.datetime<=date_add('%s', interval 1 day)
								GROUP BY t2.f_page_id
								ORDER BY flow_sum DESC LIMIT 100;",
								$min_post_id, $selected_page_id, $flow_data['datetimestart'], $flow_data['datetimestop'])
							);

	$table_output = <<<ENDHTML
		<br />
		<table class="wpvfadmintable">
		<tr>
			<th>Delta $delta</th>
			<th>Title</th>
			<th>Count</th>
			<th>Time (s)</th>
			<th>1 sigma</th>
		</tr>
ENDHTML;

	$flow_values = array();
	$total_flow_sum = 0;
	$total_nodes_count = 0;
	foreach ($results as $res) {
		if ($res->title != 'self') {
			$total_flow_sum += $res->flow_sum;
			$total_nodes_count++;
			if (! in_array($res->flow_sum,  $flow_values)) {
				array_push($flow_values, $res->flow_sum);
			}
		}
	}

	$flow_threshold = 0;
	if ($max_nodes < 999) {
		sort($flow_values);
		$value_index = 0;
		$flow_count = count($results);
		while($flow_count > $max_nodes && $value_index < count($flow_values)) {
			$flow_threshold = $flow_values[$value_index];
			$flow_count = 0;
			foreach ($results as $res) {
				if ($res->title != 'self') {
					if ($res->flow_sum >= $flow_threshold) {
						$flow_count++;
					}
				}
			}
			$value_index++;
		}
	}


	$filtered_page_count = 0;
	$filtered_hit_count = 0;
	$node_exists = array();
	$nodes_count = 0;

	$staytime_mean = 0;
	$staytime_sigma = 0;
	$staytime_count = 0;

	foreach ($results as $res) {

		$time_mean  = round($res->time_sum/$res->flow_sum, 0);
		$time_sigma = 0;
		if ($res->time_sigma) {
			$time_sigma = round($res->time_sigma, 0);
		}

		$table_output .= '<tr>';
		$table_output .= '<td>' . $res->page_id . '</td>';
		$table_output .= '<td>' . $res->title . '</td>';
		$table_output .= '<td>' . $res->flow_sum . '</td>';
		$table_output .= '<td>' . $time_mean . '</td>';
		$table_output .= '<td>' . $time_sigma . '</td>';
		$table_output .= '</tr>';

		if ($res->title != 'self') {

			if ($res->flow_sum >= $flow_threshold) {
				$nodes_count++;
				$node_label = html_entity_decode($res->title);
				$node_label = preg_replace('/\\\\\'/', "", $node_label);
				if ($res->post_id == -1) {
					$node_label =  '404 error: ' . $node_label;
				}
				$node_name  = $res->page_id;
				if (! in_array($node_name, $node_exists)) {
					array_push($node_exists, $node_name);
					$node = array(
						"name" => $node_label,
						"url" => '?page=wpvf_mode_singlepage&select_page_id=' . $res->page_id
					);
					array_push($flow_data['nodes'], $node);
					$flow_data['node_id'][$res->page_id] = count($flow_data['nodes']) - 1;
				}

				$title = $node_label . ' → ' . $flow_data['nodes'][0]['name'];
				if ($delta == +1) {
					$title = $flow_data['nodes'][0]['name'] . ' → ' . $node_label;
				}
				$staytime = $time_mean . ' ± '. $time_sigma . ' ' . __('seconds', 'wp-visitorflow') . ' ' . __('page view duration', 'wp-visitorflow');
				$link_class = 'link_intern';
				if ($res->internal == 0) {
					$link_class = 'link_extern';
					$staytime = __('Coming from an external website.', 'wp-visitorflow');
					if ($res->title == 'unknown') {
						$staytime = __('Coming from an unknown external source, e.g. a bookmark or direct enter.', 'wp-visitorflow');
					}
				}

				$label = $title .  '\n'
						. $res->flow_sum . ($res->flow_sum == 1 ? ' click' : ' clicks') .' (= '
						. round(100*$res->flow_sum/$total_flow_sum,1) . '% ' . __('of all clicks at this step', 'wp-visitorflow') . ')\n'
						. $staytime;

				$link = array("source" => $flow_data['node_id'][$res->page_id],
							"target" => 0,
							"value" => $res->flow_sum,
							"label" => $label,
							"class" => $res->internal == 1 ? 'link_intern' : 'link_extern'
							);
				if ($delta == +1) {
					$link['source'] = 0;
					$link['target'] = $flow_data['node_id'][$res->page_id];
				}
				array_push($flow_data['links'], $link);
			}
			else {
				$filtered_page_count++;
				$filtered_hit_count += $res->flow_sum;
			}

			if ($delta == +1) {
				$staytime_mean += $time_mean;
				$staytime_sigma += $time_sigma;
				$staytime_count++;
			}

		}
	}

	if ($filtered_page_count > 0) {
		$nodes_count++;
		// "Others" sankey node:
		$node_label = __('Other pages', 'wp-visitorflow');
		$node_name = 0;
		if (! in_array($node_name, $node_exists)) {
			array_push($node_exists, $node_name);
				$node = array("name" => $node_label,
							"url" => '');
			array_push($flow_data['nodes'], $node);
			$flow_data['others_node_id'] = count($flow_data['nodes']) - 1;
		}

		$title = $node_label . ' → ' . $flow_data['nodes'][0]['name'];
		if ($delta == +1) {
			$title = $flow_data['nodes'][0]['name'] . ' → ' . $node_label;
		}

		$label = $title .  '\n'
				. $filtered_hit_count . ($filtered_hit_count == 1 ? ' click' : ' clicks') .' (= '
				. round(100*$filtered_hit_count/$total_flow_sum,1) . '% ' . __('of all clicks at this step', 'wp-visitorflow') . ')\n'
				. sprintf( __('(includes %s pages with clicks below the threshold)', 'wp-visitorflow'),
							$filtered_page_count);

		$link = array("source" => $flow_data['others_node_id'],
					"target" => 0,
					"value" => $filtered_hit_count,
					"label" => $label,
					"class" => 'link_other'
					);
		if ($delta == +1) {
			$link['source'] = 0;
			$link['target'] = $flow_data['others_node_id'];
			$link['staytime'] = 'links to other pages';
		}
		array_push($flow_data['links'], $link);
	}

	$table_output .= '</table>';

	$flow_data[$delta]['total_flow_sum'] = $total_flow_sum;
	$flow_data[$delta]['total_nodes_count'] = $total_nodes_count;
	$flow_data[$delta]['nodes_count'] = $nodes_count;
	$flow_data[$delta]['flow_threshold'] = $flow_threshold;
	if ($delta == +1 && $staytime_count > 0) {
		$flow_data[$delta]['staytime_mean'] = round($staytime_mean/$staytime_count, 0);
		$flow_data[$delta]['staytime_sigma'] = round($staytime_sigma/$staytime_count, 0);
	}
	else {
		$flow_data[$delta]['staytime_mean'] = '&minus;';
		$flow_data[$delta]['staytime_sigma'] = '&minus;';
	}
	$flow_data[$delta]['filtered_page_count'] = $filtered_page_count;

	$flow_data[$delta]['table_output'] = $table_output;

	return $flow_data;
}
