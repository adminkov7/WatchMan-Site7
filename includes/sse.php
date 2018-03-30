<?php
/*
Slave module:   sse.php
Description:    to send a count of records of visitor
Version:        2.2.7
Author:         Oleg Klenitskiy
Author URI:     https://www.adminkov.bcr.by/category/wordpress/
*/

// we specify that we need at least WP
define('SHORTINIT', true);
// loadable environment WordPress
require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php' );

function wms7_mail_inbox_connection() {
//функция с таким же названием есть в файле mail.php плагина
    $val = get_option('wms7_main_settings');    
    $select_box = $val['mail_select'];
    $box = $val[$select_box];

    $server = '{'.$box["imap_server"].':'.$box["mail_box_port"].'/imap/'.$box["mail_box_encryption"].'/novalidate-cert}INBOX';

    $username = $box["mail_box_name"];
    $password = $box["mail_box_pwd"];

    $imap = @imap_open($server, $username, $password);

    return $imap;
}

function wms7_mail_unseen() {
//функция с таким же названием есть в файле mail.php плагина    
    $imap = wms7_mail_inbox_connection();
    $i=0;
    if($imap) {
        $MC = imap_check($imap);
        // Получим обзор всех писем в ящике
        $result = imap_fetch_overview($imap,"1:{$MC->Nmsgs}",0);
        foreach ($result as $overview) {
            if ($overview->seen == '0'){
                $i++;
            }
        }
        imap_close($imap);
    }
    return $i;
}

function wms7_count_rows() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'watchman_site';
    $sql = "SELECT count(*) FROM $table_name ";
    $count_rows = $wpdb->get_var($sql);

    return $count_rows;
}

function sendMessage($data1, $data2) {
    header("Content-Type: text/event-stream");
    header("Cache-Control: no-cache");
    header("Connection: keep-alive");

    echo "data: $data1";
    echo "|$data2";
    echo "\n\n";

    ob_flush();
    flush();
}

while (true) {
    $new_count_rows = wms7_count_rows();
    $mail_unseen = wms7_mail_unseen();
    sendMessage($new_count_rows, $mail_unseen);
    sleep(10);
}