<?php
/**
 * The table builder's inspector (deviation 151): the panel beside the canvas (under it on narrow screens) that shows
 * what the selection can change. table-builder-inspector.js shows one part at a time and fills it from the canvas:
 * - nothing selected: how to select and drag;
 * - a product (R21, R23): its name and page, its badge (an icon from the library and words), Remove;
 * - a field: its name and kind, that it applies to every product, Remove from every product (R31); for a button, the
 *   selected product's text with its icon and address (R35: the Primary's is the product's link, read only);
 * - a row or column (R33): its fields, Remove.
 * The icon picker (icon-popover.js) reads the library from `data-icons`: each icon's class, name and picture.
 *
 * @package Affibloom
 * @var array{icons: list<array{class: string, name: string, src: string, missing: bool}>, can_add: bool} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\ProductScreen;

$icon_input = static function ( string $id, string $label, string $placeholder ): void {
	?>
	<div class="affibloom-icon-input" data-icon-input>
		<?php /* translators: 1: what the icon is for (Badge, Button Text), 2: the icon's name or "No icon". */ ?>
		<button type="button" class="affibloom-icon-input__icon" data-icon-choose aria-haspopup="dialog" data-label-format="<?php echo esc_attr__( '%1$s icon: %2$s', 'affibloom' ); ?>" data-label="<?php echo esc_attr( $label ); ?>">
			<span class="affibloom-icon-input__glyph" data-icon-glyph></span>
		</button>
		<input type="text" id="<?php echo esc_attr( $id ); ?>" class="affibloom-input affibloom-icon-input__words" data-icon-words placeholder="<?php echo esc_attr( $placeholder ); ?>" autocomplete="off">
	</div>
	<?php
};
?>
<aside class="affibloom-card affibloom-table-inspector" data-table-inspector aria-labelledby="affibloom-table-inspector-title"
	data-icons="<?php echo esc_attr( (string) wp_json_encode( $view['icons'] ) ); ?>" data-can-add-icon="<?php echo $view['can_add'] ? '1' : '0'; ?>"
	data-no-icon="<?php esc_attr_e( 'No icon', 'affibloom' ); ?>" data-product-url="<?php echo esc_url( ProductScreen::url( 0 ) ); ?>"
	data-icon-dialog="<?php esc_attr_e( 'Choose an icon', 'affibloom' ); ?>" data-icon-search="<?php esc_attr_e( 'Search icons', 'affibloom' ); ?>"
	data-icon-empty="<?php esc_attr_e( 'No icons match.', 'affibloom' ); ?>" data-icon-add="<?php esc_attr_e( 'Add icon', 'affibloom' ); ?>"
	data-icon-media="<?php esc_attr_e( 'Select an icon', 'affibloom' ); ?>" data-icon-media-button="<?php esc_attr_e( 'Use this icon', 'affibloom' ); ?>"
	data-icon-failed="<?php esc_attr_e( 'Could not add the icon. Please try again.', 'affibloom' ); ?>"
	<?php /* translators: %s: the product's name. */ ?>
	data-button-for="<?php esc_attr_e( 'For %s. Each product has its own button text and address.', 'affibloom' ); ?>"
	data-button-saved="<?php esc_attr_e( 'Button saved.', 'affibloom' ); ?>">
	<template data-inspector-badge-none>
		<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
		<span><?php esc_html_e( 'Badge', 'affibloom' ); ?></span>
	</template>
	<template data-inspector-unknown-icon>
		<span class="affibloom-table-icon affibloom-table-icon--unknown"><?php $this->render( 'admin/partials/icon', array( 'name' => 'warning' ) ); ?></span>
	</template>
	<div class="affibloom-table-inspector__head">
		<h2 id="affibloom-table-inspector-title" class="affibloom-table-inspector__title" data-inspector-title data-default="<?php esc_attr_e( 'Customize', 'affibloom' ); ?>"><?php esc_html_e( 'Customize', 'affibloom' ); ?></h2>
		<button type="button" class="affibloom-icon-button" data-inspector-clear hidden aria-label="<?php esc_attr_e( 'Clear the selection', 'affibloom' ); ?>" title="<?php esc_attr_e( 'Clear the selection', 'affibloom' ); ?>">
			<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
		</button>
	</div>

	<div class="affibloom-table-inspector__part" data-inspector-part="none">
		<p class="affibloom-table-inspector__lead"><?php esc_html_e( 'Select a product, a field or a row to customize it.', 'affibloom' ); ?></p>
		<ul class="affibloom-table-inspector__tips">
			<li><?php esc_html_e( 'Drag a product, a row or a field by its grip to move it; the others make room.', 'affibloom' ); ?></li>
			<li><?php esc_html_e( 'Drop a field in another row to move it there for every product.', 'affibloom' ); ?></li>
			<li><?php esc_html_e( 'Click a badge or a button to give it an icon.', 'affibloom' ); ?></li>
			<li><?php esc_html_e( 'With the keyboard: focus a grip, press Space, move with the arrow keys, press Space again.', 'affibloom' ); ?></li>
		</ul>
	</div>

	<div class="affibloom-table-inspector__part" data-inspector-part="product" hidden>
		<p class="affibloom-table-inspector__subject">
			<strong data-inspector-product-name></strong>
			<a class="affibloom-table-inspector__link" data-inspector-product-link href="#" target="_blank" rel="noopener">
				<?php esc_html_e( 'Edit product', 'affibloom' ); ?>
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'new-tab' ) ); ?>
			</a>
		</p>
		<div class="affibloom-field">
			<label class="affibloom-label" for="affibloom-inspector-badge"><?php esc_html_e( 'Badge', 'affibloom' ); ?></label>
			<?php $icon_input( 'affibloom-inspector-badge', __( 'Badge', 'affibloom' ), __( 'Best Overall', 'affibloom' ) ); ?>
			<p class="affibloom-field__hint"><?php esc_html_e( 'Shown above the product. Saved as you leave the box or choose an icon.', 'affibloom' ); ?></p>
		</div>
		<button type="button" class="affibloom-button affibloom-button--secondary affibloom-table-inspector__danger" data-inspector-remove-product>
			<?php $this->render( 'admin/partials/icon', array( 'name' => 'trash' ) ); ?>
			<span><?php esc_html_e( 'Remove from table', 'affibloom' ); ?></span>
		</button>
	</div>

	<div class="affibloom-table-inspector__part" data-inspector-part="field" hidden>
		<p class="affibloom-table-inspector__subject">
			<strong data-inspector-field-name></strong>
			<span class="affibloom-table-inspector__kind" data-inspector-field-kind></span>
		</p>
		<form class="affibloom-table-inspector__button" data-inspector-button hidden novalidate>
			<p class="affibloom-field__hint" data-inspector-button-for></p>
			<div class="affibloom-field">
				<label class="affibloom-label" for="affibloom-inspector-button-text"><?php esc_html_e( 'Button Text', 'affibloom' ); ?></label>
				<?php $icon_input( 'affibloom-inspector-button-text', __( 'Button Text', 'affibloom' ), __( 'Buy Now', 'affibloom' ) ); ?>
				<p class="affibloom-field__error" data-error-for="button_text" hidden></p>
			</div>
			<div class="affibloom-field">
				<label class="affibloom-label" for="affibloom-inspector-button-url"><?php esc_html_e( 'Button URL', 'affibloom' ); ?></label>
				<input type="url" id="affibloom-inspector-button-url" class="affibloom-input" name="button_url" autocomplete="off">
				<p class="affibloom-field__hint" data-inspector-primary-hint hidden><?php esc_html_e( "The Primary Button goes to the product's link.", 'affibloom' ); ?></p>
				<p class="affibloom-field__error" data-error-for="button_url" hidden></p>
			</div>
			<p class="affibloom-field__error" data-inspector-button-error role="alert" hidden></p>
			<button type="submit" class="affibloom-button affibloom-button--primary" data-busy-label="<?php esc_attr_e( 'Saving…', 'affibloom' ); ?>"><?php esc_html_e( 'Save button', 'affibloom' ); ?></button>
		</form>
		<p class="affibloom-field__hint"><?php esc_html_e( 'A field is the same for every product: moving or removing it changes them all.', 'affibloom' ); ?></p>
		<button type="button" class="affibloom-button affibloom-button--secondary affibloom-table-inspector__danger" data-inspector-remove-field>
			<?php $this->render( 'admin/partials/icon', array( 'name' => 'trash' ) ); ?>
			<span><?php esc_html_e( 'Remove from every product', 'affibloom' ); ?></span>
		</button>
	</div>

	<div class="affibloom-table-inspector__part" data-inspector-part="row" hidden>
		<p class="affibloom-table-inspector__subject"><strong data-inspector-row-name></strong></p>
		<p class="affibloom-field__hint"><?php esc_html_e( 'Drag its grip to move it. Its fields keep their values if it is removed.', 'affibloom' ); ?></p>
		<button type="button" class="affibloom-button affibloom-button--secondary affibloom-table-inspector__danger" data-inspector-remove-row>
			<?php $this->render( 'admin/partials/icon', array( 'name' => 'trash' ) ); ?>
			<span data-inspector-row-remove><?php esc_html_e( 'Remove', 'affibloom' ); ?></span>
		</button>
	</div>
	<?php if ( $view['can_add'] ) : ?>
		<p class="affibloom-table-inspector__manage">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=affibloom-settings&tab=display' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Manage icons', 'affibloom' ); ?></a>
		</p>
	<?php endif; ?>
</aside>
