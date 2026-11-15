<?php
/**
 * The disclosure when it shows (F5 R24, R25).
 *
 * @package Affibloom
 * @var \Affibloom\Application\Box\BoxView $view
 */

defined( 'ABSPATH' ) || exit;

if ( null === $view->disclosure ) {
	return;
}
?>
<div class="affibloom-disclosure">
	<span><?php echo esc_html( $view->disclosure ); ?></span>
</div>
