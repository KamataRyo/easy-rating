<?php

require_once (dirname(__FILE__) . '/config.php');


function easy_rating_form ($args) {
    echo  get_rating_form($args);
}

//data-属性のついたdivだけを吐き出す。
function get_rating_form($args) {
    //引数検証
    $the_default = array(
        'uid'              => ip2long($_SERVER["REMOTE_ADDR"]),#wp_get_current_user()->ID,
        //評価対象のオブジェクトタイプ、enum('post', 'comment', 'user')
        'type'             => 'post',
        //評価対象のidを指定
        'type_id'          => get_the_ID(),
        //評価クラス.集計キー。テンプレートタグのクラスに出力されるので、これに対してフロントエンドでビューを適当に実装する
        'class'            => '',
        //ログアウトしているときのクラス
        'class_logout'     => EASY_RATING_SYSTEM_CLASS_LOGOUT_DEFAULT,
        //追加のクラス
        'additional_class' => '',
        //統計値を出力するかどうか
        'return_statics'   => true,
        //そのユーザーの評価値を出力するかどうか
        'return_your_rate' => true,
        //ratable 属性をつけるかどうか
        'is_ratable'       => true
    );
    $args = wp_parse_args( $args, $the_default );
    if ( is_user_logged_in() ) {
        $args['class_logout'] = '';
    }

    //Attr.に入れてタグを編む
    $result = array(
        //クラスを集約して吐き出す
        'class'        => implode( " ", array_filter (
                            array(
                                EASY_RATING_SYSTEM_COMMON_CLASS,
                                $args['class'],
                                $args['class_logout'],
                                $args['additional_class']
                            ), "strlen"
                           ) ),
        'data-ratable' => $args['is_ratable'],
        'data-label' => easy_rating_get_label_from_class($args['class'])
    );

    if ( $args['return_statics'] ) {
        //統計処理されたrateの値を取得
        $rate_meta_key = easy_rating_get_meta_key ($args['class']);
        $result['data-rate'] = get_object_meta ($args['type'], $args['type_id'], $rate_meta_key, true );
    }
    if ( $args['return_your_rate'] ) {
        //必要な場合自分のrate値を取得
        $your_rate = get_your_rate($args);
        $result['data-urate'] = $your_rate;
    }

    if ($args['is_ratable']) {
        //rate対象を特定
        $result['data-type']    = $args['type'];
        $result['data-tid']     = $args['type_id'];
        $result['data-class']   = $args['class'];
        //CSRF対策
    	$result['data-nonce'] = wp_create_nonce( 'easy-rating-system' );
        //ajaxのエントリーポイントを指定
        $result['data-ajaxdir'] = plugin_dir_url( __FILE__ ) . 'ajax/';
        //クラスを集約して吐き出す
    }
    //CSRF対策
	$nonce = wp_create_nonce( 'easy-rating-system' );
    //ajaxのエントリーポイントを指定
    $ajax_dir = plugin_dir_url( __FILE__ ) . 'ajax/';
    //クラスを集約して吐き出す
    $the_classes = implode( " ", array_filter (
        array(
            EASY_RATING_SYSTEM_COMMON_CLASS,
            $args['class'],
            $args['class_logout']
        ),
        "strlen"
    ) );

    return easy_rating_knit_a_html_tag( array(
        'tagname' => 'div',
        'attributes' => $result
    ) );
}

function easy_get_your_rate($args)
{
    global $wpdb;
    $sql = "
        SELECT rate FROM ". EASY_RATING_DB_TABLE_NAME . "
        WHERE user_id = %d AND
              type = %s AND
              type_id = %d AND
              class = %s
        LIMIT 1;
        ";
    $prpr = $wpdb -> prepare (
        $sql,
        $args['uid'],
        $args['type'],
        $args['type_id'],
        $args['class']
    );
    $row = $wpdb -> get_var( $prpr );
    return $row;
}



function easy_rating_system_query_type_IDs_by_rate ($args) {
    $default_args = array(
        //'user_id'   => ,
        // 'type'     => ,
        // 'class'    => ,
        // 'more'     => ,
        // 'and_more' => ,
        // 'less'     => ,
        // 'and_less' => ,
        // 'equal'    => ,
    );
    $args = wp_parse_args( $args, $default_args );
    extract( $args );

    global $wpdb;
    $where_user_id  = isset( $user_id )  ? $wpdb->prepare( " AND user_id=%s", $user_id )     : '';
    $where_type     = isset( $type )     ? $wpdb->prepare( " AND type=%s",    $type )     : '';
    $where_class    = isset( $class )    ? $wpdb->prepare( " AND class=%s",   $class )    : '';
    $where_more     = isset( $more  )    ? $wpdb->prepare( " AND rate>%d",    $more )     : '';
    $where_and_more = isset( $and_more ) ? $wpdb->prepare( " AND rate>=%d",   $and_more ) : '';
    $where_less     = isset( $less  )    ? $wpdb->prepare( " AND rate<%d",    $less )     : '';
    $where_and_less = isset( $and_less ) ? $wpdb->prepare( " AND rate<=%d",   $and_less ) : '';
    $where_equal    = isset( $equal )    ? $wpdb->prepare( " AND rate=%d",    $equal )    : '';


    $sql = 'select type_id from ' . EASY_RATING_DB_TABLE_NAME .
        ' where 1=1' .
        $where_user_id .
        $where_type .
        $where_class .
        $where_more .
        $where_and_more .
        $where_less .
        $where_and_less .
        $where_equal .';';
    return $wpdb->get_col( $sql );

}





//すべてのtype x type_id x classの評価値rに対して、filter['class'](r)を計算し、{type}metaに格納
//wp_cronで実行
function easy_rating_do_stat() {
    global $wpdb;
    global $CONST_RATING_CLASS_SETTINGS;
    foreach ($CONST_RATING_CLASS_SETTINGS as $class => $setting) {
        $sql = "
            SELECT type, type_id, {$setting['filter']} AS rate
            FROM " . EASY_RATING_DB_TABLE_NAME . "
            WHERE (type='post' OR type='comment' OR type='user') AND class=%s
            GROUP BY type, type_id
            ";
        $prep =  $wpdb -> prepare(
            $sql,
            $class
        );
        //エラー処理必要
        $rows = $wpdb -> get_results( $prep, 'ARRAY_A' );

        foreach ($rows as $row) {
            $type = $row['type'];
            $id = $row['type_id'];
            $rate = $row['rate'];
            $meta_key = easy_rating_get_meta_key( $class );

            if (!($type === 'post' || $type === 'comment' || $type ==='user')) {
                # 不明なタイプのデータベースログを発見した場合
                # そのうちログとりまたは削除の処理の追加が必要
                continue;
            }
            # 変なidがdbに入っていた場合の処理の追加が必要
            if ( get_object_meta( $type, $id, $meta_key, true ) != '' ) {
                update_object_meta( $type, $id, $meta_key, $rate );
            } else {
                add_object_meta( $type, $id, $meta_key, $rate, true );
            }
        }
    }
}


//とりあえず毎回集計させている
easy_rating_do_stat();
// add_action ('easy_rating_system_stat_cron', 'easy_rating_do_stat');
// //イベントの登録
// if ( ! wp_next_scheduled( 'easy_rating_system_stat_cron' ) ) {
//   wp_schedule_event( time(), 'hourly', 'easy_rating_system_stat_cron' );
// }

//イベントの解除
function easy_rating_system_stat_cron_disable() {
    wp_clear_scheduled_hook('easy_rating_system_stat_cron');
}


register_deactivation_hook(__FILE__, 'easy_rating_system_stat_cron_disable');
