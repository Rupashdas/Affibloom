<?php
/**
 * Success toast, bottom right: a white card with a status bar and icon. Disappears by itself (CSS only).
 *
 * @package Affibloom
 * @var array{message: string} $view
 */

defined( 'ABSPATH' ) || exit;

if ( '' === $view['message'] ) {
	return;
}
?>
<div class="affibloom-toast" role="status" aria-live="polite">
	<svg class="affibloom-toast__icon" width="18" height="18" viewBox="0 0 18 18" aria-hidden="true" focusable="false">
		<circle class="affibloom-toast__icon-circle" cx="9" cy="9" r="9"/>
		<path d="M5 9.2l2.6 2.6L13 6.4" stroke="#fff" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
	</svg>
	<span><?php echo esc_html( $view['message'] ); ?></span>
</div>
