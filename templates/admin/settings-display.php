<?php
/**
 * Settings → Display: one configuration per display type (docs/features/F16/requirements.md).
 *
 * The structure is Lasso's (the user, 2026-11-13, amending ADR-8): a split card with the preview and its
 * toolbar on the left and the settings rail on the right, holding the theme row, the section tabs and the
 * label-left / control-right rows. Only the colours and the typography are Affibloom's.
 *
 * The rail has Lasso's two tabs, Layout & spacing first. Task 10C adds the Layout accordion, Task 10E the
 * site-wide behaviour, and Task 10D the preview itself.
 *
 * @package Affibloom
 * @var array{tab: string, tabs: list<string>, type: \Affibloom\Domain\Display\DisplayType, types: list<\Affibloom\Domain\Display\DisplayType>, configs: array<string, \Affibloom\Domain\Display\DisplayConfig>, columns: int, choices: \Affibloom\Interface\Admin\IconChoices, icons: list<array<string, mixed>>, settings: \Affibloom\Domain\Settings\Settings, notice: string, panel: string} $view
 */

defined( 'ABSPATH' ) || exit;

$settings    = $view['settings'];
$panel       = $view['panel'];
$panels      = array(
	'layout' => __( 'Layout & spacing', 'affibloom' ),
	'colors' => __( 'Colors & labels', 'affibloom' ),
);
$type_key    = $view['type']->key();
$type_labels = array(
	'single' => __( 'Single', 'affibloom' ),
	'grid'   => __( 'Grid', 'affibloom' ),
	'list'   => __( 'List', 'affibloom' ),
);
// The Disclosure element row writes the scope setting of its type (requirements B5).
$disclosure_keys = array(
	'single' => 'show_disclosure',
	'grid'   => 'show_disclosure_grid',
	'list'   => 'show_disclosure_list',
);

/*
 * Each type's part of the rail, live for the type shown and in a <template> for the others (deviation 144). Each type's
 * fields are named `display[<type>][…]`, so one save carries every type that was edited.
 */
