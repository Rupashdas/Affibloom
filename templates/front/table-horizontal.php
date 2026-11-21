<?php
/**
 * A Horizontal comparison table (F25 R46; Task T12; Lasso's displays/table-horizontal.php): `div.row-style.template-1
 * .horizontal-table` > `table.affibloom-table[.hide-column-headers]`; with Show Headers a header row of field names; a
 * row per product, a `td.td-template-1 > div.cell` per field group, the badge in the first, then `ul.field-group` with
 * a `li` per field. The phone cards come with Task T14.
 *
 * @package Affibloom
 * @var \Affibloom\Application\Shortcode\TableReaderView $table
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="row-style template-1 horizontal-table">
	<table class="affibloom-table<?php echo $table->show_headers ? '' : ' hide-column-headers'; ?>">
		<?php if ( $table->show_headers ) : ?>
			<thead>
				<tr>
					<?php foreach ( $table->headers as $header ) : ?>
						<th scope="col"><span><?php echo esc_html( $header ); ?></span></th>
					<?php endforeach; ?>
				</tr>
			</thead>
		<?php endif; ?>
		<tbody>
			<?php foreach ( $table->rows as $row ) : ?>
				<tr>
					<?php foreach ( $row['cells'] as $at => $cells ) : ?>
						<td class="td-template-1">
							<div class="cell">
								<?php if ( 0 === $at && '' !== $row['badge'] ) : ?>
									<div class="badge-text-wrapper">
										<span class="badge-text"><?php echo wp_kses_post( $row['badge'] ); ?></span>
									</div>
								<?php endif; ?>
								<ul class="field-group">
									<?php foreach ( $cells as $cell ) : ?>
										<li><?php require __DIR__ . '/partials/table-field.php'; ?></li>
									<?php endforeach; ?>
								</ul>
							</div>
						</td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
