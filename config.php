<?php

global $wpdb;
define ('EASY_RATING_SYSTEM_COMMON_CLASS', 'easy-rating-system');
define ('EASY_RATING_SYSTEM_CLASS_LOGOUT_DEFAULT', 'easy-rating-system-logout');
define ('EASY_RATING_DB_TABLE_NAME', $wpdb->prefix . 'easy_rating' );
define ('EASY_RATING_CHARSET_COLLATE', $wpdb -> get_charset_collate() );
define ('EASY_RATING_TYPE_META_KEY_PREFIX','easy_rating_system_rate');

//ここは、そのうち設定画面から設定できるようにする
$CONST_RATING_CLASS_SETTINGS = array(
    '5star access' => array(
        'label'   => 'アクセス',
        'max'     => 5,
        'min'     => 1,
        'filter'  => '2+ROUND(3*AVG(rate)/5,1)'
    ),
    '5star crowdless' => array(
        'label'   => 'ガラスキ度',
        'max'     => 5,
        'min'     => 1,
        'filter'  => '2+ROUND(3*AVG(rate)/5,1)'
    ),
    '5star clean' => array(
        'label'   => 'きれいさ',
        'max'     => 5,
        'min'     => 1,
        'filter'  => '2+ROUND(3*AVG(rate)/5,1)'
    ),
    '5star oldstyle' => array(
        'label'   => '古風度',
        'max'     => 5,
        'min'     => 1,
        'filter'  => '2+ROUND(3*AVG(rate)/5,1)'
    ),
    '5star water' => array(
        'label'   => '泉質',
        'max'     => 5,
        'min'     => 1,
        'filter'  => '2+ROUND(3*AVG(rate)/5,1)'
    ),
    '5star niceview' => array(
        'label'   => 'ロケーション',
        'max'     => 5,
        'min'     => 1,
        'filter'  => '2+ROUND(3*AVG(rate)/5,1)'
    ),
    '5star openness' => array(
        'label'   => '広さ',
        'max'     => 5,
        'min'     => 1,
        'filter'  => '2+ROUND(3*AVG(rate)/5,1)'
    ),
    '5star' => array(
        'label'   => '総合評価',
        'max'     => 5,
        'min'     => 1,
        'filter'  => '2+ROUND(3*AVG(rate)/5,1)'
    ),
    'gone' => array(
        'label'   => '行った',
        'max'     => 1,
        'min'     => 0,
        'filter'  => 'SUM(rate)'
    ),
    'gonna' => array(
        'label'   => '行きたい',
        'max'     => 1,
        'min'     => 0,
        'filter'  => 'SUM(rate)'
    ),
    'goodfor family' => array(
        'label'   => '家族連れ',
        'max'     => 1,
        'min'     => 0,
        'filter'  => 'SUM(rate)'
    ),
    'goodfor parent-and-child' => array(
        'label'   => '親子での利用',
        'max'     => 1,
        'min'     => 0,
        'filter'  => 'SUM(rate)'
    ),
    'goodfor lovers' => array(
        'label'   => '恋人どうし',
        'max'     => 1,
        'min'     => 0,
        'filter'  => 'SUM(rate)'
    ),
    'goodfor youth' => array(
        'label'   => '若者',
        'max'     => 1,
        'min'     => 0,
        'filter'  => 'SUM(rate)'
    ),
    'goodfor travellers' => array(
        'label'   => '旅行者',
        'max'     => 1,
        'min'     => 0,
        'filter'  => 'SUM(rate)'
    ),
    'goodfor medication' => array(
        'label'   => '湯治客',
        'max'     => 1,
        'min'     => 0,
        'filter'  => 'SUM(rate)'
    )
);

function easy_rating_get_label_from_class($class) {
    global $CONST_RATING_CLASS_SETTINGS;
    return $CONST_RATING_CLASS_SETTINGS[$class]['label'];
}
#post, comment, user meta に評価統計を登録するためのmetakey取得関数
function easy_rating_get_meta_key ($class) {
    return EASY_RATING_TYPE_META_KEY_PREFIX . '_' . $class;
}

//post, comment, user metaへのラッパーaccesor
function get_object_meta ($obj, $obj_id, $key = '', $single = false){
    switch ($obj) {
        case 'post'   : return get_post_meta   ($obj_id, $key, $single);
        case 'comment': return get_comment_meta($obj_id, $key, $single);
        case 'user'   : return get_user_meta   ($obj_id, $key, $single);
        default       : throw new Exception("unknown type to get meta", 1);
    }
}
function add_object_meta ($obj, $obj_id, $meta_key, $meta_value , $unique = false){
    switch ($obj) {
        case "post"   : return add_post_meta   ($obj_id, $meta_key, $meta_value, $unique);
        case 'comment': return add_comment_meta($obj_id, $meta_key, $meta_value, $unique);
        case 'user'   : return add_user_meta   ($obj_id, $meta_key, $meta_value, $unique);
        default       : throw new Exception("unknown type to add meta", 1);
    }
}
function update_object_meta ($obj, $obj_id, $meta_key, $meta_value , $prev_value = ''){
    switch ($obj) {
        case 'post'   : return update_post_meta   ($obj_id, $meta_key, $meta_value, $prev_value);
        case 'comment': return update_comment_meta($obj_id, $meta_key, $meta_value, $prev_value);
        case 'user'   : return update_user_meta   ($obj_id, $meta_key, $meta_value, $prev_value);
        default       : throw new Exception("unknown type to update meta", 1);
    }
}

//タグを編む関数
//無害化処理しかしないので、htmlはおかしくなる可能性がある
function easy_rating_knit_a_html_tag($args) {
    if (!isset($args['tagname'])) {
        throw new Exception("no tag name error", 1);
    }
    $template = "<%s%s>%s</%s>";
    $tagname = esc_html( $args['tagname'] );
    $attributes = '';
    foreach ( $args['attributes'] as $key => $value ) {
        $attributes .= ' ' . esc_html( $key ) . '="' . esc_attr( $value ) . '"';
    }
    $text = isset( $args['text'] ) ? esc_html( $args['text'] ) : '';

    return sprintf($template, $tagname, $attributes, $text, $tagname);
}

class Validator {
    public $class = '';
    public function __construct($val) {
        $class = $val;
    }
    public function is_valid($rate) {
        global $CONST_RATING_CLASS_SETTINGS;
        global $class;
        $not_over = $rate <= $CONST_RATING_CLASS_SETTINGS[$class]['max'];
        $not_below = $rate >= $CONST_RATING_CLASS_SETTINGS[$class]['min'];
        return is_numeric($rate) && $not_over && $not_below;
    }
}
