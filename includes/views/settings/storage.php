<?php
	if (! is_admin() || ! current_user_can( self::$config->getSetting('admin_access_capability') ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	global $wp_roles;
	$roles = $wp_roles->get_names();

	// Save new settings?
	if (array_key_exists('wpvf_save_settings', $_POST)) {

		// Record Visitor Flow?
		self::$config->setSetting('record_visitorflow', false, 0);
		if (array_key_exists('record_visitorflow', $_POST)) {
			self::$config->setSetting('record_visitorflow', true, 0);
		}

		// Use frontend JS?
		self::$config->setSetting('use_frontend_js', false, 0);
		if (array_key_exists('use_frontend_js', $_POST)) {
			self::$config->setSetting('use_frontend_js', true, 0);
		}

		// Flowdata storage time
		if (array_key_exists('flowdata_storage_time', $_POST)) {
			self::$config->setSetting( 'flowdata_storage_time', htmlspecialchars( stripslashes( $_POST['flowdata_storage_time'] ) ) , 0);
		}
		if (self::$config->getSetting('flowdata_storage_time') < 3) { self::$config->setSetting('flowdata_storage_time', 3, 0); }
		if (self::$config->getSetting('flowdata_storage_time') > 365) { self::$config->setSetting('flowdata_storage_time', 365, 0); }

		// Minimum time between:
		if (array_key_exists('minimum_time_between', $_POST)) {
			self::$config->setSetting('minimum_time_between', htmlspecialchars( stripslashes( $_POST['minimum_time_between'] ) ), 0);
			if (self::$config->getSetting('minimum_time_between') < 1) { self::$config->setSetting('minimum_time_between', 1, 0); }
			if (self::$config->getSetting('minimum_time_between') > 10000) { self::$config->setSetting('minimum_time_between', 10000, 0); }
		}

		// Exclude Detected Bots
		self::$config->setSetting('exclude_bots', false, 0);
		if (array_key_exists('exclude_bots', $_POST)) {
			self::$config->setSetting('exclude_bots', true, 0);
		}

		// Exclude Unkown UA Strings
		self::$config->setSetting('exclude_unknown_useragents', false, 0);
		if (array_key_exists('exclude_unknown_useragents', $_POST)) {
			self::$config->setSetting('exclude_unknown_useragents', true, 0);
		}

		// Exclude crawlers
		$excluded_crawlers = array();
		if (array_key_exists('crawlers_exclude_list', $_POST)) {
			$exclude_list_string = str_replace(array("\r\n", "\r"), "\n",  htmlspecialchars( stripslashes( $_POST['crawlers_exclude_list'] ) ) );
			$list = explode( "\n", $exclude_list_string);
			foreach ($list as $item) {
				$item = preg_replace('/\s+/', ' ', $item);
				$item = str_replace("\t", '', $item);
				$item = trim($item);
				if ($item) {
					array_push($excluded_crawlers, $item);
				}
			}
		}
		self::$config->setSetting('crawlers_exclude_list', $excluded_crawlers );

		// Exclude pages
		self::$config->setSetting('exclude_404', false, 0);
		if (array_key_exists('exclude_404', $_POST)) {
			self::$config->setSetting('exclude_404', true, 0);
		}
		$excluded_pagestrings = array();
		if (array_key_exists('pages_exclude_list', $_POST)) {
			$exclude_list_string = str_replace(array("\r\n", "\r"), "\n",  htmlspecialchars( stripslashes( $_POST['pages_exclude_list'] ) ) );
			$list = explode( "\n", $exclude_list_string);
			foreach ($list as $item) {
				$item = preg_replace('/\s+/', ' ', $item);
				$item = str_replace("\t", '', $item);
				$item = trim($item);
				if ($item) {
					array_push($excluded_pagestrings, $item);
				}
			}
		}
		self::$config->setSetting('pages_exclude_list', $excluded_pagestrings );

		// Include WP Roles
		foreach ($roles as $role ) {
			$setting_key = 'include_' . str_replace(" ", "_", strtolower($role) );
			$option_key = 'wpvf_' . $setting_key;
			if (array_key_exists($option_key, $_POST)) {
				self::$config->setSetting($setting_key, true, 0);
			}
			else {
				self::$config->setSetting($setting_key, false, 0);
			}
		}

		// Save settings
		self::$config->saveSettings();

		// Start clean-up (in case that flowdata_storage_time was shortened)
		WP_VisitorFlow_Maintenance::init();
		WP_VisitorFlow_Maintenance::cleanupData();

		echo '<br><div class="wpvf_message">' . __('Settings saved.', 'wp-visitorflow') . "</div>\n";
		echo '<br>';
	}

	// Print settings menu
?>
	<br>
	<div class="container-fluid">
		<div class="row">
			<div class="col-xs-12 col-sm-7">

				<form id="wpvf_settings" method="post">
				<input type="hidden" name="tab" value="general" />
				<input type="hidden" name="wpvf_save_settings" value="1" />

				<div class="wpvf-background">
					<table class="form-table">
					<tbody>

					<tr>
						<th scope="row" colspan="2" style="padding:0 10px;">
							<h3 style="margin-top:0;"><?php _e('General Storage Settings', 'wp-visitorflow'); ?></h3>
						</th>
					</tr>

					<tr>
						<th scope="row"><?php echo __('Record visitor flow', 'wp-visitorflow'); ?>:</th>
						<td>
							<input id="record_visitorflow" type="checkbox" value="1" name="record_visitorflow" <?php echo self::$config->getSetting('record_visitorflow') == true? 'checked="checked"' : ''; ?>>
							<label for="record_visitorflow"><?php echo sprintf(__('Active (default: %s)', 'wp-visitorflow'), self::$config->getDefaultSettings('record_visitorflow') == true ? __('active', 'wp-visitorflow') : __('inactive', 'wp-visitorflow') ); ?></label>
							<p class="description"><?php
								echo __('If active, the visitor flow will be recorded. If inactive, no data will be stored.', 'wp-visitorflow');
							?></p>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php echo __('Enable client JS requests', 'wp-visitorflow'); ?>:</th>
						<td>
							<input id="use_frontend_js" type="checkbox" value="1" name="use_frontend_js" <?php echo self::$config->getSetting('use_frontend_js') == true? 'checked="checked"' : ''; ?>>
							<label for="use_frontend_js"><?php echo sprintf(__('Active (default: %s)', 'wp-visitorflow'), self::$config->getDefaultSettings('use_frontend_js') == true ? __('active', 'wp-visitorflow') : __('inactive', 'wp-visitorflow') ); ?></label>
							<p class="description">
								<?php echo __('Enables tracking via an additional JavaScript function on client site. Recommended if caching plugins are active.', 'wp-visitorflow'); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="timebetween"><?php echo __('Minimum time between visits', 'wp-visitorflow'); ?>:</label>
						</th>
						<td>
							<input id="timebetween" type="number" name="minimum_time_between" value="<?php echo self::$config->getSetting('minimum_time_between'); ?>" min="0" max="2000">
							<label for="timebetween"><?php echo sprintf(__('minutes (default: %s minutes)', 'wp-visitorflow'), self::$config->getDefaultSettings('minimum_time_between') ); ?></label>
							<p class="description">
								<?php _e('Is the time between two visits by a single client shorter than this period, the client will be counted as the same visitor. Otherwise, the client will be counted as a new visitor.', 'wp-visitorflow'); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="storagetime"><?php echo __('Flow data storage time', 'wp-visitorflow'); ?>:</label>
						</th>
						<td>
							<input id="storagetime" type="number" name="flowdata_storage_time" value="<?php echo self::$config->getSetting('flowdata_storage_time'); ?>" min="3" max="365">
							<label for="storagetime"><?php echo sprintf(__('days (default: %s days)', 'wp-visitorflow'), self::$config->getDefaultSettings('flowdata_storage_time') ); ?></label>
							<p class="description">
								<?php echo __('Detailed flow data is stored only for this amount of days. Older data will be automatically deleted to keep the database lean and the webpage performance high.', 'wp-visitorflow'); ?>
								<?php echo __('Aggregated data such as daily page hit counts will not be affected.', 'wp-visitorflow'); ?>
							</p>
						</td>
					</tr>

					</tbody>
					</table>
				</div>


				<div class="wpvf-background">
					<table class="form-table">
					<tbody>

					<tr>
						<th scope="row" colspan="2"><h3><?php _e('Exclude Remote Clients', 'wp-visitorflow'); ?></h3></th>
					</tr>
					<tr>
						<th scope="row"><?php echo __('Exclude detected bots', 'wp-visitorflow'); ?>:</th>
						<td>
							<input id="exclude_bots" type="checkbox" value="1" name="exclude_bots" <?php echo self::$config->getSetting('exclude_bots') == true? 'checked="checked"' : ''; ?>>
							<label for="exclude_bots"><?php echo sprintf(__('Active (default: %s)', 'wp-visitorflow'), self::$config->getDefaultSettings('exclude_bots') == true ? __('active', 'wp-visitorflow') : __('inactive', 'wp-visitorflow') ); ?></label>
							<p class="description"><?php echo __('Exclude bots, crawlers, web spiders etc. from the flow statistics. Bot visits will be counted, but not tacked.', 'wp-visitorflow'); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php echo __('Exclude unknown UA strings', 'wp-visitorflow'); ?>:</th>
						<td>
							<input id="exclude_unknown_useragents" type="checkbox" value="1" name="exclude_unknown_useragents" <?php echo self::$config->getSetting('exclude_unknown_useragents') == true? 'checked="checked"' : ''; ?>>
							<label for="exclude_unknown_useragents"><?php echo sprintf(__('Active (default: %s)', 'wp-visitorflow'), self::$config->getDefaultSettings('exclude_unknown_useragents') == true ? __('active', 'wp-visitorflow') : __('inactive', 'wp-visitorflow') ); ?></label>
							<p class="description"><?php echo __('Exclude unknown remote clients from the statistics.', 'wp-visitorflow'); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="timebetween"><?php echo __('UA string exclusion list', 'wp-visitorflow'); ?>:</label>
						</th>
						<td>
							<textarea name="crawlers_exclude_list" id="crawlers_exclude_list"  rows="6" cols="40"><?php
							foreach( self::$config->getSetting('crawlers_exclude_list') as $string ) {
								echo $string."\n";
							}
							?></textarea>
							<p class="description"><?php echo __('Any remote client with a HTTP User Agent that includes one of the text strings in this list will be excluded from the statistics.', 'wp-visitorflow'); ?></p>
							<p class="description"><?php echo __('You can edit the list and/or include new text strings at the end of the list. One string per line.', 'wp-visitorflow'); ?></p>
						</td>
					</tr>

					</tbody>
					</table>
				</div>

				<br>
				<br>

				<div class="wpvf-background">
					<table class="form-table">
					<tbody>

					<tr>
						<th scope="row" colspan="2"><h3><?php _e('Exclude WordPress Pages', 'wp-visitorflow'); ?></h3></th>
					</tr>
					<tr>
						<th scope="row"><?php echo __('Exclude 404 error pages', 'wp-visitorflow'); ?>:</th>
						<td>
							<input id="exclude_404" type="checkbox" value="1" name="exclude_404" <?php echo self::$config->getSetting('exclude_404') == true? 'checked="checked"' : ''; ?>>
							<label for="exclude_404"><?php echo sprintf(__('Active (default: %s)', 'wp-visitorflow'), self::$config->getDefaultSettings('exclude_404') == true ? __('active', 'wp-visitorflow') : __('inactive', 'wp-visitorflow') ); ?></label>
							<p class="description"><?php echo __('Exclude non-existent pages/files, which lead to a 404 error page.', 'wp-visitorflow'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="timebetween"><?php echo __('Pages URL exclusion list', 'wp-visitorflow'); ?>:</label>
						</th>
						<td>
							<textarea name="pages_exclude_list" id="pages_exclude_list"  rows="6" cols="40"><?php
							foreach( self::$config->getSetting('pages_exclude_list') as $string ) {
								echo $string."\n";
							}
							?></textarea>
							<p class="description"><?php echo __('Any page URL that includes one of the text strings in this list will be excluded from the statistics.', 'wp-visitorflow'); ?></p>
							<p class="description"><?php echo __('You can edit the list and/or include new text strings at the end of the list. One string per line.', 'wp-visitorflow'); ?></p>
						</td>
					</tr>

				</tbody>
					</table>
				</div>

				<br>
				<br>

				<div class="wpvf-background">
					<table class="form-table">
					<tbody>

					<tr>
						<th scope="row" colspan="2"><h3><?php echo __('Exclude WordPress Users', 'wp-visitorflow'); ?></h3></th>
					</tr>
<?php

	foreach ($roles as $role ) {
		$setting_key = 'include_' . str_replace(" ", "_", strtolower($role) );
		$option_key = 'wpvf_' . $setting_key;
		$translated_role_name = translate_user_role($role);
?>
					<tr>
						<th scope="row"><label for="<?php echo $option_key;?>"><?php echo $translated_role_name; ?>:</label></th>
						<td>
							<input id="<?php echo $option_key;?>" type="checkbox" value="1" name="<?php echo $option_key;?>" <?php echo self::$config->getSetting($setting_key) == false ? 'checked="checked"' : ''; ?>>
							<label for="<?php echo $option_key;?>"><?php echo sprintf(__('Active (default: %s)', 'wp-visitorflow'), self::$config->getDefaultSettings($setting_key) == false ? __('active', 'wp-visitorflow') : __('inactive', 'wp-visitorflow') ); ?></label>
							<p class="description"><?php echo sprintf(__('Exclude role "%s" in the statistics.', 'wp-visitorflow'), $translated_role_name); ?></p>
						</td>
					</tr>
<?php
	}
?>
					</tbody>
					</table>
				</div>

				<?php submit_button(); ?>

				</form>

			</div>

			<!-- Documentation -->
			<div class="col-xs-12 col-sm-5">

				<div class="wpvf-docu-background">
					<table class="form-table">
					<tbody>

					<tr>
						<td style="padding:0 10px;">
							<h3><?php _e('What data is recorded?', 'wp-visitorflow'); ?></h3>
							<p>
								<strong><?php _e('Visitors', 'wp-visitorflow'); ?>:</strong>
								<?php _e('Information about the Visitor\'s client is obtained by analysis of the <a class="wpvf wpvfextern" href="https://en.wikipedia.org/wiki/User_agent"> User-Agent string</a>. The following data is recorded about any visitor:', 'wp-visitorflow'); ?>
							</p>
							<ul>
								<li style="list-style-type:circle;margin-left:20px;"><?php _e('Agent name, engine and version (e.g. Firefox/Gecko/v62.0)', 'wp-visitorflow'); ?></li>
								<li style="list-style-type:circle;margin-left:20px;"><?php _e('Operation system and version (e.g. Windows, iOS or Android)', 'wp-visitorflow'); ?></li>
								<li style="list-style-type:circle;margin-left:20px;"><?php _e('IP address (by default in encrypted form, see Settings/Privacy)', 'wp-visitorflow'); ?></li>
							</ul>
							<p>
								<?php _e('A visitor is detected as a new visitor if it is the first visit or if the last visit has been longer ago than 60 minutes (can be changed in Settings/Storage).', 'wp-visitorflow'); ?>
								<?php _e('Bots, spiders and web crawlers are detected by the HTTP user-agent string, too. The analysis of the user-agent string is performed by using the <a class="wpvf wpvfextern" href="https://github.com/matomo-org/device-detector">Device Detector</a> library.</a> ', 'wp-visitorflow'); ?>
							</p>
							<p>
								<strong><?php _e('Page views', 'wp-visitorflow'); ?>:</strong>
								<?php _e('For any visitor, the visited page and the date and time is recorded. Thereby, the click flow through you website can be tracked for any visitor and the total <strong>flow data</strong> of all visitors can be analysed.', 'wp-visitorflow'); ?>
							</p>
							<br>

							<h3><?php _e('How long is the data recorded?', 'wp-visitorflow'); ?></h3>
							<p>
								<?php _e('The detailed visitor and flow data is stored for 365 days by default (can be changed in Settings/Storage).', 'wp-visitorflow'); ?>
								<?php _e('Older data is automatically deleted and only information about total visits per page and day is kept in the database for an infinite amount of time.', 'wp-visitorflow'); ?>
							</p>
							<br>

							<h3><?php _e('Trouble with caching plugins?', 'wp-visitorflow'); ?></h3>
							<p>
								<?php _e('Visitors and page views are automatically recorded in the WordPress backend (by default).', 'wp-visitorflow'); ?>
								<?php _e('However, we found that some caching plugins circumvent this recording.', 'wp-visitorflow'); ?>
								<?php _e('If you use such a cache, you should enable "Enable client JS requests" in Settings/Storage.', 'wp-visitorflow'); ?>
								<?php _e('Thereby, a small JavaScript function is added to your website, which triggers the data recording by WP VisitorFlow.', 'wp-visitorflow'); ?>
							</p>
							<br>

							<h3><?php _e('Exclude visitors and page views', 'wp-visitorflow'); ?></h3>
							<p>
								<strong><?php _e('Detected bots', 'wp-visitorflow'); ?>:</strong>
								<?php _e('Many page views originate from automated bots and crawlers.', 'wp-visitorflow'); ?>
								<?php _e('By default, these bots are excluded from the flow data statistics.', 'wp-visitorflow'); ?>
								<?php _e('They are detected by their submitted HTTP user agent-strings and only the total number of page views are counted.', 'wp-visitorflow'); ?>
							</p>
							<p>
								<strong><?php _e('Unknown UA strings', 'wp-visitorflow'); ?>:</strong>
								<?php _e('If the submitted user-agent string is unknown, the visits are counted but excluded from the flow data statistics (by default).', 'wp-visitorflow'); ?>
								<?php _e('Moreover, certain UA strings can be detected by means of a white-list and excluded from the flow data statistics, too', 'wp-visitorflow'); ?>
							</p>
							<p>
								<strong><?php _e('404 error pages', 'wp-visitorflow'); ?>:</strong>
								<?php _e('If a requested page is not available, WordPress delivers a 404 error. ', 'wp-visitorflow'); ?>
								<?php _e('By default, these not existing pages are included in the flow statistics, because often you want to see these errors.', 'wp-visitorflow'); ?>
								<?php _e('However, you can deactivate the recording of 404 pages.', 'wp-visitorflow'); ?>
							</p>
							<p>
								<strong><?php _e('URL exclusion list', 'wp-visitorflow'); ?>:</strong>
								<?php _e('If you want to exclude certain pages from the statistics, you can use a white-list.', 'wp-visitorflow'); ?>
							</p>
							<p>
								<strong><?php _e('Exclude WordPress Users', 'wp-visitorflow'); ?>:</strong>
								<?php echo __('By default, any page view of logged-in WordPress user is not recorded. You can set the recording of any logged-in WordPress user per role.', 'wp-visitorflow'); ?>
							</p>

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
