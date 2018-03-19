<?php
/*
Plugin Name:  WatchMan-Site7
Plugin URI:   https://wordpress.org/plugins/watchman-site7/
Description:  This plugin is designed for site administrators and is used to control the visits of site. The plugin has a number of useful service functions for monitoring important system files website.
Author:       Oleg Klenitskiy
Author URI:   https://www.adminkov.bcr.by/category/wordpress/
Contributors: adminkov
Version:      2.2.5
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Domain Path:  /languages
Text Domain:  wms7
*/

//For use standart class WP_List_Table
if( !class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
require_once(__DIR__ . '/includes/mail.php');
require_once(__DIR__ . '/includes/statistic.php');
require_once(__DIR__ . '/includes/wp-cron.php');
require_once(__DIR__ . '/includes/ip-info.php');
require_once(__DIR__ . '/includes/io-interface.php');
require_once(__DIR__ . '/includes/widget.php');
require_once(__DIR__ . '/settings/watchman_site_countries.php');

//Register wms7-navigator.js
function wms7_navigator_load_script() {
  if (strpos($_SERVER['REQUEST_URI'], 'wms7') == false){
    wp_enqueue_script( 'wms7-navigator', plugins_url('/js/wms7-navigator.js', __FILE__ ), array(), NULL, false);
    //for use module wms7-navigator.js
    $wms7_url = plugin_dir_url( __FILE__ );
    ?>
    <script>
    var wms7_url = '<?php echo($wms7_url); ?>';
    </script>
    <?php
    //
  }
}
add_action('wp_enqueue_scripts', 'wms7_navigator_load_script');
//Register wms7-script.js, wms7-style.css
function wms7_load_css_js() {
  if (strpos($_SERVER['REQUEST_URI'], 'wms7') == true){
    wp_enqueue_script( 'wms7-script', plugins_url('/js/wms7-script.js', __FILE__ ), array(), NULL, false);
    wp_enqueue_style( 'wms7', plugins_url('/css/wms7-style.css', __FILE__ ), false, NULL, 'all'); 

    //for use module wms7-script.js
    $wms7_url = plugin_dir_url( __FILE__ );
    ?>
    <script>
    var wms7_url = '<?php echo($wms7_url); ?>';
    </script>
    <?php
    //
  }
}
add_action('admin_enqueue_scripts', 'wms7_load_css_js');

/**
 * wms7_List_Table class that will display custom table
 */

class wms7_List_Table extends WP_List_Table {
  private $wms7Data;

  function __construct() {
    global $wms7; 
      
    parent::__construct(array(
      'singular' 	=> 'wms7_visitor',
      'plural' 		=> 'wms7_visitors',
      'sse'      => false,
      ));
  }

  function wms7_set($name, $value){
    $this->wms7Data[$name] = $value;
  }

  function wms7_get($name){
    return (isset($this->wms7Data[$name])) ? $this->wms7Data[$name] : false;
  }

  function wms7_get_current_url(){
 
    $param = false;
    //get current args from the URL
    $args = wp_parse_args( parse_url($_SERVER["REQUEST_URI"], PHP_URL_QUERY) );

    if( isset($args['filter_country']) )
      $param['filter_country'] = sanitize_text_field($args['filter_country']);

    if( isset($args['filter_role']) )
      $param['filter_role'] = sanitize_text_field($args['filter_role']);

    if( isset($args['filter_time']) )
      $param['filter_time'] = sanitize_text_field($args['filter_time']);

    if( isset($args['filter']) )
      $param['filter'] = sanitize_text_field($args['filter']);

    if( isset($args['result']) )
      $param['result'] = sanitize_text_field($args['result']);    

    $menu_page_url = menu_page_url('wms7_visitors', false);
    ( is_array($param) && !empty($param) ) ? $url = add_query_arg( $param, $menu_page_url) : $url = $menu_page_url;      
    //save the current url of the plugin in wp_options
    update_option( 'wms7_current_url', $url ); 

    return $url;
  }
  //Standart function: WP_List_Table
  function extra_tablenav( $which ) {
    $url = $this->wms7_get_current_url();
    $val = get_option('wms7_screen_settings');

    if ( $which == 'top' ){

      $all_link = isset($val['all_link']) ? $val['all_link'] : 0;
      $unlogged_link = isset($val['unlogged_link']) ? $val['unlogged_link'] : 0;
      $successful_link = isset($val['successful_link']) ? $val['successful_link'] : 0;
      $failed_link = isset($val['failed_link']) ? $val['failed_link'] : 0;
      $robots_link = isset($val['robots_link']) ? $val['robots_link'] : 0;
      $blacklist_link = isset($val['blacklist_link']) ? $val['blacklist_link'] : 0;      

      $hidden_all_link = ($all_link == '1') ? "" : 'hidden="true"';
      $hidden_unlogged_link = ($unlogged_link =='1') ? "" : 'hidden="true"';
      $hidden_successful_link = ($successful_link =='1') ? "" : 'hidden="true"';
      $hidden_failed_link = ($failed_link =='1') ? "" : 'hidden="true"';
      $hidden_robots_link = ($robots_link =='1') ? "" : 'hidden="true"';
      $hidden_blacklist_link = ($blacklist_link =='1') ? "" : 'hidden="true"';

      echo '<a class="visit_result" title="' . __('Filter 2 level', 'wms7').'">'.__('Visits','wms7').' : </a>';

      echo '<input onclick=visit(id) class="radio" id="radio-1" name="radio_visits" type="radio" value="1" '.$hidden_all_link.' >
      <label for="radio-1" '.$hidden_all_link.'>'.__('All','wms7').'('. $this->wms7_get('allTotal').')</label>';

      echo '<input onclick=visit(id) class="radio" id="radio-2" name="radio_visits" type="radio" value="1" '.$hidden_unlogged_link.'>
      <label for="radio-2" '.$hidden_unlogged_link.'>'.__('Unlogged','wms7').'('. $this->wms7_get('visitsTotal').')</label>';

      echo '<input onclick=visit(id) class="radio" id="radio-3" name="radio_visits" type="radio" value="1" '.$hidden_successful_link.'>
      <label for="radio-3" '.$hidden_successful_link.'>'.__('Success','wms7').'('. $this->wms7_get('successTotal').')</label>';
  
      echo '<input onclick=visit(id) class="radio" id="radio-4" name="radio_visits" type="radio" value="1" '.$hidden_failed_link.'>
      <label for="radio-4" '.$hidden_failed_link.'>'.__('Failed','wms7').'('. $this->wms7_get('failedTotal').')</label>';
 
      echo '<input onclick=visit(id) class="radio" id="radio-5" name="radio_visits" type="radio" value="1" '.$hidden_robots_link.'>
      <label for="radio-5" '.$hidden_robots_link.'>'.__('Robots','wms7').'('. $this->wms7_get('robotsTotal').')</label>';

      echo '<input onclick=visit(id) class="radio" id="radio-6" name="radio_visits" type="radio" value="1" '.$hidden_blacklist_link.'>
      <label for="radio-6" '.$hidden_blacklist_link.'>'.__('Black list','wms7').'('. $this->wms7_get('blacklistTotal').')</label>';
    }
      //switcher top & bottom
      $mode = ( isset($_GET['mode']) ) ? sanitize_text_field($_GET['mode']) : "list";
      $table = new wms7_List_Table();
      $table->view_switcher($mode);

    if ( $which == 'bottom' ) {

      $index_php = isset($val['index_php']) ? $val['index_php'] : 0;
      $robots_txt = isset($val['robots_txt']) ? $val['robots_txt'] : 0;
      $htaccess = isset($val['htaccess']) ? $val['htaccess'] : 0;
      $wp_config_php = isset($val['wp_config_php']) ? $val['wp_config_php'] : 0;
      $wp_cron = isset($val['wp_cron']) ? $val['wp_cron'] : 0;
      $statistic = isset($val['statistic']) ? $val['statistic'] : 0;
      $mail = isset($val['mail']) ? $val['mail'] : 0;

      $hidden_index_php = ($index_php == '1') ? "" : 'style="display:none"';
      $hidden_robots_txt = ($robots_txt =='1') ? "" : 'style="display:none"';
      $hidden_htaccess = ($htaccess =='1') ? "" : 'style="display:none"';
      $hidden_wp_config_php = ($wp_config_php =='1') ? "" : 'style="display:none"';
      $hidden_wp_cron = ($wp_cron =='1') ? "" : 'style="display:none"';
      $hidden_statistic = ($statistic =='1') ? "" : 'style="display:none"';
      $hidden_mail = ($mail =='1') ? "" : 'style="display:none"';

      //The code adds the buttons after the table
      $btn0 = '<form id="заглушка" method="POST">
              </form>';
      echo ($btn0);
      $btn1 = '<form id="win1" method="POST">
              <input type="submit" value="index" id="btn_bottom" class="button"  name="footer" '.$hidden_index_php.' title="'.__('index.php of site', 'wms7').'" style="width:80px;" >
              </form>';
      echo ($btn1);
      $btn2 = '<form id="win2" method="POST">
              <input type="submit" value="robots" id="btn_bottom" class="button"  name="footer" '.$hidden_robots_txt.' title="'.__('robots.txt of site', 'wms7').'" style="width:80px;" >
              </form>';
      echo ($btn2);
      $btn3 = '<form id="win3" method="POST">
              <input type="submit" value="htaccess" id="btn_bottom" class="button"  name="footer" '.$hidden_htaccess.' title="'.__('.htaccess of site', 'wms7').'" style="width:80px;" >
              </form>';
      echo ($btn3);
      $btn4 = '<form id="win4" method="POST">
              <input type="submit" value="wp_config" id="btn_bottom" class="button"  name="footer" '.$hidden_wp_config_php.' title="'.__('wp-config.php of site', 'wms7').'" style="width:80px;" >
              </form>';
      echo ($btn4);
      $btn5 = '<form id="win5" method="POST">
              <input type="submit" value="wp_cron" id="btn_bottom" class="button"  name="footer" '.$hidden_wp_cron.'  title="'.__('wp-cron events of site', 'wms7').'" style="width:80px;" >
              </form>';
      echo ($btn5);
      $btn6 = '<form id="win6" method="POST">
              <input type="submit" value="statistic" id="btn_bottom" class="button"  name="footer" '.$hidden_statistic.' title="'.__('statistic of visits to site', 'wms7').'" style="width:80px;" >
              </form>';
      echo ($btn6);
      $btn7 = '<form id="win7" method="POST">
              <input type="submit" value="sma" id="btn_bottom" class="button"  name="footer" '.$hidden_mail.' title="'.__('simple mail agent', 'wms7').'" style="width:80px;" >
              </form>';
      echo ($btn7);
    }
  }

  function wms7_IP_compromising( $user_IP ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'watchman_site';
 
    $ip_compromising = $wpdb->get_results( $wpdb->prepare( 
      "
      SELECT `user_ip` 
      FROM $table_name  
      WHERE `user_ip` = %s AND `black_list` <> %s
      ",
      $user_IP,''
      ),
      'ARRAY_A'
    );     
      if (count($ip_compromising) == 0) {
        return FALSE;
        }else{
        return TRUE;
      }
  }
  //Standart function: WP_List_Table
  function column_default($item, $column_name) {
    switch($column_name){
      case 'id':
      case 'uid':
      case 'time_visit':
      return $item[$column_name];
      case 'user_login':
      if( $item['uid'] ) {
      	$avatar = get_avatar( $item['uid'], 30 );
    	}
    	if (isset($avatar)){
    		return $avatar.'<br>'.$item[$column_name];
    	}else{
    		return $item[$column_name];
    	}
      case 'user_role':
      if( !$item['uid'] )
        return;
      $user = new WP_User( $item['uid'] );
      if ( !empty( $user->roles ) && is_array( $user->roles ) ) {
        foreach($user->roles as $role){
          $roles[] = $role;
        }
        unset($role);
        return implode(', ', $roles);
      }
      break;
      case 'page_from':        
      $output = $item[$column_name];
      $output = ( isset($_GET['mode']) && 'excerpt' == $_GET['mode'] ) ? $output : substr($output, 0, 130) . '...';
      return $output;  
      case 'info': 
      $data = unserialize($item[$column_name]);
      if(is_array($data)){
        $output = '';
        foreach($data as $k => $v){
          $output .= $k .': '. $v .'<br />';
        }
        unset($k);
        $output = ( isset($_GET['mode']) && 'excerpt' == $_GET['mode'] ) ? $output : substr($output, 0, 130) . '...';
        return $output;
      }
      break;
      default:
      return $item[$column_name];
    }
  }
  //Standart function: WP_List_Table
  function column_user_role($item) {
    if( !$item['uid'] ) return;

    $user = new WP_User( $item['uid'] );
    $user_role = isset($user->roles[0]) ? $user->roles[0] : 'undefined';
    return $user_role;
  }
  //Standart function: WP_List_Table
  function column_user_ip($item) {
    //Checking the compromising IP
    if ($this->wms7_IP_compromising($item['user_ip']) == TRUE) {
      $item['user_ip'] = '<span class="failed">'.$item['user_ip'].'</span>';
    }

    $URL = $this->wms7_get_current_url();
    $actions = array(
      'map' => sprintf('<a href="'.$URL.'&action=map&id=%s&paged=%s">%s</a>', $item['id'], get_option('wms7_current_page'), __('Map', 'wms7'))
    );

    return sprintf('%s %s',
      $item['user_ip'] . '<br>' . $item['country'],
      $this->row_actions($actions)
    );
  }
  //Standart function: WP_List_Table
  function column_black_list($item) {

    $URL = $this->wms7_get_current_url();
    $output='';

    $data = unserialize($item['black_list']);
    if(is_array($data)){
      $output = '';
      foreach($data as $k => $v){
        $output .= $k .': '. $v .'<br />';
      }
      unset($k);
    }

    $actions = array(
      'edit' => sprintf('<a href="?page=wms7_black_list&id=%s">%s</a>', $item['id'], __('Edit', 'wms7')),
      'clear' => sprintf('<a href="'.$URL.'&action=clear&id=%s&paged=%s">%s</a>', $item['id'], get_option('wms7_current_page'), __('Clear', 'wms7')),
    );

    return sprintf('%s %s',
      $output,
      $this->row_actions($actions)
    );
  }
  //Standart function: WP_List_Table
  function column_cb($item) {
    return sprintf(
      '<input type="checkbox" name="id[]" value="%s" />',
      $item['id']
    );
  }
  //Standart function: WP_List_Table
  function get_columns() {
    $columns = array(
      'cb'            =>'<input type="checkbox" />',
      'id'            => __('ID', 'wms7'),
      'uid'           => __('UID', 'wms7'),
      'user_login'    => __('Login', 'wms7'),
      'user_role'     => __('Role', 'wms7'),
      'time_visit'    => __('time_visit', 'wms7'),
      'user_ip'       => __('Visitor IP', 'wms7'),
      'black_list'    => __('Black list', 'wms7'),
      'page_visit'    => __('Page Visit', 'wms7'),
      'page_from'     => __('Page From', 'wms7'),
      'info'          => __('Info', 'wms7'),
    );
    return $columns;
  }
  //Standart function: WP_List_Table
  function get_sortable_columns() {
    $sortable_columns = array(
      'id'            => array('id',true), 
      'uid'           => array('uid',false),
      'user_login'    => array('user_login', false),
      'user_role'     => array('user_role', false),
      'time_visit'    => array('time_visit',true),
      'user_ip'       => array('user_ip', false),
      'page_visit'    => array('page_visit',true),
      'page_from'     => array('page_from',true),
      );
    return $sortable_columns;
  }
  //Standart function: WP_List_Table
  function get_bulk_actions() {
    $actions = array(
      'delete' => __('Delete', 'wms7'),
      'export' => __('Export', 'wms7')
      );
    return $actions;
  }
  //Standart function: WP_List_Table
  function process_bulk_action() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'watchman_site';

    if (get_option('wms7_current_page') == $this->get_pagenum()) {
        //do not sanitize_text_field $_REQUEST['id']
        if (isset($_REQUEST['id'])) {update_option('wms7_id',$_REQUEST['id']);}

        if (isset($_REQUEST['action']) || isset($_REQUEST['action2'])) {
          if ($_REQUEST['action'] == -1) {
            update_option('wms7_action',sanitize_text_field($_REQUEST['action2']));
          }else{
            update_option('wms7_action',sanitize_text_field($_REQUEST['action']));        
          }
        }
      }else{
        delete_option('wms7_id');
        delete_option('wms7_id_del'); //for action - delete
        delete_option('wms7_action');

        if (isset($_REQUEST["action"])) {
          // to update the url of main plugin page
          $URL = $this->wms7_get_current_url().'&paged='.$this->get_pagenum();
          echo '<script>location.replace("'.$URL.'");</script>';
        }
    }

    if ('export' === $this->current_action()) {
      wms7_output_csv();
    }

    if ('delete' === $this->current_action()) {

      $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
      if (is_array($ids)) $ids = implode(',', $ids);
      
      $ids = sanitize_text_field($ids);
      if ($ids !== '') {
        $wms7_id_del = $wpdb->query( $wpdb->prepare(
          "
          DELETE 
          FROM $table_name 
          WHERE `id` IN ($ids)  AND `black_list` = %s
          ",
          ''
        ));
        update_option('wms7_id_del',$wms7_id_del);
      }
    }

    if ('clear' === $this->current_action()) {
      
      $id = sanitize_text_field($_REQUEST['id']);
      $wpdb->update( $table_name, array( 'black_list' => '' ), array( 'ID' => $id ) );
      // delete ip from .htaccess
      $user_ip = $wpdb->get_results( $wpdb->prepare(
        "
        SELECT `user_ip` 
        FROM $table_name 
        WHERE `id` = %s
        ",
        $id
        ),
        'ARRAY_A'
      );
      $fld = implode("^", $user_ip[0]);

       wms7_ip_delete_from_file($fld);
    }
  }

  //Standart function: WP_List_Table
  function prepare_items() {
    global $wpdb, $wms7;

    $this->process_bulk_action();

    $table_name = $wpdb->prefix . 'watchman_site';

    $where = $wms7->wms7_make_where_query();

    $where6 = $where5 = $where4 = $where3 = $where2 = $where;

    unset($where2['result']);
    unset($where3['result']);
    unset($where4['result']);
    unset($where5['result']);
    unset($where6['result']);

    $where2['login_result'] = "login_result = '1'"; // success visits
    $where3['login_result'] = "login_result = '0'"; // failed visits
    $where4['login_result'] = "login_result = '2'"; // simple visits
    $where5['login_result'] = "login_result = '3'"; // robots visits
    $where6['login_result'] = "black_list <> ''";   // black list

    if(is_array($where2) && !empty($where2)){
      $where2 = 'WHERE ' . implode(' AND ', $where2);
      }else{$where2 = '';}
    if(is_array($where3) && !empty($where3)){
      $where3 = 'WHERE ' . implode(' AND ', $where3);
      }else{$where3 = '';}
    if(is_array($where4) && !empty($where4)){
      $where4 = 'WHERE ' . implode(' AND ', $where4);
      }else{$where4 = '';}
    if(is_array($where5) && !empty($where5)){
      $where5 = 'WHERE ' . implode(' AND ', $where5);
      }else{$where5 = '';}
    if(is_array($where6) && !empty($where6)){
      $where6 = 'WHERE ' . implode(' AND ', $where6);
      }else{$where6 = '';}

    $sql1 = "SELECT count(*) FROM $table_name ";
    $allTotal = $wpdb->get_var($sql1);
    $sql2 = "SELECT count(*) FROM $table_name ".$where2;
    $successTotal = $wpdb->get_var($sql2);
    $sql3 = "SELECT count(*) FROM $table_name ".$where3;
    $failedTotal = $wpdb->get_var($sql3);
    $sql4 = "SELECT count(*) FROM $table_name ".$where4;
    $visitsTotal = $wpdb->get_var($sql4);
    $sql5 = "SELECT count(*) FROM $table_name ".$where5;
    $robotsTotal = $wpdb->get_var($sql5);           
    $sql6 = "SELECT count(*) FROM $table_name ".$where6;
    $blacklistTotal = $wpdb->get_var($sql6); 

    $this->wms7_set('allTotal', $allTotal);
    $this->wms7_set('successTotal', $successTotal);
    $this->wms7_set('failedTotal', $failedTotal);
    $this->wms7_set('visitsTotal', $visitsTotal);
    $this->wms7_set('robotsTotal', $robotsTotal);
    $this->wms7_set('blacklistTotal', $blacklistTotal);   
   //save the current parameters for popup windows statistic
    update_option( 'wms7_current_param', array(	'allTotal'=>$allTotal,
    																						'visitsTotal'=> $visitsTotal,
    																						'successTotal'=>$successTotal,
    																						'failedTotal'=> $failedTotal,
    																						'robotsTotal'=> $robotsTotal,
    																						'blacklistTotal'=> $blacklistTotal) ); 

    $screen = get_current_screen();
    $per_page_option = 'wms7_visitors_per_page';
    $per_page = get_option($per_page_option, 10);
    $offset = $per_page * ($this->get_pagenum() - 1);

    $columns = $this->get_columns();
    $hidden_cols = get_user_option( 'manage' . $screen->id . 'columnshidden' );
    $hidden = ( $hidden_cols ) ? $hidden_cols : array();
    $sortable = $this->get_sortable_columns();

    $this->_column_headers = array($columns, $hidden, $sortable);

    $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'id';
    $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'desc';

    $this->items = $wms7->wms7_visit_get_data($orderby, $order, $per_page, $offset);

    if(isset($_GET['result']) && $_GET['result'] == '4'){
      $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $wms7->table {$where6}");
    }
    else if(isset($_GET['result']) && $_GET['result'] == '3'){
      $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $wms7->table {$where5}");
    }
    else if(isset($_GET['result']) && $_GET['result'] == '2'){
      $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $wms7->table {$where4}");
    }
    else if (isset($_GET['result']) && $_GET['result'] == '1'){
      $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $wms7->table {$where2}");
    }
    else if(isset($_GET['result']) && $_GET['result'] == '0'){
      $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $wms7->table {$where3}");

    }else{
      $where = ($where) ? ('WHERE ' . implode(' AND ', $where)) : false;
      $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $wms7->table {$where}");
    }

   //save the current page number of the table in wp_options
    update_option( 'wms7_current_page', $this->get_pagenum() );   

    $this->set_pagination_args(array(
        'total_items' => $total_items, // total items defined above
        'per_page' => $per_page, // per page constant defined at top of method
        'total_pages' => ceil($total_items / $per_page) // calculate pages count
    ));
  }
}

