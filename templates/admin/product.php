<?php
/**
 * Product page, Details tab: Lasso's link details page (url-details.php and dashboard/header.php) in Aurora's shell.
 * The band holds the name, the link under it and Lasso's four tabs; below, Lasso's two cards (the link on the left,
 * the display on the right) and its footer, the arrangement the user chose at gate 4 (F1 requirements, D1).
 *
 * Built in Task 14A: the band, the left card and the ways in. Placeholders until their tasks: the right card (14B),
 * custom fields (14C), Groups (the Groups area), Permalink (link cloaking), the Locations, Opportunities and Variants
 * tabs (their areas) and Delete This Link (Task 17). Save Changes stores the form through PATCH /products/{id}
 * (Task 15A, assets/admin/product-save.js).
 *
 * @package Affibloom
 * @var array{product: \Affibloom\Domain\Product\Product, button_text: string, button_text2: string, duplicate: bool, choices: \Affibloom\Interface\Admin\IconChoices, theme: string, themes: list<string>, disclosure: string, own_text: string, show_price: bool, amazon_tab: string, fields: \Affibloom\Domain\Fields\ProductFields, library: \Affibloom\Domain\Fields\FieldLibrary, types: list<\Affibloom\Domain\Fields\FieldType>, tracking: array{tag: string, product: int, settings: string}|null, notice: string} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Product\ProductType;
use Affibloom\Interface\Admin\ProductScreen;

$product = $view['product'];
// Lasso hides Permalink and Link Cloaking for an Amazon link, a search page included (url-details.php 194, 491).
$is_amazon = ProductType::Amazon === $product->type;
// D5: the link under the title is the destination until link cloaking exists (Lasso's rule when cloaking is off).
$destination = $product->url->value();
$soon_tabs   = array(
	__( 'Locations', 'affibloom' ),
	__( 'Opportunities', 'affibloom' ),
	__( 'Variants', 'affibloom' ),
);
?>
<div class="wrap affibloom-admin">
	<?php $this->render( 'admin/partials/header', array( 'current' => 'dashboard' ) ); ?>

	<div class="affibloom-page affibloom-page--wide affibloom-product-page">
		<div class="affibloom-band">
			<div class="affibloom-band__inner">
				<h1 class="affibloom-page__title affibloom-product__title" id="affibloom-product-title">
					<?php echo esc_html( '' !== $product->name ? $product->name : __( 'Enter a URL Name', 'affibloom' ) ); ?>
				</h1>
				<a class="affibloom-product-url" href="<?php echo esc_url( $destination ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $destination ); ?></a>

				<nav class="affibloom-tabs" aria-label="<?php esc_attr_e( 'Product sections', 'affibloom' ); ?>">
					<a class="affibloom-tabs__tab is-active" aria-current="page" href="<?php echo esc_url( ProductScreen::url( (int) $product->id ) ); ?>"><?php esc_html_e( 'Details', 'affibloom' ); ?></a>
					<?php foreach ( $soon_tabs as $tab_label ) : ?>
						<span class="affibloom-tabs__tab is-disabled" aria-disabled="true" aria-describedby="affibloom-soon-note">
							<?php echo esc_html( $tab_label ); ?>
							<?php $this->render( 'admin/partials/soon', array( 'variant' => 'chip' ) ); ?>
						</span>
					<?php endforeach; ?>
				</nav>
			</div>
		</div>

		<div class="affibloom-page__body affibloom-product">
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
			$this->render( 'admin/partials/share-kit' );
			?>

			<?php // Save Changes, or Enter in a text field, sends the form through product-save.js (Task 15A). ?>
			<form id="affibloom-product-form" class="affibloom-product__form" data-product-id="<?php echo esc_attr( (string) $product->id ); ?>" data-product-type="<?php echo esc_attr( $product->type->value ); ?>" novalidate
				data-saved-text="<?php esc_attr_e( 'Product saved.', 'affibloom' ); ?>"
				data-failed-text="<?php esc_attr_e( "Try again. For some reason your product didn't save.", 'affibloom' ); ?>"
				data-empty-name="<?php esc_attr_e( 'Enter a URL Name', 'affibloom' ); ?>">
				<div class="affibloom-card affibloom-product__card affibloom-product__card--link">
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
								'value'       => '',
								'help'        => __( 'This slug will be used to cloak the original target URL', 'affibloom' ),
								'placeholder' => 'affiliate-name',
								'soon'        => true,
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
						data-up-format="<?php /* translators: %s: a button's name, e.g. Button 2. */ echo esc_attr__( 'Move %s up', 'affibloom' ); ?>"
						data-down-format="<?php /* translators: %s: a button's name, e.g. Button 2. */ echo esc_attr__( 'Move %s down', 'affibloom' ); ?>"
						data-remove-format="<?php /* translators: %s: a button's name, e.g. Button 2. */ echo esc_attr__( 'Remove %s', 'affibloom' ); ?>"
						data-confirm="<?php esc_attr_e( 'Remove this button? Its link and text are gone once you save.', 'affibloom' ); ?>">
						<input type="hidden" name="primary_position" value="<?php echo esc_attr( (string) $position ); ?>" data-main-position>
						<div class="affibloom-buttons__list" data-button-list>
							<?php
							foreach ( $blocks as $number => $button_block ) {
								$this->render(
									'admin/partials/product-button',
									$button_block + array(
										'number'      => $number + 1,
										// The primary text falls back to the type's primary text; every other to the secondary's (F29 D2).
										'placeholder' => $button_block['main'] ? $view['button_text'] : $view['button_text2'],
										'choices'     => $view['choices'],
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

					<?php // Groups: a placeholder until the Groups area (Lasso: a multi-select that can create a group). ?>
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
							$this->render( 'admin/partials/soon', array( 'variant' => 'chip' ) );
							?>
						</div>
						<select class="affibloom-input" id="affibloom-groups" aria-describedby="affibloom-groups-help" disabled>
							<option><?php esc_html_e( 'Select a group', 'affibloom' ); ?></option>
						</select>
					</div>

					<?php // Custom fields (Task 14C): Lasso's dashed box with the product's fields, then "Add Custom Field". ?>
					<div class="affibloom-product__fields" data-product-fields data-product-id="<?php echo esc_attr( (string) $product->id ); ?>">
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
				</div>

				<?php
				// The preview and the display fields (Task 14B).
				$this->render(
					'admin/partials/product-display-card',
					array(
						'product'    => $product,
						'theme'      => $view['theme'],
						'themes'     => $view['themes'],
						'disclosure' => $view['disclosure'],
						'own_text'   => $view['own_text'],
						'show_price' => $view['show_price'],
						'amazon_tab' => $view['amazon_tab'],
						'choices'    => $view['choices'],
					)
				);
				?>
			</form>

			<?php // Lasso's footer: Delete This Link on the left, Save Changes on the right ("Learn About This Page" is Lasso's help site). ?>
			<div class="affibloom-product__footer">
				<?php // Task 17A: Lasso's url-delete dialog first; its Delete removes the product for good (F1 D33). ?>
				<button type="button" class="affibloom-link-button affibloom-link-button--danger" id="affibloom-delete" data-affibloom-open="affibloom-delete-modal">
					<?php $this->render( 'admin/partials/icon', array( 'name' => 'trash' ) ); ?>
					<span><?php esc_html_e( 'Delete This Link', 'affibloom' ); ?></span>
				</button>
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
	<?php $this->render( 'admin/partials/toast', array( 'message' => $view['notice'] ) ); ?>
	<?php $this->render( 'admin/partials/toast-template' ); ?>
</div>
