<?php
/**
 * The display modal (F4 D1): one modal for the block, the Classic editor's button and the Elementor widget, printed on
 * their screens by EditorAssets and driven by assets/editor/display-modal.js. Task 23A builds its frame and the first
 * step, Lasso's "Choose a Display Type" (display-add.php 13-74; R9): two rows of four, AI Text left out (B5). Each type
 * opens its step (Single, Button and Image the links, Grid, List and Gallery the groups, Table the tables), which the
 * tasks 23B-23F add as `<section data-step>` parts. Table waits for the Tables area (F25, Phase 7): shown, disabled,
 * "Soon". The look is the design system's Modal (Meadow); the root carries `.affibloom-admin` for its tokens.
 *
 * @package Affibloom
 * @var array{steps: list<string>} $view
 */

defined( 'ABSPATH' ) || exit;

$display_types = array(
	// [ type, label, icon, the step it opens or '' while its area is not built ].
	array( 'single', __( 'Single', 'affibloom' ), 'box', 'links' ),
	array( 'grid', __( 'Grid', 'affibloom' ), 'grid', 'groups' ),
	array( 'list', __( 'List', 'affibloom' ), 'list', 'groups' ),
	array( 'table', __( 'Table', 'affibloom' ), 'table', '' ),
	array( 'button', __( 'Button', 'affibloom' ), 'button', 'links' ),
	array( 'image', __( 'Image', 'affibloom' ), 'image', 'links' ),
	array( 'gallery', __( 'Gallery', 'affibloom' ), 'gallery', 'groups' ),
);
?>
<div id="affibloom-display-modal" class="affibloom-admin affibloom-modal affibloom-block-modal" hidden data-display-modal>
	<div class="affibloom-modal__backdrop" data-modal-close></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="affibloom-display-type-title">
		<button type="button" class="affibloom-modal__close" data-modal-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">
			<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
		</button>

		<section class="affibloom-modal-step" data-step="type" aria-labelledby="affibloom-display-type-title">
			<div class="affibloom-modal__head">
				<h2 id="affibloom-display-type-title" class="affibloom-modal__title"><?php esc_html_e( 'Choose a Display Type', 'affibloom' ); ?></h2>
			</div>
			<div class="affibloom-modal__body">
				<ul class="affibloom-type-tiles">
					<?php foreach ( $display_types as [ $display_type, $label, $icon, $step ] ) : ?>
						<?php $ready = '' !== $step && in_array( $step, $view['steps'], true ); ?>
						<li>
							<button type="button" class="affibloom-type-tile" data-type="<?php echo esc_attr( $display_type ); ?>" data-opens="<?php echo esc_attr( $step ); ?>"
								<?php echo $ready ? '' : 'aria-disabled="true" aria-describedby="affibloom-display-soon"'; ?>>
								<span class="affibloom-chip affibloom-type-tile__chip"><?php $this->render( 'admin/partials/icon', array( 'name' => $icon ) ); ?></span>
								<span class="affibloom-type-tile__name"><?php echo esc_html( $label ); ?></span>
								<?php
								if ( ! $ready ) {
									$this->render( 'admin/partials/soon', array( 'variant' => 'chip' ) );
								}
								?>
							</button>
						</li>
					<?php endforeach; ?>
				</ul>
				<span id="affibloom-display-soon" hidden><?php esc_html_e( 'Coming soon', 'affibloom' ); ?></span>
			</div>
		</section>
	</div>
</div>
