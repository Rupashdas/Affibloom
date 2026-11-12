<?php
/**
 * Toast, bottom right: a dark card with an icon. Disappears by itself (CSS only). "success" shows a check;
 * "info" (nothing was done, e.g. a link the site already has) shows an "i".
 *
 * @package Affibloom
 * @var array{message: string, kind?: string} $view
 */

defined( 'ABSPATH' ) || exit;

if ( '' === $view['message'] ) {
	return;
}
$toast_info = 'info' === ( $view['kind'] ?? 'success' );
?>
<div class="affibloom-toast<?php echo $toast_info ? ' affibloom-toast--info' : ''; ?>" role="status" aria-live="polite">
	<span class="affibloom-toast__icon"><?php $this->render( 'admin/partials/icon', array( 'name' => $toast_info ? 'info' : 'check' ) ); ?></span>
	<span class="affibloom-toast__message"><?php echo esc_html( $view['message'] ); ?></span>
</div>
