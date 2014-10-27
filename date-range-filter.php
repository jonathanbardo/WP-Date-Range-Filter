<?php
/**
 * Plugin Name: Date range filter
 * Description: Easily filter the admin list of post and custom post type with a date range
 * Version: 0.0.2
 * Author: Jonathan Bardo
 * License: GPLv2+
 * Text Domain: date-range-filter
 * Domain Path: /languages
 * Author URI: http://jonathanbardo.com
 */

class Date_Range_Filter {
	/**
	 * Holds the plugin version number
	 */
	const VERSION = '0.0.2';

	/**
	 * Contain the called class name
	 *
	 * @var string
	 */
	protected static $class;
	
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
	}

	/**
	 * Add daterange select
	 */
	public static function add_daterange_select() {
		// Carbon is great to handle dates
		if ( ! class_exists( 'Carbon\Carbon' ) ) {
			require_once DATE_RANGE_FILTER_INC_DIR . 'vendor/Carbon.php';
		}

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
				<option><?php _e( 'Show All Time', 'date-range-filter' ); ?></option>
				<option value="custom" <?php selected( 'custom' === $date_predefined ); ?>><?php esc_attr_e( 'Custom Date Range', 'date-range-filter' ) ?></option>
				<?php foreach ( $intervals as $key => $interval ) {
					$interval = $interval;
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
						   class="date-picker field-from"
						   placeholder="<?php esc_attr_e( 'Start Date', 'default' ) ?>"
						   value="<?php echo esc_attr( $date_from ) ?>">
				</div>
				<span class="connector dashicons"></span>

				<div class="box">
					<i class="date-remove dashicons"></i>
					<input type="text"
						   name="date_to"
						   class="date-picker field-to"
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
	private static function get_predefined_intervals() {
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
					'end'   => Carbon\Carbon::today( $timezone )->startOfDay(),
				),
				'yesterday' => array(
					'label' => esc_html__( 'Yesterday', 'date-range-filter' ),
					'start' => Carbon\Carbon::today( $timezone )->startOfDay()->subDay(),
					'end'   => Carbon\Carbon::today( $timezone )->startOfDay()->subSecond(),
				),
				'last-7-days' => array(
					'label' => sprintf( esc_html__( 'Last %d Days', 'date-range-filter' ), 7 ),
					'start' => Carbon\Carbon::today( $timezone )->subDays( 7 ),
					'end'   => Carbon\Carbon::today( $timezone ),
				),
				'last-14-days' => array(
					'label' => sprintf( esc_html__( 'Last %d Days', 'date-range-filter' ), 14 ),
					'start' => Carbon\Carbon::today( $timezone )->subDays( 14 ),
					'end'   => Carbon\Carbon::today( $timezone ),
				),
				'last-30-days' => array(
					'label' => sprintf( esc_html__( 'Last %d Days', 'date-range-filter' ), 30 ),
					'start' => Carbon\Carbon::today( $timezone )->subDays( 30 ),
					'end'   => Carbon\Carbon::today( $timezone ),
				),
				'this-month' => array(
					'label' => esc_html__( 'This Month', 'date-range-filter' ),
					'start' => Carbon\Carbon::today( $timezone )->day( 1 ),
					'end'   => Carbon\Carbon::today( $timezone )->startOfDay(),
				),
				'last-month' => array(
					'label' => esc_html__( 'Last Month', 'date-range-filter' ),
					'start' => Carbon\Carbon::today( $timezone )->day( 1 )->subMonth(),
					'end'   => Carbon\Carbon::today( $timezone )->day( 1 )->subSecond(),
				),
				'last-3-months' => array(
					'label' => sprintf( esc_html__( 'Last %d Months', 'date-range-filter' ), 3 ),
					'start' => Carbon\Carbon::today( $timezone )->subMonths( 3 ),
					'end'   => Carbon\Carbon::today( $timezone ),
				),
				'last-6-months' => array(
					'label' => sprintf( esc_html__( 'Last %d Months', 'date-range-filter' ), 6 ),
					'start' => Carbon\Carbon::today( $timezone )->subMonths( 6 ),
					'end'   => Carbon\Carbon::today( $timezone ),
				),
				'last-12-months' => array(
					'label' => sprintf( esc_html__( 'Last %d Months', 'date-range-filter' ), 12 ),
					'start' => Carbon\Carbon::today( $timezone )->subMonths( 12 ),
					'end'   => Carbon\Carbon::today( $timezone ),
				),
				'this-year' => array(
					'label' => esc_html__( 'This Year', 'date-range-filter' ),
					'start' => Carbon\Carbon::today( $timezone )->day( 1 )->month( 1 ),
					'end'   => Carbon\Carbon::today( $timezone )->startOfDay(),
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

		if ( 'edit.php' !== $pagenow ) {
			return false;
		}

		wp_enqueue_style( 'date-range-filter-datepicker', DATE_RANGE_FILTER_URL . 'css/datepicker.css', array( 'jquery-ui' ), self::VERSION );
		wp_enqueue_style( 'date-range-filter-admin', DATE_RANGE_FILTER_URL . 'css/admin.css', array(), self::VERSION );
		wp_register_style( 'jquery-ui', '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.1/themes/base/jquery-ui.css', array(), '1.10.1' );
		wp_register_script( 'date-range-filter-admin', DATE_RANGE_FILTER_URL . 'js/admin.js', array( 'jquery', ), self::VERSION );
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
			&& 'edit.php' === $pagenow
			&& isset( $_GET['date_from'] ) && ! empty( $_GET['date_from'] )
			&& isset( $_GET['date_to'] ) && ! empty( $_GET['date_to'] )
		) {
			$from = explode( '/', sanitize_text_field( $_GET['date_from'] ) );//input var okay
			$to   = explode( '/', sanitize_text_field( $_GET['date_to'] ) );//input var ok

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
}

add_action( 'plugins_loaded', array( 'Date_Range_Filter', 'setup' ) );