if( !class_exists( 'WatchManSite7' ) ) {
  class WatchManSite7 {
    private $table_name = 'watchman_site';
    private $user_login;
    private $hidden;
    private $login_result;

  function __construct() {
    global $wpdb;

    $this->table = $wpdb->prefix . $this->table_name;

    add_action('init', array($this, 'wms7_languages'));
    add_action('init', array($this, 'wms7_init_visit_actions'));
    add_action('init', array($this, 'wms7_lat_lon_save'));
    add_action('admin_init', array($this, 'wms7_main_settings'));
    add_action('admin_menu', array($this, 'wms7_admin_menu'));
    add_action('admin_head', array($this, 'wms7_screen_options'));
    add_action('plugins_loaded', array($this, 'wms7_load_locale'), 10 );
    add_filter('screen_settings', array($this, 'wms7_screen_settings_add'), 10, 2);
    add_filter('set-screen-option', array($this, 'wms7_screen_settings_save'), 11, 3);

    add_action( 'wms7_truncate', array($this, 'wms7_truncate_log'));
    if (!wp_next_scheduled('wms7_truncate')) {wp_schedule_event(time(), 'daily', 'wms7_truncate');}
    add_action( 'wms7_htaccess', array($this, 'wms7_ctrl_htaccess'));
    if (!wp_next_scheduled('wms7_htaccess')) {wp_schedule_event(time(), 'hourly', 'wms7_htaccess');}
  }

  function wms7_ctrl_htaccess(){
    //insert/delete - Deny from IP
    $output = explode('&#010;',$this->wms7_black_list_info());
    foreach($output as $step1){
      if (!empty($step1)) { 
          $step2 = explode('&#009;', $step1);
          if (date('Y-m-d') >= $step2[0] &&  date('Y-m-d') <= $step2[1]){
              wms7_ip_insert_to_file($step2[2]);
            }else{
              wms7_ip_delete_from_file($step2[2]);
          }
      }
    }
    unset($step1);
  }
    
  function wms7_ctrl_htaccess_add(){    
    //insert/delete - RewriteCond %{HTTP_USER_AGENT} name robot
    $val =  get_option('wms7_main_settings');
    $val = $val['robots_banned'];

    wms7_rewritecond_delete();

    if (!empty($val)) {
      $result=explode(';', $val);
      foreach($result as $robot_banned){
        if (!empty($robot_banned)) {         
          wms7_rewritecond_insert($robot_banned);
        }
      }
      unset($robot_banned);
    }
  }

  function wms7_truncate_log(){
    global $wpdb;

    $opt = get_option('wms7_main_settings');
    $log_duration = (int)$opt['log_duration'];

    if( 0 < $log_duration ){
      $sql = $wpdb->prepare( 
        "
        DELETE 
        FROM {$this->table} 
        WHERE `black_list` = '' AND `time_visit` < DATE_SUB(CURDATE(),INTERVAL %d DAY)
        ", 
        $log_duration
      );
      $wpdb->query($sql);
    }
  }

  function wms7_lat_lon_save() {
    if ( stristr($_SERVER['REQUEST_URI'], 'wp-admin')) {return;}
    
    if (isset($_POST['Err_code_js'])) $Err_code = sanitize_text_field($_POST['Err_code_js']);
    if (isset($_POST['Err_msg_js'])) {$Err_msg =  sanitize_text_field($_POST['Err_msg_js']);
      }else{
      $Err_msg ='(ok)';
    }
     $Err_msg = str_replace("\'", "", $Err_msg);
    if (isset($_POST['Lat_wifi_js'])) $Lat_wifi = sanitize_text_field($_POST['Lat_wifi_js']);
    if (isset($_POST['Lon_wifi_js'])) $Lon_wifi = sanitize_text_field($_POST['Lon_wifi_js']);
    if (isset($_POST['Acc_wifi_js'])) $Acc_wifi = sanitize_text_field($_POST['Acc_wifi_js']);

    if ( isset($Lat_wifi) && isset($Lon_wifi) && isset($Acc_wifi) ) {
     	$this->wms7_save_geolocation($Lat_wifi, $Lon_wifi, $Acc_wifi, '0', 'ok');
		}
    if ( isset($Err_code) && isset($Err_msg) ) {
     	$this->wms7_save_geolocation('', '', '', $Err_code, $Err_msg);
		}
    unset($_POST['Err_code_js']);
    unset($_POST['Err_msg_js']);
    unset($_POST['Lat_wifi_js']);
    unset($_POST['Lon_wifi_js']);
    unset($_POST['Acc_wifi_js']);
  }

  function wms7_save_geolocation($Lat_wifi, $Lon_wifi, $Acc_wifi, $Err_code, $Err_msg) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'watchman_site';
    $id = get_option( 'wms7_last_id' );

    $wpdb->update( $table_name,
      array( 'geo_wifi' =>
         'Err_code=' . $Err_code . '<br>'
        .'Err_msg=' .  $Err_msg . '<br>'
        .'Lat_wifi=' . $Lat_wifi . '<br>'
        .'Lon_wifi=' . $Lon_wifi .'<br>'
        .'Acc_wifi=' . $Acc_wifi ),
      array( 'ID' => $id ),
      array('%s')
    );
  }

  function wms7_load_locale(){
    load_plugin_textdomain( 'wms7', false, basename(dirname(__FILE__)) . '/languages/' );
  }

  function wms7_init_visit_actions(){

    //Action on successful login
    add_action( 'wp_login', array($this, 'wms7_login_success') );
    
    //Action on failed login
    add_action( 'wp_login_failed', array($this, 'wms7_login_failed') );
  
    //Action visit to site
    $this->wms7_visit_site($this->user_login);
  }

  function wms7_visit_site($user_login){
    $userdata =  wp_get_current_user();
    if ($userdata->ID !== 0) {
      $this->login_result = 1;
    }else{
      $this->login_result = 2;
    }
    $this->wms7_login_action($user_login);
  }

  function wms7_login_success($user_login){
    $this->login_result = 1;
    $this->wms7_login_action($user_login);
  }

  function wms7_login_failed($user_login){
    $this->login_result = 0;
    $this->wms7_login_action($user_login);
  }

  function wms7_login_action($user_login){
    //get user role
    global $current_user;

    //get user IP
    $user_IP = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']) : sanitize_text_field($_SERVER['REMOTE_ADDR']);

    //get user IP info
    $user_IP_info = 'REQUEST_URI = '.sanitize_text_field($_SERVER['REQUEST_URI']). '&#010;'.
                    'REMOTE_ADDR = '.sanitize_text_field($_SERVER['REMOTE_ADDR']). '&#010;'.                   
                    'SERVER_ADDR = '.sanitize_text_field($_SERVER['SERVER_ADDR']). '&#010;'.
                    'SERVER_NAME = '.sanitize_text_field($_SERVER['SERVER_NAME']). '&#010;'.
                    'SERVER_SOFTWARE = '.sanitize_text_field($_SERVER['SERVER_SOFTWARE']);

    //Check $user_IP is excluded from the protocol visits
    if ($this->wms7_IP_excluded( $user_IP )) {
      $excluded = TRUE;
      return;
      }else{
      $excluded = FALSE;
    }    

    $userdata =  wp_get_current_user();

    $uid = ($userdata->ID) ? $userdata->ID : 0;

    $user_login = (isset($user_login)) ? sanitize_text_field($user_login) : $userdata->user_login;
    $log = (isset($_POST['log'])) ? ('log: '.sanitize_text_field($_POST['log'])) : null;
    $pwd = (isset($_POST['pwd'])) ? ('<br>pwd: '.sanitize_text_field($_POST['pwd'])) : null;
    $rmbr = (isset($_POST['rememberme'])) ? ('<br>rmbr: '.sanitize_text_field($_POST['rememberme'])) : null;
    $user = ($user_login) ? $user_login : $log.$pwd.$rmbr;

    $user_roles = $current_user->roles;
    $user_role = array_shift($user_roles);
    if (is_null($user_role)) $user_role ='';
    
    //get page_visit
    $page_visit = sanitize_text_field($_SERVER['REQUEST_URI']);
    if (stristr($page_visit, 'watchman-site7/watchman-site7')) {
      $page_visit = '<b style="border: 1px solid black;padding: 1px;">'.__('get the geolocation', 'wms7').'</b>';
    }

    //get page_from
    $page_from = isset($_SERVER['HTTP_REFERER']) ? sanitize_text_field($_SERVER['HTTP_REFERER']) :'';

    if ( stristr($page_visit, 'wp-admin')) {return;}
    if ( stristr($page_visit, 'admin-sse.php')) {return;}
    if ( stristr($page_visit, 'wp-cron.php')) {return;}
    if ( stristr($page_from, $page_visit)) {return;}

    //get info
    if ($this->login_result == 0) $data['Login'] = '<span class="failed">'.__('Failed','wms7').'</span>';
    if ($this->login_result == 1) $data['Login'] = '<span class="successful">'.__('Success','wms7').'</span>';
    if ($this->login_result == 2) $data['Login'] = '<span class="unlogged">'.__('Unlogged','wms7').'</span>';
    $data['User Agent'] = sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] );
    $serialized_data = serialize($data);

    //get robot
	   $robot ='';
    if ($this->wms7_robots($serialized_data)) {	 
        $val = get_option('wms7_main_settings');
  		if ($val['robots_reg']) {
  			$robot = $this->wms7_robots($serialized_data);
          	$this->login_result = '3';        
  		}else{ return;}
    }

    //get whois_service
    $val = get_option('wms7_main_settings');
    $whois_service = isset($val['whois_service']) ? $val['whois_service'] : '';

    $arr = wms7_who_is($user_IP, $whois_service);
    //Not use sanitize_text_field or esc_attr
    $country = isset($arr['country']) ? ($arr['country']) : '';
    $geo_ip = isset($arr['geo_ip']) ? $arr['geo_ip'] : '';
    $provider = isset($arr['provider']) ? $arr['provider'] : '';
    //
    $values = array(
      'uid'           => $uid,
      'user_login'    => $user,
      'user_role'     => $user_role,
      'time_visit'    => current_time('mysql'),
      'user_ip'       => $user_IP,
      'user_ip_info'  => $user_IP_info,
      'black_list'    => '',
      'whois_service' => isset($whois_service) ? $whois_service : '',
      'country'       => isset($country) ? $country : '',
      'provider'      => isset($provider) ? $provider : '',
      'geo_ip'        => isset($geo_ip) ? $geo_ip : '',
      'login_result'  => $this->login_result,
      'robot'         => $robot,
      'page_visit'    => $page_visit,
      'page_from'     => $page_from,
      'info'          => $serialized_data,
      );

