<?php
/**
 * Class WP_VisitorFlow_Config_Test
 *
 * @package Wp_Visitorflow
 */

/**
 * WP_VisitorFlow tests
 */
class WP_VisitorFlow_Config_Test extends WP_UnitTestCase {

	public function setUp()
    {
        parent::setUp();

        $this->class_instance = WP_VisitorFlow_Config::getInstance();
	}

	/**
	 * Test settings
	 */
	public function test_settings() {

		$read_access_capability = $this->class_instance->getSetting('read_access_capability');
		$this->assertTrue( $read_access_capability == 'manage_options' );

		$this->class_instance->setSetting('record_visitorflow', false);
		$this->assertTrue ($this->class_instance->getSetting('record_visitorflow') == false);
		$this->class_instance->setSetting('record_visitorflow', true);

		$this->class_instance->loadUserSettings();
		$flowchart_start_type = $this->class_instance->getUserSetting('flowchart_start_type');
		$this->assertTrue( $flowchart_start_type == 'step' );

	}
}
