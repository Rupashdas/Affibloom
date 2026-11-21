<?php
/**
 * Lasso's "Hold Up" dialog: shown instead of a delete question when something still uses what would be deleted. Nothing is
 * deleted; Ok closes it. Used by Delete This Field (Lasso's `#group_not_delete`, which its field page opened the wrong id
 * of, so nothing ever showed: F26 R18). The caller passes the dialog's id and its sentence.
 * The product page's Delete This Link (Lasso's url-delete-reject.php; F12 L15, D26; Task 36E) also passes the posts that
 * use the product (`hosts`, the Locations tab's first page, with `more` linking to the tab) and its only button,
 * "Keep This Link".
 *
 * @package Affibloom
 * @var array{id: string, text: string, hosts?: \Affibloom\Domain\LinkIndex\LocationPage, more?: string, button?: string} $view
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
			<?php if ( isset( $view['hosts'] ) ) : ?>
				<ul class="affibloom-holdup__hosts">
					<?php foreach ( $view['hosts']->items as $holdup_host ) : ?>
						<li>
							<a href="<?php echo esc_url( (string) get_edit_post_link( $holdup_host->id, 'raw' ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( '' !== $holdup_host->title ? $holdup_host->title : __( '(no title)', 'affibloom' ) ); ?></a>
						</li>
					<?php endforeach; ?>
				</ul>
				<?php if ( $view['hosts']->total > count( $view['hosts']->items ) && isset( $view['more'] ) ) : ?>
					<p class="affibloom-holdup__more">
						<a href="<?php echo esc_url( $view['more'] ); ?>">
							<?php
							/* translators: %s: how many more posts use the product. */
							echo esc_html( sprintf( __( 'and %s more on the Locations tab', 'affibloom' ), number_format_i18n( $view['hosts']->total - count( $view['hosts']->items ) ) ) );
							?>
						</a>
					</p>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<div class="affibloom-modal__foot">
			<button type="button" class="affibloom-button affibloom-button--primary" data-affibloom-close><?php echo esc_html( $view['button'] ?? __( 'Ok', 'affibloom' ) ); ?></button>
		</div>
	</div>
</div>
