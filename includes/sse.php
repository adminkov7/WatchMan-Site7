<?php
/*
Slave module:   sse.php
Description:    to send a count of records of visitor
Version:        2.2.3
Author:         Oleg Klenitskiy
Author URI:     https://www.adminkov.bcr.by/category/wordpress/
*/

// we specify that we need at least WP
define('SHORTINIT', true);
// loadable environment WordPress
require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php' );

function wms7_count_rows () {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'watchman_site';
    $sql = "SELECT count(*) FROM $table_name ";
    $count_rows = $wpdb->get_var($sql);

    return $count_rows;
}

function sendMessage($data) {
    header("Content-Type: text/event-stream");
    header("Cache-Control: no-cache");
    header("Connection: keep-alive");

    echo "data: $data\n\n";
    ob_flush();
    flush();
}

while (true) {
    $new_count_rows = wms7_count_rows();
    sendMessage($new_count_rows);
    sleep(10);
}