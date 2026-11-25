<?php
/**
 * Settings → General, Notifications (F9 R51-R54; Task 9K): the switch that turns off the "?" help tips, through a body
 * class from HelpTips. Lasso's Configure Amazon and Import notification switches are not here: no notice read them
 * (removed 2026-11-25).
 *
 * @package Affibloom
 * @var array{settings: \Affibloom\Domain\Settings\Settings} $view
 */

defined( 'ABSPATH' ) || exit;

$rows = array(
	'disable_help_tips' => __( 'Disable Help Tooltips', 'affibloom' ),
);
?>
<div class="affibloom-card" id="affibloom-card-notifications">
	<div class="affibloom-card__head">
		<h2 class="affibloom-card__title"><?php esc_html_e( 'Notifications', 'affibloom' ); ?></h2>
		<p class="affibloom-card__intro"><?php esc_html_e( 'Toggle which notifications you want enabled.', 'affibloom' ); ?></p>
	</div>
	<div class="affibloom-card__body affibloom-card__body--fields">
		<?php
		foreach ( $rows as $key => $label ) {
			$this->render(
				'admin/partials/switch-row',
				array(
					'id'      => 'affibloom-setting-' . $key,
					'name'    => 'settings[' . $key . ']',
					'label'   => $label,
					'checked' => $view['settings']->bool( $key ),
				)
			);
		}
		?>
	</div>
</div>
