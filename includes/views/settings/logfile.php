<?php
	if (! is_admin() || ! current_user_can( self::$config->getSetting('admin_access_capability') ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	// Get DB object and table names from visitorflow class
	$db = WP_VisitorFlow_Database::getDB();
	$meta_table  = WP_VisitorFlow_Database::getTableName('meta');

	$event_types = array(
		'setstart'   => __('Reset DB start date/time', 'wp-visitorflow'),
		'cleanup'    => __('DB clean-up', 'wp-visitorflow'),
		'aggregat'   => __('Data aggregation', 'wp-visitorflow'),
		'initpages'  => __('Data table initialization', 'wp-visitorflow'),
		'newversion' => __('Plugin update', 'wp-visitorflow'),
		'delvisit'   => __('Visit manually deleted', 'wp-visitorflow'),
	);

	$results = $db->get_results(
		"SELECT *
		   FROM $meta_table
		  WHERE type='log'
	   ORDER BY id DESC
		  LIMIT 1000;"
	);

	$table_data = array();

	foreach ( $results as $res ) {

		$event_name = $res->label;
		if (array_key_exists($event_name, $event_types)) {
			$event_name = $event_types[$event_name];
		}

		$entry = array( 'datetime' => $res->datetime,
						'datetime_nice' => sprintf(
							__('%s ago', 'wp-visitorflow'),
							WP_VisitorFlow_Admin::getNiceTimeDifference(
								$res->datetime,
								self::$config->getDatetime() )
						),
						'label'    => $event_name,
						'value'    => $res->value
					);

		array_push($table_data, $entry);
	}

	$columns = array('datetime' => __('Date/Time', 'wp-visitorflow'),
						'datetime_nice' => '&nbsp;',
						'label' => __('Event', 'wp-visitorflow'),
						'value' => __('Message', 'wp-visitorflow'),
						);
	$sortable_columns = array( 'datetime' => array('datetime', false),
								'label' => array('label', false),
								);

	$myTable = new WP_VisitorFlow_Table( $columns, $sortable_columns, $table_data);
	$myTable->prepare_items();

	// Show visits table
?>
	<h2>Logfile</h2>
<?php
	$myTable->display();
