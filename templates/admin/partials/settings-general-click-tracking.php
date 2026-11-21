<?php
/**
 * Settings → General, Advanced Click Tracking (F12 R14 replaced, D7; Task 33D): Lasso's section and toggle, on by
 * default, switching Affibloom's own local counting (clicks.js and POST /clicks). The help text names no cloud: the
 * clicks are counted on this site and shown on the Dashboard and the Locations tabs.
 *
 * @package Affibloom
 * @var array{settings: \Affibloom\Domain\Settings\Settings} $view
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="affibloom-card" id="affibloom-card-click-tracking">
	<div class="affibloom-card__head">
		<h2 class="affibloom-card__title"><?php esc_html_e( 'Advanced Click Tracking', 'affibloom' ); ?></h2>
		<p class="affibloom-card__intro"><?php esc_html_e( "Count clicks on product links and boxes, shown on the Dashboard and each product's Locations tab.", 'affibloom' ); ?></p>
	</div>
	<div class="affibloom-card__body affibloom-card__body--fields">
		<?php
		$this->render(
			'admin/partials/switch-row',
			array(
				'id'      => 'affibloom-setting-click_tracking',
				'name'    => 'settings[click_tracking]',
				'label'   => __( 'Page and Link-Level Tracking', 'affibloom' ),
				'checked' => $view['settings']->bool( 'click_tracking' ),
				'help'    => __( 'When enabled, you can see which pages and links your readers click.', 'affibloom' ),
			)
		);
		?>
	</div>
</div>
