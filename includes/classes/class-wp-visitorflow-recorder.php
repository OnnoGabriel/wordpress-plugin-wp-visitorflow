<?php
/**
 *	Record Page Visits class for WP VisitorFlow
 *
 * @package    WP-VisitorFlow
 * @author     Onno Gabriel
 **/

// Prevent calls from outside of WordPress
defined( 'ABSPATH' ) || exit;

// Load vendor libs
require_once dirname( __FILE__ ) . '/../../vendor/autoload.php';
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Device\DeviceParserAbstract;

if (! class_exists("WP_VisitorFlow_Recorder")) :	// Prevent multiple class definitions

class WP_VisitorFlow_Recorder
{

	private static $config = 0;

	/**
	 * Constructor
	 **/
	public static function init() {
		if ( ! self::$config ) {
			self::$config = WP_VisitorFlow_Config::getInstance();
			WP_VisitorFlow_Database::init();
		}
	}

	/**
	 * Record visit
	 **/
	public static function recordVisit() {

		// Check frontend JS call?
		parse_str( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_QUERY ), $queries );
		if ( self::$config->getSetting('use_frontend_js') && ! array_key_exists( 'wpvf_page', $queries ) ) {
			return false;
		}

		// Get HTTP User-Agent String
		$ua_string = false;
		if( array_key_exists('HTTP_USER_AGENT', $_SERVER) ) {
			$ua_string = $_SERVER['HTTP_USER_AGENT'];
		}
		elseif ( self::$config->getSetting('exclude_unknown_useragents') ) {
			WP_VisitorFlow_Database::increaseMeta('count uastring', 'unknown');
			return false;
		}

		// Self-referrer?
		if ($ua_string) {
			global $wp_version;
			if (    $ua_string == "WordPress/" . $wp_version . "; " . get_home_url("/")
				||  $ua_string == "WordPress/" . $wp_version . "; " . get_home_url()
		       ) {
				WP_VisitorFlow_Database::increaseMeta('count exclusion', 'self-referrer');
				return false;
			}
		}

		// 404 error page?
		if( is_404() ) {
			if ( $this->get_setting('exclude_404') ) {
				WP_VisitorFlow_Database::increaseMeta('count exclusion', '404');
				return false;
			}
		}

		// Parse HTTP User-Agent String by Device Detector
		$client = self::parseUAString( $ua_string );

		// Bot detected?
		if ( $client['bot'] == true ) {
			WP_VisitorFlow_Database::increaseMeta('count bot', $client['bot_name']);
            if ( self::$config->getSetting('exclude_bots') ) {
                return false;
			}
		}
		else {

			// Track also unknown/hidden user agents?
			if ( (! is_array($client) || ! array_key_exists('agent_name',  $client) || ! $client['agent_name'] ) ) {
				WP_VisitorFlow_Database::increaseMeta('count uastring', 'unknown');
				if ( self::$config->getSetting('exclude_unknown_useragents') ) {
					return false;
				}
			}

		}

		// Check if user is logged-in and role is included to the statistic recording
		if (is_user_logged_in() ) {
			$current_user = wp_get_current_user();

			if (count($current_user->roles) > 0) { // Check - but only if logged-in user has at least one role
				$included = false;
				foreach( $current_user->roles as $role ) {
					$setting_key = 'include_' . str_replace(" ", "_", strtolower($role) );
					if( self::$config->getSetting($setting_key) == true ) {
						$included = true;
					}
				}
				if (! $included) {
					return false;
				}
			}
		}

		// Check if the HTTP User-Agent String contains a string from the crawlers exclusion list
		if ($ua_string) {
			foreach ( self::$config->getSetting('crawlers_exclude_list') as $crawler_string) {
				if ( preg_match('/' . preg_quote(strtolower($crawler_string), '/') . '/', strtolower($ua_string)) ) {
					WP_VisitorFlow_Database::increaseMeta('count uastring', $crawler_string);
					return false;
				}
			}
		}

		// Check if the page contains a string from the pages exclusion list
		if (array_key_exists('REQUEST_URI', $_SERVER) && $_SERVER['REQUEST_URI']) {
			$page = $_SERVER['REQUEST_URI'];
			foreach ( self::$config->getSetting('pages_exclude_list') as $page_string) {
				if ( preg_match('/' . preg_quote(strtolower($page_string), '/') . '/', strtolower($page)) ) {
					WP_VisitorFlow_Database::increaseMeta('count pagestring', $page_string);
					return false;
				}
			}
		}

