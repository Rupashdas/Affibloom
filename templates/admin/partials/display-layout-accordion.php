<?php
/**
 * The rail's Layout accordion: Lasso's rows, their visibility rules and the phone layout.
 *
 * Lasso's structure and wording (admin/views/settings/display.php 415-498, settings.js 1405-1430): open by
 * default; Columns only for Grid; Card layout hidden for Grid (always stacked); Image position offers the pair
 * that belongs to the orientation; Mobile overflow only for List while phones are being edited. The radios post
 * the desktop layout, so the form works without JavaScript; the script switches the rows to the phone layout
 * when the toolbar's device toggle says Mobile, and writes the phone overrides into the hidden fields below.
 *
 * @package Affibloom
 * @var array{layout: \Affibloom\Domain\Display\Layout, type: \Affibloom\Domain\Display\DisplayType, columns: int} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Display\DisplayType;
use Affibloom\Domain\Display\Layout;

$layout  = $view['layout'];
$is_grid = $view['type']->is( DisplayType::grid() );
$is_list = $view['type']->is( DisplayType::list() );
$phone   = $layout->phone();

$rows = array(
	'orientation'    => array(
		'label'   => __( 'Card layout', 'affibloom' ),
		'options' => array(
			'stacked'      => __( 'Stacked', 'affibloom' ),
			'side-by-side' => __( 'Side by side', 'affibloom' ),
		),
	),
	'imagePosition'  => array(
		'label'   => __( 'Image position', 'affibloom' ),
		'options' => array(
			'left'   => __( 'Left', 'affibloom' ),
			'right'  => __( 'Right', 'affibloom' ),
			'top'    => __( 'Top', 'affibloom' ),
			'bottom' => __( 'Bottom', 'affibloom' ),
		),
	),
	'align'          => array(
		'label'   => __( 'Text alignment', 'affibloom' ),
		'options' => array(
			'left'   => __( 'Left', 'affibloom' ),
			'center' => __( 'Center', 'affibloom' ),
			'right'  => __( 'Right', 'affibloom' ),
		),
	),
	'buttonAlign'    => array(
		'label'   => __( 'Button alignment', 'affibloom' ),
		'options' => array(
			'spread' => __( 'Spread', 'affibloom' ),
			'left'   => __( 'Left', 'affibloom' ),
			'center' => __( 'Center', 'affibloom' ),
			'right'  => __( 'Right', 'affibloom' ),
		),
	),
	'ctaWidth'       => array(
		'label'   => __( 'Button width', 'affibloom' ),
		'options' => array(
			'hug'  => __( 'Hug', 'affibloom' ),
			'full' => __( 'Full width', 'affibloom' ),
		),
	),
	'mobileOverflow' => array(
		'label'   => __( 'Mobile overflow', 'affibloom' ),
		'options' => array(
			'stack' => __( 'Stack', 'affibloom' ),
			'swipe' => __( 'Swipe row', 'affibloom' ),
		),
	),
	'imageFit'       => array(
		'label'   => __( 'Image fit', 'affibloom' ),
		'options' => array(
			'contain' => __( 'Contain', 'affibloom' ),
			'cover'   => __( 'Fill', 'affibloom' ),
		),
	),
);

// Lasso's summary: "3 col · side by side · image left" (the columns part on Grid only).
$summary_parts   = $is_grid ? array( sprintf( /* translators: %d: grid columns. */ __( '%d col', 'affibloom' ), $view['columns'] ) ) : array();
$summary_parts[] = 'side-by-side' === $layout->value( 'orientation' ) ? __( 'side by side', 'affibloom' ) : __( 'stacked', 'affibloom' );
/* translators: %s: image position (left, right, top or bottom). */
$summary_parts[] = sprintf( __( 'image %s', 'affibloom' ), $layout->value( 'imagePosition' ) );
?>
<details class="affibloom-accordion" data-accordion="layout" open>
	<summary class="affibloom-accordion__toggle">
		<span><?php esc_html_e( 'Layout', 'affibloom' ); ?></span>
		<span class="affibloom-accordion__summary" data-layout-summary
			data-columns-format="<?php echo esc_attr( /* translators: %d: grid columns. */ __( '%d col', 'affibloom' ) ); ?>"
			data-side="<?php esc_attr_e( 'side by side', 'affibloom' ); ?>" data-stacked="<?php esc_attr_e( 'stacked', 'affibloom' ); ?>"
			data-image-format="<?php echo esc_attr( /* translators: %s: image position. */ __( 'image %s', 'affibloom' ) ); ?>">
			<?php echo esc_html( implode( ' · ', $summary_parts ) ); ?>
		</span>
	</summary>
	<div class="affibloom-accordion__body" data-layout data-grid="<?php echo $is_grid ? '1' : '0'; ?>" data-list="<?php echo $is_list ? '1' : '0'; ?>"
		data-desktop-orientation="<?php echo esc_attr( $layout->value( 'orientation' ) ); ?>">
		<div class="affibloom-layout-note" data-phone-note hidden>
			<span class="affibloom-layout-note__text" data-phone-note-text
				data-inherit="<?php esc_attr_e( 'Same as desktop', 'affibloom' ); ?>" data-custom="<?php esc_attr_e( 'Custom mobile layout', 'affibloom' ); ?>"></span>
			<?php
			$this->render(
				'admin/partials/help',
				array(
					'id'    => 'affibloom-layout-phone-help',
					'text'  => __( "Changes here apply to phones only and desktop edits won't change them. Settings you haven't changed here follow desktop.", 'affibloom' ),
					'label' => __( 'Mobile layout', 'affibloom' ),
				)
			);
			?>
			<button type="button" class="affibloom-layout-note__reset" data-phone-reset hidden><?php esc_html_e( 'Use desktop layout', 'affibloom' ); ?></button>
		</div>

		<input type="hidden" name="display[layout][phone_present]" value="1">
		<?php foreach ( Layout::PHONE_KEYS as $phone_key ) : ?>
			<input type="hidden" name="display[layout][phone][<?php echo esc_attr( $phone_key ); ?>]" data-phone-field="<?php echo esc_attr( $phone_key ); ?>"
				value="<?php echo esc_attr( $phone[ $phone_key ] ?? '' ); ?>" <?php disabled( ! isset( $phone[ $phone_key ] ) ); ?>>
		<?php endforeach; ?>

		<?php if ( $is_grid ) : ?>
			<fieldset class="affibloom-stepper-row" data-layout-row="columns">
				<legend class="affibloom-stepper-row__label"><?php esc_html_e( 'Columns', 'affibloom' ); ?></legend>
				<div class="affibloom-options">
					<?php for ( $column = 1; $column <= 3; $column++ ) : ?>
						<label class="affibloom-options__option">
							<input type="radio" name="display[columns]" value="<?php echo esc_attr( (string) $column ); ?>" data-columns <?php checked( $view['columns'], $column ); ?>>
							<span><?php echo esc_html( (string) $column ); ?></span>
						</label>
					<?php endfor; ?>
				</div>
			</fieldset>
		<?php endif; ?>

		<?php
		foreach ( $rows as $key => $row ) :
			// A grid card is always stacked, and only List has a mobile overflow (settings.js 1412-1418).
			if ( ( 'orientation' === $key && $is_grid ) || ( 'mobileOverflow' === $key && ! $is_list ) ) {
				continue;
			}
			?>
			<fieldset class="affibloom-stepper-row" data-layout-row="<?php echo esc_attr( $key ); ?>" <?php echo 'mobileOverflow' === $key ? 'hidden' : ''; ?>>
				<legend class="affibloom-stepper-row__label"><?php echo esc_html( $row['label'] ); ?></legend>
				<div class="affibloom-options">
					<?php foreach ( $row['options'] as $value => $option_label ) : ?>
						<?php
						// Image position offers the pair that belongs to the orientation; the script swaps the pair.
						$pair   = in_array( $value, array( 'left', 'right' ), true ) ? 'side-by-side' : 'stacked';
						$hidden = 'imagePosition' === $key && $pair !== $layout->value( 'orientation' );
						?>
						<label class="affibloom-options__option" <?php echo 'imagePosition' === $key ? 'data-orientation="' . esc_attr( $pair ) . '"' : ''; ?> <?php echo $hidden ? 'hidden' : ''; ?>>
							<input type="radio" name="display[layout][desktop][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>"
								data-layout-field="<?php echo esc_attr( $key ); ?>" <?php checked( $layout->value( $key ), $value ); ?>>
							<span><?php echo esc_html( $option_label ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
			</fieldset>
		<?php endforeach; ?>
	</div>
</details>
