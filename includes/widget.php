<?php
/*
Slave module:   widget.php
Description:    Create widget for WatchMan-site7
Version:        2.2.3
Author:         Oleg Klenitskiy
Author URI: 		https://www.adminkov.bcr.by/category/wordpress/
*/

class wms7_widget extends WP_Widget {

	function __construct() {
		parent::__construct(
// ID for widget
		'wms7_widget', 

// Name widget
		__('WatchMan-Site7', 'wms7'), 

// Description widget
		array( 'description' => __( 'Counter of visits to the site', 'wms7' ), ) 
			);
	}

	public function widget( $args, $instance ) {
		global $wpdb;
    $table_name = $wpdb->prefix . 'watchman_site';
		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $args['before_widget'];
		if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];


    $sql_month_visits = "select count(id) as `fld0` from $table_name where month(time_visit) = month(now()) and year(time_visit) = year(now())"; 
    $data_month_visits = $wpdb->get_results($sql_month_visits, 'ARRAY_A');
    $sql_month_visitors = "select count(DISTINCT user_ip) as `fld1` from $table_name where login_result <> 3 and month(time_visit) = month(now()) and year(time_visit) = year(now())";
    $data_month_visitors = $wpdb->get_results($sql_month_visitors, 'ARRAY_A');
    $sql_month_robots = "select count(DISTINCT robot) as `fld1` from $table_name where login_result = 3 and month(time_visit) = month(now()) and year(time_visit) = year(now())";
    $data_month_robots = $wpdb->get_results($sql_month_robots, 'ARRAY_A');

    $sql_week_visits = "select count(id) as `fld0` from $table_name where year(time_visit) = year(now()) and week(time_visit) = week(now())"; 
    $data_week_visits = $wpdb->get_results($sql_week_visits, 'ARRAY_A');
    $sql_week_visitors = "select count(DISTINCT user_ip) as `fld1` from $table_name where login_result <> 3 and year(time_visit) = year(now()) and week(time_visit) = week(now())"; 
    $data_week_visitors = $wpdb->get_results($sql_week_visitors, 'ARRAY_A');
    $sql_week_robots = "select count(DISTINCT robot) as `fld1` from $table_name where login_result = 3 and year(time_visit) = year(now()) and week(time_visit) = week(now())"; 
    $data_week_robots = $wpdb->get_results($sql_week_robots, 'ARRAY_A');        

    $sql_today_visits = "select count(id) as `fld0` from $table_name where time_visit >= CURDATE()"; 
    $data_today_visits = $wpdb->get_results($sql_today_visits, 'ARRAY_A');
    $sql_today_visitors = "select count(DISTINCT user_ip) as `fld1` from $table_name where login_result <> 3 and time_visit >= CURDATE()"; 
    $data_today_visitors = $wpdb->get_results($sql_today_visitors, 'ARRAY_A');
    $sql_today_robots = "select count(DISTINCT robot) as `fld1` from $table_name where login_result = 3 and DATE(`time_visit`) = CURDATE()"; 
    $data_today_robots = $wpdb->get_results($sql_today_robots, 'ARRAY_A');

		$tbl_counter = '
    <table class="counter" name="counter" style="font-size: 8pt; background: '.$instance[ 'grnd' ].';">
    	<tr><th>'.__('Interval', 'wms7').'</th><th>'.__('Visits', 'wms7').'</th><th>'.__('Visitors', 'wms7').'</th><th>'.__('Robots', 'wms7').'</th></tr>
    	<tr style="color: blue;"><th>'.__('month', 'wms7').'</th><th>'.$data_month_visits[0]['fld0'].'</th><th>'.$data_month_visitors[0]['fld1'].'</th><th>'.$data_month_robots[0]['fld1'].'</th></tr>
    	<tr style="color: green;"><th>'.__('week', 'wms7').'</th><th>'.$data_week_visits[0]['fld0'].'</th><th>'.$data_week_visitors[0]['fld1'].'</th><th>'.$data_week_robots[0]['fld1'].'</th></tr>
    	<tr style="color: brown;"><th>'.__('today', 'wms7').'</th><th>'.$data_today_visits[0]['fld0'].'</th><th>'.$data_today_visitors[0]['fld1'].'</th><th>'.$data_today_robots[0]['fld1'].'</th></tr>
    </table>';