$template = $this;
$regions  = static function ( string $region, string $partial, callable $data ) use ( $template, $view, $type_key ): void {
	foreach ( $view['types'] as $display_type ) {
		$key = $display_type->key();
		$template->render(
			'admin/partials/display-type-region',
			array(
				'region'  => $region,
				'type'    => $key,
				'live'    => $key === $type_key,
				'partial' => $partial,
				'data'    => $data( $display_type, $view['configs'][ $key ], 'display[' . $key . ']' ),
			)
		);
	}
};
?>
<div class="wrap affibloom-admin affibloom-admin--display">
	<?php $this->render( 'admin/partials/header' ); ?>

	<div class="affibloom-page">
		<?php
		$this->render(
			'admin/partials/settings-band',
			array(
				'tab'  => $view['tab'],
				'tabs' => $view['tabs'],
			)
		);
		?>

		<div class="affibloom-page__body">
			<?php // Saved over REST, staying on the page (settings-save.js; deviations 141, 143). ?>
			<form class="affibloom-settings" method="post" data-save-when-changed data-unsaved-guard data-rest-save="/affibloom/v1/settings/display"
				data-failed-text="<?php esc_attr_e( "Try again. For some reason your settings didn't save.", 'affibloom' ); ?>">
				<input type="hidden" name="type" value="<?php echo esc_attr( $type_key ); ?>" data-display-type-field>

				<div class="affibloom-card affibloom-split">
					<div class="affibloom-split__body">
						<div class="affibloom-split__main">
							<div class="affibloom-split__toolbar">
								<nav class="affibloom-segment" aria-label="<?php esc_attr_e( 'Display type', 'affibloom' ); ?>">
									<?php foreach ( $view['types'] as $display_type ) : ?>
										<a class="affibloom-segment__item<?php echo $display_type->key() === $type_key ? ' is-active' : ''; ?>" data-display-type="<?php echo esc_attr( $display_type->key() ); ?>"
											href="<?php echo esc_url( admin_url( 'admin.php?page=affibloom-settings&tab=display&type=' . $display_type->key() . '&panel=' . $panel ) ); ?>"
											<?php echo $display_type->key() === $type_key ? 'aria-current="page"' : ''; ?>>
											<?php echo esc_html( $type_labels[ $display_type->key() ] ); ?>
										</a>
									<?php endforeach; ?>
								</nav>

								<?php // Lasso's device segment: it edits the phone layout and sizes the preview (Task 10D). ?>
								<div class="affibloom-segment affibloom-segment--icons" role="group" aria-label="<?php esc_attr_e( 'Preview device width', 'affibloom' ); ?>" data-device-toggle hidden>
									<button type="button" class="affibloom-segment__item is-active" data-device="desktop" aria-pressed="true" title="<?php esc_attr_e( 'Desktop (1280px)', 'affibloom' ); ?>">
										<?php $this->render( 'admin/partials/icon', array( 'name' => 'desktop' ) ); ?>
										<span class="screen-reader-text"><?php esc_html_e( 'Desktop (1280px)', 'affibloom' ); ?></span>
									</button>
									<button type="button" class="affibloom-segment__item" data-device="mobile" aria-pressed="false" title="<?php esc_attr_e( 'Mobile (390px)', 'affibloom' ); ?>">
										<?php $this->render( 'admin/partials/icon', array( 'name' => 'phone' ) ); ?>
										<span class="screen-reader-text"><?php esc_html_e( 'Mobile (390px)', 'affibloom' ); ?></span>
									</button>
								</div>
							</div>

							<div class="affibloom-split__preview">
								<div class="affibloom-split__stage">
									<?php // Loading until the first box is drawn: the placeholder shows only when none could be (display-settings.js). ?>
									<div class="affibloom-split__frame is-loading" data-preview-frame
										data-preview-title="<?php esc_attr_e( 'Display preview', 'affibloom' ); ?>"
										data-preview-error="<?php esc_attr_e( 'The preview could not be drawn. Try again.', 'affibloom' ); ?>">
										<?php $this->render( 'admin/partials/box-skeleton' ); ?>
										<div class="affibloom-empty">
											<span class="affibloom-chip"><?php $this->render( 'admin/partials/icon', array( 'name' => 'tag' ) ); ?></span>
											<p class="affibloom-empty__message"><?php esc_html_e( 'The box preview appears here.', 'affibloom' ); ?></p>
										</div>
									</div>
								</div>
							</div>
						</div>

						<aside class="affibloom-split__rail">
							<div class="affibloom-split__rail-head">
								<?php
								$regions(
									'theme',
									'admin/partials/display-theme-row',
									static fn( $display_type, $config, $name ): array => array(
										'config' => $config,
										'name'   => $name,
									)
								);
								?>

								<input type="hidden" name="panel" value="<?php echo esc_attr( $panel ); ?>" data-rail-panel-field>
								<div class="affibloom-rail-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Display settings', 'affibloom' ); ?>">
									<?php foreach ( $panels as $panel_key => $panel_label ) : ?>
										<button type="button" class="affibloom-rail-tabs__tab<?php echo $panel === $panel_key ? ' is-active' : ''; ?>" role="tab"
											id="affibloom-tab-<?php echo esc_attr( $panel_key ); ?>" aria-controls="affibloom-panel-<?php echo esc_attr( $panel_key ); ?>"
											aria-selected="<?php echo $panel === $panel_key ? 'true' : 'false'; ?>"
											<?php echo $panel === $panel_key ? '' : 'tabindex="-1"'; ?> data-rail-tab="<?php echo esc_attr( $panel_key ); ?>">
											<?php echo esc_html( $panel_label ); ?>
										</button>
									<?php endforeach; ?>
								</div>
							</div>

							<div class="affibloom-split__rail-scroll">
								<div class="affibloom-rail-panel<?php echo 'layout' === $panel ? ' is-active' : ''; ?>" role="tabpanel"
									id="affibloom-panel-layout" aria-labelledby="affibloom-tab-layout" data-rail-panel="layout">
									<?php
									$regions(
										'layout',
										'admin/partials/display-layout-panel',
										static fn( $display_type, $config, $name ): array => array(
											'config'     => $config,
											'type'       => $display_type,
											'name'       => $name,
											'columns'    => $view['columns'],
											'disclosure' => $settings->bool( $disclosure_keys[ $display_type->key() ] ),
											'mirror'     => 'affibloom-setting-' . $disclosure_keys[ $display_type->key() ],
										)
									);
									?>
								</div>

								<div class="affibloom-rail-panel<?php echo 'colors' === $panel ? ' is-active' : ''; ?>" role="tabpanel"
									id="affibloom-panel-colors" aria-labelledby="affibloom-tab-colors" data-rail-panel="colors">
									<p class="affibloom-rail-hint"><?php esc_html_e( 'Colors and button labels apply to the selected display type (Single, Grid, or List).', 'affibloom' ); ?></p>
									<?php
									$regions(
										'colors',
										'admin/partials/display-colors-accordion',
										static fn( $display_type, $config, $name ): array => array(
											'config'  => $config,
											'name'    => $name,
											'choices' => $view['choices'],
										)
									);
									?>

									<?php $this->render( 'admin/partials/display-behaviour-accordions', array( 'settings' => $settings ) ); ?>
								</div>
							</div>

							<?php // Lasso keeps Reset and Save together at the foot of the rail (display.php 737-742). ?>
							<div class="affibloom-rail-save">
								<button type="submit" form="affibloom-display-reset" class="affibloom-button affibloom-button--secondary" data-reset-button hidden>
									<?php esc_html_e( 'Reset Settings', 'affibloom' ); ?>
								</button>
								<button type="submit" id="affibloom-settings-save" class="affibloom-button affibloom-button--primary"><?php esc_html_e( 'Save Changes', 'affibloom' ); ?></button>
							</div>
						</aside>
					</div>
				</div>
			</form>

			<?php // A form of its own: the reset button above submits it through its form attribute. ?>
			<?php // Every field changes, so after the reset the screen loads what is stored (settings-save.js). ?>
			<form id="affibloom-display-reset" method="post" data-reset-form data-rest-save="/affibloom/v1/settings/display/reset"
				data-confirm="<?php esc_attr_e( 'Reset all display settings to factory defaults? This cannot be undone.', 'affibloom' ); ?>"
				data-save-title="<?php esc_attr_e( 'Resetting Display Settings', 'affibloom' ); ?>"
				data-failed-text="<?php esc_attr_e( 'Error resetting display settings.', 'affibloom' ); ?>">
				<input type="hidden" name="type" value="<?php echo esc_attr( $type_key ); ?>" data-display-type-field>
			</form>

			<?php // Site-wide, so outside the per-type editor above (F28, Task 14F). ?>
			<?php $this->render( 'admin/partials/icon-set', array( 'icons' => $view['icons'] ) ); ?>
		</div>
	</div>

	<?php
	$this->render( 'admin/partials/save-modal' );
	$this->render( 'admin/partials/unsaved-modal' );
	$this->render( 'admin/partials/toast', array( 'message' => $view['notice'] ) );
	$this->render( 'admin/partials/toast-template' );
	?>
	<?php $this->render( 'admin/partials/footer' ); ?>
</div>
