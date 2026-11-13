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
 * @var array{tab: string, tabs: list<string>, type: \Affibloom\Domain\Display\DisplayType, types: list<\Affibloom\Domain\Display\DisplayType>, config: \Affibloom\Domain\Display\DisplayConfig, columns: int, action: string, reset: string, settings: \Affibloom\Domain\Settings\Settings, notice: string, panel: string} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Display\Theme;

$config   = $view['config'];
$settings = $view['settings'];
// The Disclosure element row writes the scope setting of the type being edited (requirements B5).
$disclosure_key = array(
	'single' => 'show_disclosure',
	'grid'   => 'show_disclosure_grid',
	'list'   => 'show_disclosure_list',
)[ $view['type']->key() ];
$panel          = $view['panel'];
$panels         = array(
	'layout' => __( 'Layout & spacing', 'affibloom' ),
	'colors' => __( 'Colors & labels', 'affibloom' ),
);
$palette        = $config->palette();
$type_key       = $view['type']->key();
$type_labels    = array(
	'single' => __( 'Single', 'affibloom' ),
	'grid'   => __( 'Grid', 'affibloom' ),
	'list'   => __( 'List', 'affibloom' ),
);

// Lasso 370's fields, labels and order (docs/spec/interface.md); help texts from Lasso's own form.
$fields = array(
	array( 'main', 'color', __( 'Badge', 'affibloom' ), __( 'This is the color of your badge background.', 'affibloom' ) ),
	array( 'title', 'color', __( 'Title', 'affibloom' ), __( 'This is the color of the title text of your display.', 'affibloom' ) ),
	array( 'background', 'color', __( 'Background', 'affibloom' ), __( 'This is the color of the inside of your display.', 'affibloom' ) ),
	array( 'button_text', 'color', __( 'Button + Badge Text', 'affibloom' ), __( 'This is text color for your badges and buttons.', 'affibloom' ) ),
	array( 'pros', 'color', __( 'Pros', 'affibloom' ), __( 'This is the main color of the Pros Field.', 'affibloom' ) ),
	array( 'cons', 'color', __( 'Cons', 'affibloom' ), __( 'This is the main color of the Cons Field.', 'affibloom' ) ),
	array( 'primary_button_text', 'text', __( 'Primary button text', 'affibloom' ), __( 'If you leave your display button text blank, this is what it will default to.', 'affibloom' ) ),
	array( 'button', 'color', __( 'Primary button color', 'affibloom' ), __( "This is the color of your display's main CTA button.", 'affibloom' ) ),
	array( 'secondary_button_text', 'text', __( 'Secondary button text', 'affibloom' ), __( 'If you set a secondary button for your display and leave it blank, this is what it will default to.', 'affibloom' ) ),
	array( 'secondary_button', 'color', __( 'Secondary button color', 'affibloom' ), __( "This is the color of your display's secondary CTA button.", 'affibloom' ) ),
);
?>
<div class="wrap affibloom-admin affibloom-admin--display">
	<?php $this->render( 'admin/partials/header', array( 'current' => 'settings' ) ); ?>

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
			<form class="affibloom-settings" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-save-when-changed>
				<input type="hidden" name="action" value="<?php echo esc_attr( $view['action'] ); ?>">
				<input type="hidden" name="type" value="<?php echo esc_attr( $type_key ); ?>">
				<?php wp_nonce_field( $view['action'] ); ?>

				<div class="affibloom-card affibloom-split">
					<div class="affibloom-split__body">
						<div class="affibloom-split__main">
							<div class="affibloom-split__toolbar">
								<nav class="affibloom-segment" aria-label="<?php esc_attr_e( 'Display type', 'affibloom' ); ?>">
									<?php foreach ( $view['types'] as $display_type ) : ?>
										<a class="affibloom-segment__item<?php echo $display_type->key() === $type_key ? ' is-active' : ''; ?>"
											href="<?php echo esc_url( admin_url( 'admin.php?page=affibloom-settings&tab=display&type=' . $display_type->key() . '&panel=' . $panel ) ); ?>"
											<?php echo $display_type->key() === $type_key ? 'aria-current="page"' : ''; ?>>
											<?php echo esc_html( $type_labels[ $display_type->key() ] ); ?>
										</a>
									<?php endforeach; ?>
								</nav>

								<?php // Lasso's device segment. It edits the phone layout now, and sizes the preview from Task 10D. ?>
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
									<div class="affibloom-split__frame">
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
									<select class="affibloom-input affibloom-select" id="affibloom-display-theme" name="display[theme]"
										aria-describedby="affibloom-display-theme-help">
										<?php foreach ( Theme::NAMES as $theme_name ) : ?>
											<option value="<?php echo esc_attr( $theme_name ); ?>" <?php selected( $theme_name, $config->theme()->name() ); ?>>
												<?php echo esc_html( $theme_name ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</div>

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
									$this->render(
										'admin/partials/display-layout-panel',
										array(
											'config'     => $config,
											'type'       => $view['type'],
											'columns'    => $view['columns'],
											'disclosure' => $settings->bool( $disclosure_key ),
											'mirror'     => 'affibloom-setting-' . $disclosure_key,
										)
									);
									?>
								</div>

								<div class="affibloom-rail-panel<?php echo 'colors' === $panel ? ' is-active' : ''; ?>" role="tabpanel"
									id="affibloom-panel-colors" aria-labelledby="affibloom-tab-colors" data-rail-panel="colors">
									<p class="affibloom-rail-hint"><?php esc_html_e( 'Colors and button labels apply to the selected display type (Single, Grid, or List).', 'affibloom' ); ?></p>
									<details class="affibloom-accordion" data-accordion="colors" open>
									<summary class="affibloom-accordion__toggle"><span><?php esc_html_e( 'Colors & labels', 'affibloom' ); ?></span></summary>
									<div class="affibloom-accordion__body">
									<?php
									foreach ( $fields as [ $key, $field_type, $label, $help ] ) :
										$field_id = 'affibloom-display-' . $key;
										$help_id  = $field_id . '-help';
										/* translators: %s: the colour field's label. */
										$picker_label = sprintf( __( 'Pick a colour for %s', 'affibloom' ), $label );
										?>
										<div class="affibloom-row affibloom-row--<?php echo esc_attr( $field_type ); ?>">
											<span class="affibloom-row__label">
												<label class="affibloom-label" for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label>
												<?php
												$this->render(
													'admin/partials/help',
													array(
														'id'    => $help_id,
														'text'  => $help,
														'label' => $label,
													)
												);
												?>
											</span>
											<span class="affibloom-row__control">
												<?php if ( 'color' === $field_type ) : ?>
													<?php // No name: the text field beside it is what gets saved, so a CSS keyword is never rewritten. ?>
													<input type="color" class="affibloom-swatch" data-affibloom-swatch="<?php echo esc_attr( $field_id ); ?>"
														value="#000000"
														aria-label="<?php echo esc_attr( $picker_label ); ?>">
												<?php endif; ?>
												<?php if ( 'text' === $field_type ) : ?>
													<?php // The button texts take an icon from the set (F28, Task 14G). ?>
													<?php $this->render( 'admin/partials/icon-text-field', $view['choices']->field( $field_id, 'display[palette][' . $key . ']', $label, $palette->value( $key ), '', $help_id ) ); ?>
												<?php else : ?>
													<input type="text" class="affibloom-input" id="<?php echo esc_attr( $field_id ); ?>"
														name="display[palette][<?php echo esc_attr( $key ); ?>]"
														value="<?php echo esc_attr( $palette->value( $key ) ); ?>"
														maxlength="500" aria-describedby="<?php echo esc_attr( $help_id ); ?>">
												<?php endif; ?>
											</span>
										</div>
									<?php endforeach; ?>
									</div>
									</details>

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
			<form id="affibloom-display-reset" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-reset-form
				data-confirm="<?php esc_attr_e( 'Reset all display settings to factory defaults? This cannot be undone.', 'affibloom' ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( $view['reset'] ); ?>">
				<input type="hidden" name="type" value="<?php echo esc_attr( $type_key ); ?>">
				<?php wp_nonce_field( $view['reset'], '_affibloom_reset_nonce', false ); ?>
			</form>

			<?php // Site-wide, so outside the per-type editor above (F28, Task 14F). ?>
			<?php $this->render( 'admin/partials/icon-set', array( 'icons' => $view['icons'] ) ); ?>
		</div>
	</div>

	<?php $this->render( 'admin/partials/toast', array( 'message' => $view['notice'] ) ); ?>
</div>