$format = array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s','%s','%s','%s');

    $this->wms7_save_data($values, $format, $robot, $excluded);   
  }

  function wms7_save_data($values, $format, $robot, $excluded){
    global $wpdb;

    $wpdb->insert( $this->table, $values, $format );
    update_option( 'wms7_last_id', $wpdb->insert_id );
  }

  function wms7_IP_excluded( $user_IP ){

    $val = get_option('wms7_main_settings');
    $val = $val['ip_excluded'];

    if (empty($val)) {
      return FALSE;
    }else{
      if(stristr($val, $user_IP) === FALSE) {
        return FALSE;
      }else{
        return TRUE;
      }
    }
  }

  function wms7_robots($serialized_data){

    $val = get_option('wms7_main_settings');
    $val = $val['robots'];

    if (!empty($val)) {
      $result=explode(';', $val);

      foreach($result as $robot){
        if (!empty($robot)) {  
          if (stristr($serialized_data, $robot) )  return $robot;
        }
      }
      unset($robot);
      return FALSE;
    }
  }

  function wms7_admin_menu() {

    wp_register_style('wms7-style', WP_PLUGIN_URL . '/css/wms7-style.css');

    add_menu_page(__('Visitors', 'wms7'), __('Visitors', 'wms7'), 'activate_plugins', 'wms7_visitors', array($this,'wms7_visit_manager'), 'dashicons-shield','71');
    add_submenu_page('wms7_visitors', __('Visitors', 'wms7'), __('Visitors', 'wms7'), 'activate_plugins', 'wms7_visitors', array($this,'wms7_visit_manager'));
      // add new will be described in next part
    add_submenu_page('NULL', __('Black list', 'wms7'), __('Black list', 'wms7'), 'activate_plugins', 'wms7_black_list', array($this, 'wms7_black_list'));
      // settings will be described in next part
    add_submenu_page('wms7_visitors', __('Settings', 'wms7'), __('Settings', 'wms7'), 'activate_plugins', 'wms7_settings',array($this, 'wms7_settings'));    
  }

  function wms7_visit_get_data($orderby = false, $order = false, $limit = 0, $offset = 0){
    global $wpdb;

    $where = '';

    $where = $this->wms7_make_where_query();

    $orderby = (!isset($orderby) || $orderby == '') ? 'time_visit' : $orderby;
    $order = (!isset($order) || $order == '') ? 'DESC' : $order;

    if( is_array($where) && !empty($where) )
      $where = ' WHERE ' . implode(' AND ', $where);

    $sql = "SELECT * FROM $this->table" . $where . " ORDER BY {$orderby} {$order} " . 'LIMIT ' . $limit . ' OFFSET ' . $offset;
    $data = $wpdb->get_results($sql, 'ARRAY_A');

    return $data;
  }

  function wms7_make_where_query(){
    $where = false;
    if( isset($_GET['filter']) && '' != $_GET['filter'] )
    {
      $filter = sanitize_text_field( $_GET['filter'] );
      $where['filter'] = "(user_login LIKE '%{$filter}%' OR user_ip LIKE '%{$filter}%')";
    }

    if( isset($_GET['filter_country']) && '' != $_GET['filter_country'] )
    {
      $filter_country = sanitize_text_field( $_GET['filter_country'] );
      $where['filter_country'] = "country LIKE '%{$filter_country}%'";
    }

    if( isset($_GET['filter_role']) && '' != $_GET['filter_role'] )
    {
      $filter_role = sanitize_text_field( $_GET['filter_role'] );
      if ($filter_role == 0 ) {
        $where['filter_role'] = "uid <> 0 AND user_role = '{$filter_role}'";
      }else{
        $where['filter_role'] = "user_role = {$filter_role}";
      }
    }

    if( isset($_GET['filter_time']) && '' != $_GET['filter_time'] ){
      $filter_time = sanitize_text_field( $_GET['filter_time'] );
      $year = substr($filter_time, 0, 4);
      $month = substr($filter_time, -2);
      $where['filter_time'] = "YEAR(time_visit) = {$year} AND MONTH(time_visit) = {$month}";
    }

    if( isset($_GET['result']) && ('5' !== $_GET['result']) ){
      $result = sanitize_text_field( $_GET['result'] );
      if ($result == 4) {
        $where['result'] = "black_list <> ''";
      }else{
        $where['result'] = "login_result = '{$result}'";
      }
    }        
    return $where;
  }

  function wms7_screen_options(){
        //execute only on wms7_visitors page, othewise return null
    $page = ( isset($_GET['page']) ) ? sanitize_text_field($_GET['page']) : false;
    if( 'wms7_visitors' != $page &&  'wms7_settings' != $page &&  'wms7_black_list' != $page) return;

    if( 'wms7_visitors' == $page ) {
        //define options
      $per_page_field = 'per_page';
      $per_page_option = 'wms7_visitors_per_page';
      $img1 = plugins_url('/images/filters_1level.png', __FILE__);
      $img2 = plugins_url('/images/filters_2level.png', __FILE__);
      $img3 = plugins_url('/images/panel_info.png', __FILE__);
      $img4 = plugins_url('/images/bulk_actions.png', __FILE__);
      $img5 = plugins_url('/images/screen_options.png', __FILE__);
      $img6 = plugins_url('/images/other_functions.png', __FILE__);
      $img7 = plugins_url('/images/map1.png', __FILE__);
      $img8 = plugins_url('/images/map2.png', __FILE__);
      $img9 = plugins_url('/images/sse.png', __FILE__);
      $url = site_url();
        //if per page option is not set, use default
      $per_page_val = get_option($per_page_option, '10');

      $args = array('label' => __('The number of elements on the page:', 'wms7'), 'default' => $per_page_val );

        //display options
      add_screen_option($per_page_field, $args);

      get_current_screen() -> add_help_tab(array(
        'id'        => 'wms7-tab-1',
        'title'     => __('1.Description', 'wms7'),
        'content'   => '<p>'.__('This plugin is written for administrators of sites created on Wordpress. The main functions of the plugin are: <br />1. Record the date and time of visit to the site by people, robots.<br />2. The entry registration site visit: successful, unsuccessful, no registration.<br />3. The entry address of the visitor: country of the visitor, the address of a web resource.<br />4. Record information about the browser, OS of the visitor.<br />5. A visitor record in the category of unwelcome and a ban on visiting the site in a set period of time.<br />For convenience the administrator of the site plugin used:<br />1. Filters 1 level.<br />2. Filters 2 level.<br />3. The deletion of unnecessary records on the visit in automatic and manual modes.<br />4. Export records of visits to the site in an external file for later analysis.', 'wms7').'</p>',
        ));
      get_current_screen() -> add_help_tab(array(
        'id'        => 'wms7-tab-2',
        'title'     => __('2.Filters level 1', 'wms7'),
        'content'   => __('The first level filters are filters located in the upper part of the main page of the plugin:<br />- (I group) <a href="https://codex.wordpress.org/Roles_and_Capabilities#Roles" target="_blank">role</a> of visitors.<br />- (I group) date (month/year) visiting the site.<br />- (group II), the username or IP of the visitor of the website.<br />Filters of the first level are major and affect the operation of filters of the second level. At the first level of filters in groups I and II are mutually exclusive and can simultaneously work with only one group of filters of the first level. The range of values in the drop-down filter list 1 level I group is based on actual visits to the site visitors. <br /><br />Filter 1 level (groups I and II)', 'wms7').'<br /><img src='.$img1.' style="float: left;">',
        ));
      get_current_screen() -> add_help_tab(array(
        'id'        => 'wms7-tab-3',
        'title'     => __('3.Filters level 2', 'wms7'),
        'content'   => __('The second level filters are filters located in the upper part of the main page of the plugin under the colour panel:<br />- All visits (number of visits).<br />- Visit without registering on the website (number of visits).<br />- Visits to successful registered users of the website (number of visits).<br />- Unsuccessful attempts to register on the website website visitors (number of attempts).<br />- list of the robots visiting the website (number of visits).<br />- Visitors to the website listed in the black list (the number).<br /> Filter 2 level, working within the rules set by the filters 1 level.<br /><br />Filter 2 level (6 pieces)', 'wms7').'<br /><img src='.$img2.' style="float: left;">',
        ));
      get_current_screen() -> add_help_tab(array(
        'id'        => 'wms7-tab-4',
        'title'     => __('4.Panel info', 'wms7'),
        'content'   => __('Dashboard (panel - info) consists of four information blocks:<br />- Block - settings/General.<br />it displays the settings of the plugin installed on the Settings page.<br />- Unit - History of visits to the site.<br />it displays the types of site visits (A-all visits, U-unregistered visit, S was visiting, F-unsuccessful registration attempts, R-robots). In brackets the number of visits.<br />- Block - Robots.<br />it displays the date, the time of the last visit robots entered in the list of robots on the Settings page.<br />- Block - blacklist.<br />it shows ip of the site visitors who were blocking access to the site. Display format: date of commencement of lock-access website, end date start blocking access to the site, the ip block address of the visitor.','wms7').'<br /><br />'.__('Panel info', 'wms7').'<br /><img src='.$img3.' style="float: left;">',
        ));      
      get_current_screen() -> add_help_tab(array(
        'id'        => 'wms7-tab-5',
        'title'     => __('5.Bulk actions', 'wms7'),
        'content'   => __('In the category of mass actions are included:<br /> - delete. This action allows you to delete a selected check box record in the main table - visits to the site. If any record is marked for deletion and will be marked in the black list, then the entry is NOT removed until before the administrator will deselect (command - clean) black list of this record.<br /> - export. This action allows you to export the selected record (visit site external Excel file. Subsequently, this file can be formatted into the desired form teams and use Excel as report. In the export file enter the following fields from the main table site visit: id, uid, time, user_ip, page_visit, page_from.','wms7').'<br /><br />'.__('Bulk actions','wms7').'<br /><img src='.$img4.' style="float: left;">',
        )); 
      get_current_screen() -> add_help_tab(array(
        'id'        => 'wms7-tab-6',
        'title'     => __('6.Settings screen', 'wms7'),
        'content'   => __('Group screen settings: « column » and « pagination » are the standard settings of the Wordpress screen and no additional comments need. Settings « Display panel-info » used to display or hide the 4 dashboard: settings/General, history of visits, robots visitors, blacklist ip. In the case of removal of flags from all 4 check-boxes, the entire dashboard will be hidden.<br /><br />screen Settings', 'wms7').'<br /><img src='.$img5.' style="float: left;">',
        ));  
      get_current_screen() -> add_help_tab(array(
        'id'        => 'wms7-tab-7',
        'title'     => __('7.Other functions', 'wms7'),
        'content'   => __('Additional features of the plugin are in the form of buttons located at the bottom of the main table, visit the website:<br />- « index » feature edit and save in a modal window file index.php<br />- « robots » feature edit and save in a modal window file rorots.txt<br />- « htaccess » edit function and save in a modal window file.htaccess<br />- « wp-config » function to edit and save it in a modal window file wp-config.php<br />- « wp-cron » output function and removal of task wp-cron in a modal window<br />- « statistic » statistic of visits to the site<br />- « sma » managing of current mail box<br /><br />Additional features','wms7').'<br /><img src='.$img6.' style="float: left;">',
        ));
      get_current_screen() -> add_help_tab(array(
        'id'        => 'wms7-tab-8',
        'title'     => __('8.Map', 'wms7'),
        'content'   => __('The function « Map » is in each row of the main table of the plugin, in the field « Visitor IP ». For use this function, you must register <a href="https://console.developers.google.com/apis/credentials" target="_blank">Google Maps API key</a> and save it on the plugin page <a href="'.$url.'/wp-admin/admin.php?page=wms7_settings " target="_blank">Settings</a> in the field " Google Maps API key "<br /><br />Example of displaying the location of the visitor and of the this provider:','wms7').'<br /><img src='.$img7.' style="float: left;margin: 0;"><img src='.$img8.' style="float: right;margin: 0;">',
        ));
      get_current_screen() -> add_help_tab(array(
        'id'        => 'wms7-tab-9',
        'title'     => __('9.SSE', 'wms7'),
        'content'   => __('The SSE function (Server Send Events) is made in the form of a button located at the top of the plugin main screen. The function is designed to automatically update the screen when new visitors to the site or new mail to the Inbox of the current mailbox. Access and mailbox name is defined in the settings on the basic plug - in settings page. If you are actively working with the plug - in, it is recommended to disable SSE mode, and after the work-re-enable SSE mode','wms7').'<br /><br /><img src='.$img9.'>',
        ));      
        // Help sidebars are optional
      get_current_screen()->set_help_sidebar(
        '<p><strong>' . __( 'Additional information:', 'wms7' ) . '</strong></p>' .
        '<p><a href="https://wordpress.org/plugins/watchman-site7/" target="_blank">' .  __( 'page the Wordpress repository','wms7') . '</a></p>'.
        '<p><a href="https://github.com/adminkov7/WatchMan-Site7" target="_blank">' .  __( 'page the GitHub repository','wms7') . '</a></p>'.        
        '<p><a href="https://www.adminkov.bcr.by/category/wordpress/" target="_blank">' .  __( 'home page support plugin','wms7') . '</a></p>'
        );
    } 
    if( 'wms7_settings' == $page ){
      $img1 = plugins_url('/images/options.png', __FILE__);
      $img3 = plugins_url('/images/ip_excluded.png', __FILE__);
      $img4 = plugins_url('/images/whois_service.png', __FILE__);
      $img5 = plugins_url('/images/robots.png', __FILE__);
      $img7 = plugins_url('/images/robots_banned.png', __FILE__);
      $img8 = plugins_url('/images/google_map_api.png', __FILE__);
      $img9 = plugins_url('/images/export_fields_csv.png', __FILE__);
      $img10 = plugins_url('/images/mail_ru.png', __FILE__);
      $img11 = plugins_url('/images/yandex_ru.png', __FILE__);
			$img12 = plugins_url('/images/yahoo_com.png', __FILE__);
			$img13 = plugins_url('/images/gmail_com.png', __FILE__);

      get_current_screen() -> add_help_tab(array(
        'id'        => 'wms7-tab-1',
        'title'     => __('1.General settings', 'wms7'),
        'content'   => __('Basic settings of the plugin are formed on this page and stored in the table: prefix_options in the site database. Basic settings sgruppirovany in the option: wms7_main_settings. Additionally: the screen settings are stored in the same table in option wms7_screen_settings. There are two service options: wms7_current_page and wms7_visitors_per_page. If you delete the plugin the above options will be removed. And will also be deleted table: prefix_watchman_site and prefix_watchman_site_countries.','wms7').'<br /><br />'.__('Fragment table prefix_options','wms7').'<br /><img src='.$img1.' style="float: left;">',
        )); 
      get_current_screen() -> add_help_tab(array(
        'id'        => 'wms7-tab-2',
        'title'     => __('2.fields: Number of records of visits', 'wms7'),
        'content'   => __('The value of this field determines for what period of time need to store information about the website visit.','wms7'),
        )); 
      get_current_screen() -> add_help_tab(array(
        'id'        => 'wms7-tab-3',
        'title'     => __('3.field: Do not register visits to:', 'wms7'),
        'content'   => __('Lists the ip addresses that will not be shown in the table of visits to the site. This can be useful for the ip of the administrator of the site, which makes no sense to bring to the table of visits to the site. Enumeration of ip addresses you need to divide the sign - semicolon (;)<br /><br />the List of ip addresses that will not be recorded in the table of visits to the site', 'wms7').'<br /><img src='.$img3.' style="float: left;">',
        ));
      get_current_screen() -> add_help_tab(array(
        'id'        => 'wms7-tab-4',
        'title'     => __('4.field: WHO-IS service', 'wms7'),
        'content'   => __('Preoutboxed to choose one of the 4 WHO-is providers. Information about the site visitor is provided in the form: country code of the visitor, country name of visitor, city visitor. The quality and reliability of the information provided varies from region to region.<br /><br />Information provided to who-is service provider in the column of User IP', 'wms7').'<br /><img src='.$img4.' style="float: left;">',
        ));
      get_current_screen() -> add_help_tab(array(
        'id'        => 'wms7-tab-5',
        'title'     => __('5.field: Robots', 'wms7'),
        'content'   => __('Lists the names of the robots that are of interest to track the frequency of visits to the site. An enumeration of the names of the robots need to share the sign - semicolon (;)<br /><br />a List of robots that will be recorded in the table of visits to the site', 'wms7').'<br /><img src='.$img5.' style="float: left;">',
        ));

      get_current_screen() -> add_help_tab(array(
        'id'        => 'wms7-tab-6',
        'title'     => __('6.field: Visits of robots', 'wms7'),
        'content'   => __('In the case of setting the flag, all visits by robots will be recorded in the database. The names of the robots are taken from section 5 of the Robots', 'wms7'),
        ));

      get_current_screen() -> add_help_tab(array(
        'id'        => 'wms7-tab-7',
        'title'     => __('7.field: Robots banned', 'wms7'),
        'content'   => __('A list of names of robots whose access to the site is denided. The enumeration of the names of the robots need to share the sign - semicolon (;)<br /><br />a List of robots that will be recorded into the file .htaccess. If this field is clear, all record lock be removed from the file .htaccess', 'wms7').'<br /><img src='.$img7.' style="float: left;">',
        ));

      get_current_screen() -> add_help_tab(array(
        'id'        => 'wms7-tab-8',
        'title'     => __('8.field: Google Maps API key', 'wms7'),
        'content'   => __('API key required to display in a modal window, Google maps - location of a website visitor. The map window appears when you click the Map link in the column to Visit the IP in the table main page of the plugin. Detailed information about obtaining the key is on the support page of the plugin.','wms7').'<br /><img src='.$img8.' style="float: left;"><br /><br /><br /><br /><br /><br />'.
          __('Log console Google API Console, create your project and enable Google Maps JavaScript API, Google Maps Geocoding API in this project.<br />To view the list of enabled APIs:<br />1.Go to Google API Console. <a href="https://console.developers.google.com/apis/credentials" target="_blank">Page registration Google Maps API key</a><br />2.Click Select a project, then select the same project you created and click Open.<br />3.In the API list on the Dashboard page, find Google Maps JavaScript API and Google Maps Geocoding API.<br />4.If these APIs are listed all installed. If these APIs are not in the list, add them:<br />-At the top of the page, select ENABLE API to open the Library tab. Alternatively, you can select Library in the left menu.<br />-Find the Google Maps JavaScript API and Google Maps Geocoding API and select them from the list of results.<br />-Click ENABLE. When the process is complete, Google Maps JavaScript API and Google Maps Geocoding API will appear in the API list on the Dashboard','wms7'),
        ));
      get_current_screen() -> add_help_tab(array(
        'id'        => 'wms7-tab-9',
        'title'     => __('9.field: Exporting Table Fields', 'wms7'),
        'content'   => __('Select the fields you want in the export file -csv. The Export - command is located on the main page of the plug-in in the drop-down list - Bulk action.', 'wms7').'<br /><br /><img src='.$img9.' style="float: left;">',
        ));
      get_current_screen() -> add_help_tab(array(
        'id'        => 'wms7-tab-9',
        'title'     => __('10.field: E-mail boxes', 'wms7'),
        'content'   => __('Examples of settings for a mailbox access the main providers of postal services.', 'wms7').'<br /><br /><img src='.$img10.' style="float: left;"><img src='.$img11.' style="float: left;"><img src='.$img12.' style="float: left;"><img src='.$img13.' style="float: left;">',
        ));
      // Help sidebars are optional
      get_current_screen()->set_help_sidebar(
        '<p><strong>' . __( 'Additional information:', 'wms7' ) . '</strong></p>' .
        '<p><a href="https://wordpress.org/plugins/watchman-site7/" target="_blank">' .  __( 'page the Wordpress repository','wms7') . '</a></p>'.
        '<p><a href="https://www.adminkov.bcr.by/category/wordpress/" target="_blank">' .  __( 'home page support plugin','wms7') . '</a></p>'.
        '<p><a href="https://www.adminkov.bcr.by/chat/" target="_blank">' .  __( 'video communication with the developer of the plugin','wms7') . '</a></p>'
        );      
      return current_user_can( 'manage_options' );
    }
    if( 'wms7_black_list' == $page ){
      $img1 = plugins_url('/images/black_list.png', __FILE__);
      $img2 = plugins_url('/images/ban_start_date.png', __FILE__);
      $img3 = plugins_url('/images/ban_end_date.png', __FILE__);

      get_current_screen() -> add_help_tab(array(
        'id'        => 'wms7-tab-1',
        'title'     => __('1. Black list', 'wms7'),
        'content'   => __('On this page information is generated to block access to the IP of the visitor to the site visit. Information to lock is stored in the file .htaccess in a string (for example): Deny from 107.183.254.75 <br /><br />Information about blocking the IP of the visitor is stored in the form of:', 'wms7').'<br /><img src='.$img1.' style="float: left;">',
        ));
      get_current_screen() -> add_help_tab(array(
        'id'        => 'wms7-tab-2',
        'title'     => __('2.field: Ban start date', 'wms7'),
        'content'   => __('This field indicates the start date of blocking the IP address of the visitor.<br /><br />an Example of selecting the date of blocking the IP of the visitor:', 'wms7').'<br /><img src='.$img2.' style="float: left;">',
        ));
      get_current_screen() -> add_help_tab(array(
        'id'        => 'wms7-tab-3',
        'title'     => __('3.field: Ban end date', 'wms7'),
        'content'   => __('On this page information is generated about the end of the lock IP of the visitor to the site. The reservation is removed from the file .htaccess <br /><br />end IP block the visitor:', 'wms7').'<br /><img src='.$img3.' style="float: left;">',
        ));
      get_current_screen() -> add_help_tab(array(
        'id'        => 'wms7-tab-4',
        'title'     => __('4.field: Ban message', 'wms7'),
        'content'   => __('This field is used to store information as to why the decision of the administrator about the IP blocking the website visitor', 'wms7'),
        ));
      get_current_screen() -> add_help_tab(array(
        'id'        => 'wms7-tab-5',
        'title'     => __('5.field: Ban notes', 'wms7'),
        'content'   => __('Additional, redundant field. Is used for convenience by the site administrator', 'wms7'),
        ));

        // Help sidebars are optional
      get_current_screen()->set_help_sidebar(
        '<p><strong>' . __( 'Additional information:', 'wms7' ) . '</strong></p>' .
        '<p><a href="https://wordpress.org/plugins/watchman-site7/" target="_blank">' .  __( 'page the Wordpress repository','wms7') . '</a></p>'.
        '<p><a href="https://www.adminkov.bcr.by/category/wordpress/" target="_blank">' .  __( 'home page support plugin','wms7') . '</a></p>'.
        '<p><a href="https://www.adminkov.bcr.by/chat/" target="_blank">' .  __( 'video communication with the developer of the plugin','wms7') . '</a></p>'
        );        
      return current_user_can( 'manage_options' );   
    } 
    $table = new wms7_List_Table();
  } 

  function wms7_screen_settings_add($status, $args){

    $return = $status;
    if ( $args->base == 'toplevel_page_wms7_visitors' ) {

      $val = get_option('wms7_screen_settings');
      $setting_list = checked(1,isset($val['setting_list']) ? $val['setting_list'] : 0,false);
      $history_list = checked(1,isset($val['history_list']) ? $val['history_list'] : 0,false);   
      $robots_list = checked(1,isset($val['robots_list']) ? $val['robots_list'] : 0,false);
      $black_list = checked(1,isset($val['black_list']) ? $val['black_list'] : 0,false);

      $all_link = checked(1,isset($val['all_link']) ? $val['all_link'] : 0,false);
      $unlogged_link = checked(1,isset($val['unlogged_link']) ? $val['unlogged_link'] : 0,false);
      $successful_link = checked(1,isset($val['successful_link']) ? $val['successful_link'] : 0,false);
      $failed_link = checked(1,isset($val['failed_link']) ? $val['failed_link'] : 0,false);
      $robots_link = checked(1,isset($val['robots_link']) ? $val['robots_link'] : 0,false);
      $blacklist_link = checked(1,isset($val['blacklist_link']) ? $val['blacklist_link'] : 0,false);

      $index_php = checked(1,isset($val['index_php']) ? $val['index_php'] : 0,false);
      $robots_txt = checked(1,isset($val['robots_txt']) ? $val['robots_txt'] : 0,false);
      $htaccess = checked(1,isset($val['htaccess']) ? $val['htaccess'] : 0,false);
      $wp_config_php = checked(1,isset($val['wp_config_php']) ? $val['wp_config_php'] : 0,false);
      $wp_cron = checked(1,isset($val['wp_cron']) ? $val['wp_cron'] : 0,false);
      $statistic = checked(1,isset($val['statistic']) ? $val['statistic'] : 0,false);
      $mail = checked(1,isset($val['mail']) ? $val['mail'] : 0,false);

      $return .= "
      <fieldset class='panel-info-screen-setting'>
        <legend>".__('Display panel info','wms7')."</legend>
`
          <label for='setting_list'><input type='checkbox' id='setting_list' name='wms7_screen_settings[setting_list]' value='1' $setting_list /> ".__('Setting list','wms7')."</label>

          <label for='history_list'><input type='checkbox' id='history_list' name='wms7_screen_settings[history_list]' value='1' $history_list /> ".__('History list','wms7')."</label>

          <label for='robots_list'><input type='checkbox' id='robots_list' name='wms7_screen_settings[robots_list]' value='1' $robots_list /> ".__('Robots list','wms7')."</label>

          <label for='black_list'><input type='checkbox' id='black_list' name='wms7_screen_settings[black_list]' value='1' $black_list /> ".__('Black list','wms7')."</label>              
      </fieldset>

      <fieldset style='border: 1px solid black; padding: 0 10px;'>
      <legend>".__('Display filters II level','wms7')."</legend>
      
        <label for='all_link'><input type='checkbox' id='all_link' name='wms7_screen_settings[all_link]' value='1' $all_link /> ".__('All visits','wms7')."</label>

        <label for='unlogged_link'><input type='checkbox' id='unlogged_link' name='wms7_screen_settings[unlogged_link]' value='1' $unlogged_link /> ".__('Unlogged visits','wms7')."</label>

        <label for='successful_link'><input type='checkbox' id='successful_link' name='wms7_screen_settings[successful_link]' value='1' $successful_link /> ".__('Success visits','wms7')."</label>

        <label for='failed_link'><input type='checkbox' id='failed_link' name='wms7_screen_settings[failed_link]' value='1' $failed_link /> ".__('Failed visits','wms7')."</label>

        <label for='robots_link'><input type='checkbox' id='robots_link' name='wms7_screen_settings[robots_link]' value='1' $robots_link /> ".__('Robots visits','wms7')."</label>

        <label for='blacklist_link'><input type='checkbox' id='blacklist_link' name='wms7_screen_settings[blacklist_link]' value='1' $blacklist_link /> ".__('Black list','wms7')."</label>

      </fieldset>

      <fieldset style='border: 1px solid black; padding: 0 10px;'>
      <legend>".__('Display buttons add functions of bottom screen','wms7')."</legend>
      
        <label for='index_php'><input type='checkbox' id='index_php' name='wms7_screen_settings[index_php]' value='1' $index_php /> ".__('index.php','wms7')."</label>

        <label for='robots_txt'><input type='checkbox' id='robots_txt' name='wms7_screen_settings[robots_txt]' value='1' $robots_txt /> ".__('robots.txt','wms7')."</label>

        <label for='htaccess'><input type='checkbox' id='htaccess' name='wms7_screen_settings[htaccess]' value='1' $htaccess /> ".__('.htaccess','wms7')."</label>

        <label for='wp_config_php'><input type='checkbox' id='wp_config_php' name='wms7_screen_settings[wp_config_php]' value='1' $wp_config_php /> ".__('wp-config.php','wms7')."</label>

        <label for='wp_cron'><input type='checkbox' id='wp_cron' name='wms7_screen_settings[wp_cron]' value='1' $wp_cron /> ".__('wp-cron','wms7')."</label>

        <label for='statistic'><input type='checkbox' id='statistic' name='wms7_screen_settings[statistic]' value='1' $statistic /> ".__('statistic','wms7')."</label>

        <label for='mail'><input type='checkbox' id='mail' name='wms7_screen_settings[mail]' value='1' $mail /> ".__('e-mail box','wms7')."</label>

      </fieldset>"      
      ;
    } 
    return $return;
  }

  function wms7_screen_settings_save($status, $option, $value){

    if (isset($_POST['wms7_screen_settings'])) {
        foreach($_POST['wms7_screen_settings'] as $key=>$wms7_value) {
          $_POST['wms7_screen_settings'][$key] = sanitize_text_field($wms7_value);
        }
        unset($key);
        unset($wms7_value);

        update_option( 'wms7_screen_settings', $_POST['wms7_screen_settings'] );
      }else{
        update_option( 'wms7_screen_settings', NULL );
    }
    update_option( 'wms7_visitors_per_page', sanitize_option($option, $value) );

    unset($_POST['wms7_screen_settings']);
  }

  function wms7_role_time_country_filter(){
    global $wpdb;
    $table_name = $wpdb->prefix . 'watchman_site';

      //create $option_role
    $role_option = '';
    $sql = "SELECT DISTINCT user_role FROM $table_name WHERE user_role <> '' ORDER BY user_role ASC";
    $results = $wpdb->get_results($sql);

    if($results){

      $filter_role = ( isset($_GET['filter_role']) ) ? sanitize_text_field($_GET['filter_role']) : false;
      foreach($results as $row){
        $role_option .= '<option value="' . $row->user_role . '" ' . selected($row->user_role, $filter_role, false) . '>' . ' ' . $row->user_role . '</option>';
      }
      unset($row);
    }

    //create $option_date
    $time_option = '';
    $sql = "SELECT DISTINCT YEAR(time_visit) as year, MONTH(time_visit)as month FROM $table_name ORDER BY YEAR(time_visit), MONTH(time_visit) desc";
    $results = $wpdb->get_results($sql);

    if($results){

      $filter_time = ( isset($_GET['filter_time']) ) ? sanitize_text_field($_GET['filter_time']) : false;
      foreach($results as $row){
        $time_stamp = mktime(0, 0, 0, $row->month, 1, $row->year);
        $month = (strlen($row->month) == 1) ? '0' . $row->month : $row->month;
        $time_option .= '<option value="' . $row->year . $month . '" ' . selected($row->year . $month, $filter_time, false) . '>' . date('F', $time_stamp) . ' ' . $row->year . '</option>';
      }
      unset($row);
    }
    
    //create $option_country
    $country_option = '';
    $sql = "SELECT DISTINCT LEFT(`country`,4) as code_country FROM $table_name ORDER BY country ASC";
    $results = $wpdb->get_results($sql);

    if($results){

      $filter_country = ( isset($_GET['filter_country']) ) ? sanitize_text_field($_GET['filter_country']) : false;
      foreach($results as $row){
        $country_option .= '<option value="' . $row->code_country . '" ' . selected($row->code_country, $filter_country, false) . '>' . ' ' . $row->code_country . '</option>';
      }
      unset($row);
    }

    $output = '<form method="GET">';
    $output .= '<input type="hidden" name="page" value="wms7_visitors" />';
    $output .='<select name="filter_role" title="'.__('Select role of visitors','wms7').' "><option value="">' . __('Role All', 'wms7') . '</option>' . $role_option . '</select>';
    $output .= '<select name="filter_time" title="'.__('Select time of visits','wms7').' " ><option value="">' . __('Time All', 'wms7') . '</option>' . $time_option . '</select>';
    $output .='<select name="filter_country" title="'.__('Select country of visitors','wms7').' "><option value="">' . __('Country All', 'wms7') . '</option>' . $country_option . '</select>';    
    $output .= '<input class="button" id="doaction" type="submit" title="' . __('Filter 1 level, I group', 'wms7') . '" value="' . __('Filter', 'wms7') . '" />';
    $output .= '</form>';
    return $output;
  }

  function wms7_login_ip_filter(){
    $login_ip = ( isset($_GET['filter']) ) ? sanitize_text_field($_GET['filter']) : false;

    $output = '<form method="GET">';
    $output .= '<input type="hidden" name="page" value="wms7_visitors" />';
    $output .= '<input type="text" title="'.__('Enter login or visitor IP','wms7').'" placeholder = "Login or Visitor IP" name="filter" size="18" class="filter_login_ip" value="' . $login_ip . '" />';
    $output .='<input class="button" id="doaction" type="submit" title="' . __('Filter 1 level, II group', 'wms7') . '" value="'.__('Filter','wms7').'" />';
    $output .='</form>';
    return $output;
  }
  function wms7_geolocation_visitor() {

    if (!isset($_REQUEST['id'])) return;

    $val = get_option('wms7_main_settings');
    $val = $val['key_api'];

    $win_content = "
    <div id='map' style='width: 660px; height: 260px; padding: 0; margin:10px; background-color: #7D7970;'> </div>
    <script src='https://maps.googleapis.com/maps/api/js?key=$val' async defer></script>
    ";
    return $win_content;
  }

  function wms7_geo_wifi() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'watchman_site';
    $table = new wms7_List_Table();
    $id = sanitize_text_field($_REQUEST['id']);

    if ('map' == $table->current_action()) {
      //get login
      $user_login = $wpdb->get_results("SELECT `user_login` FROM $table_name WHERE `id` = $id", 'ARRAY_A');
      $login = implode("^", $user_login[0]);
      $login = isset($login) ? $login : "unlogged";
      //get ip
      $user_ip = $wpdb->get_results("SELECT `user_ip` FROM $table_name WHERE `id` = $id", 'ARRAY_A');
      $ip = implode("^", $user_ip[0]);
      //get coords
      $coords = $wpdb->get_results("SELECT `geo_wifi` FROM $table_name WHERE `id` = $id", 'ARRAY_A');
      $coords = implode("^", $coords[0]);  
      $coords = explode("<br>", $coords);

      $lat = $lon = $acc = $code = $msg = 0;

      foreach ($coords as $coord) {
        if (strpos($coord, 'Lat_wifi') === 0){
            $lat = mb_strcut($coord, 9);
            if ($lat == '') $lat = 0;
        }
        if (strpos($coord, 'Lon_wifi') === 0){
            $lon = mb_strcut($coord, 9);
            if ($lon == '') $lon = 0;
        }
        if (strpos($coord, 'Acc_wifi=') === 0){
            $acc = mb_strcut($coord, 9);
            $acc = round($acc, 2);
        }
        if (strpos($coord, 'Err_code') === 0){
            $code = mb_strcut($coord, 9);
        }
        if (strpos($coord, 'Err_msg') === 0){
            $msg = mb_strcut($coord, 8);
        }
      }
      unset($coord);
    }
    $login = 'login: '.$login;
    $arr = array ("ID"=>$id, "Login"=>$login, "IP"=>$ip, "Lat_wifi"=>$lat, "Lon_wifi"=>$lon, "Acc_wifi"=>$acc, "Err_code"=>$code, "Err_msg"=>$msg);
    return $arr;
  }

  function wms7_geo_ip() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'watchman_site';
    $table = new wms7_List_Table();
    $id = sanitize_text_field($_REQUEST['id']);

    if ('map' == $table->current_action()) {

      //provider
      $provider = $wpdb->get_results( $wpdb->prepare( 
        "
        SELECT `provider` 
        FROM $table_name 
        WHERE `id` = %s
        ",
        $id
        ),
        'ARRAY_A'
      );
      $provider = implode("^", $provider[0]);

      //get ip
      $user_ip = $wpdb->get_results( $wpdb->prepare( 
        "
        SELECT `user_ip` 
        FROM $table_name 
        WHERE `id` = %s
        ",
        $id
        ),
        'ARRAY_A'
      );
      $ip = implode("^", $user_ip[0]);

      //get coords
      $lat = '';
      $lon = '';
      $coords = $wpdb->get_results( $wpdb->prepare( 
        "
        SELECT `geo_ip` 
        FROM $table_name 
        WHERE `id` = %s
        ",
        $id
        ),
        'ARRAY_A'
      );
      $coords = implode("^", $coords[0]);
      $coords = explode("<br>", $coords);
      foreach ($coords as $coord) {
        if (strpos($coord, 'Lat_ip') === 0){
          $lat = mb_strcut($coord, 7);
        }
        if (strpos($coord, 'Lon_ip') === 0){
          $lon = mb_strcut($coord, 7);
        }
      }       
      unset($coord);
    }
    $provider = 'provider: '.$provider;
    $arr = array ("ID"=>$id,  "Provider"=>$provider, "IP"=>$ip, "Lat"=>$lat, "Lon"=>$lon, "Acc"=>"Not defined", "Err_code"=>"0", "Err_msg"=>"ok");      
    return $arr;    
  }
  function wms7_win_popup(){

    switch ($_POST['footer']) {
    case 'index':
        $str_head = 'index.php';
        break;
    case 'robots':
        $str_head = 'robots.txt';
        break;
    case 'htaccess':
        $str_head = '.htaccess';
        break;
    case 'wp_config':
        $str_head = 'wp-config.php';
        break;
    case 'wp_cron':
        $str_head = 'wp-cron tasks';
        break;
    case 'statistic':
        $str_head = 'statistic of visits';
        break;
    case 'sma':
        $val = get_option('wms7_main_settings');    
        $select_box = $val["mail_select"];
        $box = $val[$select_box];
        $str_head = 'e-mail box: '.$box["mail_box_name"];
        break;
    }

    if ( ($_POST['footer'] =='index') || ($_POST['footer'] =='robots') || 
       ($_POST['footer'] =='htaccess') || ($_POST['footer'] =='wp_config') ){
      $this->wms7_file_editor($str_head);
    }
    if ( $_POST['footer'] =='wp_cron' ) {
      $this->wms7_wp_cron($str_head); 
    }
    if ( $_POST['footer'] == 'statistic' ) {
      $this->wms7_stat($str_head); 
    }
    if ( $_POST['footer'] == 'sma' ) {
      $this->wms7_mail($str_head);
    }
  }

  function wms7_ip_enabled() {

    global $wpdb;
    $table_name = $wpdb->prefix . 'watchman_site';
    if (isset($_REQUEST['id'])) {
      $id = sanitize_text_field($_REQUEST['id']);

      $disabled = $wpdb->get_results( $wpdb->prepare( 
        "
        SELECT `geo_ip` 
        FROM $table_name  
        WHERE `id` = %s AND `geo_ip` <> %s
        ",
        $id,''
        ),
        'ARRAY_A'
      );

      if (count($disabled) == 0) {
        return 'disabled';
        }else{
        return '';
      }  
    }
  }

  function wms7_wifi_enabled() {

    global $wpdb;
    $table_name = $wpdb->prefix . 'watchman_site';
    if (isset($_REQUEST['id'])) {
      $id = sanitize_text_field($_REQUEST['id']);

      $disabled = $wpdb->get_results( $wpdb->prepare( 
        "
        SELECT `geo_wifi` 
        FROM $table_name  
        WHERE `id` = %s AND `geo_wifi` <> %s
        ",
        $id,''
        ),
        'ARRAY_A'
      );

      if (count($disabled) == 0) {
        return 'disabled';
        }else{
        return '';
      }
    }  
  }
  function wms7_map(){
    $map = $this->wms7_geolocation_visitor();

    $geo_ip = $this->wms7_geo_ip();
    $btn_ip_enabled = $this->wms7_ip_enabled();

    $provider = '"'.$geo_ip['Provider'].'"';    
    $lat = $geo_ip['Lat'];
    $lon = $geo_ip['Lon'];
    $acc = '"'.$geo_ip['Acc'].'"';    
    $err_code = $geo_ip['Err_code'];
    $err_msg = '"'.$geo_ip['Err_msg'].'"';
    //---------------------------------------------
    $geo_wifi = $this->wms7_geo_wifi();
    $btn_wifi_enabled = $this->wms7_wifi_enabled();

    $login = '"'.$geo_wifi['Login'].'"';    
    $lat_wifi = $geo_wifi['Lat_wifi'];
    $lon_wifi = $geo_wifi['Lon_wifi'];
    $acc_wifi = '"'.$geo_wifi['Acc_wifi'].'"';    
    $err_code_wifi = $geo_wifi['Err_code'];
    $err_msg_wifi = '"'.$geo_wifi['Err_msg'].'"';
    //---------------------------------------------
    $win_content = "
    <div class='win-popup'>
      <div class='popup-content'>
        <div class='popup-header'>
            <h2>".__('Geolocation visitor of site','wms7').' ip='.$geo_ip['IP']."  (id=".$geo_ip["ID"].")</h2>
            <label class='btn-close' title='close' for='win-popup' onClick='wms7_popup_close()'></label>
        </div>
            $map
        <div class='popup-footer'>
          <input type='submit' value='locate IP' id='get_location' class='button-primary' name='map_ip' $btn_ip_enabled 
            onClick='wms7_initMap($provider,$lat,$lon,$acc,$err_code,$err_msg)'>
          <input type='submit' value='locate WiFi' id='get_location' class='button-primary' name='map_wifi' $btn_wifi_enabled 
            onClick='wms7_initMap($login,$lat_wifi,$lon_wifi,$acc_wifi,$err_code_wifi,$err_msg_wifi)'>
            <div id='lat' style='margin:-32px 0 2px 200px; width:220px;'><label>Latitude: Not defined</label></div>
            <div id='lon' style='margin:-5px 0 2px 200px; width:220px;''><label>Longitude: Not defined</label></div>
            <div id='acc' style='margin:-37px 0 2px 420px;'><label>Accuracy: Not defined</label></div>
            <div id='err' style='margin-top:20px;'>Message:<label></label></div>
        </div>
      </div>            
    </div>
    ";
    echo $win_content;
  }

  function wms7_formatBytes($size, $precision = 2){
    $base = log($size, 1024);
    $suffixes = array('', 'K', 'M', 'G', 'T');   

    return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
  }

  function wms7_mail_attach($msgno, $arr_attach){
    $item = get_option('wms7_main_settings');
    $path_tmp = $item["mail_box_tmp"].'/';    
    $path = 'http://'.$_SERVER['HTTP_HOST'].$path_tmp;

    $page =get_option('wms7_current_page');
    if ($arr_attach){
      $str_attach = '';
      $img_attach = plugins_url('/images/attachment.png', __FILE__);
      $img_attach = "<img src='$img_attach' class='alignright' style='padding:0;'>";
      foreach ($arr_attach as $attach) {
        $filename = $attach['filename'];
        $fileinfo = $attach['filename'].' ('.$this->wms7_formatBytes($attach['size']).') ';
        $attach = "<a class='alignright' href='$path$filename' download><big>$fileinfo</big></a>".$img_attach;
        $str_attach = $str_attach . $attach;
      }
    }
    return $str_attach;
  }

  function wms7_mail_new($str_head, $draft){
  	if ($draft){
		    $msgno = $_GET['msgno'];
		    $box_name = $_GET['mailbox'];
	  		$str_head = $str_head." ($box_name id=".$msgno.")";
		    $header = wms7_mail_header($msgno);

		    $subject = iconv_mime_decode($header['subject'], 0 , "UTF-8");
		    $subject =str_replace(' ', '&#32', $subject); //не понятный костыль!?

		    $to = iconv_mime_decode($header['to'], 0 , "UTF-8");

		    $arr = wms7_mail_body($msgno);
		    $body = $arr[0];
		    $attach = $this->wms7_mail_attach($msgno, $arr[1]);
  		}else{
  			$str_head = $str_head;
  	}
    $win_popup = "
    <div class='win-popup'>
      <label class='btn' for='win-popup'></label>
      <input type='checkbox' style='display: none;' checked>
      <div class='popup-content'>
        <div class='popup-header'>
          <h2>$str_head</h2>
          <label class='btn-close' title='close' for='win-popup' onClick='wms7_popup_close()'></label>
        </div>
          <form id='popup_mail_view' method='POST' enctype='multipart/form-data'>
          <label style='margin-left:10px;font-weight:bold;'><big>Subject: </label></big>
<input type='text' placeholder = 'Subject' style='width: 590px; margin-left: 5px;-webkit-box-shadow: 0px 0px 10px #000;-moz-box-shadow: 0px 0px 10px #000;box-shadow: 0px 0px 10px #000;margin-bottom: 5px;text-overflow:ellipsis;' name='mail_new_subject' value=$subject ><br>
          <label style='margin-left:10px;font-weight:bold;'><big>To: </label></big>
<input type='text' placeholder = 'user name <mail@address>' size='18' style='width: 590px; margin-left: 40px;-webkit-box-shadow: 0px 0px 10px #000;-moz-box-shadow: 0px 0px 10px #000;box-shadow: 0px 0px 10px #000;margin-bottom: 5px;' name='mail_new_to' value=$to >
            <div class='popup-body-mail' style='margin-top: -5px;'>
              <textarea name='mail_new_content'>$body</textarea>
            </div>
            <div class='popup-footer'>
              <input type='submit' value='save' id='submit' class='button-primary' name='mail_new_save'/>
              <input type='submit' value='send' id='submit' class='button-primary' name='mail_new_send'/>
              <input type='submit' value='quit' id='submit' class='button-primary' name='mail_new_quit'/>
              <input type='file' name='mail_new_attach'>
              $attach
            </div>
          </form>  
      </div>
    </div>
    ";
    echo $win_popup;
  }  

	function wms7_mail_save(){
	  $val = get_option('wms7_main_settings');    
	  $select_box = $val["mail_select"];
	  $box = $val[$select_box];

	  //массив папок почтового ящика
		$folder =explode(';',$box['mail_folders'],-1);
		$i=0;
		$draft = false;
		foreach ($folder as $key => $value) {
			$pos = strpos($value, 'Draft');
			if ($pos) {
					$draft = true;
					break;
				}else{
					$pos = strpos($value, 'Черновик');
					if ($pos) {
						$draft = true;
						break;
					}
			}
			$i++;
		}
		if ($draft === FALSE) exit;
		//массив папок почтового ящика
		$folder =explode(';',$box['mail_folders_alt'],-1);
		$folder = substr($folder[$i], strpos($folder[$i], '{'));

		$date = date('D, d M Y h:i:s', time());
		//$envelope["date"] = date("j M, g.ia", strtotime($date));
		$envelope["date"] = $date;
		$envelope["subject"]  = '=?utf-8?B?'.base64_encode($_POST["mail_new_subject"]).'?=';
		$envelope["from"]  = $box["mail_box_name"];
		$envelope["to"]  = $_POST["mail_new_to"];

		if ($_FILES['mail_new_attach']['name'] == '') {
				$part1["type"] = TYPETEXT;
				$part1["subtype"] = PLAIN;
				$part1["charset"] = "UTF-8";
				$part1["contents.data"] = $_POST["mail_new_content"];
				$body[1] = $part1;
			}else{
				$part1["type"] = TYPEMULTIPART;
				$part1["subtype"] = "mixed";

				$part2["type"] = TYPETEXT;
				$part2["subtype"] = PLAIN;
				$part2["charset"] = "UTF-8";
				$part2["contents.data"] = $_POST["mail_new_content"];	

				$filename = $_FILES['mail_new_attach']['tmp_name'];
				$fp = fopen($filename, "r");
				$contents = fread($fp, filesize($filename));
				fclose($fp);

				$part3["type"] = TYPEAPPLICATION;
				$part3["encoding"] = ENCBINARY;
				$part3["subtype"] = "octet-stream";
				$part3["description"] = $_FILES['mail_new_attach']['name'];
        $part3['disposition.type'] = 'attachment';
        $part3['disposition'] = array ('filename' => $_FILES['mail_new_attach']['name']);
        $part3['type.parameters'] = array('name' => $_FILES['mail_new_attach']['name']);
				$part3["contents.data"] = $contents;	

				$body[1] = $part1;
				$body[2] = $part2;
				$body[3] = $part3;
		}

		$msg = imap_mail_compose($envelope, $body);

		$imap = wms7_mail_connection();

		if (imap_append($imap, $folder, $msg) === false) {
		   die( "could not append message: " . imap_last_error() );
		}
	}	

  function wms7_mail_view($str_head){
    $msgno = $_GET['msgno'];
    $box_name = $_GET['mailbox'];
    $header = wms7_mail_header($msgno);
    $subject = iconv_mime_decode($header['subject'], 0 , "UTF-8");
    $from = iconv_mime_decode($header['from'], 0 , "UTF-8");
    $to = iconv_mime_decode($header['to'], 0 , "UTF-8");
    $date = $header['date'];
    $arr = wms7_mail_body($msgno);
    $body = $arr[0];
    $attach = $this->wms7_mail_attach($msgno, $arr[1]);

    $win_popup = "
    <div class='win-popup'>
      <label class='btn' for='win-popup'></label>
      <input type='checkbox' style='display: none;' checked>
      <div class='popup-content'>
        <div class='popup-header'>
          <h2>$str_head  ($box_name id=$msgno)</h2>
          <label class='btn-close' title='close' for='win-popup' onClick='wms7_popup_close()'></label>
        </div>
          <form id='popup_mail_view' method='POST'>
          <label style='margin-left:10px;font-weight:bold;'><big>Subject: </label>$subject</big><br>
          <label style='margin-left:10px;font-weight:bold;'><big>From: </label>$from</big><br>
          <label style='margin-left:10px;font-weight:bold;'><big>To: </label>$to</big><br>
          <label style='margin-left:10px;font-weight:bold;'><big>Date: </label>$date</big><br>
          <div class='popup-body-mail'>
              <textarea name='content' style='height:200px'>$body</textarea>
            </div>
            <div class='popup-footer'>
              <input type='submit' value='reply' id='submit' class='button-primary' name='mail_view_reply'/>
              <input type='submit' value='quit' id='submit' class='button-primary' name='mail_view_quit' onClick='wms7_quit_btn()'/>
              $attach
            </div>
          </form>  
      </div>
    </div>
    ";
    echo $win_popup;
  }

  function wms7_mail($str_head){
    $mail_box_selector= wms7_mailbox_selector();
    $context = isset($_POST['mail_search_context']) ? $_POST['mail_search_context'] : '';
    if (isset($_POST['mail_search'])) {
      $arr = wms7_mail_search();
      $mail_table = $arr[0];
      $str_head = $str_head.' (found:'.$arr[1].')';
    }else{
      $mail_table = wms7_mail_inbox();
    }
    $win_popup = "
    <div class='win-popup'>
      <label class='btn' for='win-popup'></label>
      <input type='checkbox' style='display: none;' checked>    
      <div class='popup-content'>
        <div class='popup-header'>
          <h2>$str_head</h2>
          <label class='btn-close' title='close' for='win-popup' onClick='wms7_popup_close()'></label>
        </div>
        <form id='mailbox' method='POST'>
          $mail_box_selector
  <input type='submit' value='search' id='doaction' class='button alignright' name='mail_search' style='margin-right: 10px;-webkit-box-shadow: 0px 0px 10px #000;-moz-box-shadow: 0px 0px 10px #000;box-shadow: 0px 0px 10px #000;margin-bottom: 5px;'/>
  <input type='text' class='text alignright' placeholder = 'context' size='18' style='width: 80px; margin-right: 5px;-webkit-box-shadow: 0px 0px 10px #000;-moz-box-shadow: 0px 0px 10px #000;box-shadow: 0px 0px 10px #000;margin-bottom: 5px;' name='mail_search_context' value=$context >
          <div class='popup-body'>
          $mail_table
          </div>
          <div class='popup-footer'>
  <input type='submit' value='delete' id='submit' class='button-primary' name='mail_delete'/>
  <input type='submit' value='new' id='submit' class='button-primary' name='mail_new'/>
  <input type='submit' value='move' id='doaction' class='button alignright' name='mail_move' style='-webkit-box-shadow: 0px 0px 10px #000;-moz-box-shadow: 0px 0px 10px #000;box-shadow: 0px 0px 10px #000;margin: 0 0 5px 0;'/>
  <select name='move_box' class='text alignright' style='width: 80px; margin-right: 5px;-webkit-box-shadow: 0px 0px 10px #000;-moz-box-shadow: 0px 0px 10px #000;box-shadow: 0px 0px 10px #000;margin-bottom: 5px;'><option value='Inbox'>Inbox</option><option value='Sent'>Outbox</option><option value='Drafts'>Drafts</option><option value='Trash'>Trash</option></select>
          </div>
        </form> 
      </div>
    </div>  
    ";

    echo $win_popup;
  }

  function wms7_stat($str_head){
  	$val_options = get_option('wms7_current_param');

		$allTotal 			= $val_options['allTotal'];
    $visitsTotal 		= $val_options['visitsTotal'];		
    $successTotal 	= $val_options['successTotal'];
    $failedTotal 		= $val_options['failedTotal'];
    $robotsTotal 		= $val_options['robotsTotal'];
    $blacklistTotal = $val_options['blacklistTotal'];

  	if (isset($_POST['stat_data'])) {
	    $arr = wms7_create_table_stat();
	    $stat_table = wms7_table_stat($arr);
  		}else{
			$stat_table = "<div style='width: 660px; height: 260px; padding: 0; margin:5px 0 0 10px; background-color: #7D7970;'> </div>";
  	}
    $win_popup = "
    <div class='win-popup'>
      <label class='btn' for='win-popup'></label>
      <input type='checkbox' style='display: none;' checked>    
      <div class='popup-content'>
        <div class='popup-header'>
          <h2>$str_head</h2>
          <label class='btn-close' title='close' for='win-popup' onClick='wms7_popup_close()'></label>
        </div>
        <form id='popup_win' method='POST'>
  <div style='position:relative; float:left; margin: -5px 0 10px 10px;'>
  	<input class='radio' type='radio' id='visits' name='radio_stat' value='visits' onClick='wms7_stat_btn()'/>
    <label for='visits' style='color:black;'> ".__('Visits All','wms7')."($allTotal)</label>
    <input class='radio' type='radio' id='unlogged' name='radio_stat' value='unlogged' onClick='wms7_stat_btn()'/>
    <label for='unlogged' style='color:black;'> ".__('Unlogged','wms7')."($visitsTotal)</label>
    <input class='radio' type='radio' id='success' name='radio_stat' value='success' onClick='wms7_stat_btn()'/>
    <label for='success' style='color:black;'> ".__('Success','wms7')."($successTotal)</label>
    <input class='radio' type='radio' id='failed' name='radio_stat' value='failed' onClick='wms7_stat_btn()'/>
    <label for='failed' style='color:black;'> ".__('Failed','wms7')."($failedTotal)</label>
    <input class='radio' type='radio' id='robots' name='radio_stat' value='robots' onClick='wms7_stat_btn()'/>
    <label for='robots' style='color:black;'> ".__('Robots','wms7')."($robotsTotal)</label> 
    <input class='radio' type='radio' id='blacklist' name='radio_stat' value='blacklist' onClick='wms7_stat_btn()'/>
    <label for='blacklist' style='color:black;'> ".__('Black List','wms7')."($blacklistTotal)</label>
  </div>
          <div class='popup-body'>
            $stat_table
          </div>
          <div class='popup-footer'>
  <input type='submit' value='Table' id='submit' class='button-primary' name='stat_data'>
          </div>
        </form> 
      </div>
    </div>
    ";
    echo $win_popup;
  }

  function wms7_wp_cron($str_head){
    // create table wp-cron
    $wms7_cron = new wms7_cron();
    $cron_table = $wms7_cron->wms7_create_cron_table();
    $win_popup = "
    <div class='win-popup'>
      <label class='btn' for='win-popup'></label>
      <input type='checkbox' style='display: none;' checked>
      <div class='popup-content'>
        <div class='popup-header'>
          <h2>$str_head</h2>
          <label class='btn-close' title='close' for='win-popup' onClick='wms7_popup_close()'></label>
        </div>
        <form id='popup_win' method='POST'>
          <div class='popup-body'>
            <ul class='tasks'>
             <li class = 'tasks' style='color: red;font-weight:bold;'>".__('Not found', 'wms7')." : $wms7_cron->orphan_count</li>
             <li class = 'tasks' style='color: blue;font-weight:bold;'>".__('Plugin task', 'wms7')." : $wms7_cron->plugin_count</li>
             <li class = 'tasks' style='color: green;font-weight:bold;'>".__('Themes task', 'wms7')." : $wms7_cron->themes_count</li>
             <li class = 'tasks' style='color: brown;font-weight:bold;'>".__('WP task', 'wms7')." : $wms7_cron->wp_count</li>
            </ul>
            <table class='table'>
            <div class='loader' id='win-loader'></div>
            <thead class='thead'><tr class='tr'><th class='th' width='9%'>id</th><th class='th' width='35%'>".__('Task name', 'wms7')."</th><th class='th' width='15%'>".__('Recurrence', 'wms7')."</th><th class='th' width='20%'>".__('Next run', 'wms7')."</th><th class='th'>".__('Source task', 'wms7')."</th></tr>
            </thead>
              $cron_table
            <tfoot class='tfoot'><tr class='tr'><th class='th' width='9%'>id</th><th class='th' width='35%'>".__('Task name', 'wms7')."</th><th class='th' width='15%'>".__('Recurrence', 'wms7')."</th><th class='th' width='20%'>".__('Next run', 'wms7')."</th><th class='th'>".__('Source task', 'wms7')."</th></tr></tfoot>
            </table>
          </div>
          <div class='popup-footer'>
            <input type='submit' value='Delete' id='submit' class='button-primary'  name='cron_delete'>
            <input type='submit' value='Refresh' id='submit' class='button-primary'  name='cron_refresh' onClick='wms7_popup_loader()'>
          </div>
        </form> 
      </div>
    </div>
    ";
    echo $win_popup;    
  }

  function wms7_file_editor($str_head){
    $str_body="";  
    if (!file_exists($_SERVER['DOCUMENT_ROOT'].'/'.$str_head)){
      $str_body="File not found: " . $str_head;
    }else{
      $val = file($_SERVER['DOCUMENT_ROOT'].'/'.$str_head);
      foreach ($val as $line) {
        $str_body = $str_body.$line;
      }
      unset($line);
    }
    $win_popup = "
    <div class='win-popup'>
      <label class='btn' for='win-popup'></label>
      <input type='checkbox' style='display: none;' checked>
      <div class='popup-content'>
        <div class='popup-header'>
          <h2>$str_head</h2>
          <label class='btn-close' title='close' for='win-popup' onClick='wms7_popup_close()'></label>
        </div>
          <form id='popup_save' method='POST'>
            <div class='popup-body'>
              <textarea name='content'>$str_body</textarea>
            </div>  
            <div class='popup-footer'>
              <input type='submit' value='Save' id='submit' class='button-primary'  name='".$_POST['footer']."'>
              <label style='margin: 0;padding: 0;'>".$_SERVER['DOCUMENT_ROOT']."</label> 
            </div>
          </form>  
      </div>
    </div>
    ";
    echo $win_popup;
  }

  function wms7_visit_manager(){
    global $wpdb;

    $plugine_info = get_plugin_data(__DIR__ . '/watchman-site7.php');
    $table = new wms7_List_Table();
    $table->prepare_items();
    $message = '';
    $id =  get_option('wms7_id') ? get_option('wms7_id') : null;
    $id_del = get_option('wms7_id_del') ? get_option('wms7_id_del') : null;

    if ('delete' == get_option('wms7_action')) {
      if ( ($id) && ($id_del) && count ($id) == $id_del) {
          $message = '<div class="notice notice-success is-dismissible" id="message"><p>' . 
        __('Items deleted', 'wms7').': (count='. count($id) . ') date-time: ('.current_time('mysql') .')</p></div>';
        }else{
          $message = '<div class="notice notice-warning is-dismissible" id="message"><p>' . 
        __('Attention! Not all records deleted since the field "Black list" is not empty.', 'wms7'). ' Records to delete: '.count($id). '. Deleted records: '.$id_del.'.</p></div>';
      }  
    }
    if ('clear' == get_option('wms7_action')) {
        $this->wms7_ctrl_htaccess();
        $message = '<div class="notice notice-success is-dismissible" id="message"><p>' . 
      __('Black list item data cleaned successful', 'wms7').': (id='. $id . ') date-time: ('.current_time('mysql') . ')</p></div>';     
    }
    if ('export' == get_option('wms7_action')) {
      if ( ($id) ){
      $message = '<div class="notice notice-success is-dismissible" id="message"><p>' . 
      __('Export data items executed successful', 'wms7'). ': (count='. count($id) . ') date-time: ('.current_time('mysql') . ')</p></div>';
        }else{
      $message = '<div class="notice notice-warning is-dismissible" id="message"><p>' . 
      __('No items selected for export.', 'wms7') . '</p></div>';   
      }
    }
    echo $message;

    $opt = get_option('wms7_id');
    if (isset($opt)) delete_option('wms7_id');
    $opt = get_option('wms7_id_del');
    if (isset($opt)) delete_option('wms7_id_del');    
    $opt = get_option('wms7_action');
    if (isset($opt)) delete_option('wms7_action');    
    ?>
<a href="https://plugintests.com/plugins/watchman-site7/latest-report"><img src="https://plugintests.com/plugins/watchman-site7/php-badge.svg" style="position:absolute; margin:0 5px 5px 23px;"></a>
<a href="https://plugintests.com/plugins/watchman-site7/latest-report"><img src="https://plugintests.com/plugins/watchman-site7/wp-badge.svg" style="position:absolute;margin:0 0 0 150px;"></a>
    <div class="sse" onclick="wms7_sse()" title="<?php echo __('Refresh table of visits', 'wms7'); ?>">
      <input type="checkbox" id="sse">
      <label><i></i></label>     
    </div>
    
    <div class="wrap">

      <span class="dashicons dashicons-shield" style="float: left;"></span>

      <h1><?php echo $plugine_info["Name"].': '.__('visitors of site', 'wms7').'<span style="font-size:70%;"> (v.'.$plugine_info["Version"].')</span>'; ?></h1>

      <div class="alignleft actions">
        <?php echo $this->wms7_role_time_country_filter(); ?>
      </div>

      <div class="alignright actions">

        <?php echo $this->wms7_login_ip_filter(); ?>
      </div>

      <?php echo $this->wms7_info_panel(); ?>

      <form id="visitors-table" method="GET">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
        <?php $table->display(); ?>
      </form>
    </div>
    <?php

    if ( isset($_REQUEST['action']) && ($_REQUEST['action'] == 'map') ) {
      $this->wms7_map();
    }

    if (isset($_POST['footer'])) {$this->wms7_win_popup();}

    // save index.php
    if ( isset($_POST['index']) && ($_POST['index'] == 'Save') ) {
      wms7_save_index_php(sanitize_post( $_POST['content'], 'edit' ));
    }
    // save robots.txt
    if ( isset($_POST['robots']) && ($_POST['robots'] == 'Save') ) {
      wms7_save_robots_txt(sanitize_post( $_POST['content'], 'edit' ));
    }
    // save htaccess
    if ( isset($_POST['htaccess']) && ($_POST['htaccess'] == 'Save') ) {
      wms7_save_htaccess(sanitize_post( $_POST['content'], 'edit' ));
    }
    // save wp-config
    if ( isset($_POST['wp_config']) && ($_POST['wp_config'] == 'Save') ) {
      wms7_save_wp_config(sanitize_post( $_POST['content'], 'edit' ));
    }
    // refresh cron table
    if ( isset($_POST['cron_refresh']) || isset($_POST['cron_delete']) ) {
      $str_head = 'wp-cron tasks';
      $this->wms7_wp_cron($str_head);  
    }
    // refresh stat table
    if ( isset($_POST['stat_data']) ) {
      $str_head = 'statistic of visits';
      $this->wms7_stat($str_head);
    }
    //view mail №msgno
    if ( isset($_GET['msgno']) ) {
      $val = get_option('wms7_main_settings');    
      $select_box = $val["mail_select"];
      $box = $val[$select_box];
      $str_head = 'e-mail box: '.$box["mail_box_name"];
      //check - draft folder
			$num = substr($_GET['mailbox'], -1);
		  //массив папок почтового ящика
			$folder =explode(';',$box['mail_folders'],-1);

			$draft = false;
			$pos = strpos($folder[$num-1], 'Draft');
			if ($pos) {
					$draft = true;
				}else{
					$pos = strpos($folder[$num-1], 'Черновик');
					if ($pos) {
						$draft = true;
					}
			}
			if ($draft === FALSE) {
      		$this->wms7_mail_view($str_head);
      	}else{
      		$this->wms7_mail_new($str_head, true);
      }
    }
    //move mail
    if ( isset($_POST['mail_move']) ) {
      wms7_mail_move();      
    }
    //send mail
    if ( isset($_POST['mail_new_send']) ) {
      $val = get_option('wms7_main_settings');    
      $select_box = $val["mail_select"];
      $box = $val[$select_box];
      $str_head = 'e-mail box: '.$box["mail_box_name"];
			wms7_mail_send();
			$this->wms7_mail($str_head);
    }    
    //new mail
    if ( isset($_POST['mail_new']) ) {
      $val = get_option('wms7_main_settings');    
      $select_box = $val["mail_select"];
      $box = $val[$select_box];
      $str_head = 'e-mail box: '.$box["mail_box_name"];
      $this->wms7_mail_new($str_head, false);
    }
    //new mail save
    if ( isset($_POST['mail_new_save']) ) {
      $val = get_option('wms7_main_settings');    
      $select_box = $val["mail_select"];
      $box = $val[$select_box];
      $str_head = 'e-mail box: '.$box["mail_box_name"];
      $this->wms7_mail_save();
			$this->wms7_goto_draft();
    }
    //delete mail 
    if ( isset($_POST['mail_delete']) ) {         
      wms7_mail_delete();
      $val = get_option('wms7_main_settings');    
      $select_box = $val["mail_select"];
      $box = $val[$select_box];
      $str_head = 'e-mail box: '.$box["mail_box_name"];

      $folder = $_GET['mailbox'];
			echo "<div class='loader' id='win-loader' style='top:350px;'></div>";
			echo "<script>wms7_popup_loader();</script>";      
      echo "<script>mailbox_selector('$folder');</script>";
    }
    
    if ( 
    	(isset($_GET['mailbox']) && !isset($_GET['msgno']) && !isset($_POST['mail_new']) && !isset($_POST['mail_search'])) || 
    	isset($_POST['mail_view_quit']) || 
    	isset($_POST['mail_new_quit']) ) {

      $val = get_option('wms7_main_settings');
      $select_box = $val["mail_select"];
      $box = $val[$select_box];
      $str_head = 'e-mail box: '.$box["mail_box_name"];
      $this->wms7_mail($str_head);
    } 

    //mail_search_context
    if ( isset($_POST['mail_search']) ) {
      $val = get_option('wms7_main_settings');
      $select_box = $val["mail_select"];  
      $box = $val[$select_box];
      $str_head = 'search in: '.$box["mail_box_name"];
      $this->wms7_mail($str_head);
    }
  }

  function wms7_goto_draft(){
	  $val = get_option('wms7_main_settings');    
	  $select_box = $val["mail_select"];
	  $box = $val[$select_box];

	  //массив папок почтового ящика
		$folder =explode(';',$box['mail_folders'],-1);
		$i=0;
		$draft = false;
		foreach ($folder as $key => $value) {
			$pos = strpos($value, 'Draft');
			if ($pos) {
					$draft = true;
					break;
				}else{
					$pos = strpos($value, 'Черновик');
					if ($pos) {
						$draft = true;
						break;
					}
			}
			$i++;
		}
		if ($draft === FALSE) exit;
		$i++;

		$folder = 'folder'.$i;
		echo "<div class='loader' id='win-loader' style='top:350px;'></div>";
		echo "<script>wms7_popup_loader();</script>";
		echo "<script>mailbox_selector('$folder');</script>";
  }

  function wms7_settings(){
    $opt = get_option('wms7_id');
    if (isset($opt)) delete_option('wms7_id');
    $opt = get_option('wms7_id_del');
    if (isset($opt)) delete_option('wms7_id_del');    
    $opt = get_option('wms7_action');
    if (isset($opt)) delete_option('wms7_action');

    $plugine_info = get_plugin_data(__DIR__ . '/watchman-site7.php');

      if (isset($_REQUEST["settings-updated"])) {
        $message = '<div class="updated notice is-dismissible" id="message"><p>' . 
        __('Settings data saved successful', 'wms7') . ';  date-time: ('.current_time('mysql') . ')</p></div>';
      }
  ?>
    <div class="wrap">
      <span class="dashicons dashicons-shield" style="float: left;"></span>
      <h1><?php echo $plugine_info["Name"].': '.__('settings', 'wms7'); ?></h1>
      <br />
      <?php $message = (isset($message)) ? $message : ''; echo $message; ?>
      <form method="POST" action="options.php">
      <table bgcolor="white" width="100%" cellspacing="2" cellpadding="5" RULES="rows" style="border:1px solid #DDDDDD";>
        <tr>
          <td height="25"><font size="4"><b><?php _e("General settings","wms7") ?></b></font></td>
        </tr>
        <tr>
          <td>
            <?php
            settings_fields( 'option_group' );
            do_settings_sections( 'wms7_settings' );
            ?>
          </td>
        </tr>
      </table>
      <br />
      <button type="submit" class="button-primary" name="save">Save</button>
      <button type="button" class="button-primary" name="quit" onClick="location.href='<?php echo get_option('wms7_current_url') . '&paged='.get_option('wms7_current_page')?>'">Quit</button>
      </form>
    </div>
  <?php
  }

  function wms7_main_settings(){
      // parameters: $option_group, $wms7_main_settings
    register_setting( 'option_group', 'wms7_main_settings');

      // parameters: $id, $title, $callback, $page
    add_settings_section( 'wms7_section', '', '', 'wms7_settings' );

      // parameters: $id, $title, $callback, $page, $section, $args
    add_settings_field('field1', '<label for="wms7_main_settings[log_duration]">'.__('Duration log entries','wms7').':</label>', 
      array($this,'wms7_main_setting_field1'), 'wms7_settings', 'wms7_section' );

    add_settings_field('field2', '<label for="wms7_main_settings[ip_excluded]">'.__('Do not register visits for','wms7').':</label>', 
      array($this,'wms7_main_setting_field2'), 'wms7_settings', 'wms7_section' );

    add_settings_field('field3', '<label for="wms7_main_settings[whois_service]">'.__('WHO-IS service','wms7').':</label>', 
      array($this,'wms7_main_setting_field3'), 'wms7_settings', 'wms7_section' );

    add_settings_field('field4', '<label for="wms7_main_settings[robots]">'.__('Robots','wms7').':</label>', 
      array($this,'wms7_main_setting_field4'), 'wms7_settings', 'wms7_section' );

    add_settings_field('field5', '<label for="wms7_main_settings[robots_reg]">'.__('Visits of robots','wms7').':</label>', 
      array($this,'wms7_main_setting_field5'), 'wms7_settings', 'wms7_section' );

    add_settings_field('field6', '<label for="wms7_main_settings[robots_banned]">'.__('Robots banned','wms7').':</label>', 
      array($this,'wms7_main_setting_field6'), 'wms7_settings', 'wms7_section' );

    add_settings_field('field7', '<label for="wms7_main_settings[key_api]">'.__('Google Maps API key','wms7').':</label>', 
      array($this,'wms7_main_setting_field7'), 'wms7_settings', 'wms7_section' );
    add_settings_field('field8', '<label for="wms7_main_settings[export_csv]">'.__('Exporting Table Fields','wms7').':</label>', 
      array($this,'wms7_main_setting_field8'), 'wms7_settings', 'wms7_section' );
    add_settings_field('field9', '<label for="wms7_main_settings[mail_boxes]">'.__('E-mail boxes','wms7').':</label>',
      array($this,'wms7_main_setting_field9'), 'wms7_settings', 'wms7_section' );
    add_settings_field('field10', '<label for="wms7_main_settings[mail_box_select]">'.__('E-mail box select','wms7').':</label>',
      array($this,'wms7_main_setting_field10'), 'wms7_settings', 'wms7_section' );
    add_settings_field('field11', '<label for="wms7_main_settings[mail_box_tmp]">'.__('E-mail folder tmp','wms7').':</label>', 
      array($this,'wms7_main_setting_field11'), 'wms7_settings', 'wms7_section' );
  }

  //Filling out an option 1
  function wms7_main_setting_field1(){
    $val = get_option('wms7_main_settings');
    $val = isset($val['log_duration']) ? $val['log_duration'] : '3';
    ?>
    <input id="wms7_main_settings[log_duration]" name="wms7_main_settings[log_duration]" type="number" step="1" min="0" max="365" value="<?php echo sanitize_text_field( $val ) ?>" /><br/><label><?php _e('days. Leave empty or enter 0 if you not want the log to be truncated','wms7') ?></label>
    <?php
    //since we're on the General Settings page - update cron schedule if settings has been updated
    if( isset($_REQUEST['settings-updated']) ){
      wp_clear_scheduled_hook('wms7_truncate');
      $this->wms7_ctrl_htaccess_add();
    }  
  }

  //Filling out an option 2
  function wms7_main_setting_field2(){
    $val = get_option('wms7_main_settings');
    $val = isset($val['ip_excluded']) ? $val['ip_excluded'] : '';
    ?>
    <textarea id="wms7_main_settings[ip_excluded]" name="wms7_main_settings[ip_excluded]" placeholder="IP1;IP2;IP3;IP4"  style="margin: 0px; width: 320px; height: 45px;"><?php echo sanitize_text_field( $val ) ?></textarea><br/><label><?php _e('Visits from these IP addresses will be excluded from the protocol visits','wms7') ?></label>
    <?php
  }

  //Filling out an option 3
  function wms7_main_setting_field3(){
    $val = get_option('wms7_main_settings');
    $val = isset($val['whois_service']) ? $val['whois_service'] : 'none';

    $Rbtn0='<label><input type="radio" value="none" name="wms7_main_settings[whois_service]"/>'.__('none', 'wms7').'</label>';
    $Rbtn1='<label><input type="radio" value="IP-API" name="wms7_main_settings[whois_service]"/>IP-API</label>';
    $Rbtn2='<label><input type="radio" value="IP-Info" name="wms7_main_settings[whois_service]"/>IP-Info</label>';
    $Rbtn3='<label><input type="radio" value="Geobytes" name="wms7_main_settings[whois_service]"/>Geobytes</label>';
    $Rbtn4='<label><input type="radio" value="SxGeo" name="wms7_main_settings[whois_service]"/>SxGeo</label>';

    switch ($val) {
      case "none":
      $Rbtn0='<label><input type="radio" value="none" checked name="wms7_main_settings[whois_service]"/>'.__('none', 'wms7').'</label>'; break;      
      case "IP-API":
      $Rbtn1='<label><input type="radio" value="IP-API" checked name="wms7_main_settings[whois_service]"/>IP-API</label>'; break;       
      case "IP-Info":
      $Rbtn2='<label><input type="radio" value="IP-Info" checked name="wms7_main_settings[whois_service]"/>IP-Info</label>'; break;
      case "Geobytes":
      $Rbtn3='<label><input type="radio" value="Geobytes" checked name="wms7_main_settings[whois_service]"/>Geobytes</label>';break;      
      case "SxGeo":
      $Rbtn4='<label><input type="radio" value="SxGeo" checked name="wms7_main_settings[whois_service]"/>SxGeo</label>'; 
        break;
    }
    $output=$Rbtn0.'<br/>'.$Rbtn1.'<br/>'.$Rbtn2.'<br/>'.$Rbtn3.'<br/>'. $Rbtn4.'<br/>'. __('WHO-IS service offers information about the IP address visitors of site', 'wms7');
    echo $output;                  
  }    

  //Filling out an option 4
  function wms7_main_setting_field4(){
    $val = get_option('wms7_main_settings');
    $val = isset($val['robots']) ? $val['robots'] : 'Mail.RU_Bot;YandexBot;Googlebot;bingbot;Virusdie;AhrefsBot;YandexMetrika;MJ12bot;BegunAdvertising;Slurp;DotBot;YandexMobileBot;MegaIndex;Google;YandexAccessibilityBot;SemrushBot;Baiduspider;SEOkicks-Robot;BingPreview;rogerbot;Applebot;Qwantify;DuckDuckBot;Cliqzbot;';
    ?>
    <textarea id="wms7_main_settings[robots]" name="wms7_main_settings[robots]" placeholder="Name1;Name2;Name3;"  style="margin: 0px; width: 320px; height: 45px;"><?php echo sanitize_text_field( $val ) ?></textarea><br/><label><?php _e('Visits this name will be marked - Robot', 'wms7') ?></label>
    <?php
  }

  //Filling out an option 5
  function wms7_main_setting_field5(){
    $val = get_option('wms7_main_settings');
    $val = isset($val['robots_reg']) ? $val['robots_reg'] : '';
    ?>
    <input id="wms7_main_settings[robots_reg]" name="wms7_main_settings[robots_reg]" type="checkbox" value="1" <?php checked( $val ) ?> /><br/><label for="wms7_main_settings[robots_reg]"><?php _e('Register visits by robots.','wms7') ?></label>
    <?php
  }

  //Filling out an option 6
  function wms7_main_setting_field6(){
    $val = get_option('wms7_main_settings');
    $val = isset($val['robots_banned']) ? $val['robots_banned'] : '';
    ?>
    <textarea id="wms7_main_settings[robots_banned]" name="wms7_main_settings[robots_banned]" placeholder="Name1;Name2;Name3;"  style="margin: 0px; width: 320px; height: 45px;"><?php echo sanitize_text_field( $val ) ?></textarea><br/><label><?php _e('Visits this name will be banned', 'wms7') ?></label>
    <?php
  }  

  //Filling out an option 7
  function wms7_main_setting_field7(){
    $val = get_option('wms7_main_settings');
    $val = isset($val['key_api']) ? $val['key_api'] : '';
    ?>
    <input id="wms7_main_settings[key_api]" style="margin: 0px; width: 320px; height: 25px;" name="wms7_main_settings[key_api]" type="text" placeholder="Goggle key API"value="<?php echo sanitize_text_field( $val ) ?>" /><br/><label><?php _e('Insert Google Maps API key (for Google Maps JavaScript API and Google Maps Geocoding API). Visit ','wms7') ?></label><a href="https://console.developers.google.com/apis/credentials" target="_blank">Page registration Google Maps API key</a>
    <?php
  }

  //Filling out an option 8
  function wms7_main_setting_field8(){
    $val = get_option('wms7_main_settings');

    $id = isset($val['id']) ? $val['id'] : '';    
    $uid = isset($val['uid']) ? $val['uid'] : '';
    $user_login = isset($val['user_login']) ? $val['user_login'] : '';
    $user_role = isset($val['user_role']) ? $val['user_role'] : '';
    $time_visit = isset($val['time_visit']) ? $val['time_visit'] : '';
    $user_ip = isset($val['user_ip']) ? $val['user_ip'] : '';
    $black_list = isset($val['black_list']) ? $val['black_list'] : '';
    $page_visit = isset($val['page_visit']) ? $val['page_visit'] : '';
    $page_from = isset($val['page_from']) ? $val['page_from'] : '';
    $info = isset($val['info']) ? $val['info'] : '';
    ?>
    <input id="id" name="wms7_main_settings[id]" type="checkbox" value="1" 
    <?php checked($id) ?> /><label for='id'><?php _e('ID','wms7') ?></label>
    <input id="uid" name="wms7_main_settings[uid]" type="checkbox" value="1" 
    <?php checked( $uid ) ?> /><label for='uid'><?php _e('UID','wms7') ?></label>
    <input id="user_login" name="wms7_main_settings[user_login]" type="checkbox" value="1" 
    <?php checked( $user_login ) ?> /><label for='user_login'><?php _e('Login','wms7') ?></label>
    <input id="user_role" name="wms7_main_settings[user_role]" type="checkbox" value="1" 
    <?php checked( $user_role ) ?> /><label for='user_role'><?php _e('Role','wms7') ?></label>
    <input id="time_visit" name="wms7_main_settings[time_visit]" type="checkbox" value="1" 
    <?php checked( $time_visit ) ?> /><label for='time_visit'><?php _e('Time','wms7') ?></label>
    <input id="user_ip" name="wms7_main_settings[user_ip]" type="checkbox" value="1" 
    <?php checked( $user_ip ) ?> /><label for='user_ip'><?php _e('Visitor IP','wms7') ?></label>
    <input id="black_list" name="wms7_main_settings[black_list]" type="checkbox" value="1" 
    <?php checked( $black_list ) ?> /><label for='black_list'><?php _e('Black list','wms7') ?></label>
    <input id="page_visit" name="wms7_main_settings[page_visit]" type="checkbox" value="1" 
    <?php checked( $page_visit ) ?> /><label for='page_visit'><?php _e('Page Visit','wms7') ?></label>
    <input id="page_from" name="wms7_main_settings[page_from]" type="checkbox" value="1" 
    <?php checked( $page_from ) ?> /><label for='page_from'><?php _e('Page From','wms7') ?></label>
    <input id="info" name="wms7_main_settings[info]" type="checkbox" value="1" 
    <?php checked( $info ) ?> /><label for='info'><?php _e('Info','wms7') ?></label><br/>
    <label><?php _e('Select the fields to export to the report file','wms7') ?></label>
    <?php
  }

  //Filling out an option 9
  function wms7_main_setting_field9(){
  	//this_site------------------------------------------------------------------	
    $val = get_option('wms7_main_settings');
    $val1_box0 = $val['box0']['imap_server'];
    $val2_box0 = $val['box0']['mail_box_name'];
    $val3_box0 = $val['box0']['mail_box_pwd'];
    $val4_box0 = $val['box0']['mail_box_encryption'];  
    $val5_box0 = $val['box0']['mail_box_port'];
    $val6_box0 = $val['box0']['pwd_box'];

    $val7_box0 = $val['box0']['mail_folders'];
		$val7_box0 = str_replace(';', ';&#010', $val7_box0);
    $val7_box0_alt = $val['box0']['mail_folders_alt'];
		$val7_box0_alt = str_replace(';', ';&#010', $val7_box0_alt);	

    $val8_box0 = $val['box0']['smtp_server'];
		$val9_box0 = $val['box0']['smtp_box_encryption'];
		$val10_box0 = $val['box0']['smtp_box_port'];
    $sel1 = $sel2 = $sel3 = $sel4 = '';
    switch ($val4_box0) {
    case 'Auto':
        $sel1 = 'selected';
        break;    
    case 'No':
        $sel2 = 'selected';
        break;
    case 'SSL':
        $sel3 = 'selected';
        break;
    case 'TLS':
        $sel4 = 'selected';
        break;    
    }
    $sel1_smtp = $sel2_smtp = $sel3_smtp = $sel4_smtp = '';
    switch ($val9_box0) {
    case 'Auto':
        $sel1_smtp = 'selected';
        break;    
    case 'No':
        $sel2_smtp = 'selected';
        break;
    case 'SSL':
        $sel3_smtp = 'selected';
        break;
    case 'TLS':
        $sel4_smtp = 'selected';
        break;    
    }    
    if ( isset($_GET['checkbox']) && $_GET['checkbox'] == 'box0' ) {
      	$tbl_folders0 = $this->wms7_imap_list($_GET['checkbox']);
    	}else{
    		$tbl_folders0 = '';
    }
    ?>
  	<div id="param_mail_box_this_site" style="width:370px;">
    <fieldset style="width:860px;height:200px;border: 2px groove;margin:0;padding:0;">
      <legend><b>This site</b></legend>

    <input id="imap_server_box0" style="margin-left: 5px; width: 200px;" name="wms7_main_settings[box0][imap_server]" type="text" placeholder="imap server" value="<?php echo sanitize_text_field( $val1_box0 ) ?>" /><br/>
    <label for="imap_server_box0" style="position:relative;left:5px;"><?php _e('IMAP Server name','wms7') ?></label><br/>

    <input id="mail_box_name_box0" style="position:relative;left:5px;width:200px;" name="wms7_main_settings[box0][mail_box_name]" type="text" placeholder="mail box name" value="<?php echo sanitize_text_field( $val2_box0 ) ?>" /><br/>
    <label for="mail_box_name_box0" style="position:relative;left:5px;"><?php _e('E-mail box name','wms7') ?></label><br/>

    <input id="mail_box_pwd_box0" style="position:relative;left:5px;width:200px;" name="wms7_main_settings[box0][mail_box_pwd]" type="text" placeholder="mail box password" value="<?php echo sanitize_text_field( $val3_box0 ) ?>" /><br/>
    <label for="mail_box_pwd_box0" style="position:relative;left:5px;"><?php _e('E-mail box password','wms7') ?></label>

    <select id="encryption_box0" name="wms7_main_settings[box0][mail_box_encryption]" style="position:relative;left:90px;top:-150px;"><option <?php echo $sel1 ?> value="Auto">Auto</option><option <?php echo $sel2 ?> value="No">No</option><option <?php echo $sel3 ?> value="SSL">SSL</option><option <?php echo $sel4 ?> value="TLS">TLS</option></select>
    <label for="encryption_box0" style="position:relative;left:25px;top:-120px;"><?php _e('Encrypt','wms7') ?></label>

    <input id="mail_box_port_box0" style="position:relative;left:-25px;top:-87px;width:60px;" name="wms7_main_settings[box0][mail_box_port]" type=text placeholder="993" value="<?php echo sanitize_text_field( $val5_box0 ) ?>" />
    <label for="mail_box_port_box0" style="position:relative;left:-90px;top:-60px;"><?php _e('Port','wms7') ?></label><br/>

    <button type="button" id="box0" name="check_boxes" class="button-primary" onClick="wms7_check_boxes(id)" style="position:relative;left:225px;top:-60px;" />Check</button>
    <button type="button" id="textbox0" name="check_boxes" class="button-primary" onClick="wms7_mail_folders('box0','tbl_folders_box0','text_folders_box0','text_folders_box0_alt')" style="position:relative;left:165px;top:-30px;" />Insert</button>
    <input id="pwd_box0" name="wms7_main_settings[box0][pwd_box]" type="checkbox" style="position:relative;left:70px;top:-32px;" value="1" <?php checked( $val6_box0 ) ?> onClick="wms7_check_pwd(id)"/>

    <input id="smtp_server_box0" style="position:relative;width: 200px;top:-180px;left:505px;" name="wms7_main_settings[box0][smtp_server]" type="text" placeholder="smtp server" value="<?php echo sanitize_text_field( $val8_box0 ) ?>" />
    <label for="smtp_server_box0" style="position:relative;top:-155px;left:300px;"><?php _e('SMTP Server name','wms7') ?></label>

    <select id="smtp_encryption_box0" name="wms7_main_settings[box0][smtp_box_encryption]" style="position:relative;left:180px;top:-125px;"><option <?php echo $sel1_smtp ?> value="Auto">Auto</option><option <?php echo $sel2_smtp ?> value="No">No</option><option <?php echo $sel3_smtp ?> value="SSL">SSL</option><option <?php echo $sel4_smtp ?> value="TLS">TLS</option></select>
    <label for="smtp_encryption_box0" style="position:relative;left:115px;top:-95px;"><?php _e('SMTP Encrypt','wms7') ?></label>

    <input id="smtp_box_port_box0" style="position:relative;left:165px;top:-123px;width:60px;" name="wms7_main_settings[box0][smtp_box_port]" type=text placeholder="465" value="<?php echo sanitize_text_field( $val10_box0 ) ?>" />
    <label for="smtp_box_port_box0" style="position:relative;left:100px;top:-95px;"><?php _e('SMTP Port','wms7') ?></label><br/>

    <div id="tbl_folders_box0" style="position:relative;left:290px;top:-212px;margin:0;padding:0;width:350px;"><?php echo $tbl_folders0 ?></div>

    <textarea readonly id="text_folders_box0" name="wms7_main_settings[box0][mail_folders]" placeholder="Name folder1;&#010;Name folder2;&#010;Name folder3;"  style="position:relative;left:290px;top:-212px;width:350px;height:180px;"><?php echo sanitize_text_field( $val7_box0 ) ?></textarea>

    <textarea readonly id="text_folders_box0_alt" name="wms7_main_settings[box0][mail_folders_alt]" placeholder="Name folder1;&#010;Name folder2;&#010;Name folder3;"  style="position:relative;left:290px;top:-212px;width:350px;height:180px; display:none;"><?php echo sanitize_text_field( $val7_box0_alt ) ?></textarea> 

  	</fieldset>
  	</div>
  <?php
  //mail.ru--------------------------------------------------------------------

    $val = get_option('wms7_main_settings');
    $val1_box1 = $val['box1']['imap_server'];
    $val2_box1 = $val['box1']['mail_box_name'];
    $val3_box1 = $val['box1']['mail_box_pwd'];
    $val4_box1 = $val['box1']['mail_box_encryption'];  
    $val5_box1 = $val['box1']['mail_box_port'];
    $val6_box1 = $val['box1']['pwd_box'];

    $val7_box1 = $val['box1']['mail_folders'];
		$val7_box1 = str_replace(';', ';&#010', $val7_box1);
    $val7_box1_alt = $val['box1']['mail_folders_alt'];
		$val7_box1_alt = str_replace(';', ';&#010', $val7_box1_alt);			

    $val8_box1 = $val['box1']['smtp_server'];
		$val9_box1 = $val['box1']['smtp_box_encryption'];
		$val10_box1 = $val['box1']['smtp_box_port'];
    $sel1 = $sel2 = $sel3 = $sel4 = '';
    switch ($val4_box1) {
	    case 'Auto':
	        $sel1 = 'selected';
	        break;    
	    case 'No':
	        $sel2 = 'selected';
	        break;
	    case 'SSL':
	        $sel3 = 'selected';
	        break;
	    case 'TLS':
	        $sel4 = 'selected';
	        break;    
    }
    $sel1_smtp = $sel2_smtp = $sel3_smtp = $sel4_smtp = '';
    switch ($val9_box1) {
	    case 'Auto':
	        $sel1_smtp = 'selected';
	        break;    
	    case 'No':
	        $sel2_smtp = 'selected';
	        break;
	    case 'SSL':
	        $sel3_smtp = 'selected';
	        break;
	    case 'TLS':
	        $sel4_smtp = 'selected';
	        break;    
    }    
    if ( isset($_GET['checkbox']) && $_GET['checkbox'] == 'box1' ) {
      	$tbl_folders1 = $this->wms7_imap_list($_GET['checkbox']);
    	}else{
    		$tbl_folders1 = '';
    }
  ?>
  	<div id="param_mail_box_mail_ru" style="width:370px;">
    <fieldset style="width:860px;height:200px;border: 2px groove;margin:0;padding:0;">
      <legend><b>Mail.ru</b></legend>

    <input id="imap_server_box1" style="margin-left: 5px; width: 200px;" name="wms7_main_settings[box1][imap_server]" type="text" placeholder="imap server" value="<?php echo sanitize_text_field( $val1_box1 ) ?>" /><br/>
    <label for="imap_server_box1" style="position:relative;left:5px;"><?php _e('IMAP Server name','wms7') ?></label><br/>

    <input id="mail_box_name_box1" style="position:relative;left:5px;width:200px;" name="wms7_main_settings[box1][mail_box_name]" type="text" placeholder="mail box name" value="<?php echo sanitize_text_field( $val2_box1 ) ?>" /><br/>
    <label for="mail_box_name_box1" style="position:relative;left:5px;"><?php _e('E-mail box name','wms7') ?></label><br/>

    <input id="mail_box_pwd_box1" style="position:relative;left:5px;width:200px;" name="wms7_main_settings[box1][mail_box_pwd]" type="text" placeholder="mail box password" value="<?php echo sanitize_text_field( $val3_box1 ) ?>" /><br/>
    <label for="mail_box_pwd_box1" style="position:relative;left:5px;"><?php _e('E-mail box password','wms7') ?></label>

    <select id="encryption_box1" name="wms7_main_settings[box1][mail_box_encryption]" style="position:relative;left:90px;top:-150px;"><option <?php echo $sel1 ?> value="Auto">Auto</option><option <?php echo $sel2 ?> value="No">No</option><option <?php echo $sel3 ?> value="SSL">SSL</option><option <?php echo $sel4 ?> value="TLS">TLS</option></select>
    <label for="encryption_box1" style="position:relative;left:25px;top:-120px;"><?php _e('Encrypt','wms7') ?></label>

    <input id="mail_box_port_box1" style="position:relative;left:-25px;top:-87px;width:60px;" name="wms7_main_settings[box1][mail_box_port]" type=text placeholder="993" value="<?php echo sanitize_text_field( $val5_box1 ) ?>" />
    <label for="mail_box_port_box1" style="position:relative;left:-90px;top:-60px;"><?php _e('Port','wms7') ?></label><br/>

    <button type="button" id="box1" name="check_boxes" class="button-primary" onClick="wms7_check_boxes(id)" style="position:relative;left:225px;top:-60px;" />Check</button>
    <button type="button" id="textbox1" name="check_boxes" class="button-primary" onClick="wms7_mail_folders('box1','tbl_folders_box1','text_folders_box1','text_folders_box1_alt')" style="position:relative;left:165px;top:-30px;" />Insert</button>
    <input id="pwd_box1" name="wms7_main_settings[box1][pwd_box]" type="checkbox" style="position:relative;left:70px;top:-32px;" value="1" <?php checked( $val6_box1 ) ?> onClick="wms7_check_pwd(id)"/>

    <input id="smtp_server_box1" style="position:relative;width: 200px;top:-180px;left:505px;" name="wms7_main_settings[box1][smtp_server]" type="text" placeholder="smtp server" value="<?php echo sanitize_text_field( $val8_box1 ) ?>" />
    <label for="smtp_server_box1" style="position:relative;top:-155px;left:300px;"><?php _e('SMTP Server name','wms7') ?></label>

    <select id="smtp_encryption_box1" name="wms7_main_settings[box1][smtp_box_encryption]" style="position:relative;left:180px;top:-125px;"><option <?php echo $sel1_smtp ?> value="Auto">Auto</option><option <?php echo $sel2_smtp ?> value="No">No</option><option <?php echo $sel3_smtp ?> value="SSL">SSL</option><option <?php echo $sel4_smtp ?> value="TLS">TLS</option></select>
    <label for="smtp_encryption_box1" style="position:relative;left:115px;top:-95px;"><?php _e('SMTP Encrypt','wms7') ?></label>

    <input id="smtp_box_port_box1" style="position:relative;left:165px;top:-123px;width:60px;" name="wms7_main_settings[box1][smtp_box_port]" type=text placeholder="465" value="<?php echo sanitize_text_field( $val10_box1 ) ?>" />
    <label for="smtp_box_port_box1" style="position:relative;left:100px;top:-95px;"><?php _e('SMTP Port','wms7') ?></label><br/>

    <div id="tbl_folders_box1" style="position:relative;left:290px;top:-212px;margin:0;padding:0;width:350px;"><?php echo $tbl_folders1 ?></div>

    <textarea readonly id="text_folders_box1" name="wms7_main_settings[box1][mail_folders]" placeholder="Name folder1;&#010;Name folder2;&#010;Name folder3;"  style="position:relative;left:290px;top:-212px;width:350px;height:180px;"><?php echo sanitize_text_field( $val7_box1 ) ?></textarea>

    <textarea readonly id="text_folders_box1_alt" name="wms7_main_settings[box1][mail_folders_alt]" placeholder="Name folder1;&#010;Name folder2;&#010;Name folder3;"  style="position:relative;left:290px;top:-212px;width:350px;height:180px; display:none;"><?php echo sanitize_text_field( $val7_box1_alt ) ?></textarea> 

  	</fieldset>
  	</div>

  <?php
  //yandex.ru--------------------------------------------------------------------
    $val = get_option('wms7_main_settings');
    $val1_box2 = $val['box2']['imap_server'];
    $val2_box2 = $val['box2']['mail_box_name'];
    $val3_box2 = $val['box2']['mail_box_pwd'];
    $val4_box2 = $val['box2']['mail_box_encryption'];  
    $val5_box2 = $val['box2']['mail_box_port'];
    $val6_box2 = $val['box2']['pwd_box'];

    $val7_box2 = $val['box2']['mail_folders'];
		$val7_box2 = str_replace(';', ';&#010', $val7_box2);
    $val7_box2_alt = $val['box2']['mail_folders_alt'];
		$val7_box2_alt = str_replace(';', ';&#010', $val7_box2_alt);			

    $val8_box2 = $val['box2']['smtp_server'];
		$val9_box2 = $val['box2']['smtp_box_encryption'];
		$val10_box2 = $val['box2']['smtp_box_port'];
    $sel1 = $sel2 = $sel3 = $sel4 = '';
    switch ($val4_box2) {
	    case 'Auto':
	        $sel1 = 'selected';
	        break;    
	    case 'No':
	        $sel2 = 'selected';
	        break;
	    case 'SSL':
	        $sel3 = 'selected';
	        break;
	    case 'TLS':
	        $sel4 = 'selected';
	        break;    
    }
    $sel1_smtp = $sel2_smtp = $sel3_smtp = $sel4_smtp = '';
    switch ($val9_box2) {
	    case 'Auto':
	        $sel1_smtp = 'selected';
	        break;    
	    case 'No':
	        $sel2_smtp = 'selected';
	        break;
	    case 'SSL':
	        $sel3_smtp = 'selected';
	        break;
	    case 'TLS':
	        $sel4_smtp = 'selected';
	        break;    
    }    
    if ( isset($_GET['checkbox']) && $_GET['checkbox'] == 'box2' ) {
      	$tbl_folders2 = $this->wms7_imap_list($_GET['checkbox']);
    	}else{
    		$tbl_folders2 = '';
    }
  ?>
  	<div id="param_mail_box_yandex_ru" style="width:370px;">
    <fieldset style="width:860px;height:200px;border: 2px groove;margin:0;padding:0;">
      <legend><b>Yandex.ru</b></legend>

    <input id="imap_server_box2" style="margin-left: 5px; width: 200px;" name="wms7_main_settings[box2][imap_server]" type="text" placeholder="imap server" value="<?php echo sanitize_text_field( $val1_box2 ) ?>" /><br/>
    <label for="imap_server_box2" style="position:relative;left:5px;"><?php _e('IMAP Server name','wms7') ?></label><br/>

    <input id="mail_box_name_box2" style="position:relative;left:5px;width:200px;" name="wms7_main_settings[box2][mail_box_name]" type="text" placeholder="mail box name" value="<?php echo sanitize_text_field( $val2_box2 ) ?>" /><br/>
    <label for="mail_box_name_box2" style="position:relative;left:5px;"><?php _e('E-mail box name','wms7') ?></label><br/>

    <input id="mail_box_pwd_box2" style="position:relative;left:5px;width:200px;" name="wms7_main_settings[box2][mail_box_pwd]" type="text" placeholder="mail box password" value="<?php echo sanitize_text_field( $val3_box2 ) ?>" /><br/>
    <label for="mail_box_pwd_box2" style="position:relative;left:5px;"><?php _e('E-mail box password','wms7') ?></label>

    <select id="encryption_box2" name="wms7_main_settings[box2][mail_box_encryption]" style="position:relative;left:90px;top:-150px;"><option <?php echo $sel1 ?> value="Auto">Auto</option><option <?php echo $sel2 ?> value="No">No</option><option <?php echo $sel3 ?> value="SSL">SSL</option><option <?php echo $sel4 ?> value="TLS">TLS</option></select>
    <label for="encryption_box2" style="position:relative;left:25px;top:-120px;"><?php _e('Encrypt','wms7') ?></label>

    <input id="mail_box_port_box2" style="position:relative;left:-25px;top:-87px;width:60px;" name="wms7_main_settings[box2][mail_box_port]" type=text placeholder="993" value="<?php echo sanitize_text_field( $val5_box2 ) ?>" />
    <label for="mail_box_port_box2" style="position:relative;left:-90px;top:-60px;"><?php _e('Port','wms7') ?></label><br/>

    <button type="button" id="box2" name="check_boxes" class="button-primary" onClick="wms7_check_boxes(id)" style="position:relative;left:225px;top:-60px;" />Check</button>
    <button type="button" id="textbox2" name="check_boxes" class="button-primary" onClick="wms7_mail_folders('box2','tbl_folders_box2','text_folders_box2','text_folders_box2_alt')" style="position:relative;left:165px;top:-30px;" />Insert</button>
    <input id="pwd_box2" name="wms7_main_settings[box2][pwd_box]" type="checkbox" style="position:relative;left:70px;top:-32px;" value="1" <?php checked( $val6_box2 ) ?> onClick="wms7_check_pwd(id)"/>

    <input id="smtp_server_box2" style="position:relative;width: 200px;top:-180px;left:505px;" name="wms7_main_settings[box2][smtp_server]" type="text" placeholder="smtp server" value="<?php echo sanitize_text_field( $val8_box2 ) ?>" />
    <label for="smtp_server_box2" style="position:relative;top:-155px;left:300px;"><?php _e('SMTP Server name','wms7') ?></label>

    <select id="smtp_encryption_box2" name="wms7_main_settings[box2][smtp_box_encryption]" style="position:relative;left:180px;top:-125px;"><option <?php echo $sel1_smtp ?> value="Auto">Auto</option><option <?php echo $sel2_smtp ?> value="No">No</option><option <?php echo $sel3_smtp ?> value="SSL">SSL</option><option <?php echo $sel4_smtp ?> value="TLS">TLS</option></select>
    <label for="smtp_encryption_box2" style="position:relative;left:115px;top:-95px;"><?php _e('SMTP Encrypt','wms7') ?></label>

    <input id="smtp_box_port_box2" style="position:relative;left:165px;top:-123px;width:60px;" name="wms7_main_settings[box2][smtp_box_port]" type=text placeholder="465" value="<?php echo sanitize_text_field( $val10_box2 ) ?>" />
    <label for="smtp_box_port_box2" style="position:relative;left:100px;top:-95px;"><?php _e('SMTP Port','wms7') ?></label><br/>

    <div id="tbl_folders_box2" style="position:relative;left:290px;top:-212px;margin:0;padding:0;width:350px;"><?php echo $tbl_folders2 ?></div>

    <textarea readonly id="text_folders_box2" name="wms7_main_settings[box2][mail_folders]" placeholder="Name folder1;&#010;Name folder2;&#010;Name folder3;"  style="position:relative;left:290px;top:-212px;width:350px;height:180px;"><?php echo sanitize_text_field( $val7_box2 ) ?></textarea>

    <textarea readonly id="text_folders_box2_alt" name="wms7_main_settings[box2][mail_folders_alt]" placeholder="Name folder1;&#010;Name folder2;&#010;Name folder3;"  style="position:relative;left:290px;top:-212px;width:350px;height:180px; display:none;"><?php echo sanitize_text_field( $val7_box2_alt ) ?></textarea> 

  	</fieldset>
  	</div>
  <?php
  //yahoo.com--------------------------------------------------------------------
    $val = get_option('wms7_main_settings');
    $val1_box3 = $val['box3']['imap_server'];
    $val2_box3 = $val['box3']['mail_box_name'];
    $val3_box3 = $val['box3']['mail_box_pwd'];
    $val4_box3 = $val['box3']['mail_box_encryption'];  
    $val5_box3 = $val['box3']['mail_box_port'];
    $val6_box3 = $val['box3']['pwd_box'];

    $val7_box3 = $val['box3']['mail_folders'];
		$val7_box3 = str_replace(';', ';&#010', $val7_box3);
    $val7_box3_alt = $val['box3']['mail_folders_alt'];
		$val7_box3_alt = str_replace(';', ';&#010', $val7_box3_alt);			

    $val8_box3 = $val['box3']['smtp_server'];
		$val9_box3 = $val['box3']['smtp_box_encryption'];
		$val10_box3 = $val['box3']['smtp_box_port'];
    $sel1 = $sel2 = $sel3 = $sel4 = '';
    switch ($val4_box3) {
	    case 'Auto':
	        $sel1 = 'selected';
	        break;    
	    case 'No':
	        $sel2 = 'selected';
	        break;
	    case 'SSL':
	        $sel3 = 'selected';
	        break;
	    case 'TLS':
	        $sel4 = 'selected';
	        break;    
    }
    $sel1_smtp = $sel2_smtp = $sel3_smtp = $sel4_smtp = '';
    switch ($val9_box3) {
	    case 'Auto':
	        $sel1_smtp = 'selected';
	        break;    
	    case 'No':
	        $sel2_smtp = 'selected';
	        break;
	    case 'SSL':
	        $sel3_smtp = 'selected';
	        break;
	    case 'TLS':
	        $sel4_smtp = 'selected';
	        break;    
    }    
    if ( isset($_GET['checkbox']) && $_GET['checkbox'] == 'box3' ) {
      	$tbl_folders3 = $this->wms7_imap_list($_GET['checkbox']);
    	}else{
    		$tbl_folders3 = '';
    }
  ?>
  	<div id="param_mail_box_yahoo_com" style="width:370px;">
    <fieldset style="width:860px;height:200px;border: 2px groove;margin:0;padding:0;">
      <legend><b>Yahoo.com</b></legend>

    <input id="imap_server_box3" style="margin-left: 5px; width: 200px;" name="wms7_main_settings[box3][imap_server]" type="text" placeholder="imap server" value="<?php echo sanitize_text_field( $val1_box3 ) ?>" /><br/>
    <label for="imap_server_box3" style="position:relative;left:5px;"><?php _e('IMAP Server name','wms7') ?></label><br/>

    <input id="mail_box_name_box3" style="position:relative;left:5px;width:200px;" name="wms7_main_settings[box3][mail_box_name]" type="text" placeholder="mail box name" value="<?php echo sanitize_text_field( $val2_box3 ) ?>" /><br/>
    <label for="mail_box_name_box3" style="position:relative;left:5px;"><?php _e('E-mail box name','wms7') ?></label><br/>

    <input id="mail_box_pwd_box3" style="position:relative;left:5px;width:200px;" name="wms7_main_settings[box3][mail_box_pwd]" type="text" placeholder="mail box password" value="<?php echo sanitize_text_field( $val3_box3 ) ?>" /><br/>
    <label for="mail_box_pwd_box3" style="position:relative;left:5px;"><?php _e('E-mail box password','wms7') ?></label>

    <select id="encryption_box3" name="wms7_main_settings[box3][mail_box_encryption]" style="position:relative;left:90px;top:-150px;"><option <?php echo $sel1 ?> value="Auto">Auto</option><option <?php echo $sel2 ?> value="No">No</option><option <?php echo $sel3 ?> value="SSL">SSL</option><option <?php echo $sel4 ?> value="TLS">TLS</option></select>
    <label for="encryption_box3" style="position:relative;left:25px;top:-120px;"><?php _e('Encrypt','wms7') ?></label>

    <input id="mail_box_port_box3" style="position:relative;left:-25px;top:-87px;width:60px;" name="wms7_main_settings[box3][mail_box_port]" type=text placeholder="993" value="<?php echo sanitize_text_field( $val5_box3 ) ?>" />
    <label for="mail_box_port_box3" style="position:relative;left:-90px;top:-60px;"><?php _e('Port','wms7') ?></label><br/>

    <button type="button" id="box3" name="check_boxes" class="button-primary" onClick="wms7_check_boxes(id)" style="position:relative;left:225px;top:-60px;" />Check</button>
    <button type="button" id="textbox3" name="check_boxes" class="button-primary" onClick="wms7_mail_folders('box3','tbl_folders_box3','text_folders_box3','text_folders_box3_alt')" style="position:relative;left:165px;top:-30px;" />Insert</button>
    <input id="pwd_box3" name="wms7_main_settings[box3][pwd_box]" type="checkbox" style="position:relative;left:70px;top:-32px;" value="1" <?php checked( $val6_box3 ) ?> onClick="wms7_check_pwd(id)"/>

    <input id="smtp_server_box3" style="position:relative;width: 200px;top:-180px;left:505px;" name="wms7_main_settings[box3][smtp_server]" type="text" placeholder="smtp server" value="<?php echo sanitize_text_field( $val8_box3 ) ?>" />
    <label for="smtp_server_box3" style="position:relative;top:-155px;left:300px;"><?php _e('SMTP Server name','wms7') ?></label>

    <select id="smtp_encryption_box3" name="wms7_main_settings[box3][smtp_box_encryption]" style="position:relative;left:180px;top:-125px;"><option <?php echo $sel1_smtp ?> value="Auto">Auto</option><option <?php echo $sel2_smtp ?> value="No">No</option><option <?php echo $sel3_smtp ?> value="SSL">SSL</option><option <?php echo $sel4_smtp ?> value="TLS">TLS</option></select>
    <label for="smtp_encryption_box3" style="position:relative;left:115px;top:-95px;"><?php _e('SMTP Encrypt','wms7') ?></label>

    <input id="smtp_box_port_box3" style="position:relative;left:165px;top:-123px;width:60px;" name="wms7_main_settings[box3][smtp_box_port]" type=text placeholder="465" value="<?php echo sanitize_text_field( $val10_box3 ) ?>" />
    <label for="smtp_box_port_box3" style="position:relative;left:100px;top:-95px;"><?php _e('SMTP Port','wms7') ?></label><br/>

    <div id="tbl_folders_box3" style="position:relative;left:290px;top:-212px;margin:0;padding:0;width:350px;"><?php echo $tbl_folders3 ?></div>

    <textarea readonly id="text_folders_box3" name="wms7_main_settings[box3][mail_folders]" placeholder="Name folder1;&#010;Name folder2;&#010;Name folder3;"  style="position:relative;left:290px;top:-212px;width:350px;height:180px;"><?php echo sanitize_text_field( $val7_box3 ) ?></textarea>

    <textarea readonly id="text_folders_box3_alt" name="wms7_main_settings[box3][mail_folders_alt]" placeholder="Name folder1;&#010;Name folder2;&#010;Name folder3;"  style="position:relative;left:290px;top:-212px;width:350px;height:180px; display:none;"><?php echo sanitize_text_field( $val7_box3_alt ) ?></textarea> 

  	</fieldset>
  	</div>
  <?php
  //gmail.com--------------------------------------------------------------------
    $val = get_option('wms7_main_settings');
    $val1_box4 = $val['box4']['imap_server'];
    $val2_box4 = $val['box4']['mail_box_name'];
    $val3_box4 = $val['box4']['mail_box_pwd'];
    $val4_box4 = $val['box4']['mail_box_encryption'];  
    $val5_box4 = $val['box4']['mail_box_port'];
    $val6_box4 = $val['box4']['pwd_box'];

    $val7_box4 = $val['box4']['mail_folders'];
		$val7_box4 = str_replace(';', ';&#010', $val7_box4);
    $val7_box4_alt = $val['box4']['mail_folders_alt'];
		$val7_box4_alt = str_replace(';', ';&#010', $val7_box4_alt);		

    $val8_box4 = $val['box4']['smtp_server'];
		$val9_box4 = $val['box4']['smtp_box_encryption'];
		$val10_box4 = $val['box4']['smtp_box_port'];
    $sel1 = $sel2 = $sel3 = $sel4 = '';
    switch ($val4_box4) {
	    case 'Auto':
	        $sel1 = 'selected';
	        break;    
	    case 'No':
	        $sel2 = 'selected';
	        break;
	    case 'SSL':
	        $sel3 = 'selected';
	        break;
	    case 'TLS':
	        $sel4 = 'selected';
	        break;    
    }
    $sel1_smtp = $sel2_smtp = $sel3_smtp = $sel4_smtp = '';
    switch ($val9_box4) {
	    case 'Auto':
	        $sel1_smtp = 'selected';
	        break;    
	    case 'No':
	        $sel2_smtp = 'selected';
	        break;
	    case 'SSL':
	        $sel3_smtp = 'selected';
	        break;
	    case 'TLS':
	        $sel4_smtp = 'selected';
	        break;    
    }    
    if ( isset($_GET['checkbox']) && $_GET['checkbox'] == 'box4' ) {
      	$tbl_folders4 = $this->wms7_imap_list($_GET['checkbox']);
    	}else{
    		$tbl_folders4 = '';
    }
  ?>
  	<div id="param_mail_box_gmail_com" style="width:370px;">
    <fieldset style="width:860px;height:200px;border: 2px groove;margin:0;padding:0;">
      <legend><b>Gmail.com</b></legend>

    <input id="imap_server_box4" style="margin-left: 5px; width: 200px;" name="wms7_main_settings[box4][imap_server]" type="text" placeholder="imap server" value="<?php echo sanitize_text_field( $val1_box4 ) ?>" /><br/>
    <label for="imap_server_box4" style="position:relative;left:5px;"><?php _e('IMAP Server name','wms7') ?></label><br/>

    <input id="mail_box_name_box4" style="position:relative;left:5px;width:200px;" name="wms7_main_settings[box4][mail_box_name]" type="text" placeholder="mail box name" value="<?php echo sanitize_text_field( $val2_box4 ) ?>" /><br/>
    <label for="mail_box_name_box4" style="position:relative;left:5px;"><?php _e('E-mail box name','wms7') ?></label><br/>

    <input id="mail_box_pwd_box4" style="position:relative;left:5px;width:200px;" name="wms7_main_settings[box4][mail_box_pwd]" type="text" placeholder="mail box password" value="<?php echo sanitize_text_field( $val3_box4 ) ?>" /><br/>
    <label for="mail_box_pwd_box4" style="position:relative;left:5px;"><?php _e('E-mail box password','wms7') ?></label>

    <select id="encryption_box4" name="wms7_main_settings[box4][mail_box_encryption]" style="position:relative;left:90px;top:-150px;"><option <?php echo $sel1 ?> value="Auto">Auto</option><option <?php echo $sel2 ?> value="No">No</option><option <?php echo $sel3 ?> value="SSL">SSL</option><option <?php echo $sel4 ?> value="TLS">TLS</option></select>
    <label for="encryption_box4" style="position:relative;left:25px;top:-120px;"><?php _e('Encrypt','wms7') ?></label>

    <input id="mail_box_port_box4" style="position:relative;left:-25px;top:-87px;width:60px;" name="wms7_main_settings[box4][mail_box_port]" type=text placeholder="993" value="<?php echo sanitize_text_field( $val5_box4 ) ?>" />
    <label for="mail_box_port_box4" style="position:relative;left:-90px;top:-60px;"><?php _e('Port','wms7') ?></label><br/>

    <button type="button" id="box4" name="check_boxes" class="button-primary" onClick="wms7_check_boxes(id)" style="position:relative;left:225px;top:-60px;" />Check</button>
    <button type="button" id="textbox4" name="check_boxes" class="button-primary" onClick="wms7_mail_folders('box4','tbl_folders_box4','text_folders_box4', 'text_folders_box4_alt')" style="position:relative;left:165px;top:-30px;" />Insert</button>
    <input id="pwd_box4" name="wms7_main_settings[box4][pwd_box]" type="checkbox" style="position:relative;left:70px;top:-32px;" value="1" <?php checked( $val6_box4 ) ?> onClick="wms7_check_pwd(id)"/>

    <input id="smtp_server_box4" style="position:relative;width: 200px;top:-180px;left:505px;" name="wms7_main_settings[box4][smtp_server]" type="text" placeholder="smtp server" value="<?php echo sanitize_text_field( $val8_box4 ) ?>" />
    <label for="smtp_server_box4" style="position:relative;top:-155px;left:300px;"><?php _e('SMTP Server name','wms7') ?></label>

    <select id="smtp_encryption_box4" name="wms7_main_settings[box4][smtp_box_encryption]" style="position:relative;left:180px;top:-125px;"><option <?php echo $sel1_smtp ?> value="Auto">Auto</option><option <?php echo $sel2_smtp ?> value="No">No</option><option <?php echo $sel3_smtp ?> value="SSL">SSL</option><option <?php echo $sel4_smtp ?> value="TLS">TLS</option></select>
    <label for="smtp_encryption_box4" style="position:relative;left:115px;top:-95px;"><?php _e('SMTP Encrypt','wms7') ?></label>

    <input id="smtp_box_port_box4" style="position:relative;left:165px;top:-123px;width:60px;" name="wms7_main_settings[box4][smtp_box_port]" type=text placeholder="465" value="<?php echo sanitize_text_field( $val10_box4 ) ?>" />
    <label for="smtp_box_port_box4" style="position:relative;left:100px;top:-95px;"><?php _e('SMTP Port','wms7') ?></label><br/>

    <div id="tbl_folders_box4" style="position:relative;left:290px;top:-212px;margin:0;padding:0;width:350px;"><?php echo $tbl_folders4 ?></div>

    <textarea readonly id="text_folders_box4" name="wms7_main_settings[box4][mail_folders]" placeholder="Name folder1;&#010;Name folder2;&#010;Name folder3;"  style="position:relative;left:290px;top:-212px;width:350px;height:180px;"><?php echo sanitize_text_field( $val7_box4 ) ?></textarea>

    <textarea readonly id="text_folders_box4_alt" name="wms7_main_settings[box4][mail_folders_alt]" placeholder="Name folder1;&#010;Name folder2;&#010;Name folder3;"  style="position:relative;left:290px;top:-212px;width:350px;height:180px; display:none;"><?php echo sanitize_text_field( $val7_box4_alt ) ?></textarea>    

  	</fieldset>
  	</div>
  <?php
  }

  //Filling out an option 10
  function wms7_main_setting_field10(){
    $val = get_option('wms7_main_settings');
    $val = isset($val['mail_select']) ? $val['mail_select'] : 'box0';

    $Rbtn0='<label><input type="radio" value="box0" name="wms7_main_settings[mail_select]"/>'.__('This site', 'wms7').'</label>';
    $Rbtn1='<label><input type="radio" value="box1" name="wms7_main_settings[mail_select]"/>Mail.ru</label>';
    $Rbtn2='<label><input type="radio" value="box2" name="wms7_main_settings[mail_select]"/>Yandex.ru</label>';
    $Rbtn3='<label><input type="radio" value="box3" name="wms7_main_settings[mail_select]"/>Yahoo.com</label>';
    $Rbtn4='<label><input type="radio" value="box4" name="wms7_main_settings[mail_select]"/>Gmail.com</label>';

    switch ($val) {
      case "box0":
      $Rbtn0='<label><input type="radio" value="box0" checked name="wms7_main_settings[mail_select]"/>'.__('This site', 'wms7').'</label>'; break;      
      case "box1":
      $Rbtn1='<label><input type="radio" value="box1" checked name="wms7_main_settings[mail_select]"/>Mail.ru</label>'; break;       
      case "box2":
      $Rbtn2='<label><input type="radio" value="box2" checked name="wms7_main_settings[mail_select]"/>Yandex.ru</label>'; break;
      case "box3":
      $Rbtn3='<label><input type="radio" value="box3" checked name="wms7_main_settings[mail_select]"/>Yahoo.com</label>';break;      
      case "box4":
      $Rbtn4='<label><input type="radio" value="box4" checked name="wms7_main_settings[mail_select]"/>Gmail.com</label>'; 
        break;
    }
    $output=$Rbtn0.'<br/>'.$Rbtn1.'<br/>'.$Rbtn2.'<br/>'.$Rbtn3.'<br/>'. $Rbtn4.'<br/>'. __('Select a mailbox to manage it', 'wms7');
    echo $output;                  
  }  

  //Filling out an option 11
  function wms7_main_setting_field11(){
    $val = get_option('wms7_main_settings');
    $val = isset($val['mail_box_tmp']) ? $val['mail_box_tmp'] : '';
    ?>
    <input id="wms7_main_settings[mail_box_tmp]" style="margin: 0px; width: 320px; height: 25px;" name="wms7_main_settings[mail_box_tmp]" type="text" placeholder="/tmp"value="<?php echo sanitize_text_field( $val ) ?>" /><br/><label><?php _e('Create a directory in the root of the site for temporary storage of files attached to the mail','wms7') ?></label>
    <?php
  }

  function wms7_imap_list(){
    $prm = $_GET['checkbox'];
    $val = get_option('wms7_main_settings');
    $box = $val[$prm];
 
		if($box['imap_server']){
      $server = '{'.$box["imap_server"].':'.$box["mail_box_port"].'/imap/'.$box["mail_box_encryption"].'/novalidate-cert}INBOX';
			$imap = @imap_open($server, $box["mail_box_name"], $box["mail_box_pwd"])or die(
				imap_last_error().
				"<br>server: ".$server.
				"<br>username: ".$box["mail_box_name"].
				"<br>password: ".$box["mail_box_pwd"]);

	    $list = imap_list($imap, "{".$box['imap_server']."}", "*");

	    $tbl_head = '<table class="table_box" style="background-color: #5B5B59;"><thead class="thead_box"><tr class="tr_box">'.
	                "<td class='td_box' width='16%' style='cursor: pointer;'>id</td>".
	                "<td class='td_box' width='84%' style='cursor: pointer;'>mail box</td>".
	                '</tr></thead>';
	    $tbl_foot = '<tfoot class="tfoot_box"><tr class="tr_box">'.
	                "<td class='td_box' width='16%' style='cursor: pointer;'>id</td>".
	                "<td class='td_box' width='84%' style='cursor: pointer;'>mail box</td>".
	                '</tr></tfoot></table>';

	    $tbl_body = '<tbody class="tbody_box">';
	    if (is_array($list)) {
	    		$list = wms7_imap_list_decode($list);
	        $i = 0;
	        foreach ($list as $value) {
	        	$str = explode('|', $value);
	          $tbl_body = $tbl_body."<tr class='tr_box'><td class='td_box' width='16%'><input name=$prm"."_chk id=$prm"."_chk$i type='checkbox'>$i</td>";
	          $tbl_body = $tbl_body."<td class='td_box' data='".$str[1]."' width='84%'>$str[0]</td></tr>";
	          $i++;
	        }
	      }else{
	        $tbl_body = "imap_list failed: " . imap_last_error();
	    }

	    $tbl_body = $tbl_body.'</tbody>';
	    $tbl = $tbl_head.$tbl_body.$tbl_foot;

	    return $tbl;
  	}
  }  

  function wms7_info_panel(){
    $val = get_option('wms7_screen_settings');
    $setting_list = isset($val['setting_list']) ? $val['setting_list'] : 0;
    $history_list = isset($val['history_list']) ? $val['history_list'] : 0;   
    $robots_list = isset($val['robots_list']) ? $val['robots_list'] : 0;
    $black_list = isset($val['black_list']) ? $val['black_list'] : 0; 

    $val = $setting_list + $history_list + $robots_list + $black_list;
    $width_box = '';
    switch ($val) {
      case "1":
      $width_box = '98%'; break;
      case "2":
      $width_box = '49%'; break; 
      case "3":
      $width_box = '32.5%'; break; 
      case "4":
      $width_box = '24.5%'; break;    
    }

    $this->hidden = ($setting_list !=='1' && $history_list !=='1' && $robots_list  !=='1' && $black_list !=='1') ? 'hidden' : '';

    $hidden_setting_list = ($setting_list =='1') ? "" : 'hidden';
    $hidden_history_list = ($history_list =='1') ? "" : 'hidden';
    $hidden_robots_list  = ($robots_list  =='1') ? "" : 'hidden';
    $hidden_black_list = ($black_list =='1') ? "" : 'hidden';

    $val = get_option('wms7_main_settings');
    $log_duration = isset($val['log_duration']) ? $val['log_duration'] : 0;
    $ip_excluded = isset($val['ip_excluded']) ? $val['ip_excluded'] : '';
    $robots_reg = isset($val['robots_reg']) ? __('Yes', 'wms7') : __('No', 'wms7');    
    $whois_service = isset($val['whois_service']) ? $val['whois_service'] : 'whois_service';

	  $val = get_option('wms7_main_settings');    
	  $select_box = $val['mail_select'];
	  $box = $val["$select_box"];
		$box = $box["imap_server"];
		$pos = strpos($box, '.');
		$box = substr($box, $pos+1);
		$unseen = wms7_mail_unseen();

    echo '<fieldset class=info_panel title="'.__('Panel info', 'wms7').'" '.$this->hidden.' >';

    echo '<fieldset class=info_settings title="'.__('General settings', 'wms7').'" ' .$hidden_setting_list. ' style="width:'.$width_box. '">';
    echo '<legend class=panel_title>'.__('Settings', 'wms7').'</legend>';
    $str=__('Mail box','wms7').': '.$box.' ('.$unseen.')'.';&#010;'.
    __('Duration log entries', 'wms7').': '.$log_duration.' '.__('day','wms7').';&#010;'.
    __('Do not include visits for','wms7').': '.$ip_excluded.';&#010;'.
    __('Visits of robots','wms7').': '.$robots_reg.';&#010;';

    echo '<textarea class ="textarea_panel_info">'.$str.'</textarea>';              
    echo '</fieldset>';

    echo '<fieldset class=info_whois title="'.__('History visits', 'wms7').'" ' .$hidden_history_list. ' style="width:'.$width_box. '" >';
    echo '<legend class=panel_title>'.$whois_service.'</legend>';
    echo '<textarea class ="textarea_panel_info">'.$this->wms7_whois_service_info($whois_service).'</textarea>';
    echo '</fieldset>';

    echo '<fieldset class=info_robots title="'.__('Robots-last day visit', 'wms7').'" ' .$hidden_robots_list. ' style="width:'.$width_box. '" >';
    echo '<legend class=panel_title>'.__('Robots list', 'wms7').'</legend>';     
    echo '<textarea class ="textarea_panel_info">'.$this->wms7_robot_visit_info().'</textarea>';
    echo '</fieldset>';

    echo '<fieldset class=info_blacklist title="'.__('Black list', 'wms7').'" ' .$hidden_black_list. ' style="width:'.$width_box. '" >';
    echo '<legend class=panel_title>'.__('Black list', 'wms7').'</legend>';
    echo '<textarea class ="textarea_panel_info">'.$this->wms7_black_list_info().'</textarea>';
    echo '</fieldset>';
    echo '</fieldset>';
  }

  function wms7_black_list_info(){
    global $wpdb;
    $table_name = $wpdb->prefix . 'watchman_site';

    $results = $wpdb->get_results( $wpdb->prepare( 
      "
      SELECT `id`, `user_ip`, `black_list` 
      FROM $table_name  
      WHERE `black_list` <> %s
      ",
      ''
      )
    );

    $output = '';
    foreach($results as $row){
      $row_ip = $row->user_ip;
      $row = unserialize($row->black_list);
      $output .= $row['ban_start_date'].'&#009;'.$row['ban_end_date'].'&#009;'.$row_ip.'&#010;';
    }
    unset($row);
    return $output;
  }

  function wms7_robot_visit_info(){
    global $wpdb;
    $table_name = $wpdb->prefix . 'watchman_site';

    $results = $wpdb->get_results( $wpdb->prepare( 
      "
      SELECT MAX(`time_visit`) as `date_visit`, `robot` 
      FROM $table_name  
      WHERE `login_result`=%d GROUP BY (`robot`) ORDER BY `date_visit` DESC
      ",
      3
      )
    );

    $output = '';
    foreach($results as $row){
      $output .= $row->date_visit.'&#009;'.
      $row->robot.'&#010;';
    }
    unset($row);
    return $output;            
  }

  function wms7_whois_service_info($whois_service){
    global $wpdb;
    $table_name = $wpdb->prefix . 'watchman_site';

    $results = $wpdb->get_results( $wpdb->prepare( 
      "
      SELECT left(`time_visit`,10) as `date_visit`, count(`login_result`) as `countAll`, sum(`login_result`='0') as `count0`, sum(`login_result`='1') as `count1`, sum(`login_result`='2') as `count2` , sum(`login_result`='3') as `count3`  
      FROM $table_name  
      WHERE whois_service = %s GROUP BY `date_visit` ORDER BY `date_visit` DESC
      ",
      $whois_service
      )
    );

    $output = '';
    foreach($results as $row){
      $output .= $row->date_visit.'&#009;'.
      'A'.$row->countAll.'&#009;'.
      'U'.$row->count2.'&#009;'.
      'S'.$row->count1.'&#009;'.
      'F'.$row->count0.'&#009;'.
      'R'.$row->count3.'&#010;';
    }
    unset($row);
    return $output;
  }

  function wms7_black_list(){
    global $wpdb;
    $table_name = $wpdb->prefix . 'watchman_site';
    $plugine_info = get_plugin_data(__DIR__ . '/watchman-site7.php');

    $opt = get_option('wms7_id');
    if (isset($opt)) delete_option('wms7_id');
    $opt = get_option('wms7_id_del');
    if (isset($opt)) delete_option('wms7_id_del');    
    $opt = get_option('wms7_action');
    if (isset($opt)) delete_option('wms7_action');

    $id = sanitize_text_field($_GET['id']);

    if (isset($_REQUEST['ban_start_date']) && isset($_REQUEST['ban_end_date'])){

      $arr = array(
        'ban_start_date' => sanitize_text_field($_REQUEST['ban_start_date']),
        'ban_end_date' => sanitize_text_field($_REQUEST['ban_end_date']),
        'ban_message' => sanitize_text_field($_REQUEST['ban_message']),
        'ban_notes' => sanitize_text_field($_REQUEST['ban_notes']),
        );

      $serialized_data = serialize($arr);

      $wpdb->update( $table_name, array( 'black_list' => $serialized_data), array( 'ID' => $id ), array('%s'));
    }

    $user_ip = $wpdb->get_results( $wpdb->prepare( 
      "
      SELECT `user_ip` 
      FROM $table_name  
      WHERE `id` = %s
      ",
      $id
      ),
      'ARRAY_A'
    );

    $fld = implode("^", $user_ip[0]);

      // here we adding our custom meta box
    add_meta_box('wms7_visitors_form_meta_box', '<font size="4">'.__('Black list data for','wms7').': IP = '.$fld. ' (id='.$id.'</font>)', array($this,'wms7_black_list_meta_box'), 'Visitor', 'normal', 'default');

    ?>
    <div class="wrap">
      <span class="dashicons dashicons-shield" style="float: left;"></span>
      <h1><?php echo $plugine_info["Name"].': '.__('black list', 'wms7'); ?></h1>

      <?php

      if (isset($_REQUEST["blacklist-save"])) {

        $message = '<div class="updated notice is-dismissible" id="message"><p><strong>' . 
        __('Black list item data saved successful:', 'wms7').' (id='. $id . ') date-time: ('.current_time('mysql') .')</strong></p></div>';
        echo $message;

        // insert user_ip into .htaccess
        wms7_ip_insert_to_file($fld);
      }
      ?>  
      <form id="form" method="POST">
        <input type="hidden" name="id" value="<?php echo $id ?>"/>

        <div class="metabox-holder" id="poststuff">
          <div id="post-body">
            <div id="post-body-content">
              <?php do_meta_boxes('visitor', 'normal', $item = (isset($item)) ? $item : ''); ?>
              <input type="submit" value="<?php _e('Save', 'wms7')?>" id="submit" class="button-primary" 
              name="blacklist-save">
              <input type="button" value="<?php _e('Quit', 'wms7')?>" id="quit" class="button-primary" 
              name="quit" onClick="location.href='<?php echo get_option('wms7_current_url'). 
                '&paged='.get_option('wms7_current_page')?>'">
            </div>
          </div>
        </div>
      </form>
    </div>
    <?php
  }

  function wms7_black_list_meta_box($item){
    global $wpdb;
    $table_name = $wpdb->prefix . 'watchman_site';
    $id = sanitize_text_field($_GET['id']);

    $black_list = $wpdb->get_results( $wpdb->prepare( 
      "
      SELECT `black_list` 
      FROM $table_name  
      WHERE `id` = %s
      ",
      $id
      ),
      'ARRAY_A'
    );

    $fld = unserialize(implode("^", $black_list[0])); 

    ?>
    <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
        <tr class="form-field">        
          <th>
            <label for="ban_start_date"><?php _e('Ban start date', 'wms7')?></label>
          </th>      
          <td>
            <input id="ban_start_date" name="ban_start_date" type="date" value="<?php echo sanitize_text_field($fld['ban_start_date'])?>"  placeholder="<?php _e('Ban start date', 'wms7')?>" required>
          </td>
          <th>
            <label for="ip_info"><?php _e('IP info', 'wms7')?></label>
          </th>
          <td rowspan="2">
            <textarea id ="ip_info" name="ip_info" rows="6" style="width: 100%"><?php echo $this->wms7_ip_info()?></textarea>
          </td>
        </tr>

        <tr class="form-field">
          <th>
            <label for="ban_end_date"><?php _e('Ban end date', 'wms7')?></label>
          </th>
          <td>
            <input id="ban_end_date" name="ban_end_date" type="date" value="<?php echo sanitize_text_field($fld['ban_end_date'])?>"  placeholder="<?php _e('Ban end date', 'wms7')?>" required>
          </td>
        </tr>

         <tr class="form-field">
          <th>
            <label for="ban_message"><?php _e('Ban message', 'wms7')?></label>
          </th>
          <td colspan="3">
            <input id="ban_message" name="ban_message" type="text" style="width: 100%" value="<?php echo sanitize_text_field($fld['ban_message'])?>"  placeholder="<?php _e('Ban message', 'wms7')?>" required>
          </td>
        </tr>

        <tr class="form-field">
          <th>
            <label for="ban_notes"><?php _e('Ban notes', 'wms7')?></label>
          </th>
          <td colspan="3">
            <input id="ban_notes" name="ban_notes" type="text" style="width: 100%" value="<?php echo sanitize_text_field($fld['ban_notes'])?>" placeholder="<?php _e('Ban notes', 'wms7')?>" required>
          </td>
        </tr>
    </table>
            <label><?php _e('Note: Insert the shortcode - [black_list] in a page or an entry to display the table compromised IP addresses stored in the database -Black list.', 'wms7')?></label>
    <?php
  }

  function wms7_ip_info() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'watchman_site';
    $id = sanitize_text_field($_GET['id']);

    $user_ip_info = $wpdb->get_results( $wpdb->prepare( 
      "
      SELECT `user_ip_info` 
      FROM $table_name  
      WHERE `id` = %s
      ",
      $id
      ),
      'ARRAY_A'
    );

    $fld = implode("^", $user_ip_info[0]);

    return $fld;
  }

  function wms7_languages() {
    load_plugin_textdomain('wms7', false, dirname(plugin_basename(__FILE__)));
  }

  } //end class
}

if( class_exists( 'WatchManSite7' ) ){
  $wms7 = new WatchManSite7;
    include_once dirname( __FILE__ ) . '/includes/create-tables.php';
    //Register for activation
    register_activation_hook( __FILE__, 'wms7_create_tables');
    //Deactivation hook
    register_deactivation_hook(__FILE__, 'wms7_deactivation');

  function wms7_deactivation(){
    //clean up old cron jobs that no longer exist
    wp_clear_scheduled_hook('wms7_truncate');
    wp_clear_scheduled_hook('wms7_htaccess');
  }    
}
//Create shortcode
class wms7_black_list_shortcode {
  static $add_script;
  static function init () {
      add_shortcode('black_list', array(__CLASS__, 'wms7_black_list_func'));
  }
  static function wms7_black_list_func( $atts ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'watchman_site';

    $results = $wpdb->get_results( $wpdb->prepare( 
      "
      SELECT `id`, `user_ip`, `black_list` 
      FROM $table_name  
      WHERE `black_list` <> %s ORDER BY `user_ip` DESC
      ",
      ''
      )
    );

    $output = '';
    $i = 0;
    foreach($results as $row){
      $i = $i + 1;
      $row_ip = $row->user_ip;
      $row = unserialize($row->black_list);
      $output .= '<tr><th>'.$i.'</th><th>'.$row_ip.'</th><th>'.$row['ban_start_date'].'</th><th>'.$row['ban_end_date'].'</th><th>'.$row['ban_message'].'</th></tr>';
    }
    unset($row);
      self::$add_script = true;
      $str='<tr><th>№</th><th>'.__('IP address', 'wms7').'</th><th>'.__('Ban start', 'wms7').'</th><th>'.__('Ban end', 'wms7').'</th><th>'.__('Description', 'wms7').'</th></tr>';

      return '<table>'.$str.$output.'</table>';
  }
}
wms7_black_list_shortcode::init();