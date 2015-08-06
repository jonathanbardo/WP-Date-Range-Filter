<?php

// List ALL registered post types
$post_types = get_post_types( array(), 'objects' );
unset( $post_types['revision'], $post_types['attachment'], $post_types['nav_menu_item'] );

// Get saved post types
$drf_post_types = Date_Range_Filter::get_dashboard_widget_option( Date_Range_Filter::dashboard_widget_id, 'drf_post_types' );

// Loading values for using at add_daterange_select() function
$_GET['date_predefined'] = Date_Range_Filter::get_dashboard_widget_option( Date_Range_Filter::dashboard_widget_id, 'drf_date_predefined' );
$_GET['date_from']       = date( 'Y/m/d', strtotime( Date_Range_Filter::get_dashboard_widget_option( Date_Range_Filter::dashboard_widget_id, 'drf_date_from' ) ) );
$_GET['date_to']         = date( 'Y/m/d', strtotime( Date_Range_Filter::get_dashboard_widget_option( Date_Range_Filter::dashboard_widget_id, 'drf_date_to' ) ) );

?>
<div class="date-range-filter-config-label">
	<p><?php _e( 'Choose Post Types:', 'date-range-filter' ); ?></p>
</div>

<div class="date-range-filter-config-table">
	<table class="wp-list-table widefat striped tags">
	<?php foreach ( $post_types as $row ): ?>
		<tr>
			<td class="manage-column">
				<input type="checkbox" name="drf_post_types[]" value="<?php echo $row->name; ?>" <?php checked( in_array( $row->name, $drf_post_types ) ) ?>/> <?php echo $row->label; ?>
			</td>
		</tr>
	<?php endforeach; ?>
	</table>
</div>

<div class="date-range-filter-config-label">
	<p><?php _e( 'Choose Date Range:', 'date-range-filter' ); ?></p>
</div>

<div class="date-range-filter-config-select">
	<?php Date_Range_Filter::add_daterange_select(); ?>
</div>

<input type="hidden" name="drf_save_config" value="1" />
