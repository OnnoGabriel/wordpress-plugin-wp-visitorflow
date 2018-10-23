<?php
/**
 * Class WP_VisitorFlow_Database_Test
 *
 * @package Wp_Visitorflow
 */

/**
 * WP_VisitorFlow tests
 */
class WP_VisitorFlow_Database_Test extends WP_UnitTestCase {

	private $wpvfConfig;

	public function setUp()
    {
        parent::setUp();

		$this->wpvfConfig = WP_VisitorFlow_Config::getInstance();
		WP_VisitorFlow_Database::init();
	}


	/**
	 * Test recordVisit
	 */
	public function test_storeMeta() {

		// $result = WP_VisitorFlow_Database::test_storeMeta('test_type', 'test_label', 'test_value');
		// $this->assertTrue( $result == 'yeah!' );

	}
}
