<?php
/**
 * The price timestamp (F5 R29): always printed; when it shows, the last update and the info icon with Lasso's tooltip
 * (a CSS tooltip on `[data-tooltip]`).
 *
 * @package Affibloom
 * @var \Affibloom\Application\Box\BoxView $view
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="affibloom-date">
	<?php if ( null !== $view->date ) : ?>
		<?php echo esc_html( $view->date ); ?> <i class="affibloom-amazon-info" data-tooltip="<?php echo esc_attr__( 'Price and availability are accurate as of the date and time indicated and are subject to change.', 'affibloom' ); ?>"></i>
	<?php endif; ?>
</div>
