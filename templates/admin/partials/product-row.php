<?php
/**
 * One Dashboard row, in Lasso's order (admin/views/rows/url-row.php): select, Image, Link Name, Groups, Alert,
 * Locations, Clicks, Actions. Lasso keeps the select box under the image; here it has its own cell, which gives
 * it a target of its own and a label.
 *
 * The Alert cell is link health's (alert-cell.php, Task 31A). Locations is the product's count in the link index, a link to
 * its Locations tab, or Lasso's clock while the first full index has not finished (F12 L1-L3, D20, D21; Task 36A).
 * Placeholder until its tasks: Clicks (Tasks 33-34). Shortcode copies `[affibloom id]` (F31 R30, Task 18S10). The name and the image open the product page
 * (Task 14A), as Lasso's do; the link under the name opens the public link (F24 R14; Task CL7): the cloaked address
 * of a cloaked product, else the destination.
 *
 * Edit opens Lasso's quick edit (url-row.php 12-67; F1 requirements 10, Task 16A): the row itself becomes a form with
 * Name, Primary Destination URL, Groups, the link and the Image frame. The form is printed here, in a template after
 * the row, with the values as stored (Q3, D26), so dashboard.js builds no markup; it puts the template's row in place
 * of this one and back.
 *
 * @package Affibloom
 * @var array{product: \Affibloom\Domain\Product\Product, groups: list<\Affibloom\Domain\Group\Group>, link?: string, alert?: \Affibloom\Domain\LinkHealth\AlertState|null, locations?: int|null, known?: bool} $view
 */

defined( 'ABSPATH' ) || exit;

