<?php
/**
 * The class WP_VisitorFlow_Page is the fundamental class for the display of admin pages in WP VisitorFlow
 *
 * @package    WP-VisitorFlow
 * @author     Onno Gabriel
 **/

// Prevent calls from outside of WordPress
defined( 'ABSPATH' ) || exit;

if (! class_exists("WP_VisitorFlow_Admin_Page")) :	// Prevent multiple class definitions

class WP_VisitorFlow_Admin_Page
{
	protected $queries;
	protected $tabs;
	protected $current_tab;

	protected $read_access_capability;

	/**
	 * Constructor
	 * @param $read_access_capability - $string
	 * @param (optional) $queries - (array)
	 * @param (optional) $tabs - (array)
	 */
	public function __construct( $read_access_capability, $queries = false, $tabs = false ) {

		$this->read_access_capability = $read_access_capability;
		$this->queries = $queries;

		$this->tabs = $tabs;
		if ($tabs) {
			reset($this->tabs);
			$this->queries['tab'] = key($this->tabs);
		}

		// Get user action
		$this->getUserAction();

	}

	/**
	 * Get user action
	 */
	private function getUserAction() {
		if (array_key_exists('tab', $_GET) ) {
			$this->queries['tab'] = $_GET['tab'];
		}
	}

	/**
	 * Set tabs
	 */
	public function setTabs($tabs) {
		$this->tabs = $tabs;
	}

	/**
	 * Set queries
	 */
	public function setQueries($queries) {
		$this->queries = $queries;
	}

	/**
	 * Get current tab
	 */
	public function get_current_tab() {
		return $this->queries['tab'];
	}


	/**
	 * Print page header
	 */
	public function printHeader($title, $subtitle = false) {
?>
		<div class="wrap">
			<div style="float:left;">
				<img src="<?php echo plugin_dir_url( __FILE__ ) . '../../assets/images/Logo_250.png'; ?>" align="left" width="80" height="80" alt="Logo" />
			</div>
			<h1><?php echo $title; ?></h1>
			<p><?php echo $subtitle; ?>&nbsp;</p>
			<div style="clear:both;"></div>
<?php
		$this->printPageMenu();
	}

	/**
	 * Print page footer
	 */
	public function printFooter() {
?>
		</div>
<?php
	}

	/**
	 * Print page menu
	 */
	public function printPageMenu() {
		$modes = array('wpvf_menu' 				=> __('Overview', 'wp-visitorflow'),
					   'wpvf_mode_website'		=> __('Full Website', 'wp-visitorflow'),
					   'wpvf_mode_singlepage' 	=> __('Single Page', 'wp-visitorflow')
						);


		$current_page = 'wp_visitorflow_overview';
		if (isset($_GET['page'])){
			$current_page = $_GET['page'];
		}

		echo '<div>';
		echo '<nav class="nav-tab-wrapper">';
		foreach ($modes as $page => $title) {
			if (current_user_can($this->read_access_capability) ) {
				if ($current_page == $page) {
					echo '<a class="nav-tab nav-tab-active" href="?page=' . $page . '">' . $title . '</a>';
				}
				else {
					echo '<a class="nav-tab" href="?page=' . $page . '">' . $title . '</a>';
				}
			}
		}
		echo '</nav>';
		echo '</div>';
		echo '<div style="clear:both;"></div>';
	}

	/**
	 * Print the tabs menu
	 */
	public function printTabsMenu($new_tabs = false) {

		if ($new_tabs) {
			$this->tabs = $new_tabs;
			reset($this->tabs);
		}

		if (! $this->tabs) { return; }

		$query_string = '';
		foreach ($this->queries as $key => $value) {
			if ($key != 'tab') {
				$query_string .= '&amp;' . $key . '=' . $value;
			}
		}

		echo '<div>';
		foreach ($this->tabs as $tab => $props) {
			if (current_user_can($props['min_role']) ) {
				if ($this->queries['tab'] == $tab){
					echo '<a class="wpvf-menulink-active" href="?tab=' . $tab . $query_string . '">' .  $props['title'] . '</a>';
				}
				else {
					echo '<a class="wpvf-menulink" href="?tab=' . $tab . $query_string . '">' .  $props['title'] . '</a>';
				}
			}
		}
		echo '</div>';
?>
		<div style="clear:both;"></div>
<?php
	}
}

endif;	// Prevent multiple class definitions
