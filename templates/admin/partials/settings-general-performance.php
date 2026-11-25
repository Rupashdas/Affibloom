<?php
/**
 * Settings → General, Performance (F9 R22-R23; Task 9H): the CPU level that background work waits under (CpuGuard).
 * Lasso's Performance Mode presets are not here: the stored mode was read by nothing (removed 2026-11-25).
 *
 * @package Affibloom
 * @var array{settings: \Affibloom\Domain\Settings\Settings} $view
 */

defined( 'ABSPATH' ) || exit;

$settings = $view['settings'];
?>
<div class="affibloom-card" id="affibloom-card-performance">
	<div class="affibloom-card__head">
		<h2 class="affibloom-card__title"><?php esc_html_e( 'Performance', 'affibloom' ); ?></h2>
		<p class="affibloom-card__intro"><?php esc_html_e( 'Set the maximum CPU level that Affibloom will run at.', 'affibloom' ); ?></p>
	</div>
	<div class="affibloom-card__body affibloom-card__body--fields">
		<div class="affibloom-field">
			<span class="affibloom-field__label-row">
				<label class="affibloom-label" for="affibloom-setting-cpu_threshold"><?php esc_html_e( 'CPU level', 'affibloom' ); ?></label>
				<?php
				$this->render(
					'admin/partials/help',
					array(
						'id'    => 'affibloom-setting-cpu_threshold-help',
						'text'  => __( 'Affibloom will wait until your CPU drops below this level to continue with its updates.', 'affibloom' ),
						'label' => __( 'CPU level', 'affibloom' ),
					)
				);
				?>
			</span>
			<span class="affibloom-input-suffix">
				<input type="number" class="affibloom-input affibloom-input--number" id="affibloom-setting-cpu_threshold" name="settings[cpu_threshold]"
					value="<?php echo esc_attr( (string) $settings->int( 'cpu_threshold' ) ); ?>" min="1" max="100" step="1" aria-describedby="affibloom-setting-cpu_threshold-help">
				<span aria-hidden="true">%</span>
			</span>
		</div>
	</div>
</div>
