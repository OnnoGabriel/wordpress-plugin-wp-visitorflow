<?php
/**
 *	Data plots for WP VisitorFlow.
 *
 * @package    WP-VisitorFlow
 * @author     Onno Gabriel
 **/

// Prevent calls from outside of WordPress
defined( 'ABSPATH' ) || exit;

if (! class_exists("WP_VisitorFlow_Admin_Plots")) :	// Prevent multiple class definitions

class WP_VisitorFlow_Admin_Plots
{

	/**
	 * Plot Diagram Function using jqplot library
	 **/
	public static function lineChart( $title, $chart_data, $chart_options = array('id' => 'wpvfplot', 'width' => '600px', 'height' => '400px') ) {

		wp_enqueue_style('jqplot_css', WP_VISITORFLOW_PLUGIN_URL  . 'assets/css/jquery.jqplot.css');
		wp_register_script('jqplot_js', WP_VISITORFLOW_PLUGIN_URL. 'assets/js/jquery.jqplot.min.js' );
		wp_enqueue_script( 'jqplot_js' );
		wp_register_script('dateAxisRenderer_js', WP_VISITORFLOW_PLUGIN_URL . 'assets/js/jqplot.dateAxisRenderer.min.js' );
		wp_enqueue_script( 'dateAxisRenderer_js' );
		wp_register_script('enhancedLegendRenderer_js', WP_VISITORFLOW_PLUGIN_URL . 'assets/js/jqplot.enhancedLegendRenderer.min.js' );
		wp_enqueue_script( 'enhancedLegendRenderer_js' );
		wp_register_script('canvasAxisLabelRenderer_js', WP_VISITORFLOW_PLUGIN_URL . 'assets/js/jqplot.canvasAxisLabelRenderer.min.js' );
		wp_enqueue_script( 'canvasAxisLabelRenderer_js' );
		wp_register_script('canvasTextRenderer_js', WP_VISITORFLOW_PLUGIN_URL . 'assets/js/jqplot.canvasTextRenderer.min.js' );
		wp_enqueue_script( 'canvasTextRenderer_js' );

		// Create data and label strings
		$data_string = '';
		$label_string = '';
		foreach ($chart_data as $series) {
			if ($label_string) { $label_string .= ','; }
			$label_string .= "'" . $series['label'] . "'";

			$data = $series['data'];
			$string = '';
			foreach ($data as $x => $y) {
				if ($string) { $string .= ','; }
				$string .= "['" . $x . "'," . $y . "]";
			}

			if ($data_string) { $data_string .= ','; }
			$data_string .= '[' . $string . ']';
		}

?>
		<div id="<?php echo $chart_options['id']; ?>" style="height:<?php echo $chart_options['height']; ?>;width:<?php echo $chart_options['width']; ?>"></div>

		<script>
			jQuery(document).ready(function(){
				var data=[<?php echo $data_string; ?>];

				var plot1 = jQuery.jqplot('<?php echo $chart_options['id']; ?>', data, {
					title:'<?php echo $title; ?>',
					seriesDefaults: {
						shadow: false,
						showMarker:true
					},
					legend:{
						show:true,
						renderer: jQuery.jqplot.EnhancedLegendRenderer,
						rendererOptions:{
							seriesToggleReplot: {
								resetAxes: true
							}
						},
						placement: 'inside',
						location:'ne',
						labels: [<?php echo $label_string; ?>]
					},
					axes:{
						xaxis:{
							renderer:jQuery.jqplot.DateAxisRenderer,
							tickOptions:{formatString: '<?php echo __('%d-%b %y', 'wp-visitorflow'); ?>'},
						},
						yaxis: {
							label: 'Counts',
							labelRenderer:jQuery.jqplot.CanvasAxisLabelRenderer,
							min: 0
						}
					},
					grid: {
						shadow: false,
						backgroundColor: '#fcfcf8'
					},
					series:[{lineWidth:4, markerOptions:{style:'square'}}]
				});
			});
		</script>
<?php
	}


