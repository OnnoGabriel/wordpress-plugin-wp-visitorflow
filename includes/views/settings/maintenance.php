<?php
	if (! is_admin() || ! current_user_can( self::$config->getSetting('admin_access_capability') ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	// Save settings?
	if (array_key_exists('wpvf_save_settings', $_POST)) {

		$db = WP_VisitorFlow_Database::getDB();
		$flow_table = WP_VisitorFlow_Database::getTableName('flow');
		$pages_table = WP_VisitorFlow_Database::getTableName('pages');
		$visits_table = WP_VisitorFlow_Database::getTableName('visits');
		$aggregation_table = WP_VisitorFlow_Database::getTableName('aggregation');
		$meta_table = WP_VisitorFlow_Database::getTableName('meta');

		echo "<br>\n";

		if (array_key_exists('wpvf_reset_counters', $_POST)) {

			if (! array_key_exists('wpvf_confirm', $_POST)) {
?>
				<div class="wpvf_warning">
					<p><?php echo __('Do you really want to reset bots and exlusions counters?', 'wp-visitorflow'); ?><p>
					<p class="description"><font color="red"><?php _e('ATTENTION: There will be no second question.', 'wp-visitorflow'); ?></font></p>
					<form id="wpvf_settings" method="post">
					<input type="hidden" name="tab" value="maintenance" />
					<input type="hidden" name="wpvf_save_settings" value="1" />
					<input type="hidden" name="wpvf_reset_counters" value="1" />
					<?php submit_button(__('Yes, do it!', 'wp-visitorflow'), 'delete', 'wpvf_confirm'); ?>
					</form>
					<form id="wpvf_cancel" method="post">
					<?php submit_button(__('Cancel', 'wp-visitorflow'), 'no'); ?>
					</form>
				</div>
<?php
			}
			else {
				// Reset bot counters in "Meta" table
				$sql = "UPDATE $meta_table
						   SET value = '0'
						 WHERE (
							 type='count bot'
						  OR type='count exclusion'
						  OR type='count uastring'
						  OR type='count pagestring');";
				$result = $db->query( $sql );

				self::$config->setSetting('counters-startdatetime', self::$config->getDatetime(), 1);

				echo '<div class="wpvf_message">' . sprintf(
					__('%s bot and exclusion counters reset to zero.', 'wp-visitorflow'),
					$result
				) . "</div><br>\n";
			}
		}

		if (array_key_exists('wpvf_cleardate', $_POST)) {

			$cleardate = htmlspecialchars( stripslashes($_POST['wpvf_cleardate']));

			if (! array_key_exists('wpvf_confirm', $_POST)) {
?>
				<div class="wpvf_warning">
					<p><?php echo sprintf(
						__('Do you really want to delete all statistics data older than %s?', 'wp-visitorflow'),
						date_i18n(
							get_option( 'date_format' ),
							strtotime( $cleardate )
						)
					); ?></p>
					<p class="description"><font color="red"><?php _e('ATTENTION: There will be no second question.', 'wp-visitorflow'); ?></font></p>
					<form id="wpvf_settings" method="post">
					<input type="hidden" name="tab" value="maintenance" />
					<input type="hidden" name="wpvf_save_settings" value="1" />
					<input type="hidden" name="wpvf_cleardate" value="<?php echo $cleardate ?>" />
					<?php submit_button(__('Yes, do it!', 'wp-visitorflow'), 'delete', 'wpvf_confirm'); ?>
					</form>
					<form id="wpvf_cancel" method="post">
					<?php submit_button(__('Cancel', 'wp-visitorflow'), 'no'); ?>
					</form>
				</div>
<?php
			}
			else {

				// Clear "Flow" table
				$result = $db->query(
					$db->prepare("DELETE FROM $flow_table WHERE datetime<'%s';", $cleardate)
				);
				$message = sprintf(
					__('%s page views older than %s cleaned.', 'wp-visitorflow'),
					$result, $cleardate
				) . '<br>';

				// Clear "Pages" table
				$result = $db->query(
					"DELETE FROM $pages_table
					  WHERE NOT EXISTS (
						SELECT id
						  FROM $flow_table
						 WHERE $flow_table.f_page_id=$pages_table.id
						)
					   AND id>'3';"
				);
				$message .= sprintf(
					__('%s pages older than %s cleaned.', 'wp-visitorflow'),
					$result, $cleardate
				) . '<br>';

				// Clear "Visits" table
				$result = $db->query(
					"DELETE FROM $visits_table
					  WHERE NOT EXISTS (
						SELECT id
						  FROM $flow_table
						WHERE $flow_table.f_visit_id=$visits_table.id
						);"
				);
				$message .= sprintf(
					__('%s visits older than %s cleaned.', 'wp-visitorflow'),
					$result, $cleardate
				) . '<br>';

				// Clear "Meta" table
				$result = $db->query(
					$db->prepare("DELETE FROM $meta_table WHERE datetime<'%s';", $cleardate)
				);
				$message .= sprintf(
					__('%s meta entries older than %s cleaned.', 'wp-visitorflow'),
					$result, $cleardate
				) . '<br>';

				// Clear "Aggregation" table
				$result = $db->query(
					$db->prepare("DELETE FROM $aggregation_table WHERE date<'%s';", $cleardate)
				);
				$message .= sprintf(
					__('%s aggregation entries older than %s cleaned.', 'wp-visitorflow'),
					$result, $cleardate
				) . '<br>';

				if ($message) {
					WP_VisitorFlow_Database::storeMeta('log', 'cleanup', $message);
				}

				WP_VisitorFlow_Setup::init();
				$message .= WP_VisitorFlow_Setup::postupdate();

				echo '<p class="wpvf_message">' . $message . "</p>\n";
			}

		}

		if (array_key_exists('wpvf_clear_ua_date', $_POST)) {

			$cleardate = htmlspecialchars( stripslashes($_POST['wpvf_clear_ua_date']));

			if (! array_key_exists('wpvf_confirm', $_POST)) {
?>
				<div class="wpvf_warning">
					<p><?php echo sprintf(__('Do you really want to delete all HTTP User-Agent strings older than %s?', 'wp-visitorflow'),
										date_i18n( get_option( 'date_format' ), strtotime($cleardate))); ?></p>
					<p class="description"><font color="red"><?php _e('ATTENTION: There will be no second question.', 'wp-visitorflow'); ?></font></p>
					<form id="wpvf_settings" method="post">
					<input type="hidden" name="tab" value="maintenance" />
					<input type="hidden" name="wpvf_save_settings" value="1" />
					<input type="hidden" name="wpvf_clear_ua_date" value="<?php echo $cleardate ?>" />
					<?php submit_button(__('Yes, do it!', 'wp-visitorflow'), 'delete', 'wpvf_confirm'); ?>
					</form>
					<form id="wpvf_cancel" method="post">
					<?php submit_button(__('Cancel', 'wp-visitorflow'), 'no'); ?>
					</form>
				</div>
<?php
			}
			else {
				// Clear UA strings from "Meta" table
				$result = $db->query( $db->prepare("DELETE FROM $meta_table WHERE type='useragent' AND datetime<'%s';", $cleardate) );
				echo '<div class="wpvf_message">' . sprintf(__('%s user-agent strings removed from database.', 'wp-visitorflow'), $result) . "</div><br>\n";
			}
		}


		if (array_key_exists('wpvf_trigger_aggregation', $_POST)) {
			if (! array_key_exists('wpvf_confirm', $_POST)) {
?>
				<div class="wpvf_warning">
					<p><?php echo __('Do you really want to restart the data aggregation process?', 'wp-visitorflow'); ?></p>
					<p class="description"><font color="red"><?php _e('ATTENTION: There will be no second question.', 'wp-visitorflow'); ?></font></p>
					<form id="wpvf_settings" method="post">
					<input type="hidden" name="tab" value="maintenance" />
					<input type="hidden" name="wpvf_save_settings" value="1" />
					<input type="hidden" name="wpvf_trigger_aggregation" value="1" />
					<?php submit_button(__('Yes, do it!', 'wp-visitorflow'), 'delete', 'wpvf_confirm'); ?>
					</form>
					<form id="wpvf_cancel" method="post">
					<?php submit_button(__('Cancel', 'wp-visitorflow'), 'no'); ?>
					</form>
				</div>
<?php
			}
			else {
				if (! array_key_exists('wpvf_open_jobs', $_POST)) {
					self::$config->setSetting('last_aggregation_date', 0, 1);
				}

				self::$config->setSetting('data_aggregation_running', 0);

				$script_start = time();

				WP_VisitorFlow_Maintenance::init();
				$open_jobs = WP_VisitorFlow_Maintenance::aggregateData();
				while ( $open_jobs && time() - $script_start < 10) {
					$open_jobs = WP_VisitorFlow_Maintenance::aggregateData();
				}

				if ($open_jobs) {
?>
				<div class="wpvf_warning">
					<p><?php echo sprintf(
						__('Data aggregration completed until %s.', 'wp-visitorflow'),
						date_i18n(
							get_option( 'date_format' ),
							strtotime( self::$config->getSetting('last_aggregation_date') )
						)
					); ?></p>
					<form id="wpvf_settings" method="post">
					<input type="hidden" name="tab" value="maintenance" />
					<input type="hidden" name="wpvf_save_settings" value="1" />
					<input type="hidden" name="wpvf_trigger_aggregation" value="1" />
					<input type="hidden" name="wpvf_open_jobs" value="1" />
					<?php submit_button(__('Continue', 'wp-visitorflow'), 'delete', 'wpvf_confirm'); ?>
					</form>
					<p class="description"><font color="red"><?php _e('Data aggretion takes some time. Please press "continue" button until taks is completed.', 'wp-visitorflow'); ?></font></p>
				</div>
<?php

				}

				else {
					$message = __('Restart of the data aggregation completed.', 'wp-visitorflow') . '<br>';
					echo '<p class="wpvf_message">' . $message . "</p><br>\n";
				}
			}
		}

		WP_VisitorFlow_Analysis::init();
		WP_VisitorFlow_Analysis::updateDBInfo();
	}

	$db_info = self::$config->getSetting('db_info');

	if (! array_key_exists('wpvf_save_settings', $_POST) || array_key_exists('wpvf_confirm', $_POST) ) {
?>
	<br>

	<div class="container-fluid">
		<div class="row">
			<div class="col-xs-12 col-sm-6">

				<div class="wpvf-background">
					<table class="form-table">
					<tbody>

					<tr>
						<th scope="row" colspan="2" style="padding:0 10px;">
							<h3 style="margin-top:0;"><?php _e('Clean-Up Database', 'wp-visitorflow'); ?></h3>
						</th>
					</tr>

					<form id="wpvf_settings" method="post">
					<input type="hidden" name="tab" value="maintenance" />
					<input type="hidden" name="wpvf_save_settings" value="1" />
					<input type="hidden" name="wpvf_reset_counters" value="1" />
					<tr>
						<th scope="row">
							<label for="cleardate"><?php echo __('Reset counters', 'wp-visitorflow'); ?></label>
						</th>
						<td>
							<?php submit_button(__('Reset counters', 'wp-visitorflow'), 'delete'); ?>
							<p class="description">
								<?php _e('Current counter values', 'wp-visitorflow'); ?>:
								<?php echo number_format_i18n($db_info['bots_count']); ?>
								<?php _e('bots', 'wp-visitorflow'); ?> <?php _e('and', 'wp-visitorflow'); ?>
								<?php echo number_format_i18n($db_info['exclusions_count']); ?>
								<?php _e('exclusions', 'wp-visitorflow'); ?>.
							</p>
						</td>
					</tr>
					</tbody>
					</table>
					</form>
				</div>

				<br>
				<br>

				<div class="wpvf-background">
					<form id="wpvf_settings" method="post">
					<input type="hidden" name="tab" value="maintenance" />
					<input type="hidden" name="wpvf_save_settings" value="1" />

					<table class="form-table">
					<tbody>

					<tr>
						<th scope="row">
							<label for="cleardate"><?php echo __('Clear all statistics data', 'wp-visitorflow'); ?></label>
						</th>
						<td>
							<label for="cleardate"><?php echo __('older than', 'wp-visitorflow'); ?></label>
							<input type="date" id="cleardate" name="wpvf_cleardate" value="<?php echo date( 'Y-m-d', strtotime( '-1 days')); ?>">.<br>
							<p class="description"><?php _e('Delete all entries in the WP VisitorFlow data base older than the selected date.', 'wp-visitorflow'); ?></p>
							<?php submit_button(__('Clear Data', 'wp-visitorflow'), 'delete'); ?>
						</td>
					</tr>
					</tbody>
					</table>
					</form>

				</div>

				<br>
				<br>

				<div class="wpvf-background">
					<form id="wpvf_settings" method="post">
					<input type="hidden" name="tab" value="maintenance" />
					<input type="hidden" name="wpvf_save_settings" value="1" />

					<table class="form-table">
					<tbody>

					<tr>
						<th scope="row">
							<label for="clearuastrings"><?php echo __('Clear HTTP user agents strings', 'wp-visitorflow'); ?></label>
						</th>
						<td>
							<label for="clearuastrings"><?php echo __('older than', 'wp-visitorflow'); ?></label>
							<input type="date" id="clearuastrings" name="wpvf_clear_ua_date" value="<?php echo date( 'Y-m-d', strtotime( '-1 days')); ?>">.<br>
							<?php submit_button(__('Clear HTTP UA Strings', 'wp-visitorflow'), 'delete'); ?>
							<p class="description">
								<?php _e('Delete all HTTP User-Agent strings stored before the selected date.', 'wp-visitorflow'); ?><br>
								<?php echo number_format_i18n($db_info['meta_useragents_count']); ?>
								<?php _e('HTTP user-agent strings', 'wp-visitorflow'); ?>
								<?php _e('currently stored.', 'wp-visitorflow'); ?>
							</p>
						</td>
					</tr>
					</tbody>
					</table>
					</form>
				</div>

				<br>
				<br>
<!--
				<div class="wpvf-background">

					<form id="wpvf_settings" method="post">
					<input type="hidden" name="tab" value="maintenance" />
					<input type="hidden" name="wpvf_save_settings" value="1" />
					<input type="hidden" name="wpvf_trigger_aggregation" value="1" />

					<table class="form-table">
					<tbody>
					<tr>
						<th scope="row" colspan="2"><h3><?php _e('Trigger Data Aggregation', 'wp-visitorflow'); ?></h3></th>
					</tr>
					<tr>
						<th scope="row">
							<label for="triggeraggregation"><?php echo __('Restart data aggregation.', 'wp-visitorflow'); ?>:</label>
						</th>
						<td>
							<?php submit_button(__('Trigger Data Aggregation', 'wp-visitorflow'), 'delete'); ?>
							<p class="description"><?php _e('Restart the data aggregation, e.g. in case of inconsistencies/missing data in the timelines.', 'wp-visitorflow'); ?></p>
						</td>
					</tr>
					</tbody>
					</table>
					</form>
				</div>
-->
			</div>

			<!-- Documentation -->
			<div class="col-xs-12 col-sm-6">

				<div class="wpvf-docu-background">
					<table class="form-table">
					<tbody>

					<tr>
						<td scope="row" style="padding:0 10px;">
							<h3><?php _e('Maintenance of WP VistorFlow data', 'wp-visitorflow'); ?></h3>
							<p>
								<strong><?php _e('Counters', 'wp-visitorflow'); ?>:</strong>
								<?php _e('WP VisitorFlow counts visits by bots and crawlers and exclusions by several types of settings (see Settings/Storage).', 'wp-visitorflow'); ?>
								<?php _e('A reset of these counters sets the counter values to zero. All previously counted events will be lost.', 'wp-visitorflow'); ?>
							</p>
							<br>

							<p>
								<strong><?php _e('Clear data', 'wp-visitorflow'); ?>:</strong>
								<?php _e('Flow data consists of visitor and page view information. These data are deleted automatically after a certain period of time (see Settings/Storage).', 'wp-visitorflow'); ?>
								<?php _e('You can also manually all data older than a selected date.', 'wp-visitorflow'); ?>
							</p>
							<br>

							<p>
								<strong><?php _e('HTTP user-agent strings', 'wp-visitorflow'); ?>:</strong>
								<?php _e('HTTP user-agent strings are no mandatory data. It is safe to clear these strings at any time.', 'wp-visitorflow'); ?>
							</p>
							<br>

							<h3><?php echo __('Database Summary', 'wp-visitorflow'); ?></h3>
<?php
							WP_VisitorFlow_Admin_Tables::dbInfoTable();
?>
							<br>
							<p style="text-align:right;">
								 WP VisitorFlow v<?php echo WP_VISITORFLOW_VERSION; ?>
							</p>
						</td>
					</tr>

					</tbody>
					</table>


				</div>

			</div>

		</div>
	</div>
<?php
	}
