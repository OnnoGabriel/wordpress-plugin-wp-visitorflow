<?php
/**
 * Class WP_VisitorFlowRecorderTest
 *
 * @package Wp_Visitorflow
 */

/**
 * WP_VisitorFlow tests
 */
class WP_VisitorFlow_Recorder_Test extends WP_UnitTestCase {

	private $config;
	private $post_id = [];

	public function setUp()
    {
        parent::setUp();

		$this->config = WP_VisitorFlow_Config::getInstance();
		WP_VisitorFlow_Recorder::init();

		WP_VisitorFlow_Setup::init();
		WP_VisitorFlow_Setup::createTables();

		$this->flow_table = WP_VisitorFlow_Database::getTableName('flow');
		$this->visits_table = WP_VisitorFlow_Database::getTableName('visits');
		$this->pages_table = WP_VisitorFlow_Database::getTableName('pages');
		$this->meta_table = WP_VisitorFlow_Database::getTableName( 'meta' );
		$this->db = WP_VisitorFlow_Database::getDB();


		// Turn of "use_frontend_js"
		$this->config->setSetting('use_frontend_js', false);

		// Create one post and use this post
		$this->post_id[0] = $this->factory->post->create();
		$this->go_to('/?p=' . $this->post_id[0] );
	}

	/**
	 * Test recordVisit
	 */
	public function test_recordVisit_withNoUserAgent() {
		// Call recordVisit() with no correct User-Agent String
		$result = WP_VisitorFlow_Recorder::recordVisit();
		$this->assertTrue( $result == false );

		// ...results in exclusion type "user-agent string unknown"
		$result = $this->db->get_row(
			$this->db->prepare(
				"SELECT id, value
				   FROM $this->meta_table
				  WHERE type='%s' AND label='%s'
				  LIMIT 1;",
				'count uastring',
				'unknown'
			)
		);
		$this->assertTrue( $result->value == 1 );

	}

	/**
	 * Test recordVisit
	 */
	public function test_recordVisit_selfReferer() {
		// Call recordVisit() with self referer User-Agent String
		global $wp_version;
		$_SERVER['HTTP_USER_AGENT'] = "WordPress/" . $wp_version . "; " . get_home_url("/");
		$result = WP_VisitorFlow_Recorder::recordVisit();
		$this->assertTrue( $result == false );

	}

	/**
	 * Test recordVisit
	 */
	public function test_recordVisit_withCorrectUserAgent() {

		// Call recordVisit() with correct User-Agent String
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:62.0) Gecko/20100101 Firefox/62.0';
		$visit_id = WP_VisitorFlow_Recorder::recordVisit();

		// Visit stored in pages table?
		$count_pages = $this->db->get_var("SELECT count(id) FROM $this->pages_table;");

		$this->assertTrue( $count_pages == 3 );
		$res = $this->db->get_row("SELECT * FROM $this->pages_table WHERE f_post_id='" . $this->post_id[0] . "';");
		$this->assertTrue( $res->f_post_id == $this->post_id[0] );

		// Visit stored in flow table?
		$count_flow = $this->db->get_var("SELECT count(*) FROM $this->flow_table WHERE f_visit_id='$visit_id';");
		$this->assertTrue( $count_flow == 2 );
	}


	/**
	 * Test multi recordVisit
	 */
	public function test_RecordVisit_withMultipleCalls() {
		$this->post_id[1] = $this->factory->post->create();

		// Call recordVisit() with correct User-Agent String
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:62.0) Gecko/20100101 Firefox/62.0';

		$this->go_to('/?p=' . $this->post_id[0] );
		$visit_id = WP_VisitorFlow_Recorder::recordVisit();
		$this->go_to('/?p=' . $this->post_id[1] );
		$visit_id = WP_VisitorFlow_Recorder::recordVisit();

		// Visit stored in pages table?
		$res = $this->db->get_row("SELECT * FROM $this->pages_table WHERE f_post_id='" . $this->post_id[0] . "';");
		$this->assertTrue( $res->f_post_id == $this->post_id[0] );
		$res = $this->db->get_row("SELECT * FROM $this->pages_table WHERE f_post_id='" . $this->post_id[1] . "';");
		$this->assertTrue( $res->f_post_id == $this->post_id[1] );

		// Visit stored in flow table?
		$count_flow = $this->db->get_var("SELECT count(*) FROM $this->flow_table WHERE f_visit_id='$visit_id';");
		$this->assertTrue( $count_flow == 3 );

	}

	/**
	 * Test Search Engine Keywords
	 */
	public function test_recordVisit_SearchEngingeKeywords() {
		// Call with new HTPP user agend
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36';
		// Call with google referrer + keywords
		$_SERVER['HTTP_REFERER'] = 'http://www.google.com?q=keyword1+keyword2';
		$this->go_to('/?p=' . $this->post_id[0] );

		$visit_id = WP_VisitorFlow_Recorder::recordVisit();

		// Check keywords stored
		$res = $this->db->get_row("SELECT * FROM $this->meta_table WHERE type='se keywords';");
		$this->assertTrue( $res->label == 'google#keyword1 keyword2' );

		// Remove keywords
		$_SERVER['HTTP_REFERER'] = 'http://www.google.com';
	}


	/**
	 * Test Bot Visit
	 */
	public function test_recordVisit_asBot() {
		// Call with Bot User-Agent String
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';
		$this->go_to('/?p=' . $this->post_id[0] );
		$visit_id = WP_VisitorFlow_Recorder::recordVisit();
		$this->assertTrue( $visit_id == 0 );

		// Googlebot counter increased?
		$count = $this->db->get_var(
			"SELECT value
			   FROM $this->meta_table
			   WHERE type='count bot'
				AND label='Googlebot';
		");
		$this->assertTrue( $count == 1 );

		// Try another visit
		$visit_id = WP_VisitorFlow_Recorder::recordVisit();
		$count = $this->db->get_var(
			"SELECT value
			   FROM $this->meta_table
			   WHERE type='count bot'
				AND label='Googlebot';
		");
		$this->assertTrue( $count == 2 );

		// Flow record Bot visit
		$this->config->setSetting('exclude_bots', false);
		$visit_id = WP_VisitorFlow_Recorder::recordVisit();

		$this->assertTrue( $visit_id > 0 );

		// Check Bot visits data
		$res = $this->db->get_row("SELECT * FROM $this->visits_table WHERE id='$visit_id';");
		$this->assertTrue( $res->agent_name == 'Googlebot' );

		// Check flow
		$results = $this->db->get_results("SELECT * FROM $this->flow_table WHERE f_visit_id='$visit_id';");

		$this->assertTrue( count($results) == 2 );
		$this->assertTrue( $results[0]->step == 1 );
		$this->assertTrue( $results[1]->step == 2 );
	}

}
