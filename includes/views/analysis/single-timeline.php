<?php

/*******************************************************************************
 * Page Header
 *******************************************************************************/

$queries = array('page' => 'wpvf_mode_singlepage',
					'tab' => 'timeline',
					'select_page_id' => $selected_page_id);

$timeframePage->setTimeframe($datetimestart, $datetimestop);
$timeframePage->setQueries($queries);

// Print Timeframe Menu
$timeframePage->printTimeframeMenu( self::$config->getSetting('db-startdatetime') );

// Print Tab Menu
$timeframePage->printTabsMenu();

?>
<div style="clear:both;"></div>
<br />
<?php

/*******************************************************************************
 * Draw Timeline Diagramm
 *******************************************************************************/

	//Get data from today (if necessary):
$today = self::$config->getDatetime('Y-m-d');
$todays_data = array();
if ($datetimestop == $today) {
	$todays_data = WP_VisitorFlow_Database::getData( $today );
}

//Get data from older days:
$chart_data = array();
$results = $db->get_results($db->prepare("SELECT date, value AS count
											FROM $aggregation_table
											WHERE type='views-%d'
											AND (date BETWEEN '%s' AND date_add('%s', interval 1 day));",
											$selected_page_id, $datetimestart, $datetimestop)
							);

							if (count($results) == 0) {
?>
	<div class="wpvf_warning">
		<p><?php echo __('No data found in the selected timeframe.', 'wp-visitorflow'); ?></p>
	</div><br />
<?php
	return;
}

$data = array();
foreach ($results as $res) {
	$data[$res->date] = $res->count;
}

if ( isset($todays_data['views-' . $selected_page_id]) ) {
	$data[$today] = $todays_data['views-' . $selected_page_id];
}

array_push($chart_data, array('label' => $selected_title,
								'data' => $data) );


?>
<div class="wpvf-background">
	<h2><?php echo __('Development of Page Views within the Selected Timeframe', 'wp-visitorflow'); ?></h2>
<?php
WP_VisitorFlow_Admin_Plots::lineChart(
	'',
	$chart_data,
	array(
		'id' => 'chart_pages',
		'width' => '98%',
		'height' => '500px'
	)
);

?>
</div>
