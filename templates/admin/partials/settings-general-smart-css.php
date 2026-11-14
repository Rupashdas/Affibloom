<?php
/**
 * Settings → General, Smart CSS Loading (F9 R19; Task 9G). One switch; the box's stylesheet reads it (Task F5).
 *
 * @package Affibloom
 * @var array{settings: \Affibloom\Domain\Settings\Settings} $view
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="affibloom-card" id="affibloom-card-smart-css">
	<div class="affibloom-card__head">
		<h2 class="affibloom-card__title"><?php esc_html_e( 'Smart CSS Loading', 'affibloom' ); ?></h2>
		<p class="affibloom-card__intro"><?php esc_html_e( 'Skip display CSS on pages without Affibloom displays or tables to improve page speed. Purge page cache after enabling.', 'affibloom' ); ?></p>
	</div>
	<div class="affibloom-card__body affibloom-card__body--fields">
		<?php
		$this->render(
			'admin/partials/switch-row',
			array(
				'id'      => 'affibloom-setting-smart_css',
				'name'    => 'settings[smart_css]',
				'label'   => __( 'Enable Smart CSS Loading', 'affibloom' ),
				'checked' => $view['settings']->bool( 'smart_css' ),
			)
		);
		?>
	</div>
</div>
