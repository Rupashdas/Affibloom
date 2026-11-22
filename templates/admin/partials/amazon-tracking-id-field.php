<?php
/**
 * Amazon's "Tracking ID for This Site" field with Lasso's check (F17; extracted on its second use: Settings → Amazon and
 * the setup wizard's Amazon step, Task ON7). amazon-settings.js checks the ID as it is typed.
 *
 * @package Affibloom
 * @var array{settings: AffibloomDomainSettingsSettings} $view
 */

defined( 'ABSPATH' ) || exit;

$settings      = $view['settings'];
$tracking_rule = '^[a-zA-Z0-9\-_.]+-[0-9]{2,3}$';
?>
<div class="affibloom-field">
	<label class="affibloom-label" for="affibloom-setting-tracking_id"><?php esc_html_e( 'Tracking ID for This Site', 'affibloom' ); ?></label>
	<input type="text" class="affibloom-input" id="affibloom-setting-tracking_id" name="settings[tracking_id]"
		value="<?php echo esc_attr( $settings->string( 'tracking_id' ) ); ?>" placeholder="tracking-20"
		data-tracking-rule="<?php echo esc_attr( $tracking_rule ); ?>" aria-describedby="affibloom-setting-tracking_id-error">
	<?php // After a refused save (settings-save.js) the message says the stored ID was kept; typing restores Lasso's message. ?>
	<p class="affibloom-field__error" id="affibloom-setting-tracking_id-error" data-live="<?php esc_attr_e( 'This is an invalid Tracking ID', 'affibloom' ); ?>"
		data-refused="<?php esc_attr_e( 'That was not a valid Tracking ID, so the previous one was kept.', 'affibloom' ); ?>" hidden>
		<?php esc_html_e( 'This is an invalid Tracking ID', 'affibloom' ); ?>
	</p>
</div>
