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
 * The steps' Create buttons and the dialogs they open (Task 23E) are printed only for those who may manage products (F4 D8).
 *
 * @var array{steps: list<string>, can_manage: bool} $view
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
				<?php if ( $view['can_manage'] ) : ?>
					<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary affibloom-modal__head-action" data-create-link aria-haspopup="dialog">
						<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
						<span><?php esc_html_e( 'Create a Link', 'affibloom' ); ?></span>
					</button>
				<?php endif; ?>
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
				<?php if ( $view['can_manage'] ) : ?>
					<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary affibloom-modal__head-action" data-create-group aria-haspopup="dialog">
						<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
						<span><?php esc_html_e( 'Create Group', 'affibloom' ); ?></span>
					</button>
				<?php endif; ?>
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
						<?php if ( $view['can_manage'] ) : ?>
							<?php // The name opens "Add to Group" for the group (R17, R20). ?>
							<button type="button" class="affibloom-link-row__name affibloom-link-row__open" data-row-name data-row-open aria-haspopup="dialog"></button>
						<?php else : ?>
							<strong class="affibloom-link-row__name" data-row-name></strong>
						<?php endif; ?>
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
<?php
if ( ! $view['can_manage'] ) {
	return;
}
// "Add a New Group" (Task 23E; Lasso's #lasso-create-group-modal, R19): Name, Description, Save Changes.
?>
<div id="affibloom-create-group-modal" class="affibloom-admin affibloom-modal affibloom-block-modal affibloom-sub-modal" hidden data-create-group-modal
	data-required="<?php esc_attr_e( 'Name is required.', 'affibloom' ); ?>"
	data-failed="<?php esc_attr_e( 'Could not create the group. Please try again.', 'affibloom' ); ?>">
	<div class="affibloom-modal__backdrop" data-modal-close></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="affibloom-create-group-title">
		<button type="button" class="affibloom-modal__close" data-modal-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">
			<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
		</button>
		<div class="affibloom-modal__head">
			<h2 id="affibloom-create-group-title" class="affibloom-modal__title"><?php esc_html_e( 'Add a New Group', 'affibloom' ); ?></h2>
		</div>
		<form class="affibloom-modal__body affibloom-modal-form" id="affibloom-create-group-form" novalidate>
			<div class="affibloom-field">
				<label class="affibloom-label" for="affibloom-create-group-name"><?php esc_html_e( 'Name', 'affibloom' ); ?></label>
				<input type="text" class="affibloom-input" id="affibloom-create-group-name" name="name" placeholder="<?php esc_attr_e( 'Group Name Goes Here', 'affibloom' ); ?>" aria-describedby="affibloom-create-group-name-error">
				<p class="affibloom-field__error" id="affibloom-create-group-name-error" data-name-error role="alert" hidden></p>
			</div>
			<div class="affibloom-field">
				<label class="affibloom-label" for="affibloom-create-group-description"><?php esc_html_e( 'Description', 'affibloom' ); ?></label>
				<textarea class="affibloom-input affibloom-input--area" id="affibloom-create-group-description" name="description" rows="3"></textarea>
			</div>
			<p class="affibloom-field__error" data-form-error role="alert" hidden></p>
		</form>
		<div class="affibloom-modal__foot">
			<button type="submit" form="affibloom-create-group-form" class="affibloom-button affibloom-button--primary" data-create-save><?php esc_html_e( 'Save Changes', 'affibloom' ); ?></button>
		</div>
	</div>
</div>

<?php
// "Add to Group" (Task 23E; Lasso's #lasso-add-links-to-group-modal, R20-R23): the group's search of products, five a page.
?>
<div id="affibloom-add-to-group-modal" class="affibloom-admin affibloom-modal affibloom-block-modal affibloom-sub-modal" hidden data-add-to-group-modal
	data-empty="<?php esc_attr_e( 'No links found.', 'affibloom' ); ?>"
	data-failed="<?php esc_attr_e( 'Could not add link to this group. Please try again.', 'affibloom' ); ?>"
	data-added="<?php esc_attr_e( 'Added', 'affibloom' ); ?>"
	data-page-label="<?php /* translators: %d: a page number. */ esc_attr_e( 'Page %d', 'affibloom' ); ?>">
	<div class="affibloom-modal__backdrop" data-modal-close></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="affibloom-add-to-group-title" aria-describedby="affibloom-add-to-group-name">
		<button type="button" class="affibloom-modal__close" data-modal-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">
			<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
		</button>
		<div class="affibloom-modal__head">
			<h2 id="affibloom-add-to-group-title" class="affibloom-modal__title"><?php esc_html_e( 'Add to Group', 'affibloom' ); ?></h2>
			<button type="button" class="affibloom-button affibloom-button--small affibloom-button--primary affibloom-modal__head-action" data-add-group>
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
				<span><?php esc_html_e( 'Add Group', 'affibloom' ); ?></span>
			</button>
			<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-create-link aria-haspopup="dialog">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
				<span><?php esc_html_e( 'Create a Link', 'affibloom' ); ?></span>
			</button>
			<p class="affibloom-modal__intro" id="affibloom-add-to-group-name" data-group-name></p>
		</div>
		<div class="affibloom-modal__body">
			<label class="screen-reader-text" for="affibloom-add-to-group-search"><?php esc_html_e( 'Search URLs', 'affibloom' ); ?></label>
			<input type="search" id="affibloom-add-to-group-search" class="affibloom-input affibloom-search affibloom-modal-search" placeholder="<?php esc_attr_e( 'Search URLs', 'affibloom' ); ?>" data-step-search>
			<p class="affibloom-field__error affibloom-add-error" data-add-error role="alert" hidden></p>
			<ul class="affibloom-link-rows" data-step-list aria-live="polite"></ul>
			<p class="affibloom-link-rows__message" data-step-message role="status" hidden></p>
			<nav class="affibloom-pagination affibloom-modal-pages" aria-label="<?php esc_attr_e( 'Link pages', 'affibloom' ); ?>" data-step-pages
				data-previous="<?php esc_attr_e( 'Previous page', 'affibloom' ); ?>" data-next="<?php esc_attr_e( 'Next page', 'affibloom' ); ?>"></nav>
		</div>
		<div class="affibloom-modal__foot">
			<button type="button" class="affibloom-button affibloom-button--secondary" data-add-done><?php esc_html_e( 'Done', 'affibloom' ); ?></button>
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
				<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary affibloom-select-button" data-row-select>
					<?php $this->render( 'admin/partials/icon', array( 'name' => 'check' ) ); ?>
					<span data-select-label><?php esc_html_e( 'Select', 'affibloom' ); ?></span>
				</button>
			</li>
		</template>
	</div>
