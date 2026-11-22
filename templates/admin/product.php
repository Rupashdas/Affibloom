<?php
/**
 * Product page, Details tab: Lasso's link details page (url-details.php and dashboard/header.php) in Meadow's shell
 * (Task MD5, design system 0b.4). The head holds "‹ Dashboard", the image, the name, the link under it and Lasso's four
 * tabs; below, two columns, 58% / 42%: the form in cards that keep Lasso's reading order (its left column, then its
 * right one) and, in the sticky aside, the live preview. Lasso's footer (Delete This Link, Save Changes) is a bar stuck
 * to the foot of the window. The arrangement of the fields is the one the user chose at gate 4 (F1 requirements, D1).
 *
 * Built in Task 14A: the head, the left card and the ways in. Placeholders until their tasks: the right card (14B),
 * custom fields (14C), Groups (the Groups area), Permalink (link cloaking), the Locations, Opportunities and Variants
 * tabs (their areas) and Delete This Link (Task 17). Save Changes stores the form through PATCH /products/{id}
 * (Task 15A, assets/admin/product-save.js).
 *
 * @package Affibloom
 * @var array{product: \Affibloom\Domain\Product\Product, tabs: array{label: string, items: list<array{label: string, url?: string, current?: bool, count?: int, show_zero?: bool}>}, button_text: string, button_text2: string, duplicate: bool, choices: \Affibloom\Interface\Admin\IconChoices, theme: string, themes: list<string>, disclosure: string, own_text: string, show_price: bool, amazon_tab: string, can_refresh: bool, amazon_card: bool, amazon_row: \Affibloom\Domain\Amazon\AmazonProduct|null, fields: \Affibloom\Domain\Fields\ProductFields, library: \Affibloom\Domain\Fields\FieldLibrary, types: list<\Affibloom\Domain\Fields\FieldType>, tracking: array{tag: string, product: int, settings: string}|null, notice: string} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Product\ProductType;
use Affibloom\Interface\Admin\AdminMenu;

$product = $view['product'];
// Lasso hides Permalink and Link Cloaking for an Amazon link, a search page included (url-details.php 194, 491).
$is_amazon = ProductType::Amazon === $product->type;
// F24 R13, R14 (Task CL3): the link under the title is the public link, cloaked or the destination.
$public_link = $view['public_link'];
// The Primary Destination URL as stored: the default button's link.
$destination = $product->url->value();
?>
<div class="wrap affibloom-admin">
	<?php $this->render( 'admin/partials/header' ); ?>

	<div class="affibloom-page affibloom-product-page">
		<?php
		// The way back, the image, the name, the link under it and Lasso's four tabs as underline tabs across the column;
		// Locations and Opportunities come with their areas.
		$this->render(
			'admin/partials/page-head',
			array(
				'title'       => '' !== $product->name ? $product->name : __( 'Enter a URL Name', 'affibloom' ),
				'title_id'    => 'affibloom-product-title',
				'title_class' => 'affibloom-product__title',
				'tooltip'     => true,
				'back'        => array(
					'url'   => admin_url( 'admin.php?page=' . AdminMenu::SLUG ),
					'label' => __( 'Dashboard', 'affibloom' ),
				),
				'image'       => $product->image_url,
				'link'        => $public_link,
				'below'       => 'admin/partials/section-tabs',
				// Lasso's four tabs (ProductScreen::tabs(): Variants with its count, F27 R4).
				'below_args'  => $view['tabs'],
			)
		);
		?>

		<div class="affibloom-page__body affibloom-product">
			<?php
			if ( null !== ( $view['issues'] ?? null ) ) {
				$this->render(
					'admin/partials/issue-notice',
					array(
						'issues'     => $view['issues'],
						'product_id' => (int) $product->id,
					)
				);
			}
			?>
			<?php if ( $view['duplicate'] ) : ?>
				<?php // Lasso's warning after "Add A New Link" finds this product (url-details.php 2597-2600). ?>
				<div class="affibloom-notice affibloom-notice--warning" role="status">
					<span class="affibloom-chip affibloom-chip--warning"><?php $this->render( 'admin/partials/icon', array( 'name' => 'warning' ) ); ?></span>
					<p class="affibloom-notice__text"><?php esc_html_e( 'This product already exists. Please update the Primary URL or add a new link.', 'affibloom' ); ?></p>
				</div>
			<?php endif; ?>
			<?php
			if ( null !== $view['tracking'] ) {
				$this->render( 'admin/partials/tracking-id-notice', $view['tracking'] );
			}
			// Lasso 370 opens its page with the Share kit (Task 14D).
			$this->render(
				'admin/partials/share-kit',
				array(
					'shortcode' => \Affibloom\Interface\ShortcodeText::product( (int) $product->id ),
					'block'     => \Affibloom\Interface\ShortcodeText::block( (int) $product->id ),
				)
			);
			?>

			<?php // MD5: two columns, the form and the Amazon data on the left, the sticky live preview on the right. ?>
			<div class="affibloom-product__grid">
				<div class="affibloom-product__main">
					<?php // Save Changes, or Enter in a text field, sends the form through product-save.js (Task 15A). ?>
					<form id="affibloom-product-form" class="affibloom-product__form" data-product-id="<?php echo esc_attr( (string) $product->id ); ?>" data-product-type="<?php echo esc_attr( $product->type->value ); ?>" novalidate
						data-saved-text="<?php esc_attr_e( 'Product saved.', 'affibloom' ); ?>"
						data-failed-text="<?php esc_attr_e( "Try again. For some reason your product didn't save.", 'affibloom' ); ?>"
						data-empty-name="<?php esc_attr_e( 'Enter a URL Name', 'affibloom' ); ?>" data-cloak-base="<?php echo esc_attr( $view['cloak_base'] ); ?>">
						<section class="affibloom-card affibloom-product__card affibloom-product__card--link" aria-labelledby="affibloom-card-links">
							<h2 class="affibloom-card__title" id="affibloom-card-links"><?php esc_html_e( 'Links & buttons', 'affibloom' ); ?></h2>
							<?php
							$this->render(
								'admin/partials/field-text',
								array(
									'id'          => 'affibloom-name',
									'name'        => 'name',
									'label'       => __( 'Name', 'affibloom' ),
									'value'       => $product->name,
									'help'        => __( 'This title will only be shown in displays', 'affibloom' ),
									'placeholder' => __( 'URL Name Goes Here', 'affibloom' ),
								)
							);

							if ( ! $is_amazon ) {
								$this->render(
									'admin/partials/field-text',
									array(
										'id'          => 'affibloom-permalink',
										'name'        => 'permalink',
										'label'       => __( 'Permalink', 'affibloom' ),
										'value'       => $product->slug,
										'help'        => __( 'This slug will be used to cloak the original target URL', 'affibloom' ),
										'placeholder' => 'affiliate-name',
									)
								);
							}
							?>

							<?php
							// The buttons (F29, Task 14H; the user, 2026-11-13): one by default, the product's own link; "Add Another Button" adds
							// more. Every button moves up and down, the default one too; the first in the list is the box's primary button. Without
							// JavaScript one empty added block follows the list, so a button can be added one save at a time.
							$buttons  = $product->buttons;
							$added    = $buttons->all();
							$position = $buttons->main_position();
							$blocks   = array();
							foreach ( $added as $index => $added_button ) {
								$blocks[] = array(
									'main'     => false,
									'index'    => $index,
									'key'      => (string) $index,
									'url'      => $added_button->url,
									'text'     => $added_button->text,
									'new_tab'  => $added_button->new_tab,
									'nofollow' => $added_button->nofollow,
								);
							}
							array_splice(
								$blocks,
								$position,
								0,
								array(
									array(
										'main'     => true,
										'index'    => 0,
										'key'      => 'main',
										'url'      => $destination,
										'text'     => $product->button_text,
										'new_tab'  => $product->new_tab,
										'nofollow' => $product->nofollow,
									),
								)
							);
							$empty_block = array(
								'main'        => false,
								'url'         => '',
								'text'        => '',
								'new_tab'     => true,
								'nofollow'    => true,
								'placeholder' => $view['button_text2'],
								'choices'     => $view['choices'],
							);
							?>
							<div class="affibloom-buttons" data-buttons
								data-legend-format="<?php /* translators: %s: a button's place in the box, e.g. 2. */ echo esc_attr__( 'Button %s', 'affibloom' ); ?>"
								data-grip-format="<?php /* translators: %s: a button's name, e.g. Button 2. */ echo esc_attr__( 'Reorder %s', 'affibloom' ); ?>"
								data-remove-format="<?php /* translators: %s: a button's name, e.g. Button 2. */ echo esc_attr__( 'Remove %s', 'affibloom' ); ?>">
								<input type="hidden" name="primary_position" value="<?php echo esc_attr( (string) $position ); ?>" data-main-position>
								<div class="affibloom-buttons__list" data-button-list>
									<?php
									foreach ( $blocks as $number => $button_block ) {
										$this->render(
											'admin/partials/product-button',
											$button_block + array(
												'number'  => $number + 1,
												// The primary text falls back to the type's primary text; every other to the secondary's (F29 D2).
												'placeholder' => $button_block['main'] ? $view['button_text'] : $view['button_text2'],
												'choices' => $view['choices'],
											)
										);
									}
									?>
								</div>
								<noscript>
									<?php
									$this->render(
										'admin/partials/product-button',
										$empty_block + array(
											'index'  => count( $added ),
											'key'    => 'new',
											'number' => count( $blocks ) + 1,
										)
									);
									?>
								</noscript>
								<template id="affibloom-extra-button-template">
									<?php
									$this->render(
										'admin/partials/product-button',
										$empty_block + array(
											'index'  => '__i__',
											'key'    => '__k__',
											'number' => '__n__',
										)
									);
									?>
								</template>
								<button type="button" class="affibloom-button affibloom-button--secondary affibloom-buttons__add" data-add-button hidden>
									<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
									<span><?php esc_html_e( 'Add Another Button', 'affibloom' ); ?></span>
								</button>
							</div>

						</section>

						<section class="affibloom-card affibloom-product__card affibloom-product__card--link" aria-labelledby="affibloom-card-organize">
							<h2 class="affibloom-card__title" id="affibloom-card-organize"><?php esc_html_e( 'Organize', 'affibloom' ); ?></h2>
							<?php // Groups (Task G3): Lasso's multi-select that can create a group, as the group picker (F23 D8). ?>
							<div class="affibloom-field">
								<div class="affibloom-field__head">
									<label class="affibloom-label" for="affibloom-groups"><?php esc_html_e( 'Groups', 'affibloom' ); ?></label>
									<?php
									$this->render(
										'admin/partials/help',
										array(
											'id'    => 'affibloom-groups-help',
											'text'  => __( 'Group this URL for better organization and to use in group displays', 'affibloom' ),
											'label' => __( 'Groups', 'affibloom' ),
										)
									);
									?>
								</div>
								<?php
								$this->render(
									'admin/partials/group-picker',
									array(
										'id'           => 'affibloom-groups',
										'groups'       => $view['chosen'],
										'label_id'     => 'affibloom-groups-label',
										'described_by' => 'affibloom-groups-help',
									)
								);
								?>
							</div>
							<?php $this->render( 'admin/partials/group-choices', array( 'groups' => $view['group_list'] ) ); ?>

							<?php // Custom fields (Task 14C): Lasso's dashed box with the product's fields, then "Add Custom Field". ?>
							<div class="affibloom-product__fields" data-product-fields data-product-id="<?php echo esc_attr( (string) $product->id ); ?>"
								data-save-failed="<?php esc_attr_e( 'Could not save the field. Please try again.', 'affibloom' ); ?>"
								data-add-failed="<?php esc_attr_e( 'Could not add the field. Please try again.', 'affibloom' ); ?>">
								<div class="affibloom-field-list" data-field-list>
									<?php
									$this->render(
										'admin/partials/product-fields',
										array(
											'product' => (int) $product->id,
											'fields'  => $view['fields'],
											'library' => $view['library'],
										)
									);
									?>
								</div>
								<button type="button" class="affibloom-button affibloom-button--primary" data-affibloom-open="affibloom-field-modal" aria-haspopup="dialog">
									<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
									<span><?php esc_html_e( 'Add Custom Field', 'affibloom' ); ?></span>
								</button>
							</div>
						</section>

						<?php
						// The display fields (Task 14B) in two cards; the preview is in the aside.
						$display_card = array(
							'product'     => $product,
							'theme'       => $view['theme'],
							'themes'      => $view['themes'],
							'disclosure'  => $view['disclosure'],
							'own_text'    => $view['own_text'],
							'show_price'  => $view['show_price'],
							'amazon_tab'  => $view['amazon_tab'],
							'can_refresh' => $view['can_refresh'],
							'choices'     => $view['choices'],
							'amazon_row'  => $view['amazon_row'],
						);
						$this->render( 'admin/partials/product-display-card', array( 'part' => 'fields' ) + $display_card );
						?>
					</form>

					<?php
					if ( $view['amazon_card'] ) {
						// What is stored from Amazon for this product (C1Q5), read-only and outside the form.
						$this->render( 'admin/partials/amazon-data-card', array( 'row' => $view['amazon_row'] ) );
					}
					?>
				</div>
				<?php // The live preview (Task 14E) stays in view while the form scrolls; it still opens the Media Library. ?>
				<aside class="affibloom-product__aside" aria-label="<?php esc_attr_e( 'Live preview', 'affibloom' ); ?>">
					<?php $this->render( 'admin/partials/product-display-card', array( 'part' => 'preview' ) + $display_card ); ?>
				</aside>
			</div>

			<?php
			// Lasso's footer: Delete This Link on the left, Save Changes on the right ("Learn About This Page" is Lasso's help
			// site), as a bar stuck to the foot of the window (MD5); it says "Unsaved changes" while the form differs from
			// what is stored (product-save.js sets data-dirty on the form).
			?>
			<div class="affibloom-product__footer">
				<?php // Task 17A: Lasso's url-delete dialog first; its Delete removes the product for good (F1 D33). In use, "Hold Up" instead (Task 36E). ?>
				<button type="button" class="affibloom-link-button affibloom-link-button--danger" id="affibloom-delete" data-affibloom-open="<?php echo esc_attr( empty( $view['in_use'] ) ? 'affibloom-delete-modal' : 'affibloom-holdup-product' ); ?>">
					<?php $this->render( 'admin/partials/icon', array( 'name' => 'trash' ) ); ?>
					<span><?php esc_html_e( 'Delete This Link', 'affibloom' ); ?></span>
				</button>
				<span class="affibloom-product__status">
					<span class="affibloom-product__status-dot" aria-hidden="true"></span>
					<?php esc_html_e( 'Unsaved changes', 'affibloom' ); ?>
				</span>
				<?php // Outside the form, as Lasso's footer is, and still its submit button, so Enter in a field saves too. ?>
				<button type="submit" form="affibloom-product-form" id="affibloom-save" class="affibloom-button affibloom-button--primary">
					<?php esc_html_e( 'Save Changes', 'affibloom' ); ?>
				</button>
			</div>
			<span id="affibloom-soon-note" hidden><?php esc_html_e( 'Coming soon', 'affibloom' ); ?></span>
		</div>
	</div>
	<?php $this->render( 'admin/partials/field-modal', array( 'types' => $view['types'] ) ); ?>
	<?php $this->render( 'admin/partials/save-modal' ); ?>
	<?php $this->render( 'admin/partials/unsaved-modal' ); ?>
	<?php
	$this->render(
		'admin/partials/delete-modal',
		array(
			'id'    => 'affibloom-delete-modal',
			'title' => __( 'Delete This Link?', 'affibloom' ),
			'text'  => __( "If deleted, you won't be able to get it back.", 'affibloom' ),
		)
	);
	?>
	<?php
	if ( ! empty( $view['in_use'] ) ) {
		$this->render(
			'admin/partials/holdup-modal',
			array(
				'id'     => 'affibloom-holdup-product',
				'text'   => __( "You can't delete a URL if it has active links or displays.", 'affibloom' ),
				'hosts'  => $view['in_use'],
				'more'   => $view['in_use_url'],
				'button' => __( 'Keep This Link', 'affibloom' ),
			)
		);
	}
	?>
	<?php // Task 2X1: an added button asks through the page's own dialog, as the delete does, never the browser's. ?>
	<?php
	$this->render(
		'admin/partials/delete-modal',
		array(
			'id'            => 'affibloom-button-remove-modal',
			'title'         => __( 'Remove this button?', 'affibloom' ),
			'text'          => __( 'Its link and text are gone once you save.', 'affibloom' ),
			'confirm_label' => __( 'Remove', 'affibloom' ),
			'confirm_attr'  => 'data-remove-confirm',
			'busy_label'    => __( 'Removing…', 'affibloom' ),
			'failed_text'   => __( 'Could not remove the button. Please try again.', 'affibloom' ),
		)
	);
	?>
	<?php $this->render( 'admin/partials/toast', array( 'message' => $view['notice'] ) ); ?>
	<?php $this->render( 'admin/partials/toast-template' ); ?>
	<?php $this->render( 'admin/partials/footer' ); ?>
</div>
