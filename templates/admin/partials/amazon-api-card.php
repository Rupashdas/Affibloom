<?php
/**
 * Settings → Amazon, left column under Amazon Associates Accounts: Lasso's "Amazon Product Data API" card (C1 R17-R18,
 * D2-D4, D16), in Affibloom's look. The state chip says what the stored credentials are; the Credential ID and Secret
 * are never printed (a blank field keeps the stored value); Validate checks the typed values (amazon-settings.js) and
 * Save Changes saves them with the tab. Lasso's "Learn" links go to its own help site and are left out (no Lasso
 * branding); the intro links to Amazon's Associates Central instead.
 *
 * @package Affibloom
 * @var array{settings: \Affibloom\Domain\Settings\Settings, api: array{state: string, partner_tag: string, version: string, has_credential_id: bool, has_secret: bool, countries: array<string, string>}} $view
 */

defined( 'ABSPATH' ) || exit;

$api     = $view['api'];
$country = $view['settings']->string( 'default_country' );
$states  = array(
	'not-set-up' => __( 'Not set up', 'affibloom' ),
	'saved'      => __( 'Saved, not validated', 'affibloom' ),
	'validated'  => __( 'Validated', 'affibloom' ),
);
$kept    = __( 'Saved — leave blank to keep', 'affibloom' );
?>
<section class="affibloom-card affibloom-api-card" aria-labelledby="affibloom-api-card-title" data-amazon-api
	data-validating-text="<?php esc_attr_e( 'Validating…', 'affibloom' ); ?>"
	data-failed-text="<?php esc_attr_e( 'Unable to verify Creators API credentials.', 'affibloom' ); ?>"
	data-states="<?php echo esc_attr( (string) wp_json_encode( $states ) ); ?>">
	<div class="affibloom-card__head affibloom-api-card__head">
		<h2 class="affibloom-card__title" id="affibloom-api-card-title"><?php esc_html_e( 'Amazon Product Data API', 'affibloom' ); ?></h2>
		<span class="affibloom-state affibloom-state--<?php echo esc_attr( $api['state'] ); ?>" data-api-state><?php echo esc_html( $states[ $api['state'] ] ); ?></span>
	</div>
	<div class="affibloom-card__body affibloom-card__body--fields">
		<div class="affibloom-field">
			<div class="affibloom-field__head">
				<label class="affibloom-label" for="affibloom-setting-default_country"><?php esc_html_e( 'Default Tracking ID', 'affibloom' ); ?></label>
				<?php
				$this->render(
					'admin/partials/help',
					array(
						'id'    => 'affibloom-setting-default_country-help',
						'text'  => __( 'Select your Amazon Associates locale.', 'affibloom' ),
						'label' => __( 'Default Tracking ID', 'affibloom' ),
					)
				);
				?>
			</div>
			<select class="affibloom-input affibloom-select" id="affibloom-setting-default_country" name="settings[default_country]" aria-describedby="affibloom-setting-default_country-help">
				<?php foreach ( $api['countries'] as $code => $name ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $country, $code ); ?>><?php echo esc_html( $name ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<p class="affibloom-api-card__intro">
			<?php
			printf(
				/* translators: %s: a link to Amazon's Associates Central, "Creators API credentials from Amazon". */
				esc_html__( "If you want to use the Amazon Creators API for product data, here's how to get your %s.", 'affibloom' ),
				'<a href="https://affiliate-program.amazon.com/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Creators API credentials from Amazon', 'affibloom' ) . '</a>'
			);
			?>
		</p>

		<div class="affibloom-field">
			<label class="affibloom-label" for="affibloom-api-credential_id"><?php esc_html_e( 'Credential ID', 'affibloom' ); ?></label>
			<input type="password" class="affibloom-input" id="affibloom-api-credential_id" name="amazon_api[credential_id]" value="" autocomplete="off"
				placeholder="amzn1.application-oa2-client.xxxxx" <?php echo $api['has_credential_id'] ? 'aria-describedby="affibloom-api-credential_id-kept"' : ''; ?>>
			<?php if ( $api['has_credential_id'] ) : ?>
				<p class="affibloom-field__hint" id="affibloom-api-credential_id-kept"><?php echo esc_html( $kept ); ?></p>
			<?php endif; ?>
		</div>

		<div class="affibloom-field">
			<label class="affibloom-label" for="affibloom-api-secret"><?php esc_html_e( 'Secret', 'affibloom' ); ?></label>
			<input type="password" class="affibloom-input" id="affibloom-api-secret" name="amazon_api[secret]" value="" autocomplete="new-password"
				placeholder="client-secret" <?php echo $api['has_secret'] ? 'aria-describedby="affibloom-api-secret-kept"' : ''; ?>>
			<?php if ( $api['has_secret'] ) : ?>
				<p class="affibloom-field__hint" id="affibloom-api-secret-kept"><?php echo esc_html( $kept ); ?></p>
			<?php endif; ?>
		</div>

		<div class="affibloom-api-card__pair">
			<div class="affibloom-field">
				<label class="affibloom-label" for="affibloom-api-partner_tag"><?php esc_html_e( 'Partner tag', 'affibloom' ); ?></label>
				<input type="text" class="affibloom-input" id="affibloom-api-partner_tag" name="amazon_api[partner_tag]" value="<?php echo esc_attr( $api['partner_tag'] ); ?>" placeholder="yourtag-20" autocomplete="off">
			</div>
			<div class="affibloom-field">
				<label class="affibloom-label" for="affibloom-api-version"><?php esc_html_e( 'Version', 'affibloom' ); ?></label>
				<input type="text" class="affibloom-input" id="affibloom-api-version" name="amazon_api[version]" value="<?php echo esc_attr( $api['version'] ); ?>" placeholder="3.1" autocomplete="off">
			</div>
		</div>

		<div class="affibloom-api-card__actions">
			<p class="affibloom-api-card__message" role="status" aria-live="polite" data-api-message></p>
			<button type="button" class="affibloom-button affibloom-button--secondary" data-api-validate><?php esc_html_e( 'Validate', 'affibloom' ); ?></button>
		</div>
	</div>
</section>
