<?php
/**
 * The class TimeframePage is used for most of the admin pages
 *
 * @package    WP-VisitorFlow
 * @author     Onno Gabriel
 **/

// Prevent calls from outside of WordPress
defined( 'ABSPATH' ) || exit;

if (! class_exists("WP_VisitorFlow_Admin_Page_Timeframe")) :	// Prevent multiple class definitions

class WP_VisitorFlow_Admin_Page_Timeframe extends WP_VisitorFlow_Admin_Page
{

	protected $timeframe_start = 0;
	protected $timeframe_stop = 0;

	/**
	 * Constructor
	 * @param $read_access_capability - $string
	 * @param (optional) $queries - (array)
	 * @param (optional) $tabs - (array)
	 */
	public function __construct($read_access_capability, $queries = false, $tabs = false){

		// Call the parent constructor (WP_VisitorFlow_Page::__construct)
		parent::__construct($read_access_capability, $queries, $tabs);

		// Get user action
		$this->getUserAction();
	}


	/**
	 * Get user action
	 */
	private function getUserAction() {
		if (array_key_exists('datetimestart', $_POST) && array_key_exists('datetimestop', $_POST)) {
			$this->timeframe_start = htmlspecialchars( stripslashes( $_POST['datetimestart'] ) );
			$this->timeframe_stop  = htmlspecialchars( stripslashes( $_POST['datetimestop'] ) );
			if (new DateTime($this->timeframe_stop) < new DateTime($this->timeframe_start)) {
				$this->timeframe_stop = $this->timeframe_start;
			}
		}
		elseif (array_key_exists('datetimestart', $_GET) && array_key_exists('datetimestop', $_GET)) {
			$this->timeframe_start = htmlspecialchars( stripslashes( $_GET['datetimestart'] ) );
			$this->timeframe_stop  = htmlspecialchars( stripslashes( $_GET['datetimestop'] ) );
			if (new DateTime($this->timeframe_stop) < new DateTime($this->timeframe_start)) {
				$this->timeframe_stop = $this->timeframe_start;
			}
		}
	}

	/**
	 * Set timeframe
	 */
	public function setTimeFrame($timeframe_start, $timeframe_stop) {
		$this->timeframe_start = $timeframe_start;
		$this->timeframe_stop = $timeframe_stop;
	}

	/**
	 * Get timeframe start
	 */
	public function getTimeframeStart() {
		return $this->timeframe_start;
	}

	/**
	 * Get timeframe end
	 */
	public function getTimeframeStop() {
		return $this->timeframe_stop;
	}

	/**
	 * Print the menu for the selection of the timeframe
	 */
	public function printTimeframeMenu($data_startdate = false) {
		$get_query = '';
		if ($this->queries) {
			foreach ($this->queries as $name => $value) {
				$get_query .= '&amp;' . $name . '=' . $value;
			}
		}

		$max_days_back = 999;
		if ($data_startdate) {

			$data_startdate = new DateTime( $data_startdate );
			$today = new DateTime();
			$date_diff = $today->diff($data_startdate);
			$max_days_back = $date_diff->format('%a');

			$timeframe_startDateTime = new DateTime($this->timeframe_start);

			if ($timeframe_startDateTime < $data_startdate ) {
				$this->timeframe_start = $data_startdate->format('Y-m-d');
			}
		}

		// End datetime ealrier than start datetime?
		if (new DateTime($this->timeframe_stop) < new DateTime($this->timeframe_start)) {
			$this->timeframe_stop = $this->timeframe_start;
		}

?>
		<br />
		<div style="float:left;padding-right:1em;">

			<table class="wpvfmenutable">
			<tr>
<?php

		$periods = array(
			array('days_back' => 0, 'label' => __('Today', 'wp-visitorflow'), 'start' => '+0', 'end' => '+0'),
			array('days_back' => 1, 'label' => __('Yesterday', 'wp-visitorflow'), 'start' => '-1', 'end' => '-1'),
			array('days_back' => 1, 'label' => __('Last 7 days', 'wp-visitorflow'), 'start' => '-7', 'end' => '+0'),
			array('days_back' => 7, 'label' => __('Last 14 days', 'wp-visitorflow'), 'start' => '-14', 'end' => '+0'),
			array('days_back' => 14, 'label' => __('Last 30 days', 'wp-visitorflow'), 'start' => '-30', 'end' => '+0'),
			array('days_back' => 30, 'label' => __('Last 60 days', 'wp-visitorflow'), 'start' => '-60', 'end' => '+0'),
			array('days_back' => 60, 'label' => __('Last 365 days', 'wp-visitorflow'), 'start' => '-365', 'end' => '+0')
		);

		foreach ($periods as $period) {
			if ($period['days_back'] <= $max_days_back) {
				$class = "border";
				if (   $this->timeframe_start == date( 'Y-m-d',  strtotime( $period['start'] . ' days') )
					&& $this->timeframe_stop == date( 'Y-m-d',  strtotime( $period['end'] . ' days') ) ) {
					$class = "border_active";
				}
				echo '<td class="' . $class . '">';
				echo '<a class="wpvf" href="?datetimestart=' . date( 'Y-m-d',  strtotime( $period['start'] . ' days') ) . '&amp;datetimestop=' . date( 'Y-m-d',  strtotime( $period['end'] . ' days') ) . $get_query .'">';
				echo $period['label'];
				echo '</a></td>';
			}
			else {
				echo '<td class="border inactive">';
				echo $period['label'];
				echo '</td>';
			}
			echo '</td>';
		}
?>
			</tr>
			<tr><td class="nohover" colspan="7">
			<form method="post">
<?php
		if ($this->queries) {
			foreach ($this->queries as $name => $value) {
?>
					<input type="hidden" name="<?php echo $name; ?>" value="<?php echo $value; ?>" />
<?php
			}
		}

?>
					<label for="datetimestart"><?php echo __('Start date:', 'wp-visitorflow'); ?></label>
					<input type="date" id="datetimestart" name="datetimestart" value="<?php echo $this->timeframe_start; ?>" />

					<label for="datetimestop"><?php echo __('End date:', 'wp-visitorflow'); ?></label>
					<input type="date" id="datetimestop" name="datetimestop" value="<?php echo $this->timeframe_stop; ?>" />

					<input type="submit" name="submit" value="<?php echo __('Go', 'wp-visitorflow'); ?>" />
				</form>
			</td></tr>
			</table>
		</div>
<?php
	}

}

endif;	// Prevent multiple class definitions
