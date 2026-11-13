<?php
/**
 * Toast, bottom right: a dark card with a check. Disappears by itself (CSS only).
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
	<span class="affibloom-toast__icon"><?php $this->render( 'admin/partials/icon', array( 'name' => 'check' ) ); ?></span>
	<span class="affibloom-toast__message"><?php echo esc_html( $view['message'] ); ?></span>
</div>
