<?php
/**
 * The dialog shown while the product page saves (Lasso's url-save.php; F1 requirements 9 S14, S10).
 *
 * First panel, "Updating Your Site": Lasso's static dialog with a moving bar and one line of text, "Just saving your URL
 * changes", or "Saving your changes." when only New Window or NoFollow changed. It has no close button: it closes
 * itself about a second after the answer.
 *
 * Second panel, "This Product Already Exists" (Task 15C): shown instead when the new Primary URL belongs to another
 * product. Lasso's words, both products with a View link, Cancel and Confirm. Confirm retires this product in favour
 * of the other; no post is touched.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="affibloom-save-modal" class="affibloom-modal affibloom-save-modal" hidden data-save-modal
	data-text-default="<?php esc_attr_e( 'Just saving your URL changes', 'affibloom' ); ?>"
	data-text-toggles="<?php esc_attr_e( 'Saving your changes.', 'affibloom' ); ?>">
	<div class="affibloom-modal__backdrop"></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="affibloom-save-title" aria-describedby="affibloom-save-text" tabindex="-1">
		<div class="affibloom-modal__body affibloom-save-modal__body" data-save-progress>
			<h2 id="affibloom-save-title" class="affibloom-modal__title"><?php esc_html_e( 'Updating Your Site', 'affibloom' ); ?></h2>
			<div class="affibloom-progress" role="progressbar" aria-labelledby="affibloom-save-title" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
				<span class="affibloom-progress__bar" data-save-bar></span>
			</div>
			<p id="affibloom-save-text" class="affibloom-modal__intro" data-save-text></p>
		</div>

		<div class="affibloom-repoint" data-repoint hidden>
			<div class="affibloom-modal__body">
				<h2 id="affibloom-repoint-title" class="affibloom-modal__title affibloom-repoint__title"><?php esc_html_e( 'This Product Already Exists', 'affibloom' ); ?></h2>
				<p id="affibloom-repoint-text" class="affibloom-modal__intro affibloom-repoint__intro">
					<?php esc_html_e( 'You already use that link on your site.', 'affibloom' ); ?>
					<strong><?php esc_html_e( 'Do you want to change all Locations to that link?', 'affibloom' ); ?></strong>
				</p>
				<?php
				$sides = array(
					'from' => __( 'Current Link', 'affibloom' ),
					'to'   => __( 'Existing Link', 'affibloom' ),
				);
				foreach ( $sides as $side => $side_label ) :
					?>
					<?php if ( 'to' === $side ) : ?>
						<p class="affibloom-repoint__arrow" aria-hidden="true">&#8595;</p>
					<?php endif; ?>
					<div class="affibloom-repoint__side" data-repoint-side="<?php echo esc_attr( $side ); ?>">
						<div class="affibloom-repoint__head">
							<strong><?php echo esc_html( $side_label ); ?></strong>
							<?php /* translators: %s: Current Link or Existing Link. */ ?>
							<a class="affibloom-button affibloom-button--small affibloom-button--secondary" href="#" target="_blank" rel="noopener noreferrer" data-repoint-view aria-label="<?php echo esc_attr( sprintf( __( 'View %s', 'affibloom' ), $side_label ) ); ?>"><?php esc_html_e( 'View', 'affibloom' ); ?></a>
						</div>
						<div class="affibloom-repoint__name" data-repoint-name></div>
						<div class="affibloom-repoint__url" data-repoint-url></div>
					</div>
				<?php endforeach; ?>
			</div>
			<div class="affibloom-modal__foot">
				<button type="button" class="affibloom-button affibloom-button--secondary" data-repoint-cancel><?php esc_html_e( 'Cancel', 'affibloom' ); ?></button>
				<button type="button" class="affibloom-button affibloom-button--primary" data-repoint-confirm><?php esc_html_e( 'Confirm', 'affibloom' ); ?></button>
			</div>
		</div>
	</div>
</div>