$product      = $view['product'];
$product_page = \Affibloom\Interface\Admin\ProductScreen::url( (int) $product->id );
/* translators: %s: product name. */
$select_label = sprintf( __( 'Select %s', 'affibloom' ), $product->name );
$product_id   = (string) $product->id;
$quick_id     = 'affibloom-quick-' . $product_id;
$shortcode    = \Affibloom\Interface\ShortcodeText::product( (int) $product->id );
$public_link  = $view['link'] ?? $product->url->value();
?>
<tr data-product-id="<?php echo esc_attr( (string) $product->id ); ?>">
	<td class="affibloom-col-select">
		<input type="checkbox" class="affibloom-check" value="<?php echo esc_attr( (string) $product->id ); ?>"
			aria-label="<?php echo esc_attr( $select_label ); ?>">
	</td>
	<td class="affibloom-col-image">
		<?php // A second way to the same page, so it is skipped by the keyboard and by screen readers. ?>
		<a class="affibloom-thumb-link" href="<?php echo esc_url( $product_page ); ?>" tabindex="-1" aria-hidden="true">
			<?php if ( '' !== $product->image_url ) : ?>
				<img class="affibloom-thumb" src="<?php echo esc_url( $product->image_url ); ?>" alt="" width="40" height="40" loading="lazy" decoding="async">
			<?php else : ?>
				<span class="affibloom-thumb affibloom-thumb--none" title="<?php esc_attr_e( 'No image', 'affibloom' ); ?>">
					<?php $this->render( 'admin/partials/icon', array( 'name' => 'image' ) ); ?>
				</span>
			<?php endif; ?>
		</a>
	</td>
	<td class="affibloom-col-name">
		<a class="affibloom-row__name" href="<?php echo esc_url( $product_page ); ?>" title="<?php echo esc_attr( $product->name ); ?>"><?php echo esc_html( $product->name ); ?></a>
		<a class="affibloom-row__url" href="<?php echo esc_url( $public_link ); ?>" target="_blank"
			rel="noopener noreferrer" title="<?php echo esc_attr( $public_link ); ?>"><?php echo esc_html( $public_link ); ?></a>
	</td>
	<?php // The product's group names, comma-separated, the whole list as the cell's title (Lasso's view-js.php 365-366; F23 R16). ?>
	<?php $group_names = implode( ', ', array_map( static fn( $group ): string => wp_specialchars_decode( $group->name, ENT_QUOTES ), $view['groups'] ) ); ?>
	<td class="affibloom-col-groups" data-groups title="<?php echo esc_attr( $group_names ); ?>">
		<?php if ( array() === $view['groups'] ) : ?>
			<span class="affibloom-soon-cell"><span aria-hidden="true">—</span><span class="screen-reader-text"><?php esc_html_e( 'No groups', 'affibloom' ); ?></span></span>
		<?php else : ?>
			<span class="affibloom-groups-cell"><?php echo esc_html( $group_names ); ?></span>
		<?php endif; ?>
	</td>
	<td class="affibloom-col-alert">
		<?php
		if ( isset( $view['alert'] ) ) {
			$this->render(
				'admin/partials/alert-cell',
				array(
					'alert'        => $view['alert'],
					'product_page' => $product_page,
				)
			);
		}
		?>
	</td>
	<td class="affibloom-col-locations">
		<?php if ( ! isset( $view['locations'] ) ) : ?>
			<?php $this->render( 'admin/partials/soon', array( 'variant' => 'cell' ) ); ?>
		<?php elseif ( 0 === $view['locations'] && ! ( $view['known'] ?? true ) ) : ?>
			<?php $location_tip = __( 'Processing. Please check back in 24 hours.', 'affibloom' ); ?>
			<span class="affibloom-alert affibloom-alert--pending" tabindex="0" data-tip="<?php echo esc_attr( $location_tip ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'clock' ) ); ?>
				<span class="screen-reader-text"><?php echo esc_html( $location_tip ); ?></span>
			</span>
		<?php else : ?>
			<?php /* translators: %d: how many places the product is used in. */ ?>
			<a class="affibloom-count-link" href="<?php echo esc_url( add_query_arg( 'tab', 'locations', $product_page ) ); ?>" title="<?php echo esc_attr( sprintf( _n( 'Used in %d place', 'Used in %d places', $view['locations'], 'affibloom' ), $view['locations'] ) ); ?>"><?php echo esc_html( (string) $view['locations'] ); ?></a>
		<?php endif; ?>
	</td>
	<td class="affibloom-col-clicks"><?php $this->render( 'admin/partials/soon', array( 'variant' => 'cell' ) ); ?></td>
	<td class="affibloom-col-actions">
		<span class="affibloom-row__actions">
			<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-quick-edit="<?php echo esc_attr( $product_id ); ?>"
				aria-expanded="false" aria-controls="<?php echo esc_attr( $quick_id ); ?>" title="<?php esc_attr_e( 'Quick Edit', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'pencil' ) ); ?>
				<span><?php esc_html_e( 'Edit', 'affibloom' ); ?></span>
			</button>
			<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary"
				data-copy-shortcode="<?php echo esc_attr( $shortcode ); ?>" data-copied="<?php esc_attr_e( 'Copied', 'affibloom' ); ?>"
				data-copy-failed="<?php esc_attr_e( 'Could not copy shortcode.', 'affibloom' ); ?>" <?php disabled( '', $shortcode ); ?>
				title="<?php echo esc_attr( '' === $shortcode ? __( 'Shortcode unavailable for this link', 'affibloom' ) : __( 'Copy shortcode', 'affibloom' ) ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'copy' ) ); ?>
				<span data-copy-label aria-live="polite"><?php esc_html_e( 'Shortcode', 'affibloom' ); ?></span>
			</button>
		</span>
	</td>
</tr>
<?php
/*
 * Lasso's editing mode of this row (url-row.php 12-67, lasso-dashboard.css 1846-2000; Q2), in Affibloom's look: a card
 * in a tinted band across the eight columns; the fields in the wider column (Lasso's 8 of 12) and the image panel
 * beside them (4 of 12), its frame as tall as the fields; Save Changes and Cancel in the card's footer, right.
 */
