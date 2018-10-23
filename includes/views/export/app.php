<?php
	if (! is_admin() || ! current_user_can( self::$config->getSetting('admin_access_capability') ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	require_once WP_VISITORFLOW_PLUGIN_PATH . 'vendor/autoload.php';
	use Endroid\QrCode\QrCode;

	if (! version_compare( get_bloginfo('version'), '4.7', '>=' ) ) {

?>

	<br />
	<div class="wpvf_warning">
		<p><strong><?php echo __('Not available.', 'wp-visitorflow'); ?></strong></p>
		<p><?php echo __('WordPress version 4.7 or higher required to use this functionality.', 'wp-visitorflow'); ?></p>
	</div>
<?php
	}

	// Print settings menu
	else {


		// Save new settings?
		if (array_key_exists('wpvf_save_settings', $_POST)) {

			// Enable app access?
			self::$config->setSetting('enable_app_access', false, 0);
			if (array_key_exists('enable_app_access', $_POST)) {
				self::$config->setSetting('enable_app_access', true, 0);
			}

			// Save settings
			self::$config->saveSettings();
		}


		$granted_tokens = self::$config->getSetting('app_granted_tokens');

		// Remove access from app?
		$del_token = '';
		if ( array_key_exists('del_token', $_POST) ) {
			$del_token = htmlspecialchars( stripslashes( $_POST['del_token'] ) );
			if (! array_key_exists('confirm_del', $_POST)) {
?>
				<br />
				<br />
				<div class="wpvf_warning">
					<p><?php echo __('Do you really want to remove the access from this device?', 'wp-visitorflow'); ?><p>
					<form method="post">
						<input type="hidden" name="confirm_del" value="1" />
						<input type="hidden" name="del_token" value="<?php echo $del_token; ?>" />
						<?php submit_button(__('Yes, remove it!', 'wp-visitorflow'), 'delete', 'wpvf_confirm_reset'); ?>
					</form>
					<form action="?page=wpvf_admin_settings&amp;tab=app" id="wpvf_cancel" method="post">
						<?php submit_button(__('Cancel', 'wp-visitorflow'), 'no'); ?>
					</form>
				</div>
				<br />
<?php
			}
			else {
				$kept_granted_tokens = array();
				foreach($granted_tokens as $token => $value) {
					if ($del_token != $token) {
						$kept_granted_tokens[$token] = $value;
					}
				}
				self::$config->setSetting('app_granted_tokens', $kept_granted_tokens, 1);
				$granted_tokens = $kept_granted_tokens;

				$message = __('Device access removed.', 'wp-visitorflow') . '<br />';
				echo '<p class="wpvf_message">' . $message . "</p><br />\n<br />\n";
					$del_token = '';
			}
		}

?>
<h2><?php _e('Access from Mobile App', 'wp-visitorflow'); ?></h2>

<div class="wpvf-background">
	<form id="wpvf_settings" method="post">
		<input type="hidden" name="tab" value="app" />
		<input type="hidden" name="wpvf_save_settings" value="1" />
		<table class="form-table">
			<tbody>

			<tr>
				<th scope="row"><?php echo __('Enable App Access', 'wp-visitorflow'); ?>:</th>
				<td>
					<input id="enable_app_access" type="checkbox" value="1" name="enable_app_access" <?php echo self::$config->getSetting('enable_app_access') == true? 'checked="checked"' : ''; ?>>
					<label for="enable_app_access"><?php echo sprintf(__('Active (default: %s)', 'wp-visitorflow'), self::$config->getDefaultSettings('enable_app_access') == true ? __('active', 'wp-visitorflow') : __('inactive', 'wp-visitorflow') ); ?></label>
					<p class="description"><?php echo __('Enabling access to WP VisitorFlow data from the mobile WordPress VisitorFlow app (Android only).', 'wp-visitorflow'); ?></p>
<?php
			submit_button();
			if (self::$config->getSetting('enable_app_access') == true) {
?>
			<strong><?php echo __('Hint', 'wp-visitorflow'); ?>:</strong> <?php echo __('Access from the mobile app to this website will be included into the recorded statistics.', 'wp-visitorflow'); ?><br />
			<?php echo __('To prevent this, just include the string "/index.php/wp-json/wp-visitorflow" to the <a class="wpvf" href="?page=wpvf_admin_settings&tab=storing">pages URL exclusion list</a>.', 'wp-visitorflow'); ?><br />
<?php
			}
?>
				</td>
			</tr>
			</tbody>
		</table>
	</form>
</div>

<br />
<br />

<?php

		if ( self::$config->getSetting('enable_app_access')== true ) {

?>
<div class="wpvf-background">
	<h2><?php _e('Access from Mobile App', 'wp-visitorflow'); ?></h2>
		<table class="form-table">
		<tbody>
			<tr>
				<th scope="row"><?php echo __('Remote Clients', 'wp-visitorflow'); ?>:</th>
				<td>
<?php

			if (count($granted_tokens) > 0) {
?>
				<?php echo __('Remote client(s) with access to WP VisitorFlow statistics on this website:', 'wp-visitorflow'); ?><br />
				<table class="wpvftable">
				<tr>
					<th><?php echo __('No.', 'wp-visitorflow'); ?></th>
					<th><?php echo __('Registered since', 'wp-visitorflow'); ?></th>
					<th><?php echo __('Manufacturer', 'wp-visitorflow'); ?></th>
					<th><?php echo __('Model', 'wp-visitorflow'); ?></th>
					<th><?php echo __('Platform', 'wp-visitorflow'); ?></th>
					<th><?php echo __('Version', 'wp-visitorflow'); ?></th>
					<th><?php echo __('Device UUID', 'wp-visitorflow'); ?></th>
					<th><?php echo __('Action', 'wp-visitorflow'); ?></th>
				</tr>

<?php
				$no = 0;
				foreach($granted_tokens as $token => $value) {
					if (! $del_token || $del_token == $token) {
						$no++;
						echo '<tr' . ($no % 2 == 0 ? ' class="darker"' : '') . '>';
						echo '<td>' . $no . '</td>';
						echo '<td>' . date_i18n( get_option( 'date_format' ), $value['register_timestamp'] )
								. " " . date('H:i:s', $value['register_timestamp']) . '</td>';
						echo '<td>' . $value['model'] . '</td>';
						echo '<td>' . $value['manufacturer'] . '</td>';
						echo '<td>' . $value['platform'] . '</td>';
						echo '<td>' . $value['version'] . '</td>';
						echo '<td>' . $value['uuid'] . '</td>';
?>
						<td>
							<form method="post">
								<input type="hidden" name="page" value="wpvf_admin_settings" />
								<input type="hidden" name="tab" value="app" />
								<input type="hidden" name="del_token" value="<?php echo $token; ?>" />
								<?php submit_button(__('Remove access', 'wp-visitorflow'), 'delete', 'wpvf_confirm_reset'); ?>
							</form>
						</td>
					</tr>
<?php
					}
				}
?>
				</table>
<?php
			}
			else {
				echo __('No remote client with access yet.', 'wp-visitorflow');
			}
?>
				</td>
			</tr>
<?php
			$seconds_left = 0;
			$code_string = '';
			$image_string = '';

			if ( array_key_exists('showQRCode', $_POST) ) {

				include_once WP_VISITORFLOW_PLUGIN_PATH . '/includes/functions/wp-visitorflow-crypto.php';
				$token = wpvf_getToken( self::$config->getSetting('app_token_length') );

				self::$config->setSetting('app_new_token', $token);
				self::$config->setSetting('app_new_token_expire_timestamp', time() + 60);

				// Save settings
				self::$config->saveSettings();

				$site_url = site_url();
				$code_string =  $site_url . '|' . $token;


				// Generate QR Code
				$qrCode = new QrCode();
				$qrCode
					->setText( $code_string )
					->setSize(250)
					->setPadding(10)
					->setErrorCorrection('high')
					->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0))
					->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0))
					->setLabel(__('Scan this code', 'wp-visitorflow'))
					->setLabelFontSize(16)
					->setImageType(QrCode::IMAGE_TYPE_PNG)
				;

				$image_string = 'data:image/png;base64,' . base64_encode($qrCode->get('png'));
				$seconds_left = 60;
			} // showQRCode

