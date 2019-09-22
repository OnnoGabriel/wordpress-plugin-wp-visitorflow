<?php
	if (! is_admin() || ! current_user_can( self::$config->getSetting('read_access_capability') ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	$db_info = self::$config->getSetting('db_info');

?>
	<table class="wpvftable">
	<tr>
		<th><?php echo __('Property', 'wp-visitorflow'); ?></th>
		<th><?php echo __('Value', 'wp-visitorflow'); ?></th>
	</tr>
	<tr class="darker">
		<td><?php echo __('Date of first record', 'wp-visitorflow'); ?></td>
		<td class="right">
			<strong><?php echo date_i18n(
				get_option( 'date_format' ),
				strtotime(
					self::$config->getSetting('db-startdatetime')
				)
			); ?></strong><br>
			(<?php echo sprintf(
				__('%s ago', 'wp-visitorflow'),
				WP_VisitorFlow_Admin::getNiceTimeDifference(
					self::$config->getSetting('db-startdatetime'),
					self::$config->getDatetime()
				)
			); ?>)
		</td>
	</tr>
	<tr>
		<td><?php echo __('Date pf first flow data', 'wp-visitorflow'); ?></td>
		<td class="right">
			<strong><?php echo date_i18n(
				get_option( 'date_format' ),
				strtotime( self::$config->getSetting('flow-startdatetime') )
			); ?></strong><br>
			(<?php echo sprintf(
				__('%s ago', 'wp-visitorflow'),
				WP_VisitorFlow_Admin::getNiceTimeDifference(
					self::$config->getSetting('flow-startdatetime'),
					self::$config->getDatetime()
				)
			); ?>)
		</td>
	</tr>
	<tr class="darker">
		<td>
			<?php echo __('Number of visits', 'wp-visitorflow'); ?>
		</td>
		<td class="right">
			<strong><?php echo number_format_i18n($db_info['visits_count']); ?></strong><br>
			(<?php echo number_format_i18n(round($db_info['visits_count'] * $db_info['db_perdayfactor'], 0)); ?> <?php echo __('per day', 'wp-visitorflow'); ?>)
		</td>
	</tr>
	<tr>
		<td>
			<?php echo __('Number of page views', 'wp-visitorflow'); ?>
		</td>
		<td class="right">
			<strong><?php echo number_format_i18n($db_info['hits_count']); ?></strong><br>
			(<?php echo number_format_i18n(round($db_info['hits_count'] * $db_info['db_perdayfactor'], 0)); ?> <?php echo __('per day', 'wp-visitorflow'); ?>)
		</td>
	</tr>
	<tr class="darker">
		<td>
			<?php echo __('Recorded bot visits', 'wp-visitorflow'); ?>
		</td>
		<td class="right">
			<strong><?php echo number_format_i18n($db_info['bots_count']); ?></strong><br>
			(<?php echo number_format_i18n(round($db_info['bots_count'] * $db_info['counters_perdayfactor'], 0)); ?> <?php echo __('per day', 'wp-visitorflow'); ?>)
		</td>
	</tr>
	<tr>
		<td>
			<?php echo __('Excluded page views', 'wp-visitorflow'); ?>
		</td>
		<td class="right">
			<strong><?php echo number_format_i18n($db_info['exclusions_count']); ?></strong><br>
			(<?php echo number_format_i18n(round($db_info['exclusions_count'] * $db_info['counters_perdayfactor'], 0)); ?> <?php echo __('per day', 'wp-visitorflow'); ?>)
		</td>
	</tr>
	<tr class="darker">
		<td><?php echo __('Number of internal pages', 'wp-visitorflow'); ?></td>
		<td class="right"><strong><?php echo number_format_i18n($db_info['pages_internal_count']); ?></strong></td>
	</tr>
	<tr>
		<td><?php echo __('Number of external pages (referrers)', 'wp-visitorflow'); ?></td>
		<td class="right"><strong><?php echo number_format_i18n($db_info['pages_count'] - $db_info['pages_internal_count']); ?></strong></td>
	</tr>
	<tr class="darker">
		<td><?php echo __('Recorded HTTP User-Agent strings', 'wp-visitorflow'); ?></td>
		<td class="right"><strong><?php echo number_format_i18n($db_info['meta_useragents_count']); ?></strong></td>
	</tr>
	</table>

