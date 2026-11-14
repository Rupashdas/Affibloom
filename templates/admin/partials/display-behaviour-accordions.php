<?php
/**
 * The rail's site-wide accordions under Colors & labels: Disclosure & behavior, and Custom CSS.
 *
 * Lasso's controls, order and help texts (admin/views/settings/display.php 639-735). These settings are not per
 * display type; they post as `settings[…]` and are saved with the other site-wide settings. Help texts that name
 * Lasso ("your clickable Lasso Link", "Lasso renders…", "customize Lasso") describe the same behaviour without the
 * product name. Lasso's Brag mode (its own logo, linking to its affiliate sign-up) is left out: decision B9.
 *
 * @package Affibloom
 * @var array{settings: \Affibloom\Domain\Settings\Settings} $view
 */

defined( 'ABSPATH' ) || exit;

$settings = $view['settings'];
$per_type = __( 'Turn this on to show the disclosure in Displays by default. You can override this per display.', 'affibloom' );
$toggles  = array(
	'show_price'              => array( __( 'Show Price', 'affibloom' ), __( 'Turn this on to show the price in Displays by default. You can override this per display.', 'affibloom' ) ),
	'show_disclosure'         => array( __( 'Disclosure in Single Display', 'affibloom' ), $per_type ),
	'show_disclosure_grid'    => array( __( 'Disclosure in Grids', 'affibloom' ), $per_type ),
	// One toggle for two keys, as in Lasso; the controller copies it into show_disclosure_table.
	'show_disclosure_list'    => array( __( 'Disclosure in Lists & Tables', 'affibloom' ), __( 'Turn this on to show the disclosure in List and Table displays by default. You can override this per display.', 'affibloom' ) ),
	'link_from_display_title' => array( __( 'Link from Display Title', 'affibloom' ), __( 'When enabled, your Display titles will be your clickable link. Toggle this off to have Display titles only appear as text.', 'affibloom' ) ),
	'keep_site_stripe_ui'     => array( __( 'Keep Site Stripe UI', 'affibloom' ), __( 'When enabled, Amazon SiteStripe-style displays use the SiteStripe iframe layout on the front end.', 'affibloom' ) ),
);
?>
<details class="affibloom-accordion" data-accordion="behaviour" open>
	<summary class="affibloom-accordion__toggle"><span><?php esc_html_e( 'Disclosure & behavior', 'affibloom' ); ?></span></summary>
	<div class="affibloom-accordion__body">
		<div class="affibloom-field">
			<div class="affibloom-field__head">
				<label class="affibloom-label" for="affibloom-setting-disclosure_text"><?php esc_html_e( 'Disclosure text', 'affibloom' ); ?></label>
				<?php
				$this->render(
					'admin/partials/help',
					array(
						'id'    => 'affibloom-setting-disclosure_text-help',
						'text'  => __( 'This is the default disclosure text used with your displays.', 'affibloom' ),
						'label' => __( 'Disclosure text', 'affibloom' ),
					)
				);
				?>
			</div>
			<textarea class="affibloom-input affibloom-input--area" id="affibloom-setting-disclosure_text" name="settings[disclosure_text]" rows="4"
				maxlength="500" aria-describedby="affibloom-setting-disclosure_text-help"><?php echo esc_textarea( $settings->string( 'disclosure_text' ) ); ?></textarea>
		</div>

		<?php
		foreach ( $toggles as $key => [ $label, $help ] ) {
			$this->render(
				'admin/partials/switch-row',
				array(
					'id'      => 'affibloom-setting-' . $key,
					'name'    => 'settings[' . $key . ']',
					'label'   => $label,
					'checked' => $settings->bool( $key ),
					'help'    => $help,
				)
			);
		}
		?>
	</div>
</details>

<details class="affibloom-accordion" data-accordion="custom-css">
	<summary class="affibloom-accordion__toggle">
		<span><?php esc_html_e( 'Custom CSS', 'affibloom' ); ?></span>
	</summary>
	<div class="affibloom-accordion__body">
		<p class="affibloom-rail-hint" id="affibloom-setting-custom_css-help"><?php esc_html_e( 'Use this to add CSS to further customize your displays.', 'affibloom' ); ?></p>
		<label class="screen-reader-text" for="affibloom-setting-custom_css"><?php esc_html_e( 'Custom CSS', 'affibloom' ); ?></label>
		<textarea class="affibloom-input affibloom-input--area affibloom-input--code" id="affibloom-setting-custom_css" name="settings[custom_css]" rows="6"
			placeholder="<?php esc_attr_e( 'Enter CSS code here', 'affibloom' ); ?>" aria-describedby="affibloom-setting-custom_css-help"><?php echo esc_textarea( $settings->string( 'custom_css' ) ); ?></textarea>
	</div>
</details>