		// Get the IP adress of the remote client
		$ip = self::getIP();

		// Check if visitor was already registered before:
		$sql_ands = '';
		$sql_params = array();
		array_push( $sql_params, self::$config->getDatetime() );
		array_push ($sql_params, self::$config->getSetting('minimum_time_between'));
		$fields = array('agent_name', 'agent_version', 'agent_engine', 'os_name', 'os_version', 'os_platform');
		foreach ($fields as $field) {
			if ( $client[$field] ) {
				$sql_ands .= " AND $field='%s'";
				array_push( $sql_params, $client[$field] );
			}
			else {
				$sql_ands .= " AND $field IS NULL";
			}
		}
		$sql_ands .= " AND ip='%s'";
		array_push( $sql_params, $ip );

		$db = WP_VisitorFlow_Database::getDB();
		$visits_table = WP_VisitorFlow_Database::getTableName( 'visits' );
		$sql = $db->prepare(
			"SELECT id FROM $visits_table
			WHERE last_visit>=date_sub('%s', interval '%d' minute)
			$sql_ands
			LIMIT 1;",
			$sql_params
		);

		$visit = $db->get_row( $sql );

		// New or old visitor?
		$new_visitor = true;
		$visit_id  = 0;
		if ( isset( $visit->id ) ) {
			$new_visitor = false;
			$visit_id = $visit->id;
		}

		/**************************************************************************************
		 * All checks done: Save visitor, page and flow to database
		 **************************************************************************************/

		// New Visitor => store visit, referrer and visited page
		if ($new_visitor) {

			// Get visited page info
			$page_info = self::getPageInfo();

			if ($page_info['internal'] == 1) {

				// Store Visitor
				$res = $db->insert(
					$visits_table,
					array(
						'last_visit' 	=> self::$config->getDatetime(),
						'agent_name' 	=> $client['agent_name'],
						'agent_version' => $client['agent_version'],
						'agent_engine' 	=> $client['agent_engine'],
						'os_name' 		=> $client['os_name'],
						'os_version'	=> $client['os_version'],
						'os_platform' 	=> $client['os_platform'],
						'ip' 		 	=> $ip
					),
					array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
				);
				$visit_id = $db->insert_id;

				// Get referrer page info
				$referrer_uri = self::getReferrerUri();

				//Store referrer
				$referer_page_id = WP_VisitorFlow_Database::storePage( $referrer_uri == 'self' ? 1 : 0, $referrer_uri, 0 );
				$referer_flow_id = WP_VisitorFlow_Database::storeFlow( $visit_id, $referer_page_id );

				//Store page
				$page_id = WP_VisitorFlow_Database::storePage( $page_info['internal'], $page_info['title'], $page_info['post_id'] );
				$flow_id = WP_VisitorFlow_Database::storeFlow( $visit_id, $page_id );

				// Store search enginge keywords (if any exists)
				if (array_key_exists('HTTP_REFERER', $_SERVER) ) {
					WP_VisitorFlow_Database::storeSEKeywords( $_SERVER['HTTP_REFERER'], $referer_page_id, $page_id );
				}
			}

			// Store HTTP User-Agent String (optional):
			if ( self::$config->getSetting('store_useragent')) {
				WP_VisitorFlow_Database::storeMeta('useragent', $visit_id, $ua_string );
			}

		}

		// Old Visitor => update visit and store visited page
		else {
			// Update Visitor
			$res = $db->update(
				$visits_table,
				array( 'last_visit' =>  self::$config->getDatetime() ),
				array( 'id' => $visit_id ),
				array('%s'),
				array('%d')
			);
			//Store page
			$page_info = self::getPageInfo();

			if ($page_info['internal'] == 1) {
				$page_id = WP_VisitorFlow_Database::storePage( $page_info['internal'], $page_info['title'], $page_info['post_id'] );
				$flow_id = WP_VisitorFlow_Database::storeFlow( $visit_id, $page_id );
			}
		}

