<?php
/**
 * A comparison table's phone cards (F25 R47, R53; Task T14; Lasso's displays/table-mobile.php): `div.table-vertical
 * .table-vertical-mobile.tb-vt.tb-vt-mb.template-1`, a `div.mobile-box` per product (its badge first when it has one),
 * then a `div.mobile-item-child.mb-i-chl.field` per field in the layout's order. table.css shows the cards and hides
 * the desktop table at 767px and narrower.
 *
 * @package Affibloom
 * @var \Affibloom\Application\Shortcode\TableReaderView $table
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="table-vertical table-vertical-mobile tb-vt tb-vt-mb template-1" style="box-shadow: none !important;">
	<?php foreach ( $table->rows as $mobile_row ) : ?>
		<div class="mobile-box<?php echo '' !== $mobile_row['badge'] ? ' has-badge-mobile has-bm' : ''; ?>">
			<?php if ( '' !== $mobile_row['badge'] ) : ?>
				<div class="badge"><?php echo wp_kses_post( $mobile_row['badge'] ); ?></div>
			<?php endif; ?>
			<div class="mobile-item-wrapper mb-i-wp">
				<?php foreach ( $mobile_row['cells'] as $mobile_cells ) : ?>
					<?php foreach ( $mobile_cells as $cell ) : ?>
						<div class="mobile-item-child mb-i-chl field">
							<?php require __DIR__ . '/partials/table-field.php'; ?>
						</div>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endforeach; ?>
</div>
