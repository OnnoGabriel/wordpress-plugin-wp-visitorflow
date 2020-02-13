<?php
	$db_info = self::$config->getSetting('db_info');

	$screen = get_current_screen();

	// Print Info Boxes
	$info_box_class = '';
	$title_class = 'wpvf_info_title';
	$arrow_class = 'wpvf_info_arrow';
	if (wp_is_mobile() || $screen->id == "dashboard" )  {
		$info_box_class .= ' wpvf_info_mobile';
		$title_class = 'wpvf_info_title_mobile';
		$arrow_class = 'wpvf_info_arrow_mobile';
	}

	$counters = array(
		array(
			'label' => __('Visitors', 'wp-visitorflow'),
			'label_period' => __('last 24h', 'wp-visitorflow'),
			'db_info_column' => 'visits_24h_count',
			'db_info_compare' => 'visits_24h_before',
			'db_info_average' => 'visits_count',
			'hours' => 24,
			'color' => 'blue'
		),
		array(
			'label' => __('Visitors', 'wp-visitorflow'),
			'label_period' => __('last 7 days', 'wp-visitorflow'),
			'db_info_column' => 'visits_7d_count',
			'db_info_compare' => 'visits_7d_before',
			'db_info_average' => 'visits_count',
			'hours' => 24*7,
			'color' => 'darkblue'
		),
		array(
			'label' => __('Page views', 'wp-visitorflow'),
			'label_period' => __('last 24h', 'wp-visitorflow'),
			'db_info_column' => 'hits_24h_count',
			'db_info_compare' => 'hits_24h_before',
			'db_info_average' => 'hits_count',
			'hours' => 24,
			'color' => 'pink'
		),
		array(
			'label' => __('Page views', 'wp-visitorflow'),
			'label_period' => __('last 7 days', 'wp-visitorflow'),
			'db_info_column' => 'hits_7d_count',
			'db_info_compare' => 'hits_7d_before',
			'db_info_average' => 'hits_count',
			'hours' => 24*7,
			'color' => 'darkpink'
		),
	);
	?>
	<div class="container-fluid">
		<div class="row">
	<?php

	foreach ($counters as $counter) {
		$count = $db_info[ $counter['db_info_column'] ];
		if (! $count) { $count = 0; }

		$arrow_html = '';
		$average_html = '';

		$minutes_run = $db_info['db_minutes_run'];
		if ($counter['hours'] <= 24) { $minutes_run = $db_info['minutes_run']; }

		if ($minutes_run > 60*$counter['hours']) { // if db (re)start was later than the hour interval
			$compare = $db_info[ $counter['db_info_compare'] ];

			if ($compare > 0) {
				$arrow = '0';
				$change = 100 * ($count - $compare) / $compare;
				$changes = array(5, 10, 20, 50, 100, 200);
				for ($i = 0; $i < count($changes); $i++) {
					if ( $change > $changes[$i] ) {
						$arrow = ($i+1) . 'p';
					}
					elseif ( - $change > $changes[$i] ) {
						$arrow = ($i+1) . 'm';
					}
				}
				$arrow_html = '<img class="' . $arrow_class . '" src="' . wp-visitorflow_PLUGIN_URL . 'assets/images/Arrow-' . $arrow . '.png" alt="arrow" />';
				$average = $db_info[ $counter['db_info_average'] ] * $counter['hours'] / ($db_info['db_minutes_run'] / 60);
				$average_html = '(&#216; ' . round($average) . ')';
			}
		}
?>
		<div class="col-xs-6 col-sm-6 col-md-6 col-lg-3" style="padding:0;">
			<div class="wpvf_info wpvf_info_<?php echo $counter['color'] . $info_box_class; ?>">
				<?php echo $arrow_html; ?>
				<span class="<?php echo $title_class; ?>"><?php echo number_format_i18n($count); ?></span><br>
				<?php echo  $counter['label']; ?><br>
				<?php echo  $counter['label_period']; ?><br>
				<?php echo $average_html; ?><br>
			</div>
		</div>
<?php
	}
?>
		<div class="col-xs-6 col-sm-6 col-md-6 col-lg-3" style="padding:0;">
			<div class="wpvf_info wpvf_info_green<?php echo $info_box_class; ?>">
				<span class="<?php echo $title_class; ?>"><?php echo  str_replace(" ", "&nbsp;", WP_VisitorFlow_Admin::getNiceTimeDifference( self::$config->getSetting('db-startdatetime'), self::$config->getDatetime() ) ); ?></span><br>
				<?php echo __('since the first record on', 'wp-visitorflow'); ?><br>
				<?php echo date_i18n( get_option( 'date_format' ), strtotime(self::$config->getSetting('db-startdatetime'))); ?>
			</div>
		</div>
		<div class="col-xs-6 col-sm-6 col-md-6 col-lg-3" style="padding:0;">
			<div class="wpvf_info wpvf_info_darkred<?php echo $info_box_class; ?>">
				<span class="<?php echo $title_class; ?>"><?php echo number_format_i18n($db_info['bots_count']); ?></span><br>
				<?php echo __('Recorded bots visits', 'wp-visitorflow'); ?><br>
				&#216; <?php echo sprintf( __('%s per day', 'wp-visitorflow'),
				number_format_i18n($db_info['bots_count'] * $db_info['counters_perdayfactor']) ); ?>
			</div>
		</div>
	</div>
</div>
<?php
