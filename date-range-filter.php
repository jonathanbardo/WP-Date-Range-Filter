<?php
/**
 * Plugin Name: Date range filter
 * Description: Easily filter the admin list of post and custom post type with a date range
 * Version: 0.0.11
 * Author: Jonathan Bardo, Ricardo Losso
 * License: GPLv2+
 * Text Domain: date-range-filter
 * Domain Path: /languages
 * Author URI: https://jonathanbardo.com
 */

class Date_Range_Filter {
	/**
	 * Holds the plugin version number
	 */
	const VERSION = '0.0.11';

	/**
	 * Contain the called class name
	 *
	 * @var string
	 */
	protected static $class;

	/**
	 *  Dashboard widget ID.
	 */
	const dashboard_widget_id = 'date_range_dashboard_widget';

	/**
	 * Object constructor
	 */
	public static function setup() {
		// I heard you like to extend plugins?
		static::$class = get_called_class();

		define( 'DATE_RANGE_FILTER_DIR',     plugin_dir_path( __FILE__ ) );
		define( 'DATE_RANGE_FILTER_URL',     plugin_dir_url( __FILE__ ) );
		define( 'DATE_RANGE_FILTER_INC_DIR', DATE_RANGE_FILTER_DIR . 'includes/' );

		add_action( 'restrict_manage_posts', array( static::$class, 'add_daterange_select' ) );
		add_action( 'admin_enqueue_scripts', array( static::$class, 'admin_menu_scripts' ) );
		add_filter( 'pre_get_posts',         array( static::$class, 'filter_main_query' ), 10, 1 );

		if ( current_user_can( apply_filters( 'date_range_filter_dashboard_cap', 'edit_dashboard' ) ) ) {
			add_action( 'wp_dashboard_setup', array( static::$class, 'add_dashboard_widget' ) );
		}

		load_plugin_textdomain( 'date-range-filter', false,  dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Add daterange select
	 */
	public static function add_daterange_select() {
		wp_enqueue_style( 'jquery-ui' );
		wp_enqueue_style( 'date-range-filter-admin' );
		wp_enqueue_style( 'date-range-filter-datepicker' );

		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'date-range-filter-admin' );

		$date_predefined = isset( $_GET['date_predefined'] ) ? sanitize_text_field( $_GET['date_predefined'] ) : '';
		$date_from       = isset( $_GET['date_from'] ) ? sanitize_text_field( $_GET['date_from'] ) : '';
		$date_to         = isset( $_GET['date_to'] ) ? sanitize_text_field( $_GET['date_to'] ) : '';

		$intervals = self::get_predefined_intervals();
		?>
		<div id="date-range-filter-interval" class="date-interval">

			<select class="field-predefined hide-if-no-js" name="date_predefined" data-placeholder="<?php _e( 'Show All Time', 'date-range-filter' ); ?>">
				<option value="""><?php _e( 'Show All Time', 'date-range-filter' ); ?></option>
				<option value="custom" <?php selected( 'custom' === $date_predefined ); ?>><?php esc_attr_e( 'Custom Date Range', 'date-range-filter' ) ?></option>
				<?php foreach ( $intervals as $key => $interval ) {
					printf(
						'<option value="%s" data-from="%s" data-to="%s" %s>%s</option>',
						esc_attr( $key ),
						esc_attr( $interval['start']->format( 'Y/m/d' ) ),
						esc_attr( $interval['end']->format( 'Y/m/d' ) ),
						selected( $key === $date_predefined ),
						esc_html( $interval['label'] )
					); // xss ok
				} ?>
			</select>

			<div class="date-inputs">
				<div class="box">
					<i class="date-remove dashicons"></i>
					<input type="text"
						   name="date_from"
						   class="field-from"
						   placeholder="<?php esc_attr_e( 'Start Date', 'default' ) ?>"
						   value="<?php echo esc_attr( $date_from ) ?>">
				</div>
				<span class="connector dashicons"></span>

				<div class="box">
					<i class="date-remove dashicons"></i>
					<input type="text"
						   name="date_to"
						   class="field-to"
						   placeholder="<?php esc_attr_e( 'End Date', 'default' ) ?>"
						   value="<?php echo esc_attr( $date_to ) ?>">
				</div>
			</div>

		</div>
		<?php
	}

	/**
	 * Get predefined intervals values
	 */
	protected static function get_predefined_intervals() {
		// Carbon is great to handle dates
		if ( ! class_exists( 'Carbon\Carbon' ) ) {
			require_once DATE_RANGE_FILTER_INC_DIR . 'vendor/Carbon.php';
		}

		$timezone = get_option( 'timezone_string' );

		if ( empty( $timezone ) ) {
			$gmt_offset = (int) get_option( 'gmt_offset' );
			$timezone   = timezone_name_from_abbr( null, $gmt_offset * 3600, true );
			if ( false === $timezone ) {
				$timezone = timezone_name_from_abbr( null, $gmt_offset * 3600, false );
			}
			if ( false === $timezone ) {
				$timezone = null;
			}
		}

		return apply_filters(
			'date_range_filter_intervals',
			array(
				'today' => array(
					'label' => esc_html__( 'Today', 'default' ),
					'start' => Carbon\Carbon::today( $timezone )->startOfDay(),
					'end'   => Carbon\Carbon::today( $timezone )->endOfDay(),
				),
				'yesterday' => array(
					'label' => esc_html__( 'Yesterday', 'date-range-filter' ),
					'start' => Carbon\Carbon::today( $timezone )->startOfDay()->subDay(),
					'end'   => Carbon\Carbon::today( $timezone )->startOfDay()->subSecond(),
				),
				'last-7-days' => array(
					'label' => sprintf( esc_html__( 'Last %d Days', 'date-range-filter' ), 7 ),
					'start' => Carbon\Carbon::today( $timezone )->subDays( 7 ),
					'end'   => Carbon\Carbon::today( $timezone )->endOfDay(),
				),
				'last-14-days' => array(
					'label' => sprintf( esc_html__( 'Last %d Days', 'date-range-filter' ), 14 ),
					'start' => Carbon\Carbon::today( $timezone )->subDays( 14 ),
					'end'   => Carbon\Carbon::today( $timezone )->endOfDay(),
				),
				'last-30-days' => array(
					'label' => sprintf( esc_html__( 'Last %d Days', 'date-range-filter' ), 30 ),
					'start' => Carbon\Carbon::today( $timezone )->subDays( 30 ),
					'end'   => Carbon\Carbon::today( $timezone )->endOfDay(),
				),
				'this-month' => array(
					'label' => esc_html__( 'This Month', 'date-range-filter' ),
					'start' => Carbon\Carbon::today( $timezone )->day( 1 ),
					'end'   => Carbon\Carbon::today( $timezone )->endOfDay(),
				),
				'last-month' => array(
					'label' => esc_html__( 'Last Month', 'date-range-filter' ),
					'start' => Carbon\Carbon::today( $timezone )->day( 1 )->subMonth(),
					'end'   => Carbon\Carbon::today( $timezone )->day( 1 )->subSecond(),
				),
				'last-3-months' => array(
					'label' => sprintf( esc_html__( 'Last %d Months', 'date-range-filter' ), 3 ),
					'start' => Carbon\Carbon::today( $timezone )->subMonths( 3 ),
					'end'   => Carbon\Carbon::today( $timezone )->endOfDay(),
				),
				'last-6-months' => array(
					'label' => sprintf( esc_html__( 'Last %d Months', 'date-range-filter' ), 6 ),
					'start' => Carbon\Carbon::today( $timezone )->subMonths( 6 ),
					'end'   => Carbon\Carbon::today( $timezone )->endOfDay(),
				),
				'last-12-months' => array(
					'label' => sprintf( esc_html__( 'Last %d Months', 'date-range-filter' ), 12 ),
					'start' => Carbon\Carbon::today( $timezone )->subMonths( 12 ),
					'end'   => Carbon\Carbon::today( $timezone )->endOfDay(),
				),
				'this-year' => array(
					'label' => esc_html__( 'This Year', 'date-range-filter' ),
					'start' => Carbon\Carbon::today( $timezone )->day( 1 )->month( 1 ),
					'end'   => Carbon\Carbon::today( $timezone )->endOfDay(),
				),
				'last-year' => array(
					'label' => esc_html__( 'Last Year', 'date-range-filter' ),
					'start' => Carbon\Carbon::today( $timezone )->day( 1 )->month( 1 )->subYear(),
					'end'   => Carbon\Carbon::today( $timezone )->day( 1 )->month( 1 )->subSecond(),
				),
			),
			$timezone
		);
	}

	/**
	 * Register all needed assets
	 */
	public static function admin_menu_scripts() {
		global $pagenow;

		if ( ! in_array( $pagenow, array( 'index.php', 'edit.php', 'upload.php' ) ) ) {
			return false;
		}

		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_register_style( 'jquery-ui', '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.1/themes/base/jquery-ui.css', array(), '1.10.1' );
		wp_enqueue_style( 'date-range-filter-datepicker', DATE_RANGE_FILTER_URL . "css/datepicker{$suffix}.css", array( 'jquery-ui' ), self::VERSION );
		wp_enqueue_style( 'date-range-filter-admin', DATE_RANGE_FILTER_URL . "css/admin{$suffix}.css", array(), self::VERSION );
		wp_register_script( 'date-range-filter-admin', DATE_RANGE_FILTER_URL . "js/admin{$suffix}.js", array( 'jquery' ), self::VERSION, true );
		wp_localize_script(
			'date-range-filter-admin',
			'date_range_filter',
			array(
				'gmt_offset'     => get_option( 'gmt_offset' ),
			)
		);
	}

	/**
	 * Look if we are on an admin list page and if so we filter the main query
	 *
	 * @param $wp_query
	 *
	 * @return
	 */
	public static function filter_main_query( $wp_query ) {
		global $pagenow;

		if (
			is_admin()
			&& $wp_query->is_main_query()
			&& in_array( $pagenow, array( 'edit.php', 'upload.php' ) )
			&& ! empty( $_GET['date_from'] )
			&& ! empty( $_GET['date_to'] )
		) {
			$from = explode( '/', sanitize_text_field( $_GET['date_from'] ) );
			$to   = explode( '/', sanitize_text_field( $_GET['date_to'] ) );

			$from = array_map( 'intval', $from );
			$to   = array_map( 'intval', $to );

			if (
				3 === count( $to )
				&& 3 === count( $from )
			) {
				list( $year_from, $month_from, $day_from ) = $from;
				list( $year_to, $month_to, $day_to )       = $to;
			} else {
				return $wp_query;
			}

			$wp_query->set(
				'date_query',
				array(
					'after' => array(
						'year'  => $year_from,
						'month' => $month_from,
						'day'   => $day_from,
					),
					'before' => array(
						'year'  => $year_to,
						'month' => $month_to,
						'day'   => $day_to,
					),
					'inclusive' => apply_filters( 'date_range_filter_query_is_inclusive', true ),
					'column'    => apply_filters( 'date_range_filter_query_column', 'post_date' ),
				)
			);
		}

		return $wp_query;
	}

	/**
	 * Add dashboard widget
	 */
	public static function add_dashboard_widget() {

		$drf_post_types = array();
		$post_types = get_post_types( array(), 'objects' );
		foreach ( $post_types as $post_type ) {
			$drf_post_types[] = $post_type->name;
		}

		$drf_date_predefined = 'last-7-days';

		$date_intervals = self::get_predefined_intervals();

		if ( array_key_exists( $drf_date_predefined, $date_intervals ) ) {
			$drf_date_from = str_replace( '.000000', '', $date_intervals[ $drf_date_predefined ]['start'] );
			$drf_date_to   = str_replace( '.000000', '', $date_intervals[ $drf_date_predefined ]['end'] );
		} else {
			$drf_date_from = '';
			$drf_date_to   = '';
		}

		// Register widget settings... Add only (will not update existing options)
		self::update_dashboard_widget_options(
			self::dashboard_widget_id,
			array(
				'drf_post_types'      => $drf_post_types,
				'drf_date_from'       => $drf_date_from,
				'drf_date_to'         => $drf_date_to,
				'drf_date_predefined' => $drf_date_predefined
			),
			true
		);

		$drf_date_predefined = self::get_dashboard_widget_option( self::dashboard_widget_id, 'drf_date_predefined' );
		$date_intervals      = self::get_predefined_intervals();

		if ( empty( $drf_date_predefined ) ) {
			$date_label = __( 'Show All Time', 'date-range-filter' );
		} else {
			if ( array_key_exists( $drf_date_predefined, $date_intervals ) ) {
				$date_label = $date_intervals[ $drf_date_predefined ]['label'];
			} else {
				$date_from  = date( 'd/m/Y', strtotime( self::get_dashboard_widget_option( self::dashboard_widget_id, 'drf_date_from' ) ) );
				$date_to    = date( 'd/m/Y', strtotime( self::get_dashboard_widget_option( self::dashboard_widget_id, 'drf_date_to' ) ) );
				$date_label = sprintf( __( '%s - %s', 'date-range-filter' ), $date_from, $date_to );
			}
		}

		$widget_title = sprintf( esc_html__( 'Posts per Date (%s)', 'date-range-filter' ), $date_label );

		wp_add_dashboard_widget(
			self::dashboard_widget_id,
			$widget_title,
			array( __CLASS__, 'dashboard_widget' ),
			array( __CLASS__, 'dashboard_config' )
		);
	}

	/**
	 * Dashboard widget
	 */
	public static function dashboard_widget() {
		// Enqueue admin script here as well
		wp_enqueue_script( 'date-range-filter-admin' );

		$drf_post_types      = self::get_dashboard_widget_option( self::dashboard_widget_id, 'drf_post_types' );
		$drf_date_from       = self::get_dashboard_widget_option( self::dashboard_widget_id, 'drf_date_from' );
		$drf_date_to         = self::get_dashboard_widget_option( self::dashboard_widget_id, 'drf_date_to' );
		$drf_date_predefined = self::get_dashboard_widget_option( self::dashboard_widget_id, 'drf_date_predefined' );
		$output              = self::count_posts_per_date( $drf_post_types, $drf_date_from, $drf_date_to, $drf_date_predefined );

		require_once( 'includes/dashboard/widget.php' );

	}

	/**
	 * Dashboard config
	 */
	public static function dashboard_config() {

		// Save widget configuration
		if ( isset( $_POST['drf_save_config'] ) and '1' === $_POST['drf_save_config'] ) {

			$drf_post_types  = ( isset( $_POST['drf_post_types'] ) ) ? $_POST['drf_post_types'] : array();
			$date_predefined = ( isset( $_POST['date_predefined'] ) ) ? stripslashes( $_POST['date_predefined'] ) : '';
			$date_from       = ( isset( $_POST['date_from'] ) ) ? stripslashes( $_POST['date_from'] ) : '';
			$date_to         = ( isset( $_POST['date_to'] ) ) ? stripslashes( $_POST['date_to'] ) : '';

			$date_intervals = self::get_predefined_intervals();

			if ( empty( $date_predefined ) ) {
				$drf_date_from = '';
				$drf_date_to   = '';
			} else {
				if ( array_key_exists( $date_predefined, $date_intervals ) ) {
					$drf_date_from = str_replace( '.000000', '', $date_intervals[ $date_predefined ]['start'] );
					$drf_date_to   = str_replace( '.000000', '', $date_intervals[ $date_predefined ]['end'] );
				} else {
					$drf_date_from = str_replace( '/', '-', $date_from ) . ' 00:00:00';
					$drf_date_to   = str_replace( '/', '-', $date_to ) . ' 23:59:59';
				}
			}

			// Save widget options
			self::update_dashboard_widget_options(
				self::dashboard_widget_id,
				array(
					'drf_post_types'      => $drf_post_types,
					'drf_date_from'       => $drf_date_from,
					'drf_date_to'         => $drf_date_to,
					'drf_date_predefined' => $date_predefined
				)
			);

		}

		require_once( 'includes/dashboard/config.php' );

	}

	/**
	 * List TOTAL post per date (group by type and status)
	 * @param $post_types
	 * @param $start_date
	 * @param $end_date
	 * @return array
	 */
	public static function count_posts_per_date( $post_types = array(), $start_date = '', $end_date = '', $date_predefined = '' ) {
		global $wpdb, $wp_post_statuses, $wp_post_types;

		$post_types = isset( $post_types ) ? $post_types : array();
		$start_date = isset( $start_date ) ? trim( $start_date ) : null;
		$end_date   = isset( $end_date ) ? trim( $end_date ) : null;

		if ( empty( $post_types ) ) {
			return array();
		}

		$start_date_query = ! empty( $start_date ) ? " AND ( p.post_date >= '{$start_date}' )" : null;
		$end_date_query   = ! empty( $end_date ) ? " AND ( p.post_date <= '{$end_date}' )" : null;
		$posts            = array();
		$post_types       = array_diff( $post_types, array( 'revision', 'attachment', 'nav_menu_item' ) );

		foreach( $post_types as $post_type ) {

			$post = array();

			$results = $wpdb->get_results(
				"SELECT
					p.post_type AS post_type,
					p.post_status AS post_status,
					COUNT( p.ID ) AS status_count
				FROM
					{$wpdb->posts} p
				WHERE
					( p.post_type = '{$post_type}' )AND ( p.post_status NOT IN ( 'trash' ) )
					$start_date_query
					$end_date_query
				GROUP BY
					p.post_status
				ORDER BY
					p.post_status ASC"
			);

			$total_count = 0;
			foreach( $results as $row ) {
				$total_count += $row->status_count;
				$row->status_name = array_key_exists( $row->post_status, $wp_post_statuses ) ? $wp_post_statuses[ $row->post_status ]->label : $row->post_status;
			}

			list( $date_from ) = explode( ' ', str_replace( '-', '/', $start_date ) );
			list( $date_to )   = explode( ' ', str_replace( '-', '/', $end_date ) );

			$post['post_type']  = $post_type;
			$post['type_label'] = $wp_post_types[ $post_type ]->labels->name;
			$post['count']      = $total_count;
			$post['status']     = $results;
			$post['url']        = get_admin_url(
				get_current_blog_id(),
				sprintf(
					"edit.php?post_type=%s&date_predefined=%s&date_from=%s&date_to=%s",
					$post_type,
					$date_predefined,
					urlencode( $date_from ),
					urlencode( $date_to )
				)
			);

			$posts[] = $post;

		}

		return $posts;

	}

	/**
	 * Gets the options for a widget of the specified name.
	 *
	 * @param string $widget_id Optional. If provided, will only get options for the specified widget.
	 * @return array An associative array containing the widget's options and values. False if no opts.
	 */
	public static function get_dashboard_widget_options( $widget_id = '' ) {

		// Fetch ALL dashboard widget options from the db...
		$opts = get_option( 'dashboard_widget_options' );

		// If no widget is specified, return everything
		if ( empty( $widget_id ) ) {
			return $opts;
		}

		// If we request a widget and it exists, return it
		if ( isset( $opts[ $widget_id ] ) ) {
			return $opts[ $widget_id ];
		}

		// Something went wrong...
		return false;

	}

	/**
	 * Gets one specific option for the specified widget.
	 * @param $widget_id
	 * @param $option
	 * @param null $default
	 *
	 * @return string
	 */
	public static function get_dashboard_widget_option( $widget_id, $option, $default = null ) {

		$opts = self::get_dashboard_widget_options( $widget_id );

		// If widget opts dont exist, return false
		if ( ! $opts ) {
			return false;
		}

		// Otherwise fetch the option or use default
		if ( isset( $opts[ $option ] ) && ! empty( $opts[ $option ] ) ) {
			return $opts[ $option ];
		} else {
			return ( isset( $default ) ) ? $default : false;
		}

	}

	/**
	 * Saves an array of options for a single dashboard widget to the database.
	 * Can also be used to define default values for a widget.
	 *
	 * @param $widget_id
	 * @param array $args
	 * @param bool $add_only
	 *
	 * @return bool
	 */
	public static function update_dashboard_widget_options( $widget_id , $args = array(), $add_only = false ) {

		// Fetch ALL dashboard widget options from the db...
		$opts = get_option( 'dashboard_widget_options' );

		// Get just our widget's options, or set empty array
		$w_opts = ( isset( $opts[ $widget_id ] ) ) ? $opts[ $widget_id ] : array();

		if ( $add_only ) {
			// Flesh out any missing options (existing ones overwrite new ones)
			$opts[ $widget_id ] = array_merge( $args, $w_opts );
		} else {
			// Merge new options with existing ones, and add it back to the widgets array
			$opts[ $widget_id ] = array_merge( $w_opts, $args );
		}

		// Save the entire widgets array back to the db
		return update_option( 'dashboard_widget_options', $opts );

	}

}

add_action( 'plugins_loaded', array( 'Date_Range_Filter', 'setup' ) );
