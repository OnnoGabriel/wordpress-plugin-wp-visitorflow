<?php
/**
 *	Database class for WP VisitorFlow.
 *
 * @package    WP-VisitorFlow
 * @author     Onno Gabriel
 **/

// Prevent calls from outside of WordPress
defined( 'ABSPATH' ) || exit;

if (! class_exists("WP_VisitorFlow_Database")) :	// Prevent multiple class definitions

class WP_VisitorFlow_Database
{

	private static $config = 0;

	private static $db;						// WordPress database object
	private static $table_name = [];			// Hash array with db table names

	/**
	 * Init
	 **/
	public static function init() {
		if ( ! self::$config ) {
			self::$config = WP_VisitorFlow_Config::getInstance();

			global $wpdb;

			// Set Wordpress database object
			self::$db = $wpdb;

			// Set database table names
			self::$table_name = array(
				'visits'       => self::$db->prefix . "visitorflow_visits",
				'pages'        => self::$db->prefix . "visitorflow_pages",
				'flow'         => self::$db->prefix . "visitorflow_flow",
				'meta'         => self::$db->prefix . "visitorflow_meta",
				'aggregation'  => self::$db->prefix . "visitorflow_aggregation"
			);
		}
	}


	/**
	 * Get DB object
	 **/
	public static function getDB() {
		return self::$db;
	}

	/**
	 * Get DB table name
	 **/
	public static function getTableName($name) {
		if ( ! array_key_exists($name, self::$table_name) ) {
			return false;
		}
		return self::$table_name[$name];
	}



	/**
	 * Store entry in "meta" DB table
	 *
	 * @var string $type
	 * @var string $label
	 * @var string $value
	  **/
	  public static function storeMeta($type, $label, $value) {
		$res = self::$db->insert(
			self::getTableName( 'meta' ),
			array(
				'type' 	=> $type,
				'label' => substr( $label, 0, 255 ),
				'value' => $value,
				'datetime' => self::$config->getDatetime()
			),
			array('%s', '%s', '%s', '%s')
		);
		return $res;
	}


	/**
	 * Increase a value in meta table
	 **/
	public static function increaseMeta($type, $label) {
		$meta_table = self::getTableName( 'meta' );

		// Type/label combination already exists?
		$result = self::$db->get_row(
			self::$db->prepare(
				"SELECT id, value
				   FROM $meta_table
				  WHERE type='%s' AND label='%s'
				  LIMIT 1;",
				$type, $label
			)
		);

		if ( ! isset( $result->id ) ) {
			$res = self::$db->insert(
				$meta_table,
				array(
					'type'     => $type,
					'label'	   => $label,
					'value'	   => 1,
					'datetime' => self::$config->getDatetime(),
				),
				array('%s', '%s', '%d', '%s')
			);
			return $res;
		}
		else {
			$sql = self::$db->prepare(
				"UPDATE $meta_table
				    SET value='%s', datetime='%s'
				  WHERE id='%d';",
				($result->value + 1),
				self::$config->getDatetime(),
				$result->id );
			return self::$db->query( $sql );
		}
	}


	/**
	 * Store page in "pages" DB table
	 **/
	public static function storePage( $internal = false, $title = false, $post_id = 0 ) {
		if (! $title) { return false; }

		$pages_table = self::getTableName( 'pages' );
		$page_id = 0;

		// Page identified by ID?
		if ($post_id > 0) {
			// Page already in DB?
			$page = self::$db->get_row(
				self::$db->prepare(
					"SELECT id, title FROM $pages_table WHERE f_post_id='%d';",
					$post_id
				)
			);
			if ( isset( $page->id ) ) {
				$page_id = $page->id;

				// Page got new title? => update page table
				if ($title != $page->title) {
					$res = self::$db->update(
						$pages_table,
						array( 'title' =>  $title ),
						array( 'id' => $page_id),
						array( '%s' ),
						array( '%d' )
					);
				}
			}
		}
		else {
			// Identify page by title and internal status
			$page_id = self::$db->get_var(
				self::$db->prepare(
					"SELECT id FROM $pages_table WHERE internal='%d' AND title='%s';",
					$internal, $title
				)
			);
		}

		// Page exists?
		if ( $page_id ) {
			return $page_id;
		}
		// No: insert new page
		else {
			$res = self::$db->insert(
				$pages_table,
				array(
					'internal'   => $internal,
					'title'		 => $title,
					'f_post_id'	 => $post_id
				),
				array('%d', '%s', '%d')
			);
			return self::$db->insert_id;
		}
	}


