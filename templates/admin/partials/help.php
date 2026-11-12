<?php
/**
 * A "?" help tip for a dense form field (docs/ui/design-system.md, "Help tip"). The tip shows on hover and on
 * keyboard focus, and the field points at its text with aria-describedby, so a screen reader reads the help with
 * the field (U3). Settings rows show their help as a visible description instead.
 *
 * @package Affibloom
 * @var array{id: string, text: string, label: string} $view
 */

defined( 'ABSPATH' ) || exit;
?>
<span class="affibloom-help">
	<button type="button" class="affibloom-help__button" aria-describedby="<?php echo esc_attr( $view['id'] ); ?>">
		<span aria-hidden="true">?</span>
		<span class="screen-reader-text">
			<?php
			/* translators: %s: the field's label. */
			printf( esc_html__( 'Help for %s', 'affibloom' ), esc_html( $view['label'] ) );
			?>
		</span>
	</button>
	<span class="affibloom-help__text" role="tooltip" id="<?php echo esc_attr( $view['id'] ); ?>"><?php echo esc_html( $view['text'] ); ?></span>
</span>
