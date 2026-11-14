<?php
/**
 * Toast, bottom right: a dark card with a check, or a cross when something did not work. Disappears by itself (CSS only).
 *
 * @package Affibloom
 * @var array{message: string, failed?: bool} $view
 */

defined( 'ABSPATH' ) || exit;

if ( '' === $view['message'] ) {
	return;
}

$failed = $view['failed'] ?? false;
?>
<div class="affibloom-toast<?php echo $failed ? ' affibloom-toast--error' : ''; ?>" role="status" aria-live="polite">
	<span class="affibloom-toast__icon"><?php $this->render( 'admin/partials/icon', array( 'name' => $failed ? 'close' : 'check' ) ); ?></span>
	<span class="affibloom-toast__message"><?php echo esc_html( $view['message'] ); ?></span>
</div>
