<?php
/**
 * Lasso's clone question (F25 R10; Task T11): "Do you want to clone <name>", Cancel and OK. tables.js puts the table's
 * name in the question, sends POST /tables/{id}/clone and shows the list's last page, where the copy is.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="affibloom-table-clone" class="affibloom-modal" hidden
	<?php /* translators: %s: the table's name. */ ?>
	data-question="<?php esc_attr_e( 'Do you want to clone %s', 'affibloom' ); ?>"
	data-failed="<?php esc_attr_e( 'Could not clone the table. Please try again.', 'affibloom' ); ?>">
	<div class="affibloom-modal__backdrop" data-affibloom-close></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="affibloom-table-clone-title">
		<div class="affibloom-modal__head">
			<span class="affibloom-chip affibloom-modal__chip" aria-hidden="true"><?php $this->render( 'admin/partials/icon', array( 'name' => 'copy' ) ); ?></span>
			<h2 id="affibloom-table-clone-title" class="affibloom-modal__title"></h2>
			<button type="button" class="affibloom-modal__close" data-affibloom-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
			</button>
		</div>
		<div class="affibloom-modal__foot">
			<button type="button" class="affibloom-button affibloom-button--secondary" data-affibloom-close><?php esc_html_e( 'Cancel', 'affibloom' ); ?></button>
			<button type="button" class="affibloom-button affibloom-button--primary" data-table-clone-confirm data-busy-label="<?php esc_attr_e( 'Cloning…', 'affibloom' ); ?>"><?php esc_html_e( 'OK', 'affibloom' ); ?></button>
		</div>
	</div>
</div>
