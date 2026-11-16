<?php
/**
 * The Display rail's theme row for one display type (F16; Lasso's display.php "Theme" select).
 *
 * @package Affibloom
 * @var array{config: \Affibloom\Domain\Display\DisplayConfig, name: string} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Display\Theme;
?>
<div class="affibloom-theme-row">
	<label class="affibloom-label" for="affibloom-display-theme"><?php esc_html_e( 'Theme', 'affibloom' ); ?></label>
	<?php
	$this->render(
		'admin/partials/help',
		array(
			'id'    => 'affibloom-display-theme-help',
			'text'  => __( 'Applies to the selected display type (Single, Grid, or List).', 'affibloom' ),
			'label' => __( 'Theme', 'affibloom' ),
		)
	);
	?>
	<select class="affibloom-input affibloom-select" id="affibloom-display-theme" name="<?php echo esc_attr( $view['name'] ); ?>[theme]"
		aria-describedby="affibloom-display-theme-help">
		<?php foreach ( Theme::NAMES as $theme_name ) : ?>
			<option value="<?php echo esc_attr( $theme_name ); ?>" <?php selected( $theme_name, $view['config']->theme()->name() ); ?>>
				<?php echo esc_html( $theme_name ); ?>
			</option>
		<?php endforeach; ?>
	</select>
</div>
