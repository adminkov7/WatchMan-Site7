<?php
/**
 * Description: Base class for displaying a list of items in an ajaxified HTML table.
 *
 * @category    Wms7_List_Table
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.0.1
 * @license     GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Description: Creates a site visit custom table.
 *
 * @category    Class
 * @package     WatchMan-Site7
 * @author      Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version     3.0.0
 * @license     GPLv2 or later
 */
class Wms7_List_Table extends WP_List_Table {
	/**
	 * Creates a site visit custom table.
	 *
	 * @var array Saves the current value in the array to all kinds of visits.
	 */
	private static $wms7_data;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wms7;

		parent::__construct(
			array(
				'plural'   => 'wms7_visitor',
				'singular' => 'wms7_visitor',
				'ajax'     => false,
				'screen'   => null,
			)
		);
	}
	/**
	 * Custom function: WP_List_Table.
	 *
	 * @param string $name  Set name variable.
	 * @param string $value Set value variable.
	 */
	public static function wms7_set( $name, $value ) {
		self::$wms7_data[ $name ] = $value;
	}
	/**
	 * Custom function: WP_List_Table.
	 *
	 * @param string $name Get name variable.
	 * @return string.
	 */
	public static function wms7_get( $name ) {
		return ( isset( self::$wms7_data[ $name ] ) ) ? self::$wms7_data[ $name ] : false;
	}
	/**
	 * Custom function: WP_List_Table.
	 *
	 * @return string.
	 */
	public function wms7_get_current_url() {
		$_request_uri = filter_input( INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_STRING );
		$param        = false;
		// get current args from the URL.
		$query = wp_parse_url( $_request_uri );
		$args  = wp_parse_args( $query['query'] );

		if ( isset( $args['filter_country'] ) ) {
			$param['filter_country'] = sanitize_text_field( $args['filter_country'] );
		}

		if ( isset( $args['filter_role'] ) ) {
			$param['filter_role'] = sanitize_text_field( $args['filter_role'] );
		}

		if ( isset( $args['filter_time'] ) ) {
			$param['filter_time'] = sanitize_text_field( $args['filter_time'] );
		}

		if ( isset( $args['filter'] ) ) {
			$param['filter'] = sanitize_text_field( $args['filter'] );
		}

		if ( isset( $args['result'] ) ) {
			$param['result'] = sanitize_text_field( $args['result'] );
		}

		if ( isset( $args['orderby'] ) ) {
			$param['orderby'] = sanitize_text_field( $args['orderby'] );
		}

		if ( isset( $args['order'] ) ) {
			$param['order'] = sanitize_text_field( $args['order'] );
		}

		if ( isset( $args['paged'] ) ) {
			$param['paged'] = sanitize_text_field( $args['paged'] );
		}

		$menu_page_url                                     = menu_page_url( 'wms7_visitors', false );
		( is_array( $param ) && ! empty( $param ) ) ? $url = add_query_arg( $param, $menu_page_url ) : $url = $menu_page_url;
		// save the current url of the plugin in wp_options.
		update_option( 'wms7_current_url', $url );

		return $url;
	}
	/**
	 * Standart function: WP_List_Table.
	 *
	 * @param string $which top or bottom.
	 */
	public function extra_tablenav( $which ) {
		$val = get_option( 'wms7_screen_settings' );

		if ( 'top' === $which ) {

			$all_link        = isset( $val['all_link'] ) ? $val['all_link'] : 0;
			$unlogged_link   = isset( $val['unlogged_link'] ) ? $val['unlogged_link'] : 0;
			$successful_link = isset( $val['successful_link'] ) ? $val['successful_link'] : 0;
			$failed_link     = isset( $val['failed_link'] ) ? $val['failed_link'] : 0;
			$robots_link     = isset( $val['robots_link'] ) ? $val['robots_link'] : 0;
			$blacklist_link  = isset( $val['blacklist_link'] ) ? $val['blacklist_link'] : 0;

			$hidden_all_link        = ( '1' === $all_link ) ? '' : 'hidden="true"';
			$hidden_unlogged_link   = ( '1' === $unlogged_link ) ? '' : 'hidden="true"';
			$hidden_successful_link = ( '1' === $successful_link ) ? '' : 'hidden="true"';
			$hidden_failed_link     = ( '1' === $failed_link ) ? '' : 'hidden="true"';
			$hidden_robots_link     = ( '1' === $robots_link ) ? '' : 'hidden="true"';
			$hidden_blacklist_link  = ( '1' === $blacklist_link ) ? '' : 'hidden="true"';
			?>
			<a class='visit_result' title='<?php echo esc_html( 'Filter 2 level', 'watchman-site7' ); ?>'><?php echo esc_html( 'Visits', 'watchman-site7' ); ?> : </a>

			<input onclick=visit(id) class='radio' id='radio-1' name='result5' type='radio' value='1' <?php echo esc_html( $hidden_all_link ); ?> >
			<label for='radio-1' <?php echo esc_html( $hidden_all_link ); ?> ><?php echo esc_html( 'All', 'watchman-site7' ); ?>(<?php echo esc_html( $this->wms7_get( 'allTotal' ) ); ?>)</label>

			<input onclick=visit(id) class='radio' id='radio-2' name='result2' type='radio' value='1' <?php echo esc_html( $hidden_unlogged_link ); ?> >
			<label for='radio-2' <?php echo esc_html( $hidden_unlogged_link ); ?> ><?php echo esc_html( 'Unlogged', 'watchman-site7' ); ?>(<?php echo esc_html( $this->wms7_get( 'visitsTotal' ) ); ?>)</label>

			<input onclick=visit(id) class='radio' id='radio-3' name='result1' type='radio' value='1' <?php echo esc_html( $hidden_successful_link ); ?> >
			<label for='radio-3' <?php echo esc_html( $hidden_successful_link ); ?> ><?php echo esc_html( 'Success', 'watchman-site7' ); ?>(<?php echo esc_html( $this->wms7_get( 'successTotal' ) ); ?>)</label>

			<input onclick=visit(id) class='radio' id='radio-4' name='result0' type='radio' value='1' <?php echo esc_html( $hidden_failed_link ); ?> >
			<label for='radio-4' <?php echo esc_html( $hidden_failed_link ); ?> ><?php echo esc_html( 'Failed', 'watchman-site7' ); ?>(<?php echo esc_html( $this->wms7_get( 'failedTotal' ) ); ?>)</label>

			<input onclick=visit(id) class='radio' id='radio-5' name='result3' type='radio' value='1' <?php echo esc_html( $hidden_robots_link ); ?> >
			<label for='radio-5' <?php echo esc_html( $hidden_robots_link ); ?> ><?php echo esc_html( 'Robots', 'watchman-site7' ); ?>(<?php echo esc_html( $this->wms7_get( 'robotsTotal' ) ); ?>)</label>

			<input onclick=visit(id) class='radio' id='radio-6' name='result4' type='radio' value='1' <?php echo esc_html( $hidden_blacklist_link ); ?> >
			<label for='radio-6' <?php echo esc_html( $hidden_blacklist_link ); ?> ><?php echo esc_html( 'Black list', 'watchman-site7' ); ?>(<?php echo esc_html( $this->wms7_get( 'blacklistTotal' ) ); ?>)</label>

			<?php
		}
		// switcher top & bottom.
		$_mode = filter_input( INPUT_GET, 'mode', FILTER_SANITIZE_STRING );
		$mode  = ( $_mode ) ? $_mode : 'list';
		$table = new wms7_List_Table();
		$table->view_switcher( $mode );

		if ( 'bottom' === $which ) {

			$index_php     = isset( $val['index_php'] ) ? $val['index_php'] : 0;
			$robots_txt    = isset( $val['robots_txt'] ) ? $val['robots_txt'] : 0;
			$htaccess      = isset( $val['htaccess'] ) ? $val['htaccess'] : 0;
			$wp_config_php = isset( $val['wp_config_php'] ) ? $val['wp_config_php'] : 0;
			$wp_cron       = isset( $val['wp_cron'] ) ? $val['wp_cron'] : 0;
			$statistic     = isset( $val['statistic'] ) ? $val['statistic'] : 0;
			$mail          = isset( $val['mail'] ) ? $val['mail'] : 0;
			$console       = isset( $val['console'] ) ? $val['console'] : 0;

			$hidden_index_php     = ( '1' === $index_php ) ? '' : 'display:none;';
			$hidden_robots_txt    = ( '1' === $robots_txt ) ? '' : 'display:none;';
			$hidden_htaccess      = ( '1' === $htaccess ) ? '' : 'display:none;';
			$hidden_wp_config_php = ( '1' === $wp_config_php ) ? '' : 'display:none;';
			$hidden_wp_cron       = ( '1' === $wp_cron ) ? '' : 'display:none;';
			$hidden_statistic     = ( '1' === $statistic ) ? '' : 'display:none;';
			$hidden_mail          = ( '1' === $mail ) ? '' : 'display:none;';
			$hidden_console       = ( '1' === $console ) ? '' : 'display:none;';

			$current_user = wp_get_current_user();
			$roles        = $current_user->roles;
			$role         = array_shift( $roles );
			if ( 'analyst_wms7' === $role ) {
				$disabled = 'disabled';
			} else {
				$disabled = '';
			}
			// The code adds the buttons after the table.
			?>
			<form id='stub' method='POST'>
			</form>

			<form id='win1' method='POST'>
				<input type='submit' value='index' id='btn_bottom1' class='button' <?php echo esc_html( $disabled ); ?> name='footer' title='<?php echo esc_html( 'index.php of site', 'watchman-site7' ); ?>'  style='width:80px;<?php echo esc_html( $hidden_index_php ); ?>' >
			</form>

			<form id='win2' method='POST'>
				<input type='submit' value='robots' id='btn_bottom2' class='button' <?php echo esc_html( $disabled ); ?> name='footer' title='<?php echo esc_html( 'robots.txt of site', 'watchman-site7' ); ?>' style='width:80px;<?php echo esc_html( $hidden_robots_txt ); ?>' >
			</form>

			<form id='win3' method='POST'>
				<input type='submit' value='htaccess' id='btn_bottom3' class='button' <?php echo esc_html( $disabled ); ?> name='footer' title='<?php echo esc_html( '.htaccess of site', 'watchman-site7' ); ?>' style='width:80px;<?php echo esc_html( $hidden_htaccess ); ?>' >
			</form>

			<form id='win4' method='POST'>
				<input type='submit' value='wp_config' id='btn_bottom4' class='button' <?php echo esc_html( $disabled ); ?> name='footer' title='<?php echo esc_html( 'wp-config.php of site', 'watchman-site7' ); ?>' style='width:80px;<?php echo esc_html( $hidden_wp_config_php ); ?>' >
			</form>

			<form id='win5' method='POST'>
				<input type='submit' value='wp_cron' id='btn_bottom5' class='button' <?php echo esc_html( $disabled ); ?> name='footer' title=' <?php echo esc_html( 'wp-cron events of site', 'watchman-site7' ); ?>' style='width:80px;<?php echo esc_html( $hidden_wp_cron ); ?>' >
			</form>

			<form id='win6' method='POST'>
				<input type='submit' value='statistic' id='btn_bottom6' class='button' <?php echo esc_html( $disabled ); ?> name='footer' title='<?php echo esc_html( 'statistic of visits to site', 'watchman-site7' ); ?>' style='width:80px;<?php echo esc_html( $hidden_statistic ); ?>' >
			</form>

			<form id='win7' method='POST'>
				<input type='submit' value='sma' id='btn_bottom7' class='button' <?php echo esc_html( $disabled ); ?> name='footer' title='<?php echo esc_html( 'simple mail agent', 'watchman-site7' ); ?>' style='width:80px;<?php echo esc_html( $hidden_mail ); ?>' >
			</form>

			<form id='win8' method='POST'>
				<input type='submit' value='console' id='btn_bottom8' class='button' <?php echo esc_html( $disabled ); ?> name='footer' title='<?php echo esc_html( 'console', 'watchman-site7' ); ?>' style='width:80px;<?php echo esc_html( $hidden_console ); ?>' >
			</form>
			<?php
		}
	}
	/**
	 * Custom function: WP_List_Table.
	 *
	 * @param string $uid User id.
	 * @return boolean.
	 */
	public function wms7_login_compromising( $uid ) {
		global $wpdb;

		$cache_key = 'wms7_login_compromising' . $uid;
		$results   = wp_cache_get( $cache_key );
		if ( ! $results ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"
					SELECT DISTINCT `black_list`
					FROM {$wpdb->prefix}watchman_site
					WHERE `uid` = %d AND `black_list` <> %s
					",
					$uid,
					''
				),
				'ARRAY_A'
			);// unprepared sql ok;db call ok;cache ok.
			wp_cache_set( $cache_key, $results );
		}
		$compromising = false;
		foreach ( $results as $item ) {
			$str = array_shift( $item );
			$arr = json_decode( $str, true );
			if ( isset( $arr['ban_login'] ) && ( '1' === $arr['ban_login'] ) ) {
				$compromising = true;
				break;
			}
		}
		return $compromising;
	}
	/**
	 * Custom function: WP_List_Table.
	 *
	 * @param string $user_ip User ip.
	 * @return boolean.
	 */
	public function wms7_ip_compromising( $user_ip ) {
		global $wpdb;

		$cache_key = 'wms7_ip_compromising' . $user_ip;
		$results   = wp_cache_get( $cache_key );
		if ( ! $results ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"
					SELECT DISTINCT `user_ip`
					FROM {$wpdb->prefix}watchman_site
					WHERE `user_ip` = %s AND `black_list` <> %s
					",
					$user_ip,
					''
				),
				'ARRAY_A'
			);// unprepared sql ok;db call ok;cache ok.
			wp_cache_set( $cache_key, $results );
		}
		if ( 0 === count( $results ) ) {
			return false;
		} else {
			return true;
		}
	}
	/**
	 * Standart function: WP_List_Table.
	 *
	 * @param array $item        item.
	 * @param array $column_name column_name.
	 * @return string.
	 */
	public function column_default( $item, $column_name ) {
		$_mode = filter_input( INPUT_GET, 'mode', FILTER_SANITIZE_STRING );

		switch ( $column_name ) {
			case 'id':
			case 'uid':
			case 'time_visit':
			case 'user_login':
			case 'user_role':
				return $item[ $column_name ];
			case 'page_visit':
				$output = $item[ $column_name ];
				$output = ( isset( $_mode ) && 'excerpt' === $_mode ) ? $output : substr( $output, 0, 130 ) . '...';
				return $output;
			case 'page_from':
				$output = $item[ $column_name ];
				$output = ( isset( $_mode ) && 'excerpt' === $_mode ) ? $output : substr( $output, 0, 130 ) . '...';
				return $output;
			case 'info':
				$data = json_decode( $item[ $column_name ], true );
				if ( is_array( $data ) ) {
					$output = '';
					foreach ( $data as $k => $v ) {
						$output .= $k . ': ' . $v . '<br />';
					}
					unset( $k );
					$output = ( isset( $_mode ) && 'excerpt' === $_mode ) ? $output : substr( $output, 0, 130 ) . '...';
					return $output;
				}
				break;
			default:
				return $item[ $column_name ];
		}
	}
	/**
	 * Custom function: WP_List_Table.
	 *
	 * @param array $item item.
	 * @return string sprintf() or item.
	 */
	public function column_user_login( $item ) {

		if ( $item['uid'] ) {
			$avatar = get_avatar( $item['uid'], 30 );
			if ( isset( $avatar ) ) {
					$user_login = $avatar . '<br>' . $item['user_login'];
			} else {
				$user_login = $item['user_login'];
			}
			$url     = $this->wms7_get_current_url();
			$actions = array(
				'message' => sprintf( '<a href="' . $url . '&uid=%s">%s</a>', $item['uid'], esc_html( 'Message', 'watchman-site7' ) ),
			);
			if ( $this->wms7_login_compromising( $item['uid'] ) ) {
				$user_login = '<span class="failed">' . $user_login . '</span>';
			}
			return sprintf(
				'%s %s',
				$user_login,
				$this->row_actions( $actions )
			);
		} else {
			return $item['user_login'];
		}
	}
	/**
	 * Custom function: WP_List_Table.
	 *
	 * @param array $item item.
	 * @return string sprintf().
	 */
	public function column_user_ip( $item ) {
		// Checking the compromising IP.
		if ( $this->wms7_ip_compromising( $item['user_ip'] ) ) {
			$item['user_ip'] = '<span class="failed">' . $item['user_ip'] . '</span>';
		}
		$url     = $this->wms7_get_current_url();
		$actions = array(
			'map' => sprintf( '<a href="' . $url . '&action=map&id=%s">%s</a>', $item['id'], __( 'Map', 'watchman-site7' ) ),
		);
		return sprintf(
			'%s %s',
			$item['user_ip'] . '<br>' . $item['country'],
			$this->row_actions( $actions )
		);
	}
	/**
	 * Custom function: WP_List_Table.
	 *
	 * @param array $item item.
	 * @return string sprintf().
	 */
	public function column_black_list( $item ) {

		$url    = $this->wms7_get_current_url();
		$output = '';

		$data = json_decode( $item['black_list'], true );
		if ( is_array( $data ) ) {
			$output = '';
			foreach ( $data as $k => $v ) {
				$output .= $k . ': ' . $v . '<br />';
			}
			unset( $k );
		}
		$actions = array(
			'edit'  => sprintf( '<a href="?page=wms7_black_list&id=%s">%s</a>', $item['id'], __( 'Edit', 'watchman-site7' ) ),
			'clear' => sprintf( '<a href="' . $url . '&action=clear&id=%s">%s</a>', $item['id'], __( 'Clear', 'watchman-site7' ) ),
		);
		return sprintf(
			'%s %s',
			$output,
			$this->row_actions( $actions )
		);
	}
	/**
	 * Custom function: WP_List_Table.
	 *
	 * @param array $item item.
	 * @return string sprintf().
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="id[]" value="%s" />',
			$item['id']
		);
	}
	/**
	 * Standart function: WP_List_Table.
	 *
	 * @return array columns.
	 */
	public function get_columns() {
		$columns = array(
			'cb'         => '<input type="checkbox" />',
			'id'         => __( 'ID', 'watchman-site7' ),
			'uid'        => __( 'UID', 'watchman-site7' ),
			'user_login' => __( 'Login', 'watchman-site7' ),
			'user_role'  => __( 'Role', 'watchman-site7' ),
			'time_visit' => __( 'Date visit', 'watchman-site7' ),
			'user_ip'    => __( 'Visitor IP', 'watchman-site7' ),
			'black_list' => __( 'Black list', 'watchman-site7' ),
			'page_visit' => __( 'Page visit', 'watchman-site7' ),
			'page_from'  => __( 'Page from', 'watchman-site7' ),
			'info'       => __( 'Info', 'watchman-site7' ),
		);
		return $columns;
	}
	/**
	 * Standart function: WP_List_Table.
	 *
	 * @return array sortable_columns.
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'id'         => array( 'id', true ),
			'uid'        => array( 'uid', false ),
			'user_login' => array( 'user_login', false ),
			'user_role'  => array( 'user_role', false ),
			'time_visit' => array( 'time_visit', true ),
			'user_ip'    => array( 'user_ip', false ),
			'page_visit' => array( 'page_visit', true ),
			'page_from'  => array( 'page_from', true ),
		);
		return $sortable_columns;
	}
	/**
	 * Standart function: WP_List_Table.
	 *
	 * @return array actions.
	 */
	public function get_bulk_actions() {
		$actions = array(
			'delete' => __( 'Delete', 'watchman-site7' ),
			'export' => __( 'Export', 'watchman-site7' ),
		);
		return $actions;
	}
	/**
	 * Standart function: WP_List_Table.
	 */
	private function process_bulk_action() {
		global $wpdb;

		$this->wms7_set( 'wms7_action', $this->current_action() );

		if ( 'export' === $this->current_action() ) {
			$_id = filter_input( INPUT_GET, 'id', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
			$this->wms7_set( 'wms7_id', $_id );
			wms7_output_csv();
		}
		if ( 'delete' === $this->current_action() ) {
			$_id = filter_input( INPUT_GET, 'id', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
			if ( ! $_id ) {
				return;
			}
			$this->wms7_set( 'wms7_id', $_id );
			$ids = implode( ',', $_id );
			if ( $ids ) {
				$cache_key = 'process_bulk_action_part1';
				$results   = wp_cache_get( $cache_key );
				if ( ! $results ) {
					$results = $wpdb->query(
						$wpdb->prepare(
							"
							DELETE
							FROM {$wpdb->prefix}watchman_site
							WHERE `id` IN ($ids)  AND `black_list` = %s
							",
							''
						)
					);// unprepared sql ok;db call ok;cache ok.
					wp_cache_set( $cache_key, $results );
				}
				$this->wms7_set( 'wms7_id_del', $results );
			}
		}
		if ( 'clear' === $this->current_action() ) {
			$_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_STRING );
			$this->wms7_set( 'wms7_id', $_id );
			$wpdb->update(
				$wpdb->prefix . 'watchman_site',
				array( 'black_list' => '' ),
				array( 'ID' => $_id )
			);// unprepared sql ok;db call ok.
			// delete ip from .htaccess.
			$cache_key = 'process_bulk_action_part2';
			$results   = wp_cache_get( $cache_key );
			if ( ! $results ) {
				$results = $wpdb->get_results(
					$wpdb->prepare(
						"
				        SELECT `user_ip`
				        FROM {$wpdb->prefix}watchman_site
				        WHERE `id` = %s
				        ",
						$_id
					),
					'ARRAY_A'
				);// unprepared sql ok;db call ok;cache ok.
				wp_cache_set( $cache_key, $results );
			}
			$user_ip = array_shift( $results[0] );

			wms7_ip_delete_from_file( $user_ip );
		}
	}
	/**
	 * Standart function: WP_List_Table.
	 */
	public function prepare_items() {
		global $wpdb, $wms7;

		$this->process_bulk_action();

		$where  = $wms7->wms7_make_where_query();
		$where6 = $where;
		$where5 = $where;
		$where4 = $where;
		$where3 = $where;
		$where2 = $where;
		$where1 = $where;

		unset( $where['result'] );
		unset( $where1['result'] );
		unset( $where2['result'] );
		unset( $where3['result'] );
		unset( $where4['result'] );
		unset( $where5['result'] );
		unset( $where6['result'] );

		$where2['login_result'] = "login_result = '1'"; // logged visits.
		$where3['login_result'] = "login_result = '0'"; // failed visits.
		$where4['login_result'] = "login_result = '2'"; // unlogged visits.
		$where5['login_result'] = "login_result = '3'"; // robots visits.
		$where6['login_result'] = "black_list <> ''";   // black list.

		if ( is_array( $where ) && ! empty( $where ) ) {
			$where = 'WHERE ' . implode( ' AND ', $where );
		} else {
			$where = '';}
		if ( is_array( $where1 ) && ! empty( $where1 ) ) {
			$where1 = 'WHERE ' . implode( ' AND ', $where1 );
		} else {
			$where1 = '';}
		if ( is_array( $where2 ) && ! empty( $where2 ) ) {
			$where2 = 'WHERE ' . implode( ' AND ', $where2 );
		} else {
			$where2 = '';}
		if ( is_array( $where3 ) && ! empty( $where3 ) ) {
			$where3 = 'WHERE ' . implode( ' AND ', $where3 );
		} else {
			$where3 = '';}
		if ( is_array( $where4 ) && ! empty( $where4 ) ) {
			$where4 = 'WHERE ' . implode( ' AND ', $where4 );
		} else {
			$where4 = '';}
		if ( is_array( $where5 ) && ! empty( $where5 ) ) {
			$where5 = 'WHERE ' . implode( ' AND ', $where5 );
		} else {
			$where5 = '';}
		if ( is_array( $where6 ) && ! empty( $where6 ) ) {
			$where6 = 'WHERE ' . implode( ' AND ', $where6 );
		} else {
			$where6 = '';}

		$_result = filter_input( INPUT_GET, 'result', FILTER_SANITIZE_STRING );

		$cache_key = 'all_total';
		$all_total = wp_cache_get( $cache_key );
		if ( ! $all_total ) {
			$all_total = $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->prefix}watchman_site {$where1}"
			);// unprepared sql ok;db call ok;cache ok.
			wp_cache_set( $cache_key, $all_total );
		}
		if ( '5' === $_result || null === $_result ) {
			$total_items = $all_total;
		}

		$cache_key     = 'success_total';
		$success_total = wp_cache_get( $cache_key );
		if ( ! $success_total ) {
			$success_total = $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->prefix}watchman_site {$where2}"
			);// unprepared sql ok;db call ok;cache ok.
			wp_cache_set( $cache_key, $success_total );
		}
		if ( '1' === $_result ) {
			$total_items = $success_total;
		}

		$cache_key    = 'failed_total';
		$failed_total = wp_cache_get( $cache_key );
		if ( ! $failed_total ) {
			$failed_total = $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->prefix}watchman_site {$where3}"
			);// unprepared sql ok;db call ok;cache ok.
			wp_cache_set( $cache_key, $failed_total );
		}
		if ( '0' === $_result ) {
			$total_items = $failed_total;
		}

		$cache_key    = 'visits_total';
		$visits_total = wp_cache_get( $cache_key );
		if ( ! $visits_total ) {
			$visits_total = $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->prefix}watchman_site {$where4}"
			);// unprepared sql ok;db call ok;cache ok.
			wp_cache_set( $cache_key, $visits_total );
		}
		if ( '2' === $_result ) {
			$total_items = $visits_total;
		}

		$cache_key    = 'robots_total';
		$robots_total = wp_cache_get( $cache_key );
		if ( ! $robots_total ) {
			$robots_total = $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->prefix}watchman_site {$where5}"
			);// unprepared sql ok;db call ok;cache ok.
			wp_cache_set( $cache_key, $robots_total );
		}
		if ( '3' === $_result ) {
			$total_items = $robots_total;
		}

		$cache_key       = 'blacklist_total';
		$blacklist_total = wp_cache_get( $cache_key );
		if ( ! $blacklist_total ) {
			$blacklist_total = $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->prefix}watchman_site {$where6}"
			);// unprepared sql ok;db call ok;cache ok.
			wp_cache_set( $cache_key, $blacklist_total );
		}
		if ( '4' === $_result ) {
			$total_items = $blacklist_total;
		}

		$this->wms7_set( 'allTotal', $all_total );
		$this->wms7_set( 'successTotal', $success_total );
		$this->wms7_set( 'failedTotal', $failed_total );
		$this->wms7_set( 'visitsTotal', $visits_total );
		$this->wms7_set( 'robotsTotal', $robots_total );
		$this->wms7_set( 'blacklistTotal', $blacklist_total );
		$this->wms7_set( 'where', $where );

		$screen          = get_current_screen();
		$per_page_option = 'wms7_visitors_per_page';
		$per_page        = get_option( $per_page_option, 10 );
		$offset          = $per_page * ( $this->get_pagenum() - 1 );

		$columns     = $this->get_columns();
		$hidden_cols = get_user_option( 'manage' . $screen->id . 'columnshidden' );
		$hidden      = ( $hidden_cols ) ? $hidden_cols : array();
		$sortable    = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$_orderby = filter_input( INPUT_GET, 'orderby', FILTER_SANITIZE_STRING );
		$orderby  = ( $_orderby ) ? $_orderby : 'id';
		$_order   = filter_input( INPUT_GET, 'order', FILTER_SANITIZE_STRING );
		$order    = ( $_order ) ? $_order : 'desc';

		$this->items = $wms7->wms7_visit_get_data( $orderby, $order, $per_page, $offset );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items, // total items defined above.
				'per_page'    => $per_page, // per page constant defined at top of method.
				'total_pages' => ceil( $total_items / $per_page ), // calculate pages count.
			)
		);
		$this->wms7_get_current_url();
	}
}
