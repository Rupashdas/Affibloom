<?php
/**
 * The table builder's canvas (F25 R16, R21, R25, R33, R34, D5; Tasks T3-T9; Lasso's table-comparision.php; deviation
 * 151). Every write answers with this partial, so the canvas never drifts from the store.
 *
 * A grid whose every cell is placed by row and column (inline `grid-row` / `grid-column`), so table-builder-drag.js
 * can move a whole product or a whole row (column) under the pointer by placing its cells again:
 * - Vertical: a gutter column of row handles, then a column per product; the first row holds the products' header
 *   cells, then a row per field group.
 * - Horizontal: a row of column handles, then a row per product; the first column holds the header cells, then a
 *   column per field group.
 * A header cell holds the product's grip, its name (which selects it), Add field and Remove, its badge (drawn with its
 * icon) and the heading group's fields; a cell holds its group's field cards (table-cell.php) or, in an empty group,
 * "Click to add a Field". Without products, Lasso's empty state with its two ways to start.
 *
 * @package Affibloom
 * @var array{table: \Affibloom\Domain\Table\Table, fields: array<int, \Affibloom\Domain\Fields\Field>, names: array<int, string>, amazon: array<int, string>, icons?: array<string, string>} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Icons\IconText;
use Affibloom\Domain\Table\TableStyle;

$table      = $view['table'];
$horizontal = TableStyle::Row === $table->style;
$icons      = $view['icons'] ?? array();
$groups     = $table->groups;
$body       = array_slice( $groups, 1 );
$count      = count( $table->products );
$tracks     = $horizontal ? count( $body ) : $count;
$names_of   = static function ( array $group ) use ( $view ): string {
	$named = array();
	foreach ( $group['fields'] as $field_id ) {
		if ( isset( $view['fields'][ $field_id ] ) ) {
			$named[] = $view['fields'][ $field_id ]->name;
		}
	}
	return implode( ', ', $named );
};
// A cell's place: Vertical puts products across and groups down, after the gutter; Horizontal the other way.
$place = static function ( int $product_at, int $group_at ) use ( $horizontal ): string {
	$row    = $horizontal ? $product_at + 2 : $group_at + 1;
	$column = $horizontal ? $group_at + 1 : $product_at + 2;
	return 'grid-row: ' . $row . '; grid-column: ' . $column . ';';
};
?>
<div class="affibloom-table-builder affibloom-table-builder--<?php echo esc_attr( $table->style->value ); ?> affibloom-table-theme--<?php echo esc_attr( strtolower( $table->theme ) ); ?>"
	data-table-style="<?php echo esc_attr( $table->style->value ); ?>">
	<?php if ( 0 === $count ) : ?>
		<div class="affibloom-card affibloom-empty affibloom-table-empty">
			<span class="affibloom-chip"><?php $this->render( 'admin/partials/icon', array( 'name' => $horizontal ? 'table-rows' : 'table-columns' ) ); ?></span>
			<p class="affibloom-empty__message"><?php esc_html_e( 'Add a Product or Group to get started.', 'affibloom' ); ?></p>
			<?php if ( null !== $table->id ) : ?>
				<p class="affibloom-table-empty__actions">
					<button type="button" class="affibloom-button affibloom-button--primary" data-table-action="add-product" aria-haspopup="dialog">
						<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
						<span><?php esc_html_e( 'Add Product', 'affibloom' ); ?></span>
					</button>
					<button type="button" class="affibloom-button affibloom-button--secondary" data-table-action="add-group" aria-haspopup="dialog">
						<?php $this->render( 'admin/partials/icon', array( 'name' => 'layers' ) ); ?>
						<span><?php esc_html_e( 'Add Group', 'affibloom' ); ?></span>
					</button>
				</p>
			<?php endif; ?>
		</div>
	<?php else : ?>
		<div class="affibloom-table-grid-scroll" data-table-scroll>
			<div class="affibloom-table-grid affibloom-table-grid--<?php echo esc_attr( $table->style->value ); ?><?php echo $tracks > 5 ? ' affibloom-table-grid--wide' : ''; ?>" data-table-grid
				style="--affibloom-table-tracks: <?php echo (int) max( 1, $tracks ); ?>;">
				<div class="affibloom-table-cell affibloom-table-cell--corner" style="grid-row: 1; grid-column: 1;" aria-hidden="true"></div>
				<?php foreach ( $body as $at => $group ) : ?>
					<?php
					$group_at = $at + 1;
					$fields   = $names_of( $group );
					/* translators: %s: the fields of the row or column, e.g. "Product name, Price". */
					$line = $horizontal ? __( 'Column: %s', 'affibloom' ) : __( 'Row: %s', 'affibloom' );
					$what = sprintf( $line, '' !== $fields ? $fields : __( 'empty', 'affibloom' ) );
					?>
					<div class="affibloom-table-cell affibloom-table-cell--gutter" data-table-kind="gutter" data-table-group="<?php echo esc_attr( (string) $group['id'] ); ?>"
						data-group-at="<?php echo (int) $group_at; ?>" data-line-name="<?php echo esc_attr( $what ); ?>"
						style="<?php echo esc_attr( $horizontal ? 'grid-row: 1; grid-column: ' . ( $group_at + 1 ) . ';' : 'grid-row: ' . ( $group_at + 1 ) . '; grid-column: 1;' ); ?>">
						<button type="button" id="affibloom-grip-group-<?php echo esc_attr( (string) $group['id'] ); ?>" class="affibloom-table-grip" data-table-drag="group"
							<?php /* translators: %s: the row or column, e.g. "Row: Price". */ ?>
							aria-label="<?php echo esc_attr( sprintf( __( 'Move %s', 'affibloom' ), $what ) ); ?>" aria-describedby="affibloom-table-drag-help">
							<?php $this->render( 'admin/partials/icon', array( 'name' => 'grip' ) ); ?>
						</button>
						<button type="button" class="affibloom-table-gutter__remove" data-table-remove-row
							<?php /* translators: %s: the row or column. */ ?>
							aria-label="<?php echo esc_attr( sprintf( __( 'Remove %s', 'affibloom' ), $what ) ); ?>" title="<?php echo esc_attr( $horizontal ? __( 'Remove this column', 'affibloom' ) : __( 'Remove this row', 'affibloom' ) ); ?>">
							<?php $this->render( 'admin/partials/icon', array( 'name' => 'trash' ) ); ?>
						</button>
					</div>
				<?php endforeach; ?>
				<?php foreach ( $table->products as $product_at => $product ) : ?>
					<?php
					$name  = $view['names'][ $product->id ] ?? '';
					$badge = IconText::split( $product->badge );
					$cell  = array(
						'fields'  => $view['fields'],
						'values'  => $table->values[ $product->id ] ?? array(),
						'product' => $product->id,
						'amazon'  => $view['amazon'][ $product->id ] ?? null,
						'icons'   => $icons,
					);
					?>
					<div class="affibloom-table-cell affibloom-table-cell--head" data-table-kind="head" data-table-product="<?php echo esc_attr( (string) $product->id ); ?>"
						data-product-at="<?php echo (int) $product_at; ?>" data-table-group="<?php echo esc_attr( (string) ( $groups[0]['id'] ?? '' ) ); ?>" data-group-at="0"
						data-product-name="<?php echo esc_attr( $name ); ?>" data-badge="<?php echo esc_attr( $product->badge ); ?>" style="<?php echo esc_attr( $place( $product_at, 0 ) ); ?>">
						<div class="affibloom-table-cell__bar">
							<button type="button" id="affibloom-grip-product-<?php echo esc_attr( (string) $product->id ); ?>" class="affibloom-table-grip" data-table-drag="product"
								<?php /* translators: %s: the product's name. */ ?>
								aria-label="<?php echo esc_attr( sprintf( __( 'Move %s', 'affibloom' ), $name ) ); ?>" aria-describedby="affibloom-table-drag-help">
								<?php $this->render( 'admin/partials/icon', array( 'name' => 'grip' ) ); ?>
							</button>
							<button type="button" class="affibloom-table-cell__name" data-table-select="product" title="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $name ); ?></button>
							<button type="button" class="affibloom-icon-button" data-table-add-field aria-haspopup="dialog" title="<?php esc_attr_e( 'Add field', 'affibloom' ); ?>" aria-label="<?php esc_attr_e( 'Add field', 'affibloom' ); ?>">
								<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
							</button>
							<button type="button" class="affibloom-icon-button affibloom-icon-button--danger" data-table-remove title="<?php esc_attr_e( 'Remove this product', 'affibloom' ); ?>"
								<?php /* translators: %s: the product's name. */ ?>
								aria-label="<?php echo esc_attr( sprintf( __( 'Remove "%s"', 'affibloom' ), $name ) ); ?>">
								<?php $this->render( 'admin/partials/icon', array( 'name' => 'trash' ) ); ?>
							</button>
						</div>
						<?php // R21: the badge, drawn as readers see it; it opens the inspector's badge editor (icon and words). ?>
						<button type="button" id="affibloom-badge-<?php echo esc_attr( (string) $product->id ); ?>" class="affibloom-table-badge<?php echo '' === $product->badge ? ' affibloom-table-badge--none' : ''; ?>" data-table-badge-edit
							<?php /* translators: %s: the product's name. */ ?>
							aria-label="<?php echo esc_attr( sprintf( __( 'Badge of %s', 'affibloom' ), $name ) ); ?>">
							<?php if ( '' === $product->badge ) : ?>
								<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
								<span><?php esc_html_e( 'Badge', 'affibloom' ); ?></span>
							<?php else : ?>
								<?php
								$this->render(
									'admin/partials/table-icon-text',
									array(
										'text'  => $badge,
										'raw'   => $product->badge,
										'icons' => $icons,
									)
								);
								?>
							<?php endif; ?>
						</button>
						<div class="affibloom-table-cell__fields" data-table-fields>
							<?php $this->render( 'admin/partials/table-cell', $cell + array( 'order' => $groups[0]['fields'] ?? array() ) ); ?>
						</div>
					</div>
					<?php foreach ( $body as $at => $group ) : ?>
						<div class="affibloom-table-cell" data-table-kind="cell" data-table-product="<?php echo esc_attr( (string) $product->id ); ?>" data-product-at="<?php echo (int) $product_at; ?>"
							data-table-group="<?php echo esc_attr( (string) $group['id'] ); ?>" data-group-at="<?php echo (int) ( $at + 1 ); ?>" style="<?php echo esc_attr( $place( $product_at, $at + 1 ) ); ?>">
							<div class="affibloom-table-cell__fields" data-table-fields>
								<?php if ( array() === $group['fields'] ) : ?>
									<?php // R25: an empty row or column, every cell offering its first field. ?>
									<button type="button" class="affibloom-table-cell__empty" data-table-add-field aria-haspopup="dialog"><?php esc_html_e( 'Click to add a Field', 'affibloom' ); ?></button>
								<?php else : ?>
									<?php $this->render( 'admin/partials/table-cell', $cell + array( 'order' => $group['fields'] ) ); ?>
								<?php endif; ?>
							</div>
							<?php if ( array() !== $group['fields'] ) : ?>
								<button type="button" class="affibloom-table-cell__add" data-table-add-field aria-haspopup="dialog">
									<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
									<span><?php esc_html_e( 'Field', 'affibloom' ); ?></span>
								</button>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
</div>