/* translators: %s: product name. */
$quick_label = sprintf( __( 'Quick Edit: %s', 'affibloom' ), $product->name );
?>
<template data-quick-edit-template="<?php echo esc_attr( $product_id ); ?>">
	<tr class="affibloom-quick" id="<?php echo esc_attr( $quick_id ); ?>" data-quick-edit-for="<?php echo esc_attr( $product_id ); ?>">
		<td colspan="8">
			<form class="affibloom-quick__card" novalidate data-quick-form="<?php echo esc_attr( $product_id ); ?>" aria-label="<?php echo esc_attr( $quick_label ); ?>">
				<div class="affibloom-quick__main">
					<div class="affibloom-quick__fields">
						<div class="affibloom-field">
							<label class="affibloom-label affibloom-quick__label" for="<?php echo esc_attr( $quick_id ); ?>-name"><?php esc_html_e( 'Name', 'affibloom' ); ?></label>
							<input type="text" class="affibloom-input" id="<?php echo esc_attr( $quick_id ); ?>-name" name="name"
								value="<?php echo esc_attr( $product->name ); ?>" placeholder="<?php esc_attr_e( 'Enter a URL Name', 'affibloom' ); ?>">
						</div>
						<div class="affibloom-field">
							<label class="affibloom-label affibloom-quick__label" for="<?php echo esc_attr( $quick_id ); ?>-url"><?php esc_html_e( 'Primary Destination URL', 'affibloom' ); ?></label>
							<input type="text" class="affibloom-input" id="<?php echo esc_attr( $quick_id ); ?>-url" name="url"
								value="<?php echo esc_attr( $product->url->value() ); ?>" placeholder="https://www.example.com/affiliate-id">
						</div>
						<?php // Lasso's Groups select (several, new names added), as the group picker (Task G5; F23 R21). ?>
						<div class="affibloom-field">
							<label class="affibloom-label affibloom-quick__label" for="<?php echo esc_attr( $quick_id ); ?>-groups"><?php esc_html_e( 'Groups', 'affibloom' ); ?></label>
							<?php
							$this->render(
								'admin/partials/group-picker',
								array(
									'id'       => $quick_id . '-groups',
									'label_id' => $quick_id . '-groups',
									'groups'   => array_map(
										static fn( $group ): array => array(
											'id'   => $group->id,
											'name' => $group->name,
										),
										$view['groups']
									),
								)
							);
							?>
						</div>
						<?php // The public link (F24 R14; Task CL7), as the row's. ?>
						<p class="affibloom-quick__meta">
							<?php $this->render( 'admin/partials/icon', array( 'name' => 'link' ) ); ?>
							<a class="affibloom-quick__link" href="<?php echo esc_url( $public_link ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $public_link ); ?></a>
						</p>
					</div>

					<div class="affibloom-quick__image">
						<span class="affibloom-label affibloom-quick__label" id="<?php echo esc_attr( $quick_id ); ?>-image"><?php esc_html_e( 'Image', 'affibloom' ); ?></span>
						<?php // The frame opens the media library (Q5); "Update Image" covers it on hover and on focus, as Lasso's. ?>
						<button type="button" class="affibloom-quick__frame" data-quick-image
							aria-describedby="<?php echo esc_attr( $quick_id ); ?>-image <?php echo esc_attr( $quick_id ); ?>-image-hint"
							data-media-title="<?php esc_attr_e( 'Select an Image', 'affibloom' ); ?>" data-media-button="<?php esc_attr_e( 'Select Image', 'affibloom' ); ?>">
							<img class="affibloom-quick__picture" src="<?php echo esc_url( $product->image_url ); ?>" alt="" data-quick-picture<?php echo '' === $product->image_url ? ' hidden' : ''; ?>>
							<span class="affibloom-quick__none" data-quick-none<?php echo '' === $product->image_url ? '' : ' hidden'; ?>>
								<?php $this->render( 'admin/partials/icon', array( 'name' => 'image' ) ); ?>
								<span><?php esc_html_e( 'No image', 'affibloom' ); ?></span>
							</span>
							<span class="affibloom-quick__update">
								<?php $this->render( 'admin/partials/icon', array( 'name' => 'image' ) ); ?>
								<span><?php esc_html_e( 'Update Image', 'affibloom' ); ?></span>
							</span>
						</button>
						<p class="affibloom-quick__hint" id="<?php echo esc_attr( $quick_id ); ?>-image-hint"><?php esc_html_e( 'Click to choose from your Media Library.', 'affibloom' ); ?></p>
						<input type="hidden" name="image_url" value="<?php echo esc_attr( $product->image_url ); ?>">
						<input type="hidden" name="image_id" value="<?php echo esc_attr( (string) $product->image_id ); ?>">
					</div>
				</div>

				<div class="affibloom-quick__actions">
					<button type="submit" class="affibloom-button affibloom-button--primary"><?php esc_html_e( 'Save Changes', 'affibloom' ); ?></button>
					<button type="button" class="affibloom-button affibloom-button--secondary" data-quick-cancel><?php esc_html_e( 'Cancel', 'affibloom' ); ?></button>
				</div>
			</form>
		</td>
	</tr>
</template>
