<?php
/**
 * Lasso's info tip, reachable by keyboard and screen reader (U3).
 *
 * The tip is a button, so it takes focus; its text shows on hover and focus. The text has an id, so the
 * field it explains can point at it with aria-describedby and screen readers read it with the field.
 *
 * @package Affibloom
 * @var array{id: string, text: string} $view
 */

defined( 'ABSPATH' ) || exit;
?>
<span class="affibloom-help">
	<button type="button" class="affibloom-help__button" aria-describedby="<?php echo esc_attr( $view['id'] ); ?>">
		<span aria-hidden="true">i</span>
		<span class="screen-reader-text"><?php esc_html_e( 'Help', 'affibloom' ); ?></span>
	</button>
	<span class="affibloom-help__tip" id="<?php echo esc_attr( $view['id'] ); ?>" role="tooltip"><?php echo esc_html( $view['text'] ); ?></span>
</span>
