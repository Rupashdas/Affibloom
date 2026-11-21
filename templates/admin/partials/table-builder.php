<?php
/**
 * The table builder's region (F25 R16, R21, R34, D5; Tasks T3-T4; Lasso's table-details.php 105-157 and
 * table-comparision.php): the actions (Add Product, Add Group, Add Row for a Vertical table or Add Column for a
 * Horizontal one, Preview Table, each live with its task), then the table in its style's layout with the theme's class,
 * or Lasso's empty state. Vertical: a column per product, its header cell on top and a row per field group; Horizontal:
 * a row per product, its header cell first and a column per group. Widths split evenly up to five; past five the grid
 * scrolls. Every write answers with this partial, so the builder never drifts from the store. Task T6: Add Row / Add
 * Column, "Click to add a Field" in an empty one, Add field (+) in every cell, Vertical's "Remove same field →". Task
 * T9: a grip and Move buttons on each product's header cell and, in the first product's cells, on each row or column.
 *
 * @package Affibloom
 * @var array{table: \Affibloom\Domain\Table\Table, fields: array<int, \Affibloom\Domain\Fields\Field>, names: array<int, string>, amazon: array<int, string>} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Table\TableStyle;

$table      = $view['table'];
$horizontal = TableStyle::Row === $table->style;
$heading    = $table->groups[0]['fields'] ?? array();
$body       = array_slice( $table->groups, 1 );
$buttons    = array(
	array( 'add-product', __( 'Add Product', 'affibloom' ), 'plus', true ),
	array( 'add-group', __( 'Add Group', 'affibloom' ), 'layers', true ),
	array( 'add-line', $horizontal ? __( 'Add Column', 'affibloom' ) : __( 'Add Row', 'affibloom' ), 'plus', true ),
);
$columns    = $horizontal ? 1 + count( $body ) : count( $table->products );
$last       = count( $table->products ) - 1;
$group_at   = array_flip( array_column( $table->groups, 'id' ) );
// Lasso's order (R34): a Vertical table reads across (each group a row of every product's cells); a Horizontal table
// reads down (each product a row of its header cell and its groups).
$layout = array();
if ( $horizontal ) {
	foreach ( $table->products as $product ) {
		$layout[] = array( 'head', $product, null );
		foreach ( $body as $group ) {
			$layout[] = array( 'cell', $product, $group );
		}
	}
} else {
	foreach ( $table->products as $product ) {
		$layout[] = array( 'head', $product, null );
	}
	foreach ( $body as $group ) {
		foreach ( $table->products as $product ) {
			$layout[] = array( 'cell', $product, $group );
		}
	}
}
?>
<div class="affibloom-table-builder affibloom-table-builder--<?php echo esc_attr( $table->style->value ); ?> affibloom-table-theme--<?php echo esc_attr( strtolower( $table->theme ) ); ?>"
	data-table-style="<?php echo esc_attr( $table->style->value ); ?>">
	<div class="affibloom-table-builder__actions">
		<?php foreach ( $buttons as $button ) : ?>
			<?php if ( $button[3] ) : ?>
				<button type="button" class="affibloom-button affibloom-button--secondary" data-table-action="<?php echo esc_attr( $button[0] ); ?>" aria-haspopup="dialog"<?php echo null === $table->id ? ' hidden' : ''; ?>>
			<?php else : ?>
				<button type="button" class="affibloom-button affibloom-button--secondary" data-table-action="<?php echo esc_attr( $button[0] ); ?>" disabled title="<?php esc_attr_e( 'Coming soon', 'affibloom' ); ?>"<?php echo null === $table->id ? ' hidden' : ''; ?>>
			<?php endif; ?>
				<?php $this->render( 'admin/partials/icon', array( 'name' => $button[2] ) ); ?>
				<span><?php echo esc_html( $button[1] ); ?></span>
			</button>
		<?php endforeach; ?>
		<button type="button" class="affibloom-button affibloom-button--secondary affibloom-table-builder__preview" data-table-action="preview" disabled title="<?php esc_attr_e( 'Coming soon', 'affibloom' ); ?>"<?php echo null === $table->id ? ' hidden' : ''; ?>>
			<?php $this->render( 'admin/partials/icon', array( 'name' => 'eye' ) ); ?>
			<span><?php esc_html_e( 'Preview Table', 'affibloom' ); ?></span>
		</button>
	</div>
	<?php if ( array() === $table->products ) : ?>
		<div class="affibloom-card affibloom-empty">
			<span class="affibloom-chip"><?php $this->render( 'admin/partials/icon', array( 'name' => $horizontal ? 'table-rows' : 'table-columns' ) ); ?></span>
			<p class="affibloom-empty__message"><?php esc_html_e( 'Add a Product or Group to get started.', 'affibloom' ); ?></p>
		</div>
	<?php else : ?>
		<div class="affibloom-table-grid-scroll">
			<div class="affibloom-table-grid<?php echo $columns > 5 ? ' affibloom-table-grid--wide' : ''; ?>" style="--affibloom-table-columns: <?php echo (int) $columns; ?>">
				<?php
				foreach ( $layout as [ $kind, $product, $group ] ) :
					$name = $view['names'][ $product->id ] ?? '';
					$at   = (int) array_search( $product, $table->products, true );
					if ( 'head' === $kind ) :
						?>
						<div class="affibloom-table-cell affibloom-table-cell--head" data-table-product="<?php echo esc_attr( (string) $product->id ); ?>" data-table-group="<?php echo esc_attr( (string) ( $table->groups[0]['id'] ?? '' ) ); ?>" data-product-name="<?php echo esc_attr( $name ); ?>">
							<div class="affibloom-table-cell__tools">
								<?php
								$this->render(
									'admin/partials/table-move',
									array(
										'kind'     => 'product',
										'label'    => $name,
										'previous' => $horizontal ? __( 'Move product up', 'affibloom' ) : __( 'Move product left', 'affibloom' ),
										'next'     => $horizontal ? __( 'Move product down', 'affibloom' ) : __( 'Move product right', 'affibloom' ),
										'first'    => 0 === $at,
										'last'     => $last === $at,
									)
								);
								?>
								<button type="button" class="affibloom-icon-button" data-table-add-field aria-haspopup="dialog" title="<?php esc_attr_e( 'Add field', 'affibloom' ); ?>" aria-label="<?php esc_attr_e( 'Add field', 'affibloom' ); ?>">
									<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
								</button>
								<?php /* translators: %s: the product's name. */ ?>
								<button type="button" class="affibloom-icon-button affibloom-icon-button--danger" data-table-remove title="<?php esc_attr_e( 'Remove this product', 'affibloom' ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Remove "%s"', 'affibloom' ), $name ) ); ?>">
									<?php $this->render( 'admin/partials/icon', array( 'name' => 'trash' ) ); ?>
								</button>
							</div>
							<label class="screen-reader-text" for="affibloom-table-badge-<?php echo esc_attr( (string) $product->id ); ?>"><?php esc_html_e( 'Badge', 'affibloom' ); ?></label>
							<input type="text" id="affibloom-table-badge-<?php echo esc_attr( (string) $product->id ); ?>" class="affibloom-input affibloom-table-cell__badge" data-table-badge
								value="<?php echo esc_attr( $product->badge ); ?>" data-saved="<?php echo esc_attr( $product->badge ); ?>" placeholder="<?php esc_attr_e( 'Badge', 'affibloom' ); ?>" autocomplete="off">
							<?php
							$this->render(
								'admin/partials/table-cell',
								array(
									'fields'  => $view['fields'],
									'order'   => $heading,
									'values'  => $table->values[ $product->id ] ?? array(),
									'product' => $product->id,
									'amazon'  => $view['amazon'][ $product->id ] ?? null,
									'group'   => 0,
									'groups'  => count( $table->groups ),
									'across'  => $horizontal,
								)
							);
							?>
						</div>
					<?php else : ?>
						<div class="affibloom-table-cell" data-table-product="<?php echo esc_attr( (string) $product->id ); ?>" data-table-group="<?php echo esc_attr( (string) $group['id'] ); ?>">
							<?php if ( 0 === $at ) : ?>
								<div class="affibloom-table-cell__group">
									<?php
									// R37: the row (Vertical) or column (Horizontal) is moved from the first product's cell.
									$this->render(
										'admin/partials/table-move',
										array(
											'kind'     => 'group',
											'label'    => $horizontal ? __( 'column', 'affibloom' ) : __( 'row', 'affibloom' ),
											'previous' => $horizontal ? __( 'Move column left', 'affibloom' ) : __( 'Move row up', 'affibloom' ),
											'next'     => $horizontal ? __( 'Move column right', 'affibloom' ) : __( 'Move row down', 'affibloom' ),
											'first'    => 1 === $group_at[ $group['id'] ],
											'last'     => count( $table->groups ) - 1 === $group_at[ $group['id'] ],
										)
									);
									?>
								</div>
							<?php endif; ?>
							<?php if ( array() === $group['fields'] ) : ?>
								<?php // R25: an empty row or column, every cell offering its first field. ?>
								<button type="button" class="affibloom-table-cell__empty" data-table-add-field aria-haspopup="dialog"><?php esc_html_e( 'Click to add a Field', 'affibloom' ); ?></button>
							<?php else : ?>
								<?php
								$this->render(
									'admin/partials/table-cell',
									array(
										'fields'  => $view['fields'],
										'order'   => $group['fields'],
										'values'  => $table->values[ $product->id ] ?? array(),
										'product' => $product->id,
										'amazon'  => $view['amazon'][ $product->id ] ?? null,
										'group'   => $group_at[ $group['id'] ],
										'groups'  => count( $table->groups ),
										'across'  => $horizontal,
									)
								);
								?>
								<div class="affibloom-table-cell__foot">
									<button type="button" class="affibloom-icon-button" data-table-add-field aria-haspopup="dialog" title="<?php esc_attr_e( 'Add field', 'affibloom' ); ?>" aria-label="<?php esc_attr_e( 'Add field', 'affibloom' ); ?>">
										<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
									</button>
									<?php if ( ! $horizontal && $product === $table->products[0] ) : ?>
										<?php // R33: Vertical's "Remove same field →" on the row's first cell takes the whole row out. ?>
										<button type="button" class="affibloom-table-cell__remove-row" data-table-remove-row>
											<?php $this->render( 'admin/partials/icon', array( 'name' => 'trash' ) ); ?>
											<span><?php esc_html_e( 'Remove same field →', 'affibloom' ); ?></span>
										</button>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>
						<?php
					endif;
				endforeach;
				?>
			</div>
		</div>
	<?php endif; ?>
</div>
