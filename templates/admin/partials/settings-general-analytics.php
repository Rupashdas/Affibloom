<?php
/**
 * Settings → General, Google Analytics (F9 R10-R14; Task 9F): the Tracking ID, and the three switches that depend on it
 * (Click Tracking needs an ID; Pageview and IP Anonymization need Click Tracking). The rules are the page script's
 * (general-settings.js) and the server's (SettingsService), so the form is right without either. What gets printed on
 * the site is Task C3's.
 *
 * @package Affibloom
 * @var array{settings: \Affibloom\Domain\Settings\Settings, refused: list<string>} $view
 */

defined( 'ABSPATH' ) || exit;

$settings   = $view['settings'];
$has_id     = '' !== $settings->string( 'ga_tracking_id' );
$clicks     = $has_id && $settings->bool( 'ga_click_tracking' );
$id_refused = in_array( 'ga_tracking_id', $view['refused'], true );
?>
<div class="affibloom-card" id="affibloom-card-analytics" data-ga-card>
	<div class="affibloom-card__head">
		<h2 class="affibloom-card__title"><?php esc_html_e( 'Google Analytics', 'affibloom' ); ?></h2>
		<p class="affibloom-card__intro"><?php esc_html_e( 'Send click tracking data to Google Analytics.', 'affibloom' ); ?></p>
	</div>
	<div class="affibloom-card__body affibloom-card__body--fields">
		<div class="affibloom-field">
			<span class="affibloom-field__label-row">
				<label class="affibloom-label" for="affibloom-setting-ga_tracking_id"><?php esc_html_e( 'Tracking ID or Property ID', 'affibloom' ); ?></label>
				<?php
				$this->render(
					'admin/partials/help',
					array(
						'id'    => 'affibloom-setting-ga_tracking_id-help',
						'text'  => __( 'Enter a Tracking ID (UA-) or a Product ID (G-). Or, enter them both and separate them with a comma. You can find them in Google Analytics > Admin > Property Settings.', 'affibloom' ),
						'label' => __( 'Tracking ID or Property ID', 'affibloom' ),
					)
				);
				?>
			</span>
			<input type="text" class="affibloom-input<?php echo $id_refused ? ' is-invalid' : ''; ?>" id="affibloom-setting-ga_tracking_id" name="settings[ga_tracking_id]"
				value="<?php echo esc_attr( $settings->string( 'ga_tracking_id' ) ); ?>" placeholder="<?php esc_attr_e( 'Tracking ID or Property ID', 'affibloom' ); ?>"
				autocomplete="off" spellcheck="false" data-ga-id>
			<p class="affibloom-field__warning" data-ga-warning role="alert" hidden><?php esc_html_e( 'Please Enter GA Tracking ID Before Enable Click Tracking', 'affibloom' ); ?></p>
		</div>

		<?php
		$switches = array(
			'ga_click_tracking' => array( __( 'Click Tracking', 'affibloom' ), __( 'Enable to begin tracking Affibloom Link clicks in Google Analytics.', 'affibloom' ), ! $has_id, 'click' ),
			'ga_pageview'       => array( __( 'Pageview', 'affibloom' ), __( 'If you have Google Analytics installed outside of Affibloom, keep this disabled.', 'affibloom' ), ! $clicks, 'pageview' ),
			'ga_anonymize_ip'   => array( __( 'IP Anonymization', 'affibloom' ), __( "When enabled, we won't report visitors' IP addresses for GDPR compliance.", 'affibloom' ), ! $clicks, 'anonymize' ),
		);
		foreach ( $switches as $key => [ $label, $help, $off, $ga_role ] ) {
			$this->render(
				'admin/partials/switch-row',
				array(
					'id'       => 'affibloom-setting-' . $key,
					'name'     => 'settings[' . $key . ']',
					'label'    => $label,
					'checked'  => $settings->bool( $key ) && ! $off,
					'help'     => $help,
					'disabled' => $off,
					'data'     => array( 'ga' => $ga_role ),
				)
			);
		}
		?>
	</div>
</div>
