<?php
/**
 * Description: Used to create a widget - counter site visits.
 *
 * @category    Wms7_Widget
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.0.1
 * @license     GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Description: Used to create a widget - counter site visits.
 *
 * @category    Class
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.0.0
 * @license     GPLv2 or later
 */
class Wms7_Widget extends WP_Widget {
	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			// ID for widget.
			'Wms7_Widget',
			// Name widget.
			esc_html( 'WatchMan-Site7', 'wms7' ),
			// Description widget.
			array( 'description' => esc_html( 'Counter of visits to the site', 'wms7' ) )
		);
	}
	/**
	 * Used to prepare widget data.
	 *
	 * @param array $args     Args.
	 * @param array $instance Instance.
	 */
	public function widget( $args, $instance ) {
		global $wpdb;
		$title = apply_filters( 'widget_title', $instance['title'] );

		echo( $args['before_widget'] );
		if ( ! empty( $title ) ) {
			echo( $args['before_title'] . $title . $args['after_title'] );
		}

		$cache_key         = 'wms7_data_month_visits';
		$data_month_visits = wp_cache_get( $cache_key );
		if ( ! $data_month_visits ) {
			$data_month_visits = $wpdb->get_results(
				$wpdb->prepare(
					"
					SELECT count(id) as %s
					FROM {$wpdb->prefix}watchman_site
					WHERE MONTH(time_visit) = MONTH(now()) and YEAR(time_visit) = YEAR(now())
					",
					'fld0'
				),
				'ARRAY_A'
			);// db call ok; cache ok.
			wp_cache_set( $cache_key, $data_month_visits );
		}

		$cache_key           = 'wms7_data_month_visitors';
		$data_month_visitors = wp_cache_get( $cache_key );
		if ( ! $data_month_visitors ) {
			$data_month_visitors = $wpdb->get_results(
				$wpdb->prepare(
					"
					SELECT count(DISTINCT user_ip) as %s
					FROM {$wpdb->prefix}watchman_site
					WHERE login_result <> %d AND MONTH(time_visit) = MONTH(now()) and YEAR(time_visit) = YEAR(now())
					",
					'fld1',
					3
				),
				'ARRAY_A'
			);// db call ok; cache ok.
			wp_cache_set( $cache_key, $data_month_visitors );
		}
		$cache_key         = 'wms7_data_month_robots';
		$data_month_robots = wp_cache_get( $cache_key );
		if ( ! $data_month_robots ) {
			$data_month_robots = $wpdb->get_results(
				$wpdb->prepare(
					"
					SELECT count(DISTINCT robot) as %s
					FROM {$wpdb->prefix}watchman_site
					WHERE login_result = %d AND MONTH(time_visit) = MONTH(now()) and YEAR(time_visit) = YEAR(now())
					",
					'fld1',
					3
				),
				'ARRAY_A'
			);// db call ok; cache ok.
			wp_cache_set( $cache_key, $data_month_robots );
		}
		$cache_key        = 'wms7_data_week_visits';
		$data_week_visits = wp_cache_get( $cache_key );
		if ( ! $data_week_visits ) {
			$data_week_visits = $wpdb->get_results(
				$wpdb->prepare(
					"
					SELECT count(id) as %s
					FROM {$wpdb->prefix}watchman_site
					WHERE WEEK(time_visit) = WEEK(now()) and YEAR(time_visit) = YEAR(now())
					",
					'fld0'
				),
				'ARRAY_A'
			);// db call ok; cache ok.
			wp_cache_set( $cache_key, $data_week_visits );
		}
		$cache_key          = 'wms7_data_week_visitors';
		$data_week_visitors = wp_cache_get( $cache_key );
		if ( ! $data_week_visitors ) {
			$data_week_visitors = $wpdb->get_results(
				$wpdb->prepare(
					"
					SELECT count(DISTINCT user_ip) as %s
					FROM {$wpdb->prefix}watchman_site
					WHERE login_result <> %d AND WEEK(time_visit) = WEEK(now()) and YEAR(time_visit) = YEAR(now())
					",
					'fld1',
					3
				),
				'ARRAY_A'
			);// db call ok; cache ok.
			wp_cache_set( $cache_key, $data_week_visitors );
		}
		$cache_key        = 'wms7_data_week_robots';
		$data_week_robots = wp_cache_get( $cache_key );
		if ( ! $data_week_robots ) {
			$data_week_robots = $wpdb->get_results(
				$wpdb->prepare(
					"
					SELECT count(DISTINCT robot) as %s
					FROM {$wpdb->prefix}watchman_site
					WHERE login_result = %d AND WEEK(time_visit) = WEEK(now()) and YEAR(time_visit) = YEAR(now())
					",
					'fld1',
					3
				),
				'ARRAY_A'
			);// db call ok; cache ok.
			wp_cache_set( $cache_key, $data_week_robots );
		}
		$cache_key         = 'wms7_data_today_visits';
		$data_today_visits = wp_cache_get( $cache_key );
		if ( ! $data_today_visits ) {
			$data_today_visits = $wpdb->get_results(
				$wpdb->prepare(
					"
					SELECT count(id) as %s
					FROM {$wpdb->prefix}watchman_site
					WHERE time_visit >= CURDATE()
					",
					'fld0'
				),
				'ARRAY_A'
			);// db call ok; cache ok.
			wp_cache_set( $cache_key, $data_today_visits );
		}
		$cache_key           = 'wms7_data_today_visitors';
		$data_today_visitors = wp_cache_get( $cache_key );
		if ( ! $data_today_visitors ) {
			$data_today_visitors = $wpdb->get_results(
				$wpdb->prepare(
					"
					SELECT count(DISTINCT user_ip) as %s
					FROM {$wpdb->prefix}watchman_site
					WHERE login_result <> %d AND time_visit >= CURDATE()
					",
					'fld1',
					3
				),
				'ARRAY_A'
			);// db call ok; cache ok.
			wp_cache_set( $cache_key, $data_today_visitors );
		}
		$cache_key         = 'wms7_data_today_robots';
		$data_today_robots = wp_cache_get( $cache_key );
		if ( ! $data_today_robots ) {
			$data_today_robots = $wpdb->get_results(
				$wpdb->prepare(
					"
					SELECT count(DISTINCT robot) as %s
					FROM {$wpdb->prefix}watchman_site
					WHERE login_result = %d AND time_visit >= CURDATE()
					",
					'fld1',
					3
				),
				'ARRAY_A'
			);// db call ok; cache ok.
			wp_cache_set( $cache_key, $data_today_robots );
		}
		?>
		<table class="counter" name="counter" style="font-size: 8pt; background: <?php echo( esc_html( $instance['grnd'] ) ); ?>;">
			<tr><th><?php esc_html_e( 'Interval', 'wms7' ); ?></th><th><?php esc_html_e( 'Visits', 'wms7' ); ?></th><th><?php esc_html_e( 'Visitors', 'wms7' ); ?></th><th><?php esc_html_e( 'Robots', 'wms7' ); ?></th></tr>
			<tr style="color: blue;"><th><?php esc_html_e( 'month', 'wms7' ); ?></th><th><?php echo( esc_html( $data_month_visits[0]['fld0'] ) ); ?></th><th><?php echo( esc_html( $data_month_visitors[0]['fld1'] ) ); ?></th><th><?php echo( esc_html( $data_month_robots[0]['fld1'] ) ); ?></th></tr>
			<tr style="color: green;"><th><?php esc_html_e( 'week', 'wms7' ); ?></th><th><?php echo( esc_html( $data_week_visits[0]['fld0'] ) ); ?></th><th><?php echo( esc_html( $data_week_visitors[0]['fld1'] ) ); ?></th><th><?php echo( esc_html( $data_week_robots[0]['fld1'] ) ); ?></th></tr>
			<tr style="color: brown;"><th><?php esc_html_e( 'today', 'wms7' ); ?></th><th><?php echo( esc_html( $data_today_visits[0]['fld0'] ) ); ?></th><th><?php echo( esc_html( $data_today_visitors[0]['fld1'] ) ); ?></th><th><?php echo( esc_html( $data_today_robots[0]['fld1'] ) ); ?></th></tr>
		</table>
		<?php

		echo( ( ( $args['after_widget'] ) ) );
	}
	/**
	 * Used to prepare form of widget.
	 *
	 * @param array $instance Instance.
	 */
	public function form( $instance ) {
		if ( isset( $instance['title'] ) ) {
			$title = $instance['title'];
		} else {
			$title = __( 'Counter of visits', 'wms7' );
		}

		if ( ! isset( $instance['grnd'] ) ) {
			$instance['grnd'] = '#FFFFFF';
		}
		// For console administrative.
		?>
		<p>
			<label for="<?php echo esc_html( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo esc_html( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
			<label for="<?php echo esc_html( $this->get_field_id( 'grnd' ) ); ?>"><?php esc_html_e( 'Background color:' ); ?><br></label>
		</p>
		<?php

		$chk0 = '';
		$chk1 = '';
		$chk2 = '';
		$chk3 = '';
		$chk4 = '';
		$chk5 = '';
		$chk6 = '';
		$chk7 = '';
		$chk8 = '';

		switch ( $instance['grnd'] ) {

			case '#FFFFFF':
				$chk0 = 'checked';
				break;
			case '#CECECE':
				$chk1 = 'checked';
				break;
			case '#FFAA00':
				$chk2 = 'checked';
				break;
			case '#D778D6':
				$chk3 = 'checked';
				break;
			case '#A68BCB':
				$chk4 = 'checked';
				break;
			case '#AEBCDA':
				$chk5 = 'checked';
				break;
			case '#6AA3B1':
				$chk6 = 'checked';
				break;
			case '#34CB6B':
				$chk7 = 'checked';
				break;
			case '#AFBC4E':
				$chk8 = 'checked';
				break;
		}
		?>
		<label><input type="radio" name="grnd" id="grnd0" value="#FFFFFF" <?php echo( esc_html( $chk0 ) ); ?> style="background: #FFFFFF" />0 </label>
		<label><input type="radio" name="grnd" id="grnd1" value="#CECECE" <?php echo( esc_html( $chk1 ) ); ?> style="background: #CECECE" />1 </label>
		<label><input type="radio" name="grnd" id="grnd2" value="#FFAA00" <?php echo( esc_html( $chk2 ) ); ?> style="background: #FFAA00" />2 </label>
		<label><input type="radio" name="grnd" id="grnd3" value="#D778D6" <?php echo( esc_html( $chk3 ) ); ?> style="background: #D778D6" />3 </label>
		<label><input type="radio" name="grnd" id="grnd4" value="#A68BCB" <?php echo( esc_html( $chk4 ) ); ?> style="background: #A68BCB" />4 </label>
		<label><input type="radio" name="grnd" id="grnd5" value="#AEBCDA" <?php echo( esc_html( $chk5 ) ); ?> style="background: #AEBCDA" />5 </label>
		<label><input type="radio" name="grnd" id="grnd6" value="#6AA3B1" <?php echo( esc_html( $chk6 ) ); ?> style="background: #6AA3B1" />6 </label>
		<label><input type="radio" name="grnd" id="grnd7" value="#34CB6B" <?php echo( esc_html( $chk7 ) ); ?> style="background: #34CB6B" />7 </label>
		<label><input type="radio" name="grnd" id="grnd8" value="#AFBC4E" <?php echo( esc_html( $chk8 ) ); ?> style="background: #AFBC4E" />8 </label>
		<?php
	}
	/**
	 * Used to update data of widget.
	 *
	 * @param array $new_instance Instance.
	 * @param array $old_instance Instance.
	 */
	public function update( $new_instance, $old_instance ) {
		$_grnd             = filter_input( INPUT_POST, 'grnd', FILTER_SANITIZE_STRING );
		$instance          = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['grnd']  = ( ! empty( $new_instance['grnd'] ) ) ? $_grnd : $_grnd;
		return $instance;
	}
}
