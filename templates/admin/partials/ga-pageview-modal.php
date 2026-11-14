<?php
/**
 * "Enable Pageview": asked when the Pageview switch is turned on (F9 R14, Lasso's setting-general.js). Enable keeps it
 * on; Cancel, × and Escape turn it off again.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="affibloom-ga-pageview-modal" class="affibloom-modal" hidden data-ga-pageview-modal>
	<div class="affibloom-modal__backdrop" data-affibloom-close></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="affibloom-ga-pageview-title" aria-describedby="affibloom-ga-pageview-text">
		<div class="affibloom-modal__head">
			<h2 id="affibloom-ga-pageview-title" class="affibloom-modal__title"><?php esc_html_e( 'Enable Pageview', 'affibloom' ); ?></h2>
			<button type="button" class="affibloom-modal__close" data-affibloom-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
			</button>
		</div>
		<div class="affibloom-modal__body">
			<p id="affibloom-ga-pageview-text" class="affibloom-modal__intro"><?php esc_html_e( 'If you want Affibloom to report pageviews to Google Analytics, click Enable. If you have Google Analytics installed outside of Affibloom, keep this disabled.', 'affibloom' ); ?></p>
		</div>
		<div class="affibloom-modal__foot">
			<button type="button" class="affibloom-button affibloom-button--secondary" data-affibloom-close><?php esc_html_e( 'Cancel', 'affibloom' ); ?></button>
			<button type="button" class="affibloom-button affibloom-button--primary" data-ga-pageview-enable><?php esc_html_e( 'Enable', 'affibloom' ); ?></button>
		</div>
	</div>
</div>
