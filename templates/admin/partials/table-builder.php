<?php
/**
 * The table builder's region (F25 R16, R34, D5; Task T3; Lasso's table-details.php 105-157 and table-comparision.php):
 * the actions (Add Product, Add Group, Add Row for a Vertical table or Add Column for a Horizontal one, Preview Table,
 * each live with its task), then the table in its style's layout with the theme's class, or Lasso's empty state. Every
 * write answers with this partial, so the builder never drifts from the store.
 *
 * @package Affibloom
 * @var array{table: \Affibloom\Domain\Table\Table} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Table\TableStyle;

$table      = $view['table'];
$horizontal = TableStyle::Row === $table->style;
$buttons    = array(
	array( 'add-product', __( 'Add Product', 'affibloom' ), 'plus' ),
	array( 'add-group', __( 'Add Group', 'affibloom' ), 'layers' ),
	array( 'add-line', $horizontal ? __( 'Add Column', 'affibloom' ) : __( 'Add Row', 'affibloom' ), 'plus' ),
);
?>
<div class="affibloom-table-builder affibloom-table-builder--<?php echo esc_attr( $table->style->value ); ?> affibloom-table-theme--<?php echo esc_attr( strtolower( $table->theme ) ); ?>"
	data-table-style="<?php echo esc_attr( $table->style->value ); ?>">
	<div class="affibloom-table-builder__actions">
		<?php foreach ( $buttons as $button ) : ?>
			<button type="button" class="affibloom-button affibloom-button--secondary" data-table-action="<?php echo esc_attr( $button[0] ); ?>" disabled title="<?php esc_attr_e( 'Coming soon', 'affibloom' ); ?>"<?php echo null === $table->id ? ' hidden' : ''; ?>>
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
	<?php endif; ?>
</div>
