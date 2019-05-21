<?php
/**
 * Description: Used to create a widget - counter site visits.
 *
 * @category    Wms7_Widget
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.1.1
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
 * @version     3.1.1
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
		$title = apply_filters( 'widget_title', $instance['title'] );

		echo ( $args['before_widget'] );
		if ( ! empty( $title ) ) {
			echo( $args['before_title'] . $title . $args['after_title'] );
		}
		?>
		<table class="counter" id="counter" style="font-size: 8pt; background: <?php echo( esc_html( $instance['grnd'] ) ); ?>;">
			<tr><th><?php esc_html_e( 'Interval', 'wms7' ); ?></th><th><?php esc_html_e( 'Visits', 'wms7' ); ?></th><th><?php esc_html_e( 'Visitors', 'wms7' ); ?></th><th><?php esc_html_e( 'Robots', 'wms7' ); ?></th></tr>
			<tr style="color: blue;"><th><?php esc_html_e( 'month', 'wms7' ); ?></th><th id=counter_month_visits></th><th id=counter_month_visitors></th><th id=counter_month_robots></th></tr>
			<tr style="color: green;"><th><?php esc_html_e( 'week', 'wms7' ); ?></th><th id=counter_week_visits></th><th id=counter_week_visitors></th><th id=counter_week_robots></th></tr>
			<tr style="color: brown;"><th><?php esc_html_e( 'today', 'wms7' ); ?></th><th id=counter_today_visits></th><th id=counter_today_visitors></th><th id=counter_today_robots></th></tr>
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