	/**
	 * Pie Chart Function using jqplot library
	 **/
	public static function pieChart($title, $chart_data, $chart_options = array('id' => 'piechart', 'width' => '600px', 'height' => '400px') ) {

		wp_enqueue_style('jqplot_css',  plugin_dir_url( __FILE__ )  . '../../assets/css/jquery.jqplot.css');
		wp_register_script('jqplot_js',  plugin_dir_url( __FILE__ ) . '../../assets/js/jquery.jqplot.min.js' );
		wp_enqueue_script( 'jqplot_js' );
		wp_register_script('pieRenderer_js',  plugin_dir_url( __FILE__ ) . '../../assets/js/jqplot.pieRenderer.min.js' );
		wp_enqueue_script( 'pieRenderer_js' );

		$pie_data = '';
		arsort($chart_data);
		$entries_count = 0;
		$others_count = 0;
		foreach($chart_data as $label => $count) {
			if ($entries_count < 10) {
				if ($pie_data) { $pie_data .= ','; }
				if (! $label) { $label .= __('Unknown', 'wp-visitorflow'); }
				$pie_data .= "['" . $label . "'," . $count . "]";
				$entries_count ++;
			}
			else {
				$others_count++;
			}
		}
		if ($others_count) {
			$pie_data .= ",['" . __('Others', 'wp-visitorflow') . "'," . $others_count . "]";
		}

		if (! array_key_exists('legendrows', $chart_options)) {
			$chart_options['legendrows'] = 3;
		}

		if (! isset($chart_options['style'])) {
			$chart_options['style'] = '';
		}

?>
		<div id="<?php echo $chart_options['id']; ?>" style="height:<?php echo $chart_options['height']; ?>;width:<?php echo $chart_options['width']; ?>;<?php echo $chart_options['style']; ?>"></div>
		<script>
			jQuery(document).ready(function(){
				var data=[<?php echo $pie_data; ?>];

				jQuery.jqplot.config.enablePlugins = true;
				var pieplot = jQuery.jqplot('<?php echo $chart_options['id']; ?>', [data], {
					title:'<?php echo $title; ?>',
					grid: {
						 drawBorder: false,
						 background: '#fff',
						 shadow: false
					},
					seriesDefaults: {
						renderer: jQuery.jqplot.PieRenderer,
						rendererOptions: { showDataLabels: true }
					},
					legend: {
						show:true,
						rendererOptions: {
<?php
		if (isset($chart_options['legendcolumns'])) {
?>							numberColumns: <?php echo $chart_options['legendcolumns']; ?>
<?php	}
		elseif ($chart_options['legendrows']) {
?>							numberRows: <?php echo $chart_options['legendrows']; ?>
<?php	}
?>
						},
						location: 's'
					}
				});
			});
		</script>
<?php
	}


	/**
	 * Plot Filled Area Diagram using jqplot library
	 **/
	public static function filledAreaPlot($title, $chart_data, $chart_options = array('id' => 'wpvfplot', 'width' => '600px', 'height' => '400px')  ) {

		wp_enqueue_style('jqplot_css',  plugin_dir_url( __FILE__ )  . '../../assets/css/jquery.jqplot.css');
		wp_register_script('jqplot_js',  plugin_dir_url( __FILE__ ) . '../../assets/js/jquery.jqplot.min.js' );
		wp_enqueue_script( 'jqplot_js' );
		wp_register_script('dateAxisRenderer_js',  plugin_dir_url( __FILE__ ) . '../../assets/js/jqplot.dateAxisRenderer.min.js' );
		wp_enqueue_script( 'dateAxisRenderer_js' );
		wp_register_script('enhancedLegendRenderer_js',  plugin_dir_url( __FILE__ ) . '../../assets/js/jqplot.enhancedLegendRenderer.min.js' );
		wp_enqueue_script( 'enhancedLegendRenderer_js' );
		wp_register_script('canvasAxisLabelRenderer_js',  plugin_dir_url( __FILE__ ) . '../../assets/js/jqplot.canvasAxisLabelRenderer.min.js' );
		wp_enqueue_script( 'canvasAxisLabelRenderer_js' );
		wp_register_script('canvasTextRenderer_js',  plugin_dir_url( __FILE__ ) . '../../assets/js/jqplot.canvasTextRenderer.min.js' );
		wp_enqueue_script( 'canvasTextRenderer_js' );

		// Create data and label strings
		$data_string = '';
		$label_string = '';
		foreach ($chart_data as $series) {
			if ($label_string) { $label_string .= ','; }
			$label_string .= "'" . $series['label'] . "'";

			$data = $series['data'];
			$string = '';
			foreach ($data as $x => $y) {
				if ($string) { $string .= ','; }
				$string .= "['" . $x . "'," . $y . "]";
			}

			if ($data_string) { $data_string .= ','; }
			$data_string .= '[' . $string . ']';
		}

?>
		<div id="<?php echo $chart_options['id']; ?>" style="height:<?php echo $chart_options['height']; ?>;width:<?php echo $chart_options['width']; ?>"></div>

		<script>
			jQuery(document).ready(function(){
				var data=[<?php echo $data_string; ?>];

				var plot1 = jQuery.jqplot('<?php echo $chart_options['id']; ?>', data, {
					title:'<?php echo $title; ?>',
					stackSeries:true,
					seriesDefaults: {
						fill: true,
						shadow: false,
						showMarker:true
					},
					legend:{
						show:true,
						renderer: jQuery.jqplot.EnhancedLegendRenderer,
						rendererOptions:{
							seriesToggleReplot: {
								resetAxes: false
							}
						},
						placement: 'inside',
						location:'ne',
						labels: [<?php echo $label_string; ?>]
					},
					axes:{
						xaxis:{
							label: 'Hour of the Day',
							min: 0,
							max: 23,
							numberTicks:24
						},
						yaxis: {
							label: 'Counts',
							labelRenderer:jQuery.jqplot.CanvasAxisLabelRenderer,
							min: 0
						}
					},
					grid: {
						shadow: false,
						backgroundColor: '#fcfcf8'
					},
					series:[{lineWidth:4, markerOptions:{style:'square'}}]
				});
			});
		</script>
<?php
	}



}

endif;	// Prevent multiple class definitions