		return $visit_id;
	}


	private static function parseUAString( $ua_string ) {

		$client = [
			'agent_name' => '',
			'agent_version' => '',
			'agent_engine' => '',
			'os_name' => '',
			'os_version' => '',
			'os_platform' => ''
		];

		// Parse UA String using Device Detector
		$dd = new DeviceDetector( $ua_string) ;

		// Use a cache to increase performance
		$cache_folder = WP_CONTENT_DIR . '/extensions/wp-visitorflow/cache/';
		if (file_exists( $cache_folder ) ) {
			$dd->setCache( new Doctrine\Common\Cache\PhpFileCache( $cache_folder ) );
		}

		// Parse the UA String
		$dd->parse();

		// Bot/Crawler detected
		if ( $dd->isBot() ) {
			$client['bot'] = true;
			$botInfo = $dd->getBot(); // ['name', 'category', 'url', 'producer' => ['name', 'url'] ]
			$client['bot_name'] = $botInfo['name'] ? $botInfo['name'] : null;
			$client['agent_name'] = $client['bot_name'];
		}
		// Browser detected
		else {
			$client['bot'] = false;
			// Get Agent and OS info
			$clientInfo = $dd->getClient(); // ['type', 'name', 'short_name', 'version', 'engine']
			$osInfo = $dd->getOs();         // ['name', 'short_name', 'version', 'platform']
			$client['agent_name'] 	 = isset($clientInfo['name']) && $clientInfo['name'] ? $clientInfo['name'] : NULL;
			$client['agent_version'] = isset($clientInfo['version']) && $clientInfo['version'] ? $clientInfo['version'] : NULL;
			$client['agent_engine']  = isset($clientInfo['engine']) && $clientInfo['engine'] ? $clientInfo['engine'] : NULL;
			$client['os_name'] 	 	 = isset($osInfo['name']) && $osInfo['name'] ? $osInfo['name'] : NULL;
			$client['os_version'] 	 = isset($osInfo['version']) && $osInfo['version'] ? $osInfo['version'] : NULL;
			$client['os_platform'] 	 = isset($osInfo['platform']) && $osInfo['platform'] ? $osInfo['platform'] : NULL;
		}

		return $client;
	}


	/**
	 * Get IP address of the remote client.
	 **/
	private static function getIP() {
		// Standard value
		$ip = $_SERVER['REMOTE_ADDR'];

		// If CLIENT_IP or FORWARDED is set, use this value
		if (getenv('HTTP_CLIENT_IP')) {
			$ip = getenv('HTTP_CLIENT_IP');
		} elseif (getenv('HTTP_X_FORWARDED_FOR')) {
			$p = getenv('HTTP_X_FORWARDED_FOR');
		} elseif (getenv('HTTP_X_FORWARDED')) {
			$ip = getenv('HTTP_X_FORWARDED');
		} elseif (getenv('HTTP_FORWARDED_FOR')) {
			$ip = getenv('HTTP_FORWARDED_FOR');
		} elseif (getenv('HTTP_FORWARDED')) {
			$ip = getenv('HTTP_FORWARDED');
		}

		// Remove port (if any exists)
		if( strstr( $ip, ':' ) !== false ) {
			$parts = explode(':', $ip);
			$ip = $parts[0];
		}

		// Valid IP address?
		$long = ip2long($ip);
		if ($long == -1 || $long === false) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		// Encrypt IP address?
		if ( self::$config->getSetting('encrypt_ips') == true) {
			$ip = sha1( $ip );
		}

		return $ip;
	}



	/**
     * Get page details from WordPress API
	 *
	 * @return array['internal' => 1,
     *				 'post_id'  => -1 		: error, internal post/page could not be found
	 *							   0 		: if not a WP post or page
	 *							   post_id 	: if a WP post or page
	 *				 'title'    => post/page title
	 *                             or the page URI ]
	 */
	private static function getPageInfo() {
		$page_info = [
			'internal' => -1,
			'post_id' => 0,
			'title' => ''
		];

		$page_uri = '';

		// Get queries
		parse_str( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_QUERY ), $queries );

		// Get page_uri from frontend JS call
		if ( self::$config->getSetting('use_frontend_js') ) {
			if (! array_key_exists('wpvf_page', $queries)) {
				return $page_info;
			}
			$page_uri = urldecode( $queries['wpvf_page'] );

			$post_id = url_to_postid( $page_uri );

			// Not a valid internal URL?
			if ( ! $post_id ) {
				$page_info['internal'] = 1;
				$page_info['post_id'] = 0;
				$page_info['title'] = self::cleanURL( $page_uri );
				return $page_info;
			}

			$page_info['internal'] = 1;
			$page_info['post_id'] = $post_id;
			$page_info['title'] = get_the_title( $post_id );

			return $page_info;

		}
		// Get page_uri from $_SERVER
		else {

			// Exclude AJAX, REST or CRON requests
			if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST )
			|| ( defined( 'DOING_AJAX' ) && DOING_AJAX )
			|| wp_doing_cron() ) {
				return $page_info;
			}

			// Get page_uri from $_SERVER
			$page_uri = 'http';
			if (array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'] == 'on') { $page_uri .= 's'; }
			$page_uri .= '://' . $_SERVER['SERVER_NAME'];
			$site_url_port = parse_url( site_url(), PHP_URL_PORT );
			if (array_key_exists('SERVER_PORT', $_SERVER) && $site_url_port && $_SERVER['SERVER_PORT'] == $site_url_port) {
				$page_uri .= ':' . $_SERVER['SERVER_PORT'];
			}
			$page_uri .= $_SERVER['REQUEST_URI'];

			// Internal WP post or page?
			$page_info['internal'] = ( is_single() or is_page() ) ? 1 : 0;

			// We are on an internal WP post or page
			if ( $page_info['internal'] ) {
				$page_info['post_id'] = get_queried_object_id();
				$page_info['title'] = get_the_title();
				return $page_info;
			}
		}

		return $page_info;
	}


	/**
     * Get Referrer URI
	 *
	 * @return string $referrer_uri
	 */
	private static function getReferrerUri() {

		$referrer_uri = false;

		// Get referrer from frontend JS call
		if ( self::$config->getSetting('use_frontend_js') ) {
			if ( isset( $_SERVER['REQUEST_URI'] ) ) {
				parse_str( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_QUERY ), $queries );
				if (array_key_exists('wpvf_referrer', $queries)) {
					$referrer_uri = urldecode( $queries['wpvf_referrer'] );
				}
			}
		}
		// Get referrer from $_SERVER['HTTP_REFERER']
		else {
			if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
				$referrer_uri = esc_sql( strip_tags( $_SERVER['HTTP_REFERER'] ) );
			}
		}

		// No referrer_uri?
		if ( ! $referrer_uri || ! is_string( $referrer_uri ) ) {
			return 'unknown';
		}

		// Strip queries from URL
		$parts = explode( '?', $referrer_uri );
		if ( count($parts) > 1 ) { $referrer_uri = $parts[0]; }

		// Remove last '/' or '\' at the end (if any)
		$referrer_uri = rtrim( $referrer_uri , '/\\' );

		// No uri left => use '/' as uri
		if ( ! $referrer_uri ) {
			$referrer_uri = '/';
		}

		// Own page?
		$post_id = url_to_postid( $referrer_uri );
		if ( $post_id ) {
			return 'self';
		}

		return esc_url_raw( self::cleanURL( $referrer_uri ) );
	}


	/**
     * Clean URI
	 *
	 * @var string $uri with $site_url
	 * @return string $uri without $site_url
	 */
	private static function cleanURL( $page_uri ) {

		// Strip queries from URI
		$parts = explode( '?', $page_uri );
		if ( count ($parts ) > 1 ) { $page_uri = $parts[0]; }
		// Remove last '/' or '\' at the end (if any)
		$page_uri = rtrim($page_uri , '/\\');

		// Strip site URL from URI
		$site_url = site_url();
		if (strlen($page_uri) >= strlen($site_url) && is_string($site_url) && strlen($site_url) > 0 ) {
			if (substr($page_uri, 0, strlen($site_url)) === $site_url) {
				// Strip $site_url from URI
				$page_uri = str_ireplace( $site_url, '', $page_uri );
				// double "//" to "/"
				$page_uri = str_replace( "//", "/", $page_uri );
			}
		}

		// No uri left => use '/' as uri
		if ( ! $page_uri ) {
			$page_uri = '/';
		}

		return $page_uri;
	}

}

endif;	// Prevent multiple class definitions
