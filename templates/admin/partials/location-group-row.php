<?php
/**
 * A post with several places on the Locations tab (F12 L6; Lasso's link-display-post-group-row.php; Task 36B): the
 * toggle, the post's title and status, "(N locations)" and its permalink; closed until the toggle opens the rows
 * under it (product-locations.js).
 *
 * @package Affibloom
 * @var array{host: \Affibloom\Domain\LinkIndex\LocationHost} $view
 */

defined( 'ABSPATH' ) || exit;

$host = $view['host'];
?>
<tr class="affibloom-location-group" data-location-host="<?php echo esc_attr( (string) $host->id ); ?>" title="<?php esc_attr_e( 'Expand to view each location', 'affibloom' ); ?>">
	<td class="affibloom-col-content" colspan="4">
		<div class="affibloom-location__group">
			<button type="button" class="affibloom-location__toggle" aria-expanded="false" data-location-toggle="<?php echo esc_attr( (string) $host->id ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'chevron-down' ) ); ?>
				<span class="screen-reader-text"><?php esc_html_e( 'Toggle locations for this post', 'affibloom' ); ?></span>
			</button>
			<div>
				<?php
				$this->render(
					'admin/partials/location-host',
					array(
						'host'   => $host,
						'places' => count( $host->places ),
					)
				);
				?>
			</div>
		</div>
	</td>
</tr>
