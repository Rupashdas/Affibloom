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

		<?php
		// "Choose a Link" (Task 23B; display-add.php 76-103, R12-R15): Single, Button and Image pick a product here. Five a
		// page, newest changed first; the search runs on Enter only, spaces as wildcards (GET /products).
		?>
		<section class="affibloom-modal-step" data-step="links" aria-labelledby="affibloom-display-links-title" hidden
			data-empty="<?php esc_attr_e( 'No links found.', 'affibloom' ); ?>"
			data-failed="<?php esc_attr_e( 'Could not load links. Please try again.', 'affibloom' ); ?>"
			data-page-label="<?php /* translators: %d: a page number. */ esc_attr_e( 'Page %d', 'affibloom' ); ?>">
			<div class="affibloom-modal__head">
				<h2 id="affibloom-display-links-title" class="affibloom-modal__title"><?php esc_html_e( 'Choose a Link', 'affibloom' ); ?></h2>
			</div>
			<div class="affibloom-modal__body">
				<label class="screen-reader-text" for="affibloom-display-links-search"><?php esc_html_e( 'Search URLs', 'affibloom' ); ?></label>
				<input type="search" id="affibloom-display-links-search" class="affibloom-input affibloom-search affibloom-modal-search" placeholder="<?php esc_attr_e( 'Search URLs', 'affibloom' ); ?>" data-step-search>
				<ul class="affibloom-link-rows" data-step-list aria-live="polite"></ul>
				<p class="affibloom-link-rows__message" data-step-message role="status" hidden></p>
				<nav class="affibloom-pagination affibloom-modal-pages" aria-label="<?php esc_attr_e( 'Link pages', 'affibloom' ); ?>" data-step-pages
					data-previous="<?php esc_attr_e( 'Previous page', 'affibloom' ); ?>" data-next="<?php esc_attr_e( 'Next page', 'affibloom' ); ?>"></nav>
			</div>
			<template data-step-row>
				<li class="affibloom-link-row">
					<span class="affibloom-link-row__picture">
						<img alt="" width="44" height="44" loading="lazy" data-row-image hidden>
						<span data-row-none><?php $this->render( 'admin/partials/icon', array( 'name' => 'image' ) ); ?></span>
					</span>
					<span class="affibloom-link-row__text">
						<strong class="affibloom-link-row__name" data-row-name></strong>
						<a class="affibloom-link-row__url" target="_blank" rel="noopener noreferrer" data-row-url></a>
					</span>
					<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-row-add>
						<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
						<span><?php esc_html_e( 'Add', 'affibloom' ); ?></span>
					</button>
				</li>
			</template>
		</section>

		<?php
		// "Choose a Group" (Task 23D; display-add.php 105-131, R17-R18): Grid, List and Gallery show a group. Five a page with
		// each group's link count; the search runs on Enter only (GET /groups).
		/* translators: %s: how many links a group holds (one). */
		$group_one = _n( '%s link', '%s links', 1, 'affibloom' );
		/* translators: %s: how many links a group holds (several). */
		$group_many = _n( '%s link', '%s links', 2, 'affibloom' );
		?>
		<section class="affibloom-modal-step" data-step="groups" aria-labelledby="affibloom-display-groups-title" hidden
			data-empty="<?php esc_attr_e( 'No groups found.', 'affibloom' ); ?>"
			data-failed="<?php esc_attr_e( 'Could not load groups. Please try again.', 'affibloom' ); ?>"
			data-page-label="<?php /* translators: %d: a page number. */ esc_attr_e( 'Page %d', 'affibloom' ); ?>"
			data-one="<?php echo esc_attr( $group_one ); ?>" data-many="<?php echo esc_attr( $group_many ); ?>">
			<div class="affibloom-modal__head">
				<h2 id="affibloom-display-groups-title" class="affibloom-modal__title"><?php esc_html_e( 'Choose a Group', 'affibloom' ); ?></h2>
			</div>
			<div class="affibloom-modal__body">
				<label class="screen-reader-text" for="affibloom-display-groups-search"><?php esc_html_e( 'Search groups', 'affibloom' ); ?></label>
				<input type="search" id="affibloom-display-groups-search" class="affibloom-input affibloom-search affibloom-modal-search" placeholder="<?php esc_attr_e( 'Search groups', 'affibloom' ); ?>" data-step-search>
				<ul class="affibloom-link-rows" data-step-list aria-live="polite"></ul>
				<p class="affibloom-link-rows__message" data-step-message role="status" hidden></p>
				<nav class="affibloom-pagination affibloom-modal-pages" aria-label="<?php esc_attr_e( 'Group pages', 'affibloom' ); ?>" data-step-pages
					data-previous="<?php esc_attr_e( 'Previous page', 'affibloom' ); ?>" data-next="<?php esc_attr_e( 'Next page', 'affibloom' ); ?>"></nav>
			</div>
			<template data-step-row>
				<li class="affibloom-link-row affibloom-group-row">
					<span class="affibloom-link-row__picture affibloom-group-row__chip"><?php $this->render( 'admin/partials/icon', array( 'name' => 'layers' ) ); ?></span>
					<span class="affibloom-link-row__text">
						<strong class="affibloom-link-row__name" data-row-name></strong>
						<span class="affibloom-group-row__count" data-row-count></span>
					</span>
					<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-row-add>
						<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
						<span><?php esc_html_e( 'Add', 'affibloom' ); ?></span>
					</button>
				</li>
			</template>
		</section>
	</div>
</div>
