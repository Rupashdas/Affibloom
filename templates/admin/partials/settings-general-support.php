<?php
/**
 * Settings → General, the support view's cards (`&support=1`; F9 R55-R62, Task 9L): Keep Original URL, Cron, Cronjob
 * Manually and Optional Settings. They are maintenance settings that other features read (F18/F1, F11, F24, F28, C1);
 * the page marks that it showed them (`_support`) so a save from the ordinary view never switches them off. Segment
 * Analytics is Lasso's own cloud and is not built. The Rebuild Database button waits for Task 35G's rebuild route.
 *
 * @package Affibloom
 * @var array{settings: \Affibloom\Domain\Settings\Settings, refused: list<string>} $view
 */

defined( 'ABSPATH' ) || exit;

$settings       = $view['settings'];
$interval_wrong = in_array( 'cron_interval', $view['refused'], true );
$domains_wrong  = in_array( 'keep_original_domains', $view['refused'], true );
$optional       = array(
	'restrict_prefix'        => array( __( 'Restrict Prefix', 'affibloom' ), __( 'When enabled, cloaked links can only be reached through their prefix.', 'affibloom' ) ),
	'process_limit'          => array( __( 'Processes Execute Limit', 'affibloom' ), __( 'When enabled, a background job waits in the queue while too many are running.', 'affibloom' ) ),
	'fontawesome_svg'        => array( __( 'Font Awesome JS Render SVG', 'affibloom' ), __( 'Render Font Awesome icons as SVG on the front end.', 'affibloom' ) ),
	'no_feature_description' => array( __( 'Disable AAWP Description', 'affibloom' ), __( "Don't use an Amazon product's feature list as an empty description.", 'affibloom' ) ),
);
?>
<input type="hidden" name="settings[_support]" value="1">

<div class="affibloom-card" id="affibloom-card-keep-original">
	<div class="affibloom-card__head">
		<h2 class="affibloom-card__title"><?php esc_html_e( 'Keep Original URL', 'affibloom' ); ?></h2>
		<p class="affibloom-card__intro"><?php esc_html_e( "We don't convert the affiliate URLs to destination URLs", 'affibloom' ); ?></p>
	</div>
	<div class="affibloom-card__body affibloom-card__body--fields">
		<div class="affibloom-field">
			<label class="affibloom-label" for="affibloom-setting-keep_original_domains"><?php esc_html_e( 'Domains', 'affibloom' ); ?></label>
			<textarea class="affibloom-input<?php echo $domains_wrong ? ' is-invalid' : ''; ?>" id="affibloom-setting-keep_original_domains" name="settings[keep_original_domains]" rows="4"
				spellcheck="false" aria-describedby="affibloom-setting-keep_original_domains-hint"><?php echo esc_textarea( implode( "\n", $settings->list( 'keep_original_domains' ) ) ); ?></textarea>
			<p class="affibloom-field__hint" id="affibloom-setting-keep_original_domains-hint"><?php esc_html_e( 'One domain a line. Example: domain.com, not https://domain.com/', 'affibloom' ); ?></p>
		</div>
	</div>
</div>

<div class="affibloom-card" id="affibloom-card-cron">
	<div class="affibloom-card__head">
		<h2 class="affibloom-card__title"><?php esc_html_e( 'Cron', 'affibloom' ); ?></h2>
	</div>
	<div class="affibloom-card__body affibloom-card__body--fields">
		<div class="affibloom-field">
			<label class="affibloom-label" for="affibloom-setting-cron_interval"><?php esc_html_e( 'Time interval (hours)', 'affibloom' ); ?></label>
			<input type="number" class="affibloom-input affibloom-input--number<?php echo $interval_wrong ? ' is-invalid' : ''; ?>" id="affibloom-setting-cron_interval" name="settings[cron_interval]"
				value="<?php echo esc_attr( (string) $settings->int( 'cron_interval' ) ); ?>" min="0" max="3" step="1" <?php echo $interval_wrong ? 'aria-invalid="true"' : ''; ?>>
		</div>
	</div>
</div>

<div class="affibloom-card" id="affibloom-card-manual-cron">
	<div class="affibloom-card__head">
		<h2 class="affibloom-card__title"><?php esc_html_e( 'Cronjob Manually', 'affibloom' ); ?></h2>
		<p class="affibloom-card__intro"><?php esc_html_e( 'Maximum Affibloom cronjob at a time.', 'affibloom' ); ?></p>
	</div>
	<div class="affibloom-card__body affibloom-card__body--fields">
		<div class="affibloom-field">
			<span class="affibloom-field__label-row">
				<label class="affibloom-label" for="affibloom-setting-manual_process_limit"><?php esc_html_e( 'Maximum cronjobs', 'affibloom' ); ?></label>
				<?php
				$this->render(
					'admin/partials/help',
					array(
						'id'    => 'affibloom-setting-manual_process_limit-help',
						'text'  => __( "This feature runs only when the system's cronjob does not work.", 'affibloom' ),
						'label' => __( 'Maximum cronjobs', 'affibloom' ),
					)
				);
				?>
			</span>
			<input type="number" class="affibloom-input affibloom-input--number" id="affibloom-setting-manual_process_limit" name="settings[manual_process_limit]"
				value="<?php echo esc_attr( (string) $settings->int( 'manual_process_limit' ) ); ?>" min="1" max="3" step="1">
		</div>
	</div>
</div>

<div class="affibloom-card" id="affibloom-card-optional">
	<div class="affibloom-card__head">
		<h2 class="affibloom-card__title"><?php esc_html_e( 'Optional Settings', 'affibloom' ); ?></h2>
	</div>
	<div class="affibloom-card__body affibloom-card__body--fields">
		<?php
		foreach ( $optional as $key => [ $label, $help ] ) {
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
</div>
