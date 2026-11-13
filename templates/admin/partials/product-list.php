<?php
/**
 * The Dashboard's list region: the products card (table and pager) or one of the two empty states. Task 13D
 * renders this same partial for the REST response, so the page and the reload-free list share one template.
 *
 * The card carries the list's numbers for the script: data-total (products behind the list, for "Select All")
 * and data-range (what the pager says, announced after a load).
 *
 * @package Affibloom
 * @var array{products: \Affibloom\Domain\Product\ProductPage, term: string} $view
 */

defined( 'ABSPATH' ) || exit;

$products  = $view['products'];
$searching = '' !== $view['term'];
$columns   = array(
	// [ class, label, a placeholder until its task ].
	array( 'affibloom-col-image', __( 'Image', 'affibloom' ), false ),
	array( 'affibloom-col-name', __( 'Link Name', 'affibloom' ), false ),
	array( 'affibloom-col-groups', __( 'Groups', 'affibloom' ), true ),
	array( 'affibloom-col-alert', __( 'Alert', 'affibloom' ), true ),
	array( 'affibloom-col-locations', __( 'Locations', 'affibloom' ), true ),
	array( 'affibloom-col-clicks', __( 'Clicks', 'affibloom' ), true ),
	array( 'affibloom-col-actions', __( 'Actions', 'affibloom' ), false ),
);
?>
<?php if ( 0 === $products->total ) : ?>
	<div class="affibloom-card affibloom-empty" data-total="0">
		<span class="affibloom-chip">
			<?php $this->render( 'admin/partials/icon', array( 'name' => $searching ? 'info' : 'plus' ) ); ?>
		</span>
		<p class="affibloom-empty__message">
			<?php
			echo $searching
				? esc_html__( 'No products match your search.', 'affibloom' )
				: esc_html__( 'No products yet.', 'affibloom' );
			?>
		</p>
	</div>
<?php else : ?>
	<div class="affibloom-card affibloom-card--table" data-total="<?php echo esc_attr( (string) $products->total ); ?>">
		<div class="affibloom-table-scroll">
		<table class="affibloom-table">
			<thead>
				<tr>
					<th scope="col" class="affibloom-col-select">
						<input type="checkbox" class="affibloom-check" data-select-page aria-label="<?php esc_attr_e( 'Select all on this page', 'affibloom' ); ?>">
					</th>
					<?php foreach ( $columns as $column ) : ?>
						<th scope="col" class="<?php echo esc_attr( $column[0] ); ?>">
							<span class="affibloom-th"><?php echo esc_html( $column[1] ); ?>
							<?php
							if ( $column[2] ) {
								$this->render( 'admin/partials/soon', array( 'variant' => 'chip' ) );
							}
							?>
							</span>
						</th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $products->items as $product ) : ?>
					<?php $this->render( 'admin/partials/product-row', array( 'product' => $product ) ); ?>
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
			)
		);
		?>
	</div>
<?php endif; ?>
