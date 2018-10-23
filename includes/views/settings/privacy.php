<?php
	if (! is_admin() || ! current_user_can( self::$config->getSetting('admin_access_capability') ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	// Save settings?
	if (array_key_exists('wpvf_save_settings', $_POST)) {

		if (array_key_exists('encrypt_ips', $_POST)) {
			self::$config->setSetting('encrypt_ips', true, 0);
		}
		else {
			self::$config->setSetting('encrypt_ips', false, 0);
		}
		if (array_key_exists('store_useragent', $_POST)) {
			self::$config->setSetting('store_useragent', true, 0);
		}
		else {
			self::$config->setSetting('store_useragent', false, 0);
		}

		if (array_key_exists('read_access_capability', $_POST)) {
			self::$config->setSetting( 'read_access_capability', htmlspecialchars( stripslashes( $_POST['read_access_capability'] ) ) , 0);
		}
		if (array_key_exists('admin_access_capability', $_POST)) {
			self::$config->setSetting( 'admin_access_capability', htmlspecialchars( stripslashes( $_POST['admin_access_capability'] ) ) , 0);
		}

		self::$config->saveSettings();

		echo '<br><div class="wpvf_message">' . __('Settings saved.', 'wp-visitorflow') . "</div>\n";
		echo '<br>';
	}

	// Print setting menu:
?>
	<br>
	<div class="container-fluid">
		<div class="row">
			<div class="col-xs-12 col-sm-6">

				<form id="wpvf_settings" method="post">
				<input type="hidden" name="tab" value="general" />
				<input type="hidden" name="wpvf_save_settings" value="1" />

				<div class="wpvf-background">
					<table class="form-table">
					<tbody>

					<tr>
						<th scope="row" colspan="2" style="padding:0 10px;">
							<h3 style="margin-top:0;"><?php _e('IP Addresses', 'wp-visitorflow'); ?></h3>
						</th>
					</tr>
					<tr>
						<th scope="row">
							<label for="haship"><?php echo __('Anonymize IP addresses', 'wp-visitorflow'); ?>:</label>
						</th>
						<td>
							<input id="haship" type="checkbox" value="1" name="encrypt_ips" <?php echo self::$config->getSetting('encrypt_ips') == true? 'checked="checked"' : ''; ?>>
							<label for="haship"><?php echo sprintf(__('Active (default: %s)', 'wp-visitorflow'), self::$config->getDefaultSettings('encrypt_ips') == true ? __('active', 'wp-visitorflow') : __('inactive', 'wp-visitorflow') ); ?></label>
							<p class="description">
								<?php _e('Anonymize IP addresses of remote clients by encryption.', 'wp-visitorflow'); ?>
								<?php _e('This option is recommended to fulfill data privacy rules in several countries.', 'wp-visitorflow'); ?><br>
							</p>
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
						<th scope="row" colspan="2"><h3><?php _e('HTTP User agent String', 'wp-visitorflow'); ?></h3></th>
					</tr>
					<tr>
						<th scope="row">
							<label for="store_useragent"><?php echo __('Store user-agent string', 'wp-visitorflow'); ?>:</label>
						</th>
						<td>
							<input id="store_useragent" type="checkbox" value="1" name="store_useragent" <?php echo self::$config->getSetting('store_useragent') == true? 'checked="checked"' : ''; ?>>
							<label for="store_useragent"><?php echo sprintf(__('Active (default: %s)', 'wp-visitorflow'), self::$config->getDefaultSettings('store_useragent') == true ? __('active', 'wp-visitorflow') : __('inactive', 'wp-visitorflow') ); ?></label>
							<p class="description">
								<?php _e('Store the full HTTP user-agent string submitted by remote clients.', 'wp-visitorflow'); ?>
							</p>
						</td>
					</tr>

					</tbody>
					</table>
				</div>

				<br>
				<br>

<?php

	global $wp_roles;
	$capabilities = array();

	foreach ($wp_roles->roles as $role ) {
		foreach ($role['capabilities'] as $key => $value ) {
			if( substr($key,0,6) != 'level_' ) {
				$capabilities[$key] = 1;
			}
		}
	}
	ksort( $capabilities );

	$read_access_capability = self::$config->getSetting('read_access_capability');

	$options = '';
	foreach( $capabilities as $key => $value ) {
		if( $key == $read_access_capability ) { $selected = " SELECTED"; }
		else { $selected = ""; }
		$options .= '<option value="' .$key .'"' . $selected . '>' . $key . '</option>';
	}
?>

				<div class="wpvf-background">
					<table class="form-table">
					<tbody>

					<tr>
						<th scope="row" colspan="2"><h3><?php _e('Access to WP VisitorFlow', 'wp-visitorflow'); ?></h3></th>
					</tr>

					<tr>
						<th scope="row"><label for="read_access_capability"><?php _e('Access to data and visualizations', 'wp-visitorflow')?>:</label></th>
						<td>
							<select id="read_access_capability" name="read_access_capability"><?php echo $options;?></select>
							<label for="read_access_capability"><?php echo __('Default:', 'wp-visitorflow') . ' ' . self::$config->getDefaultSettings('read_access_capability'); ?></label>
						</td>
					</tr>
<?php

	$admin_access_capability = self::$config->getSetting('admin_access_capability');

	$options = '';
	foreach( $capabilities as $key => $value ) {

		if( $key == $admin_access_capability ) { $selected = " SELECTED"; }
		else { $selected = ""; }
		$options .= '<option value="' .$key .'"' . $selected . '>' . $key . '</option>';
	}
?>
					<tr>
						<th scope="row"><label for="admin_access_capability"><?php _e("Access to plugins's settings", 'wp-visitorflow')?>:</label></th>
						<td>
							<select id="admin_access_capability" name="admin_access_capability"><?php echo $options;?></select>
							<label for="admin_access_capability"><?php echo __('Default:', 'wp-visitorflow') . ' ' . self::$config->getDefaultSettings('admin_access_capability'); ?></label>
							<br>
							<br>
							<p class="description"><?php echo sprintf(__('See %s for details on capability levels.', 'wp-visitorflow'), '<a target=_blank href="http://codex.wordpress.org/Roles_and_Capabilities">' . __('WordPress Roles and Capabilities', 'wp-visitorflow') . '</a>'); ?></p>
							<p class="description"><?php echo __('Hint: manage_network = Super Admin Network, manage_options = Administrator, edit_others_posts = Editor, publish_posts = Author, edit_posts = Contributor, read = Everyone.', 'wp-visitorflow'); ?></p>
						</td>
					</tr>

					</tbody>
					</table>
				</div>

				<?php submit_button(); ?>
				</form>

			</div>

			<!-- Documentation -->
			<div class="col-xs-12 col-sm-6">

				<div class="wpvf-docu-background">
					<table class="form-table">
					<tbody>

					<tr>
						<td scope="row" style="padding:0 10px;">
							<h3><?php _e('Data Privacy Settings', 'wp-visitorflow'); ?></h3>
							<p>
								<strong><?php _e('IP address', 'wp-visitorflow'); ?>:</strong>
								<?php _e('By default, IP addresses of the visitors are only stored in encrypted form. If you need the IP addresses - in agreement with your private data policy - you can de-activate this encryption.', 'wp-visitorflow'); ?>
							</p>
							<br>

							<p>
								<strong><?php _e('HTTP user-agent strings', 'wp-visitorflow'); ?>:</strong>
								<?php _e('By default, the full HTTP user-agent strings submitted by the clients are not stored.', 'wp-visitorflow'); ?>
								<?php _e('Sometimes, you want to see the full strings, because they can be helpful in the identification of unknown crawlers and search engines.', 'wp-visitorflow'); ?>
								<?php _e('All stored full HTTP user-agent strings can easily be deleted (see Settings/Maintenance).', 'wp-visitorflow'); ?>
							</p>
							<br>

							<p>
								<strong><?php _e('Access to WP VisitorFlow', 'wp-visitorflow'); ?>:</strong>
								<?php _e('You can restrict the access to WP VisitorFlow data and settings.', 'wp-visitorflow'); ?>
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
