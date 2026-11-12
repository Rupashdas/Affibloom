<?php
/**
 * The rail's "Layout & spacing" panel: density presets, elements, spacing, typography and image height.
 *
 * Lasso's structure and wording (admin/views/settings/display.php 349-584): the density chips with their hint,
 * then accordions with a summary on the right. Task 10C adds the Layout accordion between Elements and Spacing.
 * Every control also works without JavaScript; the script only keeps the chips, steppers and summaries in step.
 *
 * @package Affibloom
 * @var array{config: \Affibloom\Domain\Display\DisplayConfig} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Display\Density;
use Affibloom\Domain\Display\Elements;

$density  = $view['config']->density();
$elements = $view['config']->elements();
$preset   = $density->preset_name();

$hints          = array(
	'compact'  => __( 'Tighter card spacing.', 'affibloom' ),
	'balanced' => __( 'Balanced is the default spacing preset.', 'affibloom' ),
	'spacious' => __( 'More open card spacing.', 'affibloom' ),
	'custom'   => __( 'Custom spacing — stepper edits will keep this preset.', 'affibloom' ),
);
$chips          = array(
	'compact'  => __( 'Compact', 'affibloom' ),
	'balanced' => __( 'Balanced', 'affibloom' ),
	'spacious' => __( 'Spacious', 'affibloom' ),
);
$element_labels = array(
	'badge'     => __( 'Badge', 'affibloom' ),
	'image'     => __( 'Image', 'affibloom' ),
	'title'     => __( 'Title', 'affibloom' ),
	'price'     => __( 'Price', 'affibloom' ),
	'prime'     => __( 'Prime badge', 'affibloom' ),
	'desc'      => __( 'Description', 'affibloom' ),
	'proscons'  => __( 'Pros & cons', 'affibloom' ),
	'primary'   => __( 'Primary button', 'affibloom' ),
	'secondary' => __( 'Secondary button', 'affibloom' ),
	'timestamp' => __( 'Timestamp', 'affibloom' ),
);
$groups         = array(
	'spacing'    => array(
		'title'    => __( 'Spacing', 'affibloom' ),
		/* translators: 1: gap between cards, 2: card padding, both in pixels. */
		'summary'  => sprintf( __( '%1$d / %2$d px', 'affibloom' ), $density->value( 'gap' ), $density->value( 'padding' ) ),
		/* translators: 1: gap between cards, 2: card padding, both in pixels. */
		'format'   => __( '%1$d / %2$d px', 'affibloom' ),
		'fields'   => 'gap,padding',
		'controls' => array(
			'gap'          => __( 'Gap between cards', 'affibloom' ),
			'padding'      => __( 'Card padding', 'affibloom' ),
			'spaceBetween' => __( 'Space between elements', 'affibloom' ),
		),
	),
	'typography' => array(
		'title'    => __( 'Typography', 'affibloom' ),
		/* translators: %d: title size in pixels. */
		'summary'  => sprintf( __( '%d px title', 'affibloom' ), $density->value( 'titleSize' ) ),
		/* translators: %d: title size in pixels. */
		'format'   => __( '%d px title', 'affibloom' ),
		'fields'   => 'titleSize',
		'controls' => array(
			'titleSize'  => __( 'Title size', 'affibloom' ),
			'titleLines' => __( 'Title lines', 'affibloom' ),
			'descSize'   => __( 'Description size', 'affibloom' ),
			'descLines'  => __( 'Description lines', 'affibloom' ),
			'priceSize'  => __( 'Price size', 'affibloom' ),
			'buttonSize' => __( 'Button size', 'affibloom' ),
		),
	),
	'image'      => array(
		'title'    => __( 'Image', 'affibloom' ),
		/* translators: %d: image height in pixels. */
		'summary'  => sprintf( __( '%d px', 'affibloom' ), $density->value( 'imageHeight' ) ),
		/* translators: %d: image height in pixels. */
		'format'   => __( '%d px', 'affibloom' ),
		'fields'   => 'imageHeight',
		'controls' => array(
			'imageHeight' => __( 'Image height', 'affibloom' ),
		),
	),
);
?>
<fieldset class="affibloom-density" data-presets="<?php echo esc_attr( (string) wp_json_encode( Density::PRESETS ) ); ?>"
	data-hints="<?php echo esc_attr( (string) wp_json_encode( $hints ) ); ?>">
	<legend class="affibloom-density__label"><?php esc_html_e( 'Density', 'affibloom' ); ?></legend>
	<p class="affibloom-rail-hint"><?php esc_html_e( 'Spacing presets apply to the selected display type (Single, Grid, or List).', 'affibloom' ); ?></p>
	<input type="hidden" name="display[density][base]" value="<?php echo esc_attr( isset( Density::PRESETS[ $preset ] ) ? $preset : '' ); ?>" data-density-base>
	<div class="affibloom-chips">
		<?php foreach ( $chips as $chip_key => $chip_label ) : ?>
			<label class="affibloom-chip-option">
				<input type="radio" name="display[density][preset]" value="<?php echo esc_attr( $chip_key ); ?>" <?php checked( $preset, $chip_key ); ?>>
				<span class="affibloom-chip-option__bars affibloom-chip-option__bars--<?php echo esc_attr( $chip_key ); ?>" aria-hidden="true"><span></span><span></span><span></span></span>
				<span class="affibloom-chip-option__name"><?php echo esc_html( $chip_label ); ?></span>
			</label>
		<?php endforeach; ?>
	</div>
	<p class="affibloom-rail-hint affibloom-density__hint" data-density-hint><?php echo esc_html( $hints[ $preset ] ?? $hints['custom'] ); ?></p>
