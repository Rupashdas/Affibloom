<?php
/**
 * The "Auto-Monetize Amazon Links" card (F35 R22; extracted on its second use: Settings → Amazon and the setup wizard's
 * Amazon step, Task ON7): the switch, the line amazon-auto-monetize.js fills when there is no tracking ID, and the
 * import's status card when it is given.
 *
 * @package Affibloom
 * @var array{settings: AffibloomDomainSettingsSettings, import?: mixed} $view
 */

defined( 'ABSPATH' ) || exit;

$settings = $view['settings'];
?>
<div class="affibloom-card">
	<div class="affibloom-card__head">
		<h2 class="affibloom-card__title"><?php esc_html_e( 'Auto-Monetize Amazon Links', 'affibloom' ); ?></h2>
		<p class="affibloom-card__intro"><?php esc_html_e( 'Automatically monetize all current and future Amazon links with your Tracking ID, migrate your SiteStripe displays, and add them to your affiliate dashboard.', 'affibloom' ); ?></p>
	</div>
	<div class="affibloom-card__body affibloom-card__body--fields">
		<?php
		$this->render(
			'admin/partials/switch-row',
			array(
				'id'      => 'affibloom-setting-auto_monetize_amazon',
				'name'    => 'settings[auto_monetize_amazon]',
				'label'   => __( 'Enable Amazon Auto-Monetization', 'affibloom' ),
				'checked' => $settings->bool( 'auto_monetize_amazon' ),
			)
		);
		?>
		<?php // R22 (Task AM3): what amazon-auto-monetize.js says when there is no tracking ID. ?>
		<p class="affibloom-field__error" id="affibloom-amazon-auto-error" role="alert" hidden
			data-needs-tag="<?php esc_attr_e( 'Tracking ID must be set to use Auto-Amazon.', 'affibloom' ); ?>"></p>
		<?php if ( null !== ( $view['import'] ?? null ) ) : ?>
			<?php $this->render( 'admin/partials/amazon-import-card', array( 'status' => $view['import'] ) ); ?>
		<?php endif; ?>
	</div>
</div>
