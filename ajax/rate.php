<?php
    //これは、ratingを行うajax APIのエントリーポイント
    //価値をdbにinsertまたはupdateしていく

    //必要なテスト項目
    //- user_idの騙り
    //- 過大・過少なrate値

    require_once( dirname(__FILE__) . '../../../../../wp-load.php' );
    require_once( dirname(__FILE__) . '/../config.php' );


    $user_id = get_current_user_id();
    $type = $_POST['type'];
    $type_id = (int)$_POST['type_id'];
    $class = $_POST['class'];
    $rate = (float)$_POST['rate'];
    $nonce = $_POST['nonce'];




    $user_id = ip2long($_SERVER["REMOTE_ADDR"]);






    //csrf対策
    if (!wp_verify_nonce( $nonce, 'easy-rating-system' )) {
        echo 'illegal post1';
        exit;
    }
    //不正なrate値チェック
    $validator = new Validator( $class );
    if ( !( $validator -> is_valid($rate) ) ) {
        echo 'illegal post3';
        exit;
    }


    //データの存在確認
    $sql = "
        SELECT * FROM " . EASY_RATING_DB_TABLE_NAME . "
        WHERE user_id = %d AND
              type = %s AND
              type_id = %d AND
              class = %s
        LIMIT 1;
        ";
    $prep =  $wpdb -> prepare(
        $sql,
        $user_id,
        $type,
        $type_id,
        $class
    );
    $data = $wpdb -> get_results( $prep, 'ARRAY_A' );

    if (!$data) {
            $wpdb -> insert(
            EASY_RATING_DB_TABLE_NAME,
            array(
                'user_id' => $user_id,
                'type'    => $type,
                'type_id' => $type_id,
                'class'   => $class,
                'rate'    => $rate,
                'created' => current_time('mysql', 1)
            ),
            array(
                '%d',
                '%s',
                '%d',
                '%s',
                '%d',
                '%s'
            )
        );
    } else {
        $wpdb -> update(
            EASY_RATING_DB_TABLE_NAME,
            array(
                'rate'     => $rate,
                'modified' => current_time('mysql', 1)
             ),
            array(
                'id' => $data[0]['id']
            ),
            array(
                '%d',
                '%s'
            ),
            array(
                '%d'
            )
        );
    }
