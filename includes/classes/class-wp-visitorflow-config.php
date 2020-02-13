<?php
/**
 *	Config class for WP VisitorFlow.
 *
 * @package    WP-VisitorFlow
 * @author     Onno Gabriel
 **/

// Prevent calls from outside of WordPress
defined( 'ABSPATH' ) || exit;

if (! class_exists("WP_VisitorFlow_Config")) :	// Prevent multiple class definitions

class WP_VisitorFlow_Config
{
	private static $instance = NULL;

	protected $settings = array(); 		// Plugin's general options/settings
	protected $user_settings = array(); // User's options/settings
	protected $user_id = 0;				// ID of current Wordpress user
	protected $datetime = 0;	  		// current date and time

	/**
	 * Constructor
	 **/
	public function __construct() {

		// Set current datetime
		$this->datetime = new DateTime();

		// Load plugin's general options
		$this->loadSettings();
	}


	/**
     * Returns the *Singleton* instance of this class.
     *
     * @return Singleton The *Singleton* instance.
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
	}

	/**
	 * Load plugin's general settings
	 **/
	private function loadSettings() {
		$this->settings = get_option( 'wp_visitorflow' );

		// If settings do not exist, load and store default values:
		if (! is_array($this->settings) ) {
			$this->settings = $this->getDefaultSettings();
			$this->saveSettings();
		}
	}

	/**
	 * Save plugin's general settings
	 **/
	public function saveSettings() {
		update_option('wp_visitorflow', $this->settings);
	}

	/**
	 * Get plugin's default of general settings
	 **/
	public function getDefaultSettings($key = false) {
		$defaults =  array(
			'record_visitorflow' => true,
			'use_frontend_js' => false,
			'read_access_capability' => 'manage_options',
			'admin_access_capability' => 'manage_options',
			'encrypt_ips' => true,
			'store_useragent' => false,
			'minimum_time_between' => 60,
			'flowdata_storage_time' => 60,
			'exclude_unknown_useragents' => true,
			'exclude_bots' => true,
			'exclude_404' => false,
			'crawlers_exclude_list' => array(
				'Java/1.',
				'libwww-perl',
				'avira'
			),
			'pages_exclude_list' => array(
				'/wp-admin',
				'/wp-login',
				'/xmlrpc.php',
				'/wp-trackback.php',
				'/index.php/wp-json/wp-visitorflow'
			),
			'db-info-timestamp' => time(),
			'db-info' => array(),
			'last_dbclean_date' => 0,
			'last_aggregation_date' => 0,
			'data_aggregation_running' => false,
			'app_granted_tokens' => array(),
			'app_token_length' => 32
		);

		if ($key) {
			if (array_key_exists($key, $defaults)) {
				return $defaults[$key];
			}
			else {
				return false;
			}
		}
		else { return $defaults; }
	}

	/**
	 * Get single setting
	 **/
	public function getSetting($key, $default = null) {
		$defaults = $this->getDefaultSettings();

		// Setting value not set => use default value (if any exist) or return false
		if (! array_key_exists($key, $this->settings) ) {
			if (isset($defaults[$key])) { return $defaults[$key];}
			elseif ($default) { return $default; }
			else { return false;	}
		}

		// Return the setting
		return $this->settings[$key];
	}

	/**
	 * Set single setting and (optional) save all settings
	 **/
	public function setSetting($key, $value, $save = false) {
		$this->settings[$key] = $value;
		// Save array in WP options:
		if ($save) { $this->saveSettings(); }
	}

	/**
	 * Load user settings
	 **/
	public function loadUserSettings() {
		if( $this->user_id == 0 ) {
			$this->user_id = get_current_user_id();
		}
		if (! $this->user_id) { return false; }

		$this->user_settings = get_user_meta( $this->user_id, 'wp-visitorflow', true );

		// If user settings do not exist, load and store default values:
		if (! is_array($this->user_settings) ) {
			$this->user_settings = $this->getDefaultUserSettings();
			$this->saveUserSettings();
		}
	}

	/**
	 * Save user settings
	 **/
	public function saveUserSettings() {
		if (! $this->user_id) { return false; }

		update_user_meta( $this->user_id, 'wp-visitorflow', $this->user_settings );
	}

	/**
	 * Get default user settings
	 **/
	public function getDefaultUserSettings($key = false) {
		$defaults =  array(
			'datetimestart' => date( 'Y-m-d' ),
			'datetimestop' => date( 'Y-m-d' ),
			'selected_page_id' 	 => 1,
			'flowchart_dimension' => 2,
			'flowchart_max_nodes' => 10,
			'flowchart_start_type' => 'step',
			'flowchart_start_step' => 0,
			'flowchart_min_step' => 0,
			'flowchart_max_step' => 3,
			'flowchart_filter_data' => 0,
			'flowchart_filter_data_selected' => array(),
			'flowchart_distance_between_steps' => 550,
		);

		if ($key) {
			if (array_key_exists($key, $defaults)) {
				return $defaults[$key];
			}
			else {
				return false;
			}
		}
		else { return $defaults; }
	}

	/**
	 * Get single user setting
	 **/
	public function getUserSetting($key, $default = null) {
		$defaults = $this->getDefaultUserSettings();

		// Setting value not set => use default value, if any exist, or return false.
		if (! array_key_exists($key, $this->user_settings) ) {
			if (isset($defaults[$key])) { return $defaults[$key];}
			elseif (isset($default))    { return $default; }
			else 						{ return false;	}
		}

		// Return the setting.
		return $this->user_settings[$key];
	}

	/**
	 * Set single user setting and (optional) save all user settings
	 **/
	public function setUserSetting($key, $value, $save = false) {
		$this->user_settings[$key] = $value;
		// Save array in WP options:
		if ($save) { $this->saveUserSettings(); }
	}


	/**
	 * Get datetime
	 **/
	public function getDatetime($format = 'Y-m-d H:i:s') {
		return $this->datetime->format($format);
	}

	/**
	 * Get User ID
	 **/
	public function getUserId() {
		return $this->user_id;
	}


	/**
	 * Get SearchEngine Array
	 **/
	public function getSearchEngines( $searchengine = false ) {
		$searchengines = array (
			'baidu' 	 => array( 'label' => 'Baidu', 		'searchpattern' => array('baidu.com'), 				'querykey' => 'wd'),
			'bing' 		 => array( 'label' => 'Bing', 		'searchpattern' => array('bing.com'), 				'querykey' => 'q'),
			'duckduckgo' => array( 'label' => 'DuckDuckGo', 'searchpattern' => array('duckduckgo.com','ddg.gg'),'querykey' => 'q'),
			'google' 	 => array( 'label' => 'Google', 	'searchpattern' => array('google.'), 				'querykey' => 'q'),
			'naver' 	 => array( 'label' => 'Naver', 		'searchpattern' => array('naver.com'), 				'querykey' => 'q'),
			'yahoo' 	 => array( 'label' => 'Yahoo!', 	'searchpattern' => array('yahoo.com'), 				'querykey' => 'p'),
			'yandex' 	 => array( 'label' => 'Yandex', 	'searchpattern' => array('yandex.ru'), 				'querykey' => 'text'),
		);
		if ($searchengine) {
			return $searchengines[$searchengine];
		}
		return $searchengines;
	}

}

endif;	// Prevent multiple class definitions
