<?php
/**
 * The product's feature bullets, inside the description block (the product's Show Features): the description's own
 * list rules in box.css draw them. Nothing when there are none.
 *
 * @package Affibloom
 * @var \Affibloom\Application\Box\BoxView $view
 */

defined( 'ABSPATH' ) || exit;

if ( array() === $view->features ) {
	return;
}
?>
<ul class="affibloom-feature-list">
	<?php foreach ( $view->features as $feature ) : ?>
		<li><?php echo esc_html( $feature ); ?></li>
	<?php endforeach; ?>
</ul>