</div>

<?php
// "Add A New Link", the editor's variant (Task 23G; F18 D6, Lasso's url-add.php 127-206, R25): the URL tab only (F18 D5).
?>
<div id="affibloom-create-link-modal" class="affibloom-admin affibloom-modal affibloom-block-modal affibloom-sub-modal" hidden data-create-link-modal
	data-failed="<?php esc_attr_e( 'Could not add the link. Please try again.', 'affibloom' ); ?>">
	<div class="affibloom-modal__backdrop" data-modal-close></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="affibloom-create-link-title" aria-describedby="affibloom-create-link-intro">
		<button type="button" class="affibloom-modal__close" data-modal-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">
			<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
		</button>
		<div class="affibloom-modal__head">
			<h2 id="affibloom-create-link-title" class="affibloom-modal__title"><?php esc_html_e( 'Add A New Link', 'affibloom' ); ?></h2>
			<p id="affibloom-create-link-intro" class="affibloom-modal__intro"><?php esc_html_e( 'Enter the destination URL your Affibloom link will redirect to.', 'affibloom' ); ?></p>
		</div>
		<form class="affibloom-modal__body affibloom-modal-form" id="affibloom-create-link-form" novalidate>
			<div class="affibloom-field">
				<label class="screen-reader-text" for="affibloom-create-link-url"><?php esc_html_e( 'Destination URL', 'affibloom' ); ?></label>
				<input type="text" class="affibloom-input" id="affibloom-create-link-url" name="url" autocomplete="off" spellcheck="false"
					placeholder="https://www.example.com/affiliate-id" aria-describedby="affibloom-create-link-error">
				<p class="affibloom-field__error" id="affibloom-create-link-error" data-url-error role="alert" hidden></p>
			</div>
		</form>
		<div class="affibloom-modal__foot">
			<button type="submit" form="affibloom-create-link-form" class="affibloom-button affibloom-button--primary" data-create-link-save><?php esc_html_e( 'Add Link', 'affibloom' ); ?></button>
		</div>
	</div>
</div>

<?php
// The quick detail (Task 23G; Lasso's url-quick-detail.php, R26-R30): the product's page, framed to its form and live
// preview (AdminAssets::framed), so every field, help text, the preview and the Media Library are the product page's.
// + Add saves that form, then inserts the product, or adds it to the pending group; Cancel returns to the list.
?>
<div id="affibloom-quick-detail-modal" class="affibloom-admin affibloom-modal affibloom-block-modal affibloom-sub-modal affibloom-quick-detail" hidden data-quick-detail-modal
	data-product-page="<?php echo esc_url( add_query_arg( array( 'affibloom_frame' => '1' ), \Affibloom\Interface\Admin\ProductScreen::url( 0 ) ) ); ?>"
	data-frame-title="<?php esc_attr_e( 'The new product', 'affibloom' ); ?>"
	data-group-failed="<?php esc_attr_e( 'The link was saved but could not be added to the group. Please try again.', 'affibloom' ); ?>">
	<div class="affibloom-modal__backdrop" data-modal-close></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="affibloom-quick-detail-title">
		<button type="button" class="affibloom-modal__close" data-modal-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">
			<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
		</button>
		<div class="affibloom-modal__head">
			<h2 id="affibloom-quick-detail-title" class="affibloom-modal__title"><?php esc_html_e( 'Quick Detail', 'affibloom' ); ?></h2>
		</div>
		<div class="affibloom-quick-detail__frame" data-quick-frame></div>
		<div class="affibloom-modal__foot">
			<p class="affibloom-field__error affibloom-quick-detail__error" data-quick-error role="alert" hidden></p>
			<button type="button" class="affibloom-button affibloom-button--secondary" data-quick-cancel><?php esc_html_e( 'Cancel', 'affibloom' ); ?></button>
			<button type="button" class="affibloom-button affibloom-button--primary" data-quick-add>
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
				<span><?php esc_html_e( 'Add', 'affibloom' ); ?></span>
			</button>
		</div>
	</div>
</div>
