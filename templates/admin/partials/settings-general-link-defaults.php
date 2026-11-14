<?php
/**
 * Settings → General, Link Defaults: the defaults a new link takes (F9 R30; Task 9B), in Lasso's order with Lasso's
 * help texts (product name removed). Changing one of the four that products also carry opens "Update Link Settings"
 * (Task 9D), which is why those rows say which setting they are.
 *
 * @package Affibloom
 * @var array{settings: \Affibloom\Domain\Settings\Settings} $view
 */

defined( 'ABSPATH' ) || exit;

$settings = $view['settings'];

// Key => label, help text, and whether the "Update Link Settings" dialog is offered (R47, R50: not for duplicates and
// auto-detect; Link Cloaking and Detect Opportunities join when F24 and F22 add their product fields).
$rows = array(
	'new_tab'              => array( __( 'New Window / Tab', 'affibloom' ), __( 'When enabled, users who click this link will have it loaded in a new tab.', 'affibloom' ), true ),
	'nofollow'             => array( __( 'NoFollow / NoIndex', 'affibloom' ), __( "When enabled, this link will be set to nofollow. This indicates to Google that it's an affiliate link.", 'affibloom' ), true ),
	'sponsored'            => array( __( 'Sponsored', 'affibloom' ), __( 'When enabled, this link will be set to sponsored.', 'affibloom' ), true ),
	'show_disclosure'      => array( __( 'Show Disclosure', 'affibloom' ), __( 'When enabled, this link will show the disclosure.', 'affibloom' ), true ),
	'allow_duplicates'     => array( __( 'Allow duplicate destinations', 'affibloom' ), __( 'Allows you to have links from different networks to the same product.', 'affibloom' ), false ),
	'link_cloaking'        => array( __( 'Link Cloaking', 'affibloom' ), __( 'Toggle on to brand your link with your domain. Toggle off to show readers your Primary Destination URL.', 'affibloom' ), false ),
	'detect_opportunities' => array( __( 'Detect Opportunities', 'affibloom' ), __( 'When off, Link Opportunities won’t show for affected links. Doesn’t change how Affibloom finds opportunities in the background.', 'affibloom' ), false ),
	'auto_detect_links'    => array( __( 'Auto-Detect Affiliate Links', 'affibloom' ), __( 'Add all detected affiliate links to your dashboard as individual products.', 'affibloom' ), false ),
);
?>
<div class="affibloom-card" id="affibloom-card-link-defaults">
	<div class="affibloom-card__head">
		<h2 class="affibloom-card__title"><?php esc_html_e( 'Link Defaults', 'affibloom' ); ?></h2>
		<p class="affibloom-card__intro"><?php esc_html_e( 'Set the default attributes for new links.', 'affibloom' ); ?></p>
	</div>
	<div class="affibloom-card__body affibloom-card__body--fields">
		<?php
		foreach ( $rows as $key => [ $label, $help, $override ] ) {
			$this->render(
				'admin/partials/switch-row',
				array(
					'id'      => 'affibloom-setting-' . $key,
					'name'    => 'settings[' . $key . ']',
					'label'   => $label,
					'checked' => $settings->bool( $key ),
					'help'    => $help,
					'data'    => $override ? array(
						'link-default' => $key,
						'link-label'   => $label,
					) : array(),
				)
			);
		}
		?>
	</div>
</div>
