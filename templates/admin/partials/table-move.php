<?php
/**
 * A builder item's grip and Move buttons (F25 R36-R39, D9; Task T9): a product, a row or column, or a field is dragged
 * by its grip (pointer events) or moved one place with the buttons (the keyboard way). The buttons at an edge are
 * disabled. table-builder.js does the move.
 *
 * @package Affibloom
 * @var array{kind: string, label: string, previous: string, next: string, first: bool, last: bool} $view
 */

defined( 'ABSPATH' ) || exit;
?>
<span class="affibloom-table-move" data-table-move="<?php echo esc_attr( $view['kind'] ); ?>">
	<?php /* translators: %s: what is dragged (a product, a row, a column, a field). */ ?>
	<button type="button" class="affibloom-grip affibloom-table-move__grip" data-table-drag="<?php echo esc_attr( $view['kind'] ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Drag %s', 'affibloom' ), $view['label'] ) ); ?>" tabindex="-1">
		<?php $this->render( 'admin/partials/icon', array( 'name' => 'grip' ) ); ?>
	</button>
	<button type="button" class="affibloom-table-move__step" data-table-step="-1" aria-label="<?php echo esc_attr( $view['previous'] ); ?>" title="<?php echo esc_attr( $view['previous'] ); ?>"<?php disabled( $view['first'] ); ?>>
		<?php $this->render( 'admin/partials/icon', array( 'name' => 'chevron-up' ) ); ?>
	</button>
	<button type="button" class="affibloom-table-move__step" data-table-step="1" aria-label="<?php echo esc_attr( $view['next'] ); ?>" title="<?php echo esc_attr( $view['next'] ); ?>"<?php disabled( $view['last'] ); ?>>
		<?php $this->render( 'admin/partials/icon', array( 'name' => 'chevron-down' ) ); ?>
	</button>
</span>