	/**
	 * Store entry in "flow" DB table
	 **/
	public static function storeFlow( $visit_id = false, $page_id = false ) {
		if (! $visit_id || ! $page_id)  { return false; }

		$flow_table = self::getTableName( 'flow' );

		// Check, if visitor is already in the flow
		$flow = self::$db->get_row(
			self::$db->prepare(
				"SELECT f_page_id, step FROM $flow_table
				  WHERE f_visit_id='%d'
			   ORDER BY step DESC
				  LIMIT 1;",
				$visit_id
			)
		);

		$step = 0;
		// New flow? Start with step = 1
		if (! isset( $flow->f_page_id ) ) {
			$step = 1;
		}
		else {
			// Are we on a new page? Or is it the same, e.g. by reloading page?
			if  ($page_id != $flow->f_page_id ) {
				$step =  $flow->step + 1;
			}
		}

		// Everything ok, store the page flow
		if ($step >= 1) {
			$res = self::$db->insert(
				$flow_table,
				array(
					'f_visit_id' => $visit_id,
					'step'	 	 => $step,
					'datetime'	 => self::$config->getDatetime(),
					'f_page_id'	 => $page_id
				),
				array('%d', '%d', '%s', '%d')
			);
			return self::$db->insert_id;
		}
		return false;
	}



	/**
	 * Save Search Engine Keywords
	 **/
	public static function storeSEKeywords( $url = false, $referer_page_id = false, $page_id = false ) {
		if ( ! $url || ! is_string($url) || ! $referer_page_id || ! $page_id) { return false; }

		// Parse the url into its components
		$url_parts = parse_url($url);

		// Check if there is any query string
		if (! array_key_exists('query', $url_parts) ) { return false; }

		// Convert query string to array
		parse_str( $url_parts['query'], $queries );

		// Get the array with search engine information
		$searchengines = self::$config->getSearchEngines();

		foreach ( $searchengines as $se_label => $engineinfo ) {

			// Check if host contains the SE pattern
			foreach ( $engineinfo['searchpattern'] as $pattern ) {

				$pattern = str_replace("\.", "\\.", $pattern);
				if ( preg_match('/' . $pattern . '/',  strtolower($url_parts['host']) ) ) {

					// If queries contains the SE querykey store the value
					if ( array_key_exists($engineinfo['querykey'], $queries) ) {
						$keywords = strip_tags ( $queries[ $engineinfo['querykey'] ] );
						if ($keywords) {
							self::storeMeta('se keywords', $se_label . '#' . $keywords, $page_id);
						}
					}
				}
			}

		}

		return true;
	}


	/**
	 * Get data from database for date $date
	 *
	 * @var string $date
	 * @return boolean
	 **/
	public static function getData( $date = false ) {
		// No $date? Use today
		if (! $date) {
			$date = $this->datetime->format('Y-m-d');
		}

		$db = WP_VisitorFlow_Database::getDB();
		$visits_table = WP_VisitorFlow_Database::getTableName('visits');
		$flow_table = WP_VisitorFlow_Database::getTableName('flow');

		$data = array();

		// Count Referrers
		$results = $db->get_results(
			$db->prepare(
				"SELECT f_page_id, COUNT(*) AS count
				   FROM $flow_table
			      WHERE datetime>='%s'
					AND datetime<adddate('%s', interval 1 day)
					AND step='1'
			   GROUP BY f_page_id;",
				$date, $date
			)
		);
		foreach ($results as $res) {
			$data['refer-' . $res->f_page_id] = $res->count;
		}

		// Count Page Views
		$results = $db->get_results(
			$db->prepare(
				"SELECT f_page_id, COUNT(*) AS count
				   FROM $flow_table
				  WHERE datetime>='%s'
					AND datetime<adddate('%s', interval 1 day)
					AND step>'1'
			   GROUP BY f_page_id;",
				$date, $date
			)
		);
		$total_view_count = 0;
		foreach ($results as $res) {
			$data['views-' . $res->f_page_id] = $res->count;
			$total_view_count +=  $res->count;
		}
		$data['views-all'] = $total_view_count;

		// Count Visits
		$visits = $db->get_var(
			$db->prepare(
				"SELECT COUNT(id)
				   FROM $visits_table
				  WHERE last_visit>='%s'
					AND last_visit<adddate('%s', interval 1 day);",
				$date, $date
			)
		);
		$data['visits'] = $visits;

		return $data;
	}



}

endif;	// Prevent multiple class definitions
