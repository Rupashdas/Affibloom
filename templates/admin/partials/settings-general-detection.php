<?php
/**
 * Settings → General, Custom Link Detection (F9 R25-R27; Task 9I): the post types, and the ACF fields where ACF is
 * active, that link detection scans besides posts and pages. Nothing chosen means the defaults. The reader is Task
 * 35D's. The choice is a plain set of checkboxes that look like chips (no select2), in Lasso's wording.
 *
 * @package Affibloom
 * @var array{settings: \Affibloom\Domain\Settings\Settings, refused: list<string>, post_types: array<string, string>, fields: array<string, string>, has_acf: bool} $view
 */

defined( 'ABSPATH' ) || exit;

$settings = $view['settings'];

$groups = array(
	'scan_post_types' => array( __( 'Custom Post Types:', 'affibloom' ), __( 'No custom post types on this site.', 'affibloom' ), $view['post_types'] ),
);
if ( $view['has_acf'] ) {
	$groups['scan_custom_fields'] = array( __( 'Custom Fields (ACF):', 'affibloom' ), __( 'No ACF fields on this site.', 'affibloom' ), $view['fields'] );
}
?>
<div class="affibloom-card" id="affibloom-card-detection">
	<div class="affibloom-card__head">
		<h2 class="affibloom-card__title"><?php esc_html_e( 'Custom Link Detection', 'affibloom' ); ?></h2>
		<p class="affibloom-card__intro"><?php esc_html_e( 'Scan for links and shortcodes in custom locations.', 'affibloom' ); ?></p>
	</div>
	<div class="affibloom-card__body affibloom-card__body--fields">
		<?php foreach ( $groups as $key => [ $label, $none, $choices ] ) : ?>
			<?php
			$chosen  = $settings->list( $key );
			$refused = in_array( $key, $view['refused'], true );
			?>
			<fieldset class="affibloom-choices" id="affibloom-setting-<?php echo esc_attr( $key ); ?>">
				<legend class="affibloom-label">
					<?php echo esc_html( $label ); ?>
					<?php
					if ( 'scan_post_types' === $key ) {
						$this->render(
							'admin/partials/help',
							array(
								'id'    => 'affibloom-setting-scan_post_types-help',
								'text'  => __( 'Additional locations where Affibloom will discover links.', 'affibloom' ),
								'label' => $label,
							)
						);
					}
					?>
				</legend>
				<?php // Nothing ticked posts nothing, so this empty value is what clears the choice. ?>
				<input type="hidden" name="settings[<?php echo esc_attr( $key ); ?>]" value="">
				<?php if ( array() === $choices ) : ?>
					<p class="affibloom-field__hint"><?php echo esc_html( $none ); ?></p>
				<?php else : ?>
					<div class="affibloom-chips">
						<?php foreach ( $choices as $name => $choice_label ) : ?>
							<label class="affibloom-chip">
								<input type="checkbox" name="settings[<?php echo esc_attr( $key ); ?>][]" value="<?php echo esc_attr( (string) $name ); ?>" <?php checked( in_array( (string) $name, $chosen, true ) ); ?>>
								<span><?php echo esc_html( $choice_label ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<?php if ( $refused ) : ?>
					<p class="affibloom-field__error"><?php esc_html_e( 'One of those is not available on this site, so the previous choice was kept.', 'affibloom' ); ?></p>
				<?php endif; ?>
			</fieldset>
		<?php endforeach; ?>
	</div>
</div>
