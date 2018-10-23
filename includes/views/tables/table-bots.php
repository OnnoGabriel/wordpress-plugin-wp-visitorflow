<?php
	if (! is_admin() || ! current_user_can( self::$config->getSetting('read_access_capability') ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	// Get DB object and table names from visitorflow class
	$db = WP_VisitorFlow_Database::getDB();
	$meta_table  = WP_VisitorFlow_Database::getTableName('meta');

	$db_info = self::$config->getSetting('db_info');

	// Get numbers of bot/crawler/spider vitis from meta table
	$results = $db->get_results(
		"SELECT label, value, datetime
			FROM $meta_table
			WHERE type='count bot'
			AND value>'0'
		ORDER BY label ASC;");

	$botcounts = array();
	$botcounts_values = array();

	foreach ($results as $result) {
		$value = $result->value;
		$datetime = sprintf(
			__('%s ago', 'wp-visitorflow'),
			WP_VisitorFlow_Admin::getNiceTimeDifference($result->datetime, self::$config->getDatetime() )
		);

		if (! in_array($value, $botcounts_values)) {
			array_push($botcounts_values, $value);
		}

		$bot = array('name' => $result->label,
						'datetime' => $datetime,
						'hitsperday' => sprintf("%3.1f", $value * $db_info['counters_perdayfactor']),
						'hits' => $value);
		array_push($botcounts, $bot);
	}

	$threshold_index = 0;

?>
	<table class="wpvftable">
	<tr>
		<th><?php echo __('Bot/Crawler Name', 'wp-visitorflow'); ?></th>
		<th><?php echo __('Hits', 'wp-visitorflow'); ?></th>
		<th><?php echo __('Hits per Day', 'wp-visitorflow'); ?></th>
		<th><?php echo __('Last Hit', 'wp-visitorflow'); ?></th>
	</tr>
<?php
	if (count($botcounts) == 0) {
		echo '<tr><td colspan="4"><em>' . __('Table still empty.', 'wp-visitorflow') . '</em></td></tr>';
	}
	else {
		$botcounts_filtered = $botcounts;

		// If not "show all" selected: truncate bot list to maximum 10 entries
		if (! array_key_exists('wpvf_botlist_showall', $_GET)) {
			sort($botcounts_values);
			$threshold = 0;
			while (count($botcounts_filtered) > 10) {
				$threshold_index++;
				$threshold = $botcounts_values[$threshold_index];
				$botcounts_filtered = array();
				foreach ($botcounts as $bot) {
					if ($bot['hits'] >= $threshold) {
						array_push($botcounts_filtered, $bot);
					}
				}
			}
		}

		// Sort bot array descending by hits
		$sort_col = array();
		foreach ($botcounts_filtered as $key => $row) {
			$sort_col[$key] = $row['hits'];
		}
		array_multisort($sort_col, SORT_DESC, $botcounts_filtered);
		$count = 1;
		foreach ($botcounts_filtered as $bot) {
			if ($count % 2 != 0) {
				echo '<tr>';
			}
			else {
				echo '<tr class="darker">';
			}
			$count++;
			echo '<td>' . $bot['name'] . '</td>';
			echo '<td class="right"><strong>' . number_format_i18n($bot['hits']). '</strong></td>';
			echo '<td class="right">' . $bot['hitsperday'] . '</td>';
			echo '<td>' . $bot['datetime'] . '</td>';
			echo '</tr>';
		}
	}
?>
	</table>
<?php

	if ($threshold_index) {
?>
		<a class="wpvf" href="?page=wpvf_menu&amp;wpvf_botlist_showall=1">[ <?php echo  __('Show all', 'wp-visitorflow'); ?> ]</a>
<?php
	}
