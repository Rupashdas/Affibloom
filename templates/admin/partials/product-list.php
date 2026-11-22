<?php
/**
 * The Dashboard's list region: the products card (table and pager) or one of the two empty states. Task 13D
 * renders this same partial for the REST response, so the page and the reload-free list share one template.
 *
 * @package Affibloom
 * @var array{products: \Affibloom\Domain\Product\ProductPage, term: string, url: string, args: array<string, string>, groups_of: array<int, list<\Affibloom\Domain\Group\Group>>, links_of?: array<int, string>, alerts_of?: array<int, \Affibloom\Domain\LinkHealth\AlertState>, listing?: \Affibloom\Domain\LinkHealth\AlertListing|null, order?: \Affibloom\Domain\Product\ListOrder, locations: array<int, int>, known: bool, clicks: array<int, int>, opportunities?: array<int, int>|null} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\LinkHealth\AlertListing;
use Affibloom\Domain\Product\ListOrder;

// A `?post_id=` that names no published product is a search that found nothing, not an empty library (Task 13F).
$products  = $view['products'];
$searching = '' !== $view['term'] || isset( $view['args']['post_id'] ) || isset( $view['args']['filter'] );
// The header sorts (F19 R1, R2, D8-D11; Task 13K): Alert by its issue's date (ascending, descending, off), Locations by
// the count and Clicks by the 30-day total (descending, then ascending). Each header is a link to the next state that
// keeps the page (R3), so it works without JavaScript; dashboard.js loads it in place.
$list_order = $view['order'] ?? new ListOrder();
$sort_href  = static function ( ListOrder $next ) use ( $view, $products ): string {
	$args = $next->is_default() ? array() : array(
		'orderby' => $next->column,
		'order'   => $next->direction,
	);
	if ( $products->current() > 1 ) {
		$args['paged'] = $products->current();
	}

	return add_query_arg( $args, remove_query_arg( array( 'orderby', 'order', 'paged' ), $view['url'] ) );
};
$columns    = array(
	// [ class, label, the column it sorts ('' none) ].
	array( 'affibloom-col-image', __( 'Image', 'affibloom' ), '' ),
	array( 'affibloom-col-name', __( 'Link Name', 'affibloom' ), '' ),
	array( 'affibloom-col-groups', __( 'Groups', 'affibloom' ), '' ),
	// On the Opportunities tab Alert sorts by the opportunity count, descending first (F19 R9, F22 R51; Task O8).
	array( 'affibloom-col-alert', __( 'Alert', 'affibloom' ), AlertListing::OPPORTUNITIES === ( isset( $view['listing'] ) ? $view['listing']->tab : '' ) ? ListOrder::OPPORTUNITY_COUNT : ListOrder::ALERT ),
	array( 'affibloom-col-locations', __( 'Locations', 'affibloom' ), ListOrder::LOCATIONS ),
	array( 'affibloom-col-clicks', __( 'Clicks', 'affibloom' ), ListOrder::CLICKS ),
	array( 'affibloom-col-actions', __( 'Actions', 'affibloom' ), '' ),
);
?>
<?php
// Lasso's Export CSV (F11 R28, D18; Task 31D): on the broken and out-of-stock tabs only; disabled with Lasso's title
// when the tab lists nothing. dashboard.js downloads the file and says how many links it holds.
$export_tab = isset( $view['listing'] ) ? $view['listing']->tab : '';
?>
<?php if ( in_array( $export_tab, array( AlertListing::BROKEN, AlertListing::OUT_OF_STOCK ), true ) ) : ?>
	<div class="affibloom-export">
		<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-export-csv
			data-exporting="<?php esc_attr_e( 'Exporting', 'affibloom' ); ?>" data-failed="<?php esc_attr_e( 'Export failed.', 'affibloom' ); ?>"
			<?php /* translators: %s: number of links. */ ?>
			data-exported-one="<?php esc_attr_e( 'Exported 1 link.', 'affibloom' ); ?>" data-exported-many="<?php esc_attr_e( 'Exported %s links.', 'affibloom' ); ?>"
			title="<?php echo esc_attr( 0 === $products->total ? __( 'No links to export for the current filters.', 'affibloom' ) : __( 'Export all filtered links to WP All Import CSV', 'affibloom' ) ); ?>"
			<?php disabled( 0, $products->total ); ?>>
			<?php $this->render( 'admin/partials/icon', array( 'name' => 'download' ) ); ?>
			<span data-export-label><?php esc_html_e( 'Export CSV', 'affibloom' ); ?></span>
		</button>
	</div>
