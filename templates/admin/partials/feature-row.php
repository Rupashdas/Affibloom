<?php
/**
 * One bullet of the product page's Features list (C1Q4): the grip it is dragged by (Arrow Up / Down from the keyboard;
 * the user, 2026-11-16: drag and drop, as Lasso's rows), its text and Remove. The labels carry the bullet's number
 * ("Feature 2", "Reorder Feature 2"); product.js renumbers them after every change.
 *
 * @package Affibloom
 * @var array{text: string, number: int} $view
 */

defined( 'ABSPATH' ) || exit;

$number = $view['number'];
?>
<li class="affibloom-features__item" data-feature>
	<?php /* translators: %d: the bullet's place in the list, from 1. */ ?>
	<button type="button" class="affibloom-grip" data-grip aria-label="<?php echo esc_attr( sprintf( __( 'Reorder Feature %d', 'affibloom' ), $number ) ); ?>" title="<?php esc_attr_e( 'Drag to reorder, or press Arrow Up or Arrow Down', 'affibloom' ); ?>">
		<?php $this->render( 'admin/partials/icon', array( 'name' => 'grip' ) ); ?>
	</button>
	<?php /* translators: %d: the bullet's place in the list, from 1. */ ?>
	<input type="text" class="affibloom-input" value="<?php echo esc_attr( $view['text'] ); ?>" data-feature-text aria-label="<?php echo esc_attr( sprintf( __( 'Feature %d', 'affibloom' ), $number ) ); ?>">
	<?php /* translators: %d: the bullet's place in the list, from 1. */ ?>
	<button type="button" class="affibloom-icon-button affibloom-icon-button--danger" data-feature-remove aria-label="<?php echo esc_attr( sprintf( __( 'Remove Feature %d', 'affibloom' ), $number ) ); ?>" title="<?php esc_attr_e( 'Remove', 'affibloom' ); ?>">
		<?php $this->render( 'admin/partials/icon', array( 'name' => 'trash' ) ); ?>
	</button>
</li>
