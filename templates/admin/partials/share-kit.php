<?php
/**
 * Lasso 370's Share kit (dashboard/header.php; F1 requirements B9, Task 14D): five buttons that copy a snippet and
 * flash "Copied!" for 1.5 seconds (product.js). The shareable URL is the destination until link cloaking exists (D5);
 * the destination is the Primary Destination URL as it is typed; the disclosure is the field's, else the site's text.
 * Copy shortcode waits for Affibloom's shortcode (Task 18S) and Copy block for the editor block (Phase 4).
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;

$share_buttons = array(
	array( 'public', 'link', __( 'Copy shareable URL', 'affibloom' ), false ),
	array( 'destination', 'new-tab', __( 'Copy destination URL', 'affibloom' ), false ),
	array( 'disclosure', 'info', __( 'Copy disclosure', 'affibloom' ), false ),
	array( 'shortcode', 'copy', __( 'Copy shortcode', 'affibloom' ), true ),
	array( 'block', 'box', __( 'Copy block', 'affibloom' ), true ),
);
?>
<section class="affibloom-card affibloom-share-kit" aria-labelledby="affibloom-share-kit-title" data-share-kit
	data-copied="<?php esc_attr_e( 'Copied!', 'affibloom' ); ?>">
	<h2 class="affibloom-card__title" id="affibloom-share-kit-title"><?php esc_html_e( 'Share kit', 'affibloom' ); ?></h2>
	<p class="affibloom-card__intro"><?php esc_html_e( 'Copy snippets for posts, email, and social.', 'affibloom' ); ?></p>
	<div class="affibloom-share-kit__buttons">
		<?php foreach ( $share_buttons as [ $target, $icon, $label, $soon ] ) : ?>
			<?php // The name stays the action while the visible text says "Copied!". ?>
			<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-share="<?php echo esc_attr( $target ); ?>"
				aria-label="<?php echo esc_attr( $label ); ?>"<?php echo $soon ? ' disabled aria-describedby="affibloom-soon-note"' : ''; ?>>
				<?php $this->render( 'admin/partials/icon', array( 'name' => $icon ) ); ?>
				<span data-share-label><?php echo esc_html( $label ); ?></span>
			</button>
			<?php
			if ( $soon ) {
				$this->render( 'admin/partials/soon', array( 'variant' => 'chip' ) );
			}
			?>
		<?php endforeach; ?>
	</div>
</section>
