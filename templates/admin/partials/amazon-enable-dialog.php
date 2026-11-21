<?php
/**
 * "Enable Auto-Amazon" (F35 R22, D17, D22; Task AM3; Lasso's auto-monetize-flip.php), in Affibloom's Modal: Lasso's
 * title and advice, **Create a Backup First** (the switch goes back off, Allow Multiple Tracking IDs back as it was,
 * WordPress's backup guide opens in a new tab) and **Enable**. assets/admin/amazon-auto-monetize.js opens it.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="affibloom-amazon-enable" class="affibloom-modal" hidden data-backup-url="https://wordpress.org/documentation/article/wordpress-backups/">
	<div class="affibloom-modal__backdrop" data-affibloom-close></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="affibloom-amazon-enable-title" aria-describedby="affibloom-amazon-enable-text">
		<div class="affibloom-modal__head">
			<span class="affibloom-chip affibloom-modal__chip" aria-hidden="true"><?php $this->render( 'admin/partials/icon', array( 'name' => 'info' ) ); ?></span>
			<h2 id="affibloom-amazon-enable-title" class="affibloom-modal__title"><?php esc_html_e( 'Enable Auto-Amazon', 'affibloom' ); ?></h2>
			<button type="button" class="affibloom-modal__close" data-affibloom-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
			</button>
		</div>
		<div class="affibloom-modal__body">
			<p id="affibloom-amazon-enable-text" class="affibloom-modal__intro"><?php esc_html_e( 'We recommend creating a backup first. This will allow you to undo this action quickly and ensure everything runs exactly as you hoped.', 'affibloom' ); ?></p>
		</div>
		<div class="affibloom-modal__foot">
			<button type="button" class="affibloom-button affibloom-button--secondary" data-amazon-backup><?php esc_html_e( 'Create a Backup First', 'affibloom' ); ?></button>
			<button type="button" class="affibloom-button affibloom-button--primary" data-amazon-enable><?php esc_html_e( 'Enable', 'affibloom' ); ?></button>
		</div>
	</div>
</div>
