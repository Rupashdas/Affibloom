<?php
/**
 * Settings → General, Performance (F9 R22-R23; Task 9H): the mode, and the CPU level that only Custom shows. Choosing
 * a preset sets the CPU level (and, in the support view, the cron interval and process limit) when the mode is
 * changed; loading the page never rewrites a stored value (D13). The reader is Task F11's.
 *
 * @package Affibloom
 * @var array{settings: \Affibloom\Domain\Settings\Settings, refused: list<string>} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Settings\PerformanceMode;

$settings     = $view['settings'];
$current_mode = $settings->string( 'performance_mode' );
$cpu_refused  = in_array( 'cpu_threshold', $view['refused'], true );
$labels       = array(
	PerformanceMode::POWER    => __( 'Power', 'affibloom' ),
	PerformanceMode::BALANCED => __( 'Balanced', 'affibloom' ),
	PerformanceMode::LIGHT    => __( 'Light', 'affibloom' ),
	PerformanceMode::CUSTOM   => __( 'Custom', 'affibloom' ),
);
?>
<div class="affibloom-card" id="affibloom-card-performance">
	<div class="affibloom-card__head">
		<h2 class="affibloom-card__title"><?php esc_html_e( 'Performance', 'affibloom' ); ?></h2>
		<p class="affibloom-card__intro"><?php esc_html_e( 'Set the maximum CPU level that Affibloom will run at.', 'affibloom' ); ?></p>
	</div>
	<div class="affibloom-card__body affibloom-card__body--fields">
		<div class="affibloom-field">
			<span class="affibloom-field__label-row">
				<label class="affibloom-label" for="affibloom-setting-performance_mode"><?php esc_html_e( 'Performance Mode:', 'affibloom' ); ?></label>
				<?php
				$this->render(
					'admin/partials/help',
					array(
						'id'    => 'affibloom-setting-performance_mode-help',
						'text'  => __( 'Affibloom will wait until your CPU drops below this level to continue with its updates.', 'affibloom' ),
						'label' => __( 'Performance Mode', 'affibloom' ),
					)
				);
				?>
			</span>
			<select class="affibloom-input" id="affibloom-setting-performance_mode" name="settings[performance_mode]" data-performance-mode>
				<?php foreach ( $labels as $value => $label ) : ?>
					<?php $preset = PerformanceMode::preset( $value ); ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_mode, $value ); ?>
						<?php echo null === $preset ? '' : 'data-cpu="' . esc_attr( (string) $preset['cpu'] ) . '" data-interval="' . esc_attr( (string) $preset['interval'] ) . '" data-limit="' . esc_attr( (string) $preset['limit'] ) . '"'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each value is escaped above. ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="affibloom-field affibloom-field--spaced" data-cpu-field <?php echo PerformanceMode::CUSTOM === $current_mode ? '' : 'hidden'; ?>>
			<label class="affibloom-label" for="affibloom-setting-cpu_threshold"><?php esc_html_e( 'CPU level', 'affibloom' ); ?></label>
			<span class="affibloom-input-suffix">
				<input type="number" class="affibloom-input affibloom-input--number<?php echo $cpu_refused ? ' is-invalid' : ''; ?>" id="affibloom-setting-cpu_threshold" name="settings[cpu_threshold]"
					value="<?php echo esc_attr( (string) $settings->int( 'cpu_threshold' ) ); ?>" min="1" max="100" step="1" <?php echo $cpu_refused ? 'aria-invalid="true"' : ''; ?>>
				<span aria-hidden="true">%</span>
			</span>
		</div>
	</div>
</div>
