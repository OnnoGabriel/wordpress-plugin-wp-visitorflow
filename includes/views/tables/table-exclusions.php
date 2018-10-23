<?php
	if (! is_admin() || ! current_user_can( self::$config->getSetting('read_access_capability') ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	$db = WP_VisitorFlow_Database::getDB();
	$meta_table  = WP_VisitorFlow_Database::getTableName('meta');

	$db_info = self::$config->getSetting('db_info');

?>
	<table class="wpvftable">
	<tr>
		<th><?php echo __('Reason for Exclusion', 'wp-visitorflow'); ?></th>
		<th><?php echo __('Page Views', 'wp-visitorflow'); ?></th>
		<th><?php echo __('Page Views per Day', 'wp-visitorflow'); ?></th>
		<th><?php echo __('Last View', 'wp-visitorflow'); ?></th>
	</tr>
<?php

	// Exclusions due to User-Agent String:
	$exclude_strings = array();
	foreach (self::$config->getSetting('crawlers_exclude_list') as $crawler_string) {
		array_push($exclude_strings, $crawler_string);
	}
	array_push($exclude_strings, 'unknown');

	$table_empty = true;
	$some_hidden = false;
	$count = 1;
	foreach ($exclude_strings as $exclude_string) {

		$result = $db->get_row(
					$db->prepare("SELECT value, datetime
									FROM $meta_table
									WHERE type='count uastring' AND label='%s';",
									$exclude_string)
								);

		$value = 0;
		$datetime = '-';
		if (isset($result->value)) {
			$value = $result->value;
			$datetime = sprintf(
				__( '%s ago', 'wp-visitorflow'),
				WP_VisitorFlow_Admin::getNiceTimeDifference($result->datetime, self::$config->getDatetime() )
			);
		}
		if ($value > 0 || array_key_exists('wpvf_exclusions_showall', $_GET)) {
			$table_empty = false;
			if ($count % 2 != 0) {
				echo '<tr>';
			}
			else {
				echo '<tr class="darker">';
			}
			$count++;
			if ($exclude_string == 'unknown') {
				echo '<td><em>UA string</em>: empty/unknown</td>';
			}
			else {
				echo '<td><em>UA string</em>: ' . $exclude_string . '</td>';
			}
			if ($value > 0) {
				echo '<td class="right"><strong>' . $value . '</strong></td>';
				echo '<td class="right">' . sprintf("%3.1f", $value * $db_info['perdayfactor']) . '</td>';
			}
			else {
				echo '<td class="right">' . $value . '</td>';
				echo '<td class="right">&minus;</td>';
			}
			echo '<td>' . $datetime . '</td>';
			echo '</tr>';
		}
		else {
			$some_hidden = true;
		}
	}

	// Exclusions due to Page String:
	$exclude_strings = array();
	foreach (self::$config->getSetting('pages_exclude_list') as $exclusion_string) {
		array_push($exclude_strings, $exclusion_string);
	}

	foreach ($exclude_strings as $exclude_string) {

		$result = $db->get_row(
					$db->prepare("SELECT value, datetime
									FROM $meta_table
									WHERE type='count pagestring' AND label='%s';",
									$exclude_string)
								);

		$value = 0;
		$datetime = '-';
		if (isset($result->value)) {
			$value = $result->value;
			$datetime = sprintf(
				__('%s ago', 'wp-visitorflow'),
				WP_VisitorFlow_Admin::getNiceTimeDifference($result->datetime, self::$config->getDatetime() )
			);
		}
		if ($value > 0 || array_key_exists('wpvf_exclusions_showall', $_GET)) {
			$table_empty = false;
			if ($count % 2 != 0) {
				echo '<tr>';
			}
			else {
				echo '<tr class="darker">';
			}
			$count++;
			echo '<td><em>' . __('Page', 'wp-visitorflow') . '</em>: ' . $exclude_string . '</td>';
			if ($value > 0) {
				echo '<td class="right"><strong>' . number_format_i18n($value) . '</strong></td>';
				echo '<td class="right">' . sprintf("%3.1f", $value * $db_info['counters_perdayfactor']) . '</td>';
			}
			else {
				echo '<td class="right">' . number_format_i18n($value) . '</td>';
				echo '<td class="right">&minus;</td>';
			}
			echo '<td>' . $datetime . '</td>';
			echo '</tr>';
		}
		else {
			$some_hidden = true;
		}
	}

	// Exclusions due 404 errors?
	$result = $db->get_row("SELECT value, datetime
							FROM $meta_table
							WHERE type='count exclusion' AND label='404'");
	if (isset($result) && $result->value) {
		if ($result->value) {
			$table_empty = false;
			$datetime = sprintf(
				__( '%s ago', 'wp-visitorflow'),
				WP_VisitorFlow_Admin::getNiceTimeDifference($result->datetime, self::$config->getDatetime() )
			);
			if ($count % 2 != 0) {
				echo '<tr>';
			}
			else {
				echo '<tr class="darker">';
			}
			$count++;
			echo '<td><em>' . __('404 errors', 'wp-visitorflow') . '</em></td>';
			echo '<td class="right"><strong>' . number_format_i18n($result->value) . '</strong></td>';
			echo '<td class="right">' . sprintf("%3.1f", $result->value * $db_info['perdayfactor']) . '</td>';
			echo '<td>' . $datetime . '</td>';
			echo '</tr>';
		}
	}

	// Exclusions due to self-referrers?
	$result = $db->get_row("SELECT value, datetime
							FROM $meta_table
							WHERE type='count exclusion' AND label='self-referrer'");
	if (isset($result) && $result->value) {
		if ($result->value) {
			$table_empty = false;
			$datetime = sprintf(
				__( '%s ago', 'wp-visitorflow'),
				WP_VisitorFlow_Admin::getNiceTimeDifference($result->datetime, self::$config->getDatetime() )
			);
			if ($count % 2 != 0) {
				echo '<tr>';
			}
			else {
				echo '<tr class="darker">';
			}
			$count++;
			echo '<td><em>' . __('Self-referrers', 'wp-visitorflow') . '</em></td>';
			echo '<td class="right"><strong>' . number_format_i18n($result->value) . '</strong></td>';
			echo '<td class="right">' . sprintf("%3.1f", $result->value * $db_info['perdayfactor']) . '</td>';
			echo '<td>' . $datetime . '</td>';
			echo '</tr>';
		}
	}


	if ($table_empty) {
		echo '<tr><td colspan="4"><em>' . __('Table still empty.', 'wp-visitorflow') . '</em></td></tr>';
	}

	echo "</table>\n";

	if ($some_hidden) {
?>
		<a class="wpvf" href="?page=wpvf_menu&amp;wpvf_exclusions_showall=1">[ <?php echo  __('Show all', 'wp-visitorflow'); ?> ]</a>
<?php
	}
