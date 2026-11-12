<?php
/**
 * Lasso-style toast, bottom right. Fades out by itself (CSS only).
 *
 * @package Affibloom
 * @var array{message: string} $view
 */

defined( 'ABSPATH' ) || exit;

if ( '' === $view['message'] ) {
	return;
}
?>
<div class="affibloom-toast" role="status" aria-live="polite"><?php echo esc_html( $view['message'] ); ?></div>