</fieldset>

<details class="affibloom-accordion" data-accordion="elements">
	<summary class="affibloom-accordion__toggle">
		<span><?php esc_html_e( 'Elements', 'affibloom' ); ?></span>
		<span class="affibloom-accordion__summary" data-elements-summary data-total="<?php echo esc_attr( (string) count( Elements::KEYS ) ); ?>"
			<?php /* translators: 1: visible element count, 2: total element count. */ ?>
			data-format="<?php echo esc_attr( __( '%1$d of %2$d visible', 'affibloom' ) ); ?>">
			<?php
			/* translators: 1: visible element count, 2: total element count. */
			echo esc_html( sprintf( __( '%1$d of %2$d visible', 'affibloom' ), $elements->shown(), count( Elements::KEYS ) ) );
			?>
		</span>
	</summary>
	<div class="affibloom-accordion__body">
		<input type="hidden" name="display[elements_present]" value="1">
		<?php foreach ( Elements::KEYS as $element ) : ?>
			<?php $element_id = 'affibloom-display-element-' . $element; ?>
			<div class="affibloom-toggle-row">
				<span class="affibloom-toggle">
					<input type="checkbox" class="affibloom-toggle__input" role="switch" id="<?php echo esc_attr( $element_id ); ?>"
						name="display[elements][<?php echo esc_attr( $element ); ?>]" value="1" data-element <?php checked( $elements->visible( $element ) ); ?>>
					<span class="affibloom-toggle__track" aria-hidden="true"></span>
				</span>
				<label class="affibloom-toggle-row__label" for="<?php echo esc_attr( $element_id ); ?>"><?php echo esc_html( $element_labels[ $element ] ); ?></label>
			</div>
		<?php endforeach; ?>
	</div>
</details>

<?php foreach ( $groups as $group_key => $group ) : ?>
	<details class="affibloom-accordion" data-accordion="<?php echo esc_attr( $group_key ); ?>">
		<summary class="affibloom-accordion__toggle">
			<span><?php echo esc_html( $group['title'] ); ?></span>
			<span class="affibloom-accordion__summary" data-group-summary="<?php echo esc_attr( $group['fields'] ); ?>"
				data-format="<?php echo esc_attr( $group['format'] ); ?>"><?php echo esc_html( $group['summary'] ); ?></span>
		</summary>
		<div class="affibloom-accordion__body">
			<?php
			foreach ( $group['controls'] as $field => $control_label ) :
				$value    = $density->value( $field );
				$label_id = 'affibloom-density-' . $field . '-label';
				?>
				<?php if ( in_array( $field, Density::LINE_KEYS, true ) ) : ?>
					<fieldset class="affibloom-stepper-row">
						<legend class="affibloom-stepper-row__label"><?php echo esc_html( $control_label ); ?></legend>
						<div class="affibloom-clamp">
							<?php foreach ( Density::LINE_OPTIONS as $option ) : ?>
								<label class="affibloom-clamp__option">
									<input type="radio" name="display[density][values][<?php echo esc_attr( $field ); ?>]" value="<?php echo esc_attr( (string) $option ); ?>"
										data-density-field="<?php echo esc_attr( $field ); ?>" <?php checked( $value, $option ); ?>>
									<span><?php echo esc_html( 0 === $option ? __( 'All', 'affibloom' ) : (string) $option ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
					</fieldset>
				<?php else : ?>
					<?php $limits = Density::LIMITS[ $field ]; ?>
					<div class="affibloom-stepper-row">
						<span class="affibloom-stepper-row__label" id="<?php echo esc_attr( $label_id ); ?>"><?php echo esc_html( $control_label ); ?></span>
						<div class="affibloom-stepper" data-density-field="<?php echo esc_attr( $field ); ?>"
							data-min="<?php echo esc_attr( (string) $limits['min'] ); ?>" data-max="<?php echo esc_attr( (string) $limits['max'] ); ?>" data-step="<?php echo esc_attr( (string) $limits['step'] ); ?>">
							<button type="button" class="affibloom-stepper__button" data-step-dir="-1"
								aria-label="<?php echo esc_attr( sprintf( /* translators: %s: control label. */ __( 'Decrease %s', 'affibloom' ), $control_label ) ); ?>">&minus;</button>
							<output class="affibloom-stepper__value" aria-labelledby="<?php echo esc_attr( $label_id ); ?>" aria-live="polite"><?php echo esc_html( (string) $value ); ?></output>
							<button type="button" class="affibloom-stepper__button" data-step-dir="1"
								aria-label="<?php echo esc_attr( sprintf( /* translators: %s: control label. */ __( 'Increase %s', 'affibloom' ), $control_label ) ); ?>">+</button>
							<input type="hidden" name="display[density][values][<?php echo esc_attr( $field ); ?>]" value="<?php echo esc_attr( (string) $value ); ?>">
						</div>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	</details>
<?php endforeach; ?>
