<?php
/**
 * A row's Monetize switch (F22 R64, R72, N1-A, N3; Task O10): off for an untied link (on opens "Monetize"); on for a
 * link monetized here (off clears it); on and disabled for a link tied by its own address, which only the post can
 * change. "Edit in post" (N1-B) sits in the row's actions, not here.
 *
 * @package Affibloom
 * @var array{row: int, url: string, state: string, amazon?: bool} $view state: 'off', 'on' or 'content'.
 */

defined( 'ABSPATH' ) || exit;

/* translators: %s: the link's address. */
$label   = sprintf( __( 'Monetize %s', 'affibloom' ), $view['url'] );
$content = 'content' === $view['state'];
$tip     = $content ? __( "This link is in the post's content; edit the post to change it.", 'affibloom' ) : '';
?>
<span class="affibloom-toggle"<?php echo '' === $tip ? '' : ' title="' . esc_attr( $tip ) . '"'; ?>>
	<input type="checkbox" class="affibloom-toggle__input" role="switch" aria-label="<?php echo esc_attr( $label ); ?>"
		<?php checked( 'off' !== $view['state'] ); ?> <?php disabled( $content ); ?>
		<?php if ( ! $content ) : ?>
			data-monetize="<?php echo esc_attr( (string) $view['row'] ); ?>" data-monetize-url="<?php echo esc_attr( $view['url'] ); ?>"<?php echo ( $view['amazon'] ?? false ) ? ' data-monetize-amazon' : ''; ?>
		<?php endif; ?>>
	<span class="affibloom-toggle__track" aria-hidden="true"></span>
</span>