		echo $tbl_counter;
	}


	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'Counter of visits', 'wms7' );
		}

		if ( !isset( $instance[ 'grnd' ] ) ) {
			$instance[ 'grnd' ] = "#FFFFFF";
		}
// For console administrative
		$fld0 = '<label><input type="radio" name="grnd" value="#FFFFFF" style="background: #FFFFFF" />0 </label>';
	  $fld1 = '<label><input type="radio" name="grnd" value="#CECECE" style="background: #CECECE" />1 </label>';
   	$fld2 = '<label><input type="radio" name="grnd" value="#FFAA00" style="background: #FFAA00" />2 </label>';
   	$fld3 = '<label><input type="radio" name="grnd" value="#D778D6" style="background: #D778D6" />3 </label>';
	  $fld4 = '<label><input type="radio" name="grnd" value="#A68BCB" style="background: #A68BCB" />4 </label>';
   	$fld5 = '<label><input type="radio" name="grnd" value="#AEBCDA" style="background: #AEBCDA" />5 </label>';
   	$fld6 = '<label><input type="radio" name="grnd" value="#6AA3B1" style="background: #6AA3B1" />6 </label>';
	  $fld7 = '<label><input type="radio" name="grnd" value="#34CB6B" style="background: #34CB6B" />7 </label>';
	  $fld8 = '<label><input type="radio" name="grnd" value="#AFBC4E" style="background: #AFBC4E" />8 </label>';
	  switch ($instance['grnd']) {
	  	
	  	case "#FFFFFF":
	  		$fld0 = '<label><input type="radio" name="grnd" value="#FFFFFF" checked style="background: #FFFFFF" />0 </label>'; break;	  	
	  	case "#CECECE":
	  		$fld1 = '<label><input type="radio" name="grnd" value="#CECECE" checked style="background: #CECECE" />1 </label>'; break;
	  	case "#FFAA00": 
	  		$fld2 = '<label><input type="radio" name="grnd" value="#FFAA00" checked style="background: #FFAA00" />2 </label>'; break;
	  	case "#D778D6":
	  		$fld3 = '<label><input type="radio" name="grnd" value="#D778D6" checked style="background: #D778D6" />3 </label>'; break;
	  	case "#A68BCB":
	  		$fld4 = '<label><input type="radio" name="grnd" value="#A68BCB" checked style="background: #A68BCB" />4 </label>'; break;
	  	case "#AEBCDA":
	  		$fld5 = '<label><input type="radio" name="grnd" value="#AEBCDA" checked style="background: #AEBCDA" />5 </label>'; break;
	  	case "#6AA3B1":
	  		$fld6 = '<label><input type="radio" name="grnd" value="#6AA3B1" checked style="background: #6AA3B1" />6 </label>'; break;
	  	case "#34CB6B":
	  		$fld7 = '<label><input type="radio" name="grnd" value="#34CB6B" checked style="background: #34CB6B" />7 </label>'; break;
	  	case "#AFBC4E":
				$fld8 = '<label><input type="radio" name="grnd" value="#AFBC4E" checked style="background: #AFBC4E" />8 </label>'; break;
	  }
	  $fld_all = $fld0 . $fld1 . $fld2 . $fld3 . $fld4 . $fld5 . $fld6 . $fld7 . $fld8;
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
			<label for="<?php echo $this->get_field_id( 'grnd' ); ?>"><?php _e( 'Background color:' ); ?><br /></label>
			<?php echo ($fld_all); ?>
		</p>
		<?php 
	}
	
	public function update( $new_instance, $old_instance ) {

		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['grnd'] = ( ! empty( $new_instance['grnd'] ) ) ? sanitize_text_field($_POST['grnd']) : sanitize_text_field($_POST['grnd']);
		return $instance;
	}
}

function wms7_load_widget() {
	register_widget( 'wms7_widget' );
}
add_action( 'widgets_init', 'wms7_load_widget' );