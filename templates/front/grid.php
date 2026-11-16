<?php
/**
 * A group shown as a grid (F30 R16; Lasso's grid.php under Affibloom's names): the wrapper, one row holding every card
 * (only when there are cards), then the grid's disclosure (R17), which shows for an empty group too.
 *
 * @package Affibloom
 * @var \Affibloom\Application\Box\GridView $grid
 * @var \Closure(\Affibloom\Application\Box\BoxView): string $card Prints one card.
 */

defined( 'ABSPATH' ) || exit;

$attributes = $grid->wrapper;
?>
<div id="<?php echo esc_attr( $grid->anchor ); ?>" class="<?php echo esc_attr( $grid->classes ); ?>"<?php require __DIR__ . '/partials/attributes.php'; ?>>
	<?php if ( array() !== $grid->cards ) : ?>
		<div class="affibloom-grid-row">
			<?php
			foreach ( $grid->cards as $grid_card ) {
				echo $card( $grid_card ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the box templates escape every value.
			}
			?>
		</div>
	<?php endif; ?>
	<?php if ( null !== $grid->disclosure ) : ?>
		<div class="affibloom-disclosure"><?php echo esc_html( $grid->disclosure ); ?></div>
	<?php endif; ?>
</div>