?>
			<tr>
				<th scope="row"><?php echo __('How to:', 'wp-visitorflow'); ?></th>
				<td>
					<img src="<?php echo WP_VISITORFLOW_PLUGIN_URL . 'assets/images/App_Demo.png'; ?>" align="right" width="300" height="340" alt="App-Demo" />
					<h2><?php echo __('How to receive this website\'s statistics on your mobile device:', 'wp-visitorflow'); ?></h2>
					<ol>
						<li>
							<?php echo __('Download and install the Android app "WP VisitorFlow" on your mobile device.', 'wp-visitorflow'); ?><br />
							<a href="https://play.google.com/store/apps/details?id=de.datacodedesign.wordpressvisitorflow">
								<img src="<?php echo WP_VISITORFLOW_PLUGIN_URL . 'assets/images/GooglePlay.png'; ?>" width="129" height="50" alt="Google Play" />
							</a><br />
							<br />
						</li>
						<li>
							<?php echo __('Generate a code containing this website\'s URL and an access token for the mobile app:', 'wp-visitorflow'); ?>
							<form method="post">
								<input type="hidden" name="page" value="wpvf_admin_settings" />
								<input type="hidden" name="tab" value="app" />
								<input type="hidden" name="showQRCode" value="1" />
							<button type="submit" id="showQRCodeButton" style="visibility:<?php echo $seconds_left > 0 ? 'hidden' : 'visible'; ?>"><?php echo __('Generate Code', 'wp-visitorflow'); ?></button>
							</form>
							<div id="qr_code" style="display:<?php echo $seconds_left > 0 ? 'block' : 'none'; ?>">
								<img  src="<?php echo $image_string ?>" style="float:left;margin-right:2em;">
								<?php echo __('You can also enter this code manually:', 'wp-visitorflow'); ?><br />
								<br />
								<table border="0">
								<tr>
									<td align="right">
										<?php echo __('Website\'s URL', 'wp-visitorflow'); ?>:<br />
										<?php echo __('Token', 'wp-visitorflow'); ?>:
									</td>
									<td><strong>
										<?php echo $site_url; ?><br />
										<?php echo $token; ?>
									</strong></td>
								</tr>
								</table>
							</div>
							<div style="clear:both;"></div>
							<span id="timer_div"></span><br />
							<br />
						</li>
						<li>
							<?php echo __('In app: Add access to this website by scanning the generated code.', 'wp-visitorflow'); ?>
						</li>
					</ol>
				</td>
			</tr>
		</tbody>
		</table>
</div>

<script language="javascript" type="text/javascript">
	var seconds_left = <?php echo $seconds_left; ?>;
	var interval = setInterval(function() {
		document.getElementById('timer_div').innerHTML = '<font color="red"><?php echo __('This code is valid for ', 'wp-visitorflow'); ?>' + --seconds_left + ' <?php echo __('seconds', 'wp-visitorflow'); ?>.</font>';

		if (seconds_left <= 0)
		{
			document.getElementById('timer_div').innerHTML = '';
			clearInterval(interval);
			document.getElementById('qr_code').style.visibility = 'hidden';
			document.getElementById('showQRCodeButton').style.visibility = 'visible';
		}
	}, 1000);
</script>
<?php
		} // enable_app_access == true


	} // get_bloginfo('version') >= '4.7'
