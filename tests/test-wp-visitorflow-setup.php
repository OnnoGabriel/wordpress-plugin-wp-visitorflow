<?php
/**
 * Class WP_VisitorFlow_Setup_Test
 *
 * @package Wp_Visitorflow
 */

/**
 * WP_VisitorFlow tests
 */
class WP_VisitorFlow_Setup_Test extends WP_UnitTestCase {

	private $config;

	public function setUp()
    {
        parent::setUp();

		$this->config = WP_VisitorFlow_Config::getInstance();
		WP_VisitorFlow_Setup::init();
	}

	/**
	 * Test createTables
	 */
	public function test_createTables() {
		// Create tables
		$old_version = get_option('wp_visitorflow_plugin_version');
		WP_VisitorFlow_Setup::createTables();
		$new_version = get_option('wp_visitorflow_plugin_version');
		$this->assertTrue( $new_version == WP_VISITORFLOW_VERSION );

		// postUpdate
		WP_VisitorFlow_Setup::postUpdate( $old_version, $new_version );

		$db = WP_VisitorFlow_Database::getDB();
		$pages_table = WP_VisitorFlow_Database::getTableName('pages');
		$result = $db->get_var("SELECT count(id) FROM $pages_table;");
		$this->assertTrue( $result == 2 ); // front page not set => only 2 pages initially set

		$flowdataStart = $this->config->getSetting('flow-startdatetime');
		$now = $this->config->getDatetime();
		$this->assertTrue( $flowdataStart == $now );
	}

	/**
	 * Test storeMeta
	 */
	public function test_storeMeta() {
		$result = WP_VisitorFlow_Database::storeMeta('test_type', 'test_label', 'test_value');
		$this->assertTrue( $result == 1 );
	}


	/**
	 * Test uninstall
	 */
	public function test_uninstall() {
		$result = WP_VisitorFlow_Setup::uninstall();
		$config = get_option('wp_visitorflow');
		$this->assertTrue( $config == false );
		$version = get_option('wp_visitorflow_plugin_version');
		$this->assertTrue( $version == false );
	}

}
