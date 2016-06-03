<?php
/**
 * Test files with PHPunit
 *
 * @package easy-rating
 */

class EasyRatingFunctionsTest extends WP_UnitTestCase {

	function setUp(){
		//register
		$this->module = new Easy_Rating();
		$this->table_name = $this->module->table_name;
	}

	function test_if_db_table_exists() {
		global $wpdb;
		$tables = array();
		$result = false;

		foreach ( $wpdb->get_results('show tables;') as $table ) {
			$property = 'Tables_in_' . $wpdb->dbname;
			$result = ( $this->table_name === $table->$property ) || $result;
		}

		$this->assertTrue( $result );
	}

	function test_db_table_columns() {
		$this->module->
	}
}