<?php else : ?>
	<?php // Elsewhere, Export CSV is the whole library with every field (Task 38: "Export them first" before an uninstall). ?>
	<div class="affibloom-export">
		<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-export-csv data-export-path="/affibloom/v1/products/export"
			data-exporting="<?php esc_attr_e( 'Exporting', 'affibloom' ); ?>" data-failed="<?php esc_attr_e( 'Export failed.', 'affibloom' ); ?>"
			<?php /* translators: %s: number of products. */ ?>
			data-exported-one="<?php esc_attr_e( 'Exported 1 product.', 'affibloom' ); ?>" data-exported-many="<?php esc_attr_e( 'Exported %s products.', 'affibloom' ); ?>"
			title="<?php esc_attr_e( 'Export every product with every field to CSV', 'affibloom' ); ?>">
			<?php $this->render( 'admin/partials/icon', array( 'name' => 'download' ) ); ?>
			<span data-export-label><?php esc_html_e( 'Export CSV', 'affibloom' ); ?></span>
		</button>
	</div>
<?php endif; ?>
<?php if ( 0 === $products->total ) : ?>
	<div class="affibloom-card affibloom-empty">
		<span class="affibloom-chip">
			<?php $this->render( 'admin/partials/icon', array( 'name' => $searching ? 'info' : 'plus' ) ); ?>
		</span>
		<?php if ( $searching ) : ?>
			<p class="affibloom-empty__message"><?php esc_html_e( 'No products match your search.', 'affibloom' ); ?></p>
		<?php else : ?>
			<?php // Lasso's empty Dashboard (admin/views/education/dashboard.php): its words, and its action, which opens "Add A New Link". ?>
			<p class="affibloom-empty__message"><?php esc_html_e( 'Get Started by Adding a Link', 'affibloom' ); ?></p>
			<p class="affibloom-empty__text"><?php esc_html_e( "Manage all of your affiliate links here, and see where they're located across your site. Get alerts when a link has new opportunities, is broken, or out of stock.", 'affibloom' ); ?></p>
			<button type="button" class="affibloom-button affibloom-button--primary affibloom-empty__action" data-affibloom-open="affibloom-add-modal" aria-haspopup="dialog">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
				<span><?php esc_html_e( 'Add a Link', 'affibloom' ); ?></span>
			</button>
		<?php endif; ?>
	</div>
<?php else : ?>
	<div class="affibloom-card affibloom-card--table">
		<div class="affibloom-table-scroll">
		<table class="affibloom-table">
			<thead>
				<tr>
					<th scope="col" class="affibloom-col-select">
						<input type="checkbox" class="affibloom-check" data-select-page aria-label="<?php esc_attr_e( 'Select all on this page', 'affibloom' ); ?>">
					</th>
					<?php foreach ( $columns as $column ) : ?>
						<?php if ( '' !== $column[2] ) : ?>
							<?php
							$applied = $list_order->direction_of( $column[2] );
							$next    = $list_order->next( $column[2] );
							?>
							<th scope="col" class="<?php echo esc_attr( $column[0] ); ?>" aria-sort="<?php echo esc_attr( '' === $applied ? 'none' : ( ListOrder::ASC === $applied ? 'ascending' : 'descending' ) ); ?>">
								<a class="affibloom-sort" href="<?php echo esc_url( $sort_href( $next ) ); ?>"
									data-sort-orderby="<?php echo esc_attr( $next->column ); ?>" data-sort-order="<?php echo esc_attr( $next->direction ); ?>"
									<?php echo ListOrder::ALERT === $column[2] ? 'title="' . esc_attr__( 'Sort by the date the issue was found', 'affibloom' ) . '"' : ''; ?>>
									<span><?php echo esc_html( $column[1] ); ?></span>
									<?php if ( '' !== $applied ) : ?>
										<?php $this->render( 'admin/partials/icon', array( 'name' => ListOrder::ASC === $applied ? 'chevron-up' : 'chevron-down' ) ); ?>
									<?php endif; ?>
								</a>
							</th>
							<?php continue; ?>
						<?php endif; ?>
						<th scope="col" class="<?php echo esc_attr( $column[0] ); ?>">
							<span class="affibloom-th"><?php echo esc_html( $column[1] ); ?></span>
						</th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $products->items as $product ) : ?>
					<?php
					$this->render(
						'admin/partials/product-row',
						array(
							'product'       => $product,
							'groups'        => $view['groups_of'][ (int) $product->id ] ?? array(),
							'link'          => $view['links_of'][ (int) $product->id ] ?? $product->url->value(),
							'alert'         => $view['alerts_of'][ (int) $product->id ] ?? null,
							'locations'     => $view['locations'][ (int) $product->id ] ?? 0,
							'known'         => $view['known'],
							'clicks'        => $view['clicks'][ (int) $product->id ] ?? 0,
							'opportunities' => $view['opportunities'][ (int) $product->id ] ?? 0,
						)
					);
					?>
				<?php endforeach; ?>
			</tbody>
		</table>
		</div>
		<?php
		$this->render(
			'admin/partials/pager',
			array(
				'products' => $products,
				'term'     => $view['term'],
				'url'      => $view['url'],
				'args'     => $view['args'],
			)
		);
		?>
	</div>
<?php endif; ?>
