<?php
/**
 * Plugin Name: Easy Rating
 * Version: 1.0.0
 * Description: Rate post, comment, author, even terms!
 * Author: KamataRyo
 * Author URI: http://biwako.io/
 * Plugin URI: https://github.com/KamataRyo/easy-rating
 * Text Domain: easy-rating
 * Domain Path: /languages/
 *
 * @package easy-rating
 */

global $wpdb;
define ('EASY_RATING_DB_TABLE_NAME', $wpdb->prefix . 'easy_rating' );


class EasyRating {

    public function __construct() {
        register_activation_hook ( __FILE__, array($this, 'easy_rating_table_create' ) );
    }
    //プラグイン有効化時の処理
    //専用db tableがなければ作成し、テーブル構造はdbDeltaで更新する
    public function easy_rating_table_create(){
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        global $wpdb;

        $sql = "CREATE TABLE " . EASY_RATING_DB_TABLE_NAME . " (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            type enum('post', 'comment', 'user') NOT NULL,
            type_id bigint(20) NOT NULL,
            class varchar(55) NOT NULL,
            rate int(3) DEFAULT 0 NOT NULL,
            created datetime NOT NULL,
            modified datetime,
            UNIQUE KEY id (id)
        ) " . EASY_RATING_CHARSET_COLLATE;
        dbDelta( $sql );
    }
}
