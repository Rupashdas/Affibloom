<?php
/**
 * One bullet of the product page's Features list (C1Q4): its text, Move up, Move down and Remove. The labels carry the
 * bullet's number ("Feature 2", "Move Feature 2 up"); product.js renumbers them after every change.
 *
 * @package Affibloom
 * @var array{text: string, number: int} $view
 */

defined( 'ABSPATH' ) || exit;

$number = $view['number'];
?>
<li class="affibloom-features__item" data-feature>
	<?php /* translators: %d: the bullet's place in the list, from 1. */ ?>
	<input type="text" class="affibloom-input" value="<?php echo esc_attr( $view['text'] ); ?>" data-feature-text aria-label="<?php echo esc_attr( sprintf( __( 'Feature %d', 'affibloom' ), $number ) ); ?>">
	<?php /* translators: %d: the bullet's place in the list, from 1. */ ?>
	<button type="button" class="affibloom-icon-button" data-feature-move="-1" aria-label="<?php echo esc_attr( sprintf( __( 'Move Feature %d up', 'affibloom' ), $number ) ); ?>" title="<?php esc_attr_e( 'Move up', 'affibloom' ); ?>">
		<?php $this->render( 'admin/partials/icon', array( 'name' => 'chevron-up' ) ); ?>
	</button>
	<?php /* translators: %d: the bullet's place in the list, from 1. */ ?>
	<button type="button" class="affibloom-icon-button" data-feature-move="1" aria-label="<?php echo esc_attr( sprintf( __( 'Move Feature %d down', 'affibloom' ), $number ) ); ?>" title="<?php esc_attr_e( 'Move down', 'affibloom' ); ?>">
		<?php $this->render( 'admin/partials/icon', array( 'name' => 'chevron-down' ) ); ?>
	</button>
	<?php /* translators: %d: the bullet's place in the list, from 1. */ ?>
	<button type="button" class="affibloom-icon-button affibloom-icon-button--danger" data-feature-remove aria-label="<?php echo esc_attr( sprintf( __( 'Remove Feature %d', 'affibloom' ), $number ) ); ?>" title="<?php esc_attr_e( 'Remove', 'affibloom' ); ?>">
		<?php $this->render( 'admin/partials/icon', array( 'name' => 'trash' ) ); ?>
	</button>
</li>
