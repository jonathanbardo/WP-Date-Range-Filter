<?php
$articles_per_date = 0;
?>
<table class="wp-list-table drf-dashboard-table widefat striped tags">
	<?php foreach ( $output as $row ): ?>
		<tr>
			<td class="manage-column">
				<span class="dashicons dashicons-arrow-right"></span>
				<?php echo $row['type_label']; ?>
				<?php
				$children = array_map( function( $child ) {
					return sprintf( '<li>%s (%s)</li>', $child->status_name, $child->status_count );
				}, $row['status'] );
				?>

				<?php if ( ! empty( $children ) ) : ?>
					<ul>
						<?php echo implode( PHP_EOL, $children ); ?>
					</ul>
				<?php endif; ?>
			</td>
			<td class="manage-column total-count"><a href="<?php echo esc_url( $row['url'] ); ?>"><?php echo $row['count']; ?></a></td>
		</tr>
		<?php $articles_per_date += (int) $row['count']; ?>
	<?php endforeach; ?>
	<tr>
		<td class="manage-column total-row">Total</td>
		<td class="manage-column total-row total-count"><?php echo $articles_per_date; ?></td>
	</tr>
</table>
