<?php
/**
 * A group shown as a grid (F30 R16; Lasso's grid.php under Affibloom's names): the wrapper; customised settings put
 * every card in one row (only when there are cards), classic ones in rows of N (`affibloom-grid-<N>`); then the grid's
 * disclosure (R17), which shows for an empty group too.
 *
 * @package Affibloom
 * @var \Affibloom\Application\Box\GridView $grid
 * @var \Closure(\Affibloom\Application\Box\BoxView): string $card Prints one card.
 */

defined( 'ABSPATH' ) || exit;

$attributes = $grid->wrapper;
$grid_rows  = null === $grid->per_row ? array( $grid->cards ) : array_chunk( $grid->cards, $grid->per_row );
$grid_class = null === $grid->per_row ? 'affibloom-grid-row' : 'affibloom-grid-row affibloom-grid-' . $grid->per_row;
?>
<div id="<?php echo esc_attr( $grid->anchor ); ?>" class="<?php echo esc_attr( $grid->classes ); ?>"<?php require __DIR__ . '/partials/attributes.php'; ?>>
	<?php if ( array() !== $grid->cards ) : ?>
		<?php foreach ( $grid_rows as $grid_row ) : ?>
			<div class="<?php echo esc_attr( $grid_class ); ?>">
				<?php
				foreach ( $grid_row as $grid_card ) {
					echo $card( $grid_card ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the box templates escape every value.
				}
				?>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>
	<?php if ( null !== $grid->disclosure ) : ?>
		<div class="affibloom-disclosure"><?php echo esc_html( $grid->disclosure ); ?></div>
	<?php endif; ?>
</div>
