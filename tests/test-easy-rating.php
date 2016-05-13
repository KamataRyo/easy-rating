<?php
/**
 * Test files with PHPunit
 *
 * @package easy-rating
 */



class EasyRatingFunctionsTest extends WP_UnitTestCase {

	function setUp(){
		$this->db = new EasyRating();
	}


	function test_class_DBWrapper() {
		global $wpdb;
		$tables = [];

		foreach ( $wpdb->get_results('show tables;') as $table ) {
			$property = 'Tables_in_' . $wpdb->dbname;
			array_push( $tables, $table->$property );
		}
		$this->assertTrue( array_key_exists( EASY_RATING_DB_TABLE_NAME, $tables ) );
	}
}
