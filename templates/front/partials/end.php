<?php
/**
 * The disclosure when it shows (F5 R24, R25), then the date, always printed and empty until Task 22C (R29).
 *
 * @package Affibloom
 * @var \Affibloom\Application\Box\BoxView $view
 */

defined( 'ABSPATH' ) || exit;

if ( null !== $view->disclosure ) :
	?>
	<div class="affibloom-disclosure">
		<span><?php echo esc_html( $view->disclosure ); ?></span>
	</div>
<?php endif; ?>
<div class="affibloom-date"></div>
