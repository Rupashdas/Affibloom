<?php
/**
 * Lasso's "Hold Up" dialog: shown instead of a delete question when something still uses what would be deleted. Nothing is
 * deleted; Ok closes it. Used by Delete This Field (Lasso's `#group_not_delete`, which its field page opened the wrong id
 * of, so nothing ever showed: F26 R18). The caller passes the dialog's id and its sentence.
 *
 * @package Affibloom
 * @var array{id: string, text: string} $view
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="<?php echo esc_attr( $view['id'] ); ?>" class="affibloom-modal" hidden>
	<div class="affibloom-modal__backdrop" data-affibloom-close></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $view['id'] ); ?>-title" aria-describedby="<?php echo esc_attr( $view['id'] ); ?>-text">
		<div class="affibloom-modal__head">
			<h2 id="<?php echo esc_attr( $view['id'] ); ?>-title" class="affibloom-modal__title"><?php esc_html_e( 'Hold Up', 'affibloom' ); ?></h2>
			<button type="button" class="affibloom-modal__close" data-affibloom-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
			</button>
		</div>
		<div class="affibloom-modal__body">
			<p id="<?php echo esc_attr( $view['id'] ); ?>-text" class="affibloom-modal__intro"><?php echo esc_html( $view['text'] ); ?></p>
		</div>
		<div class="affibloom-modal__foot">
			<button type="button" class="affibloom-button affibloom-button--primary" data-affibloom-close><?php esc_html_e( 'Ok', 'affibloom' ); ?></button>
		</div>
	</div>
</div>
