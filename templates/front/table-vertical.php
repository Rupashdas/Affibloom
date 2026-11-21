<?php
/**
 * A Vertical comparison table (F25 R45; Task T13; Lasso's displays/table-vertical.php): `div.table-vertical
 * .table-vertical-desktop.template-1[.include-badge][.table-vertical-scroll]` > `table.affibloom-table.table-borderless
 * .table-title`; a row per field, a `td.text-center.field.field-group-detail` per product, `100/n %` wide up to four
 * products (past four no width, and the row scrolls); the first row's cells start with the badge (or a space); a cell
 * after a badged one has no left border; then the phone cards (T14).
 *
 * @package Affibloom
 * @var \Affibloom\Application\Shortcode\TableReaderView $table
 */

defined( 'ABSPATH' ) || exit;

$vertical_count  = count( $table->rows );
$vertical_scroll = $vertical_count > 4;
$vertical_style  = $vertical_scroll ? '' : ' style="width: ' . esc_attr( (string) round( 100 / max( 1, $vertical_count ), 2 ) ) . '%"';
$vertical_badged = array() !== array_filter( array_column( $table->rows, 'badge' ), static fn( string $badge ): bool => '' !== $badge );
$vertical_lines  = count( $table->rows[0]['cells'] ?? array() );
?>
<div class="table-vertical table-vertical-desktop template-1<?php echo $vertical_badged ? ' include-badge' : ''; ?><?php echo $vertical_scroll ? ' table-vertical-scroll' : ''; ?>">
	<table class="affibloom-table table-borderless table-title">
		<tbody>
			<?php for ( $vertical_line = 0; $vertical_line < $vertical_lines; $vertical_line++ ) : ?>
				<tr>
					<?php $vertical_after_badge = false; ?>
					<?php foreach ( $table->rows as $row ) : ?>
						<td class="text-center field field-group-detail"<?php echo $vertical_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above from an escaped number. ?>>
							<div class="cell<?php echo '' !== $row['badge'] ? ' has-badge-text' : ''; ?><?php echo $vertical_after_badge ? ' no-border-left' : ''; ?>">
								<?php if ( 0 === $vertical_line ) : ?>
									<div class="badge-text-wrapper">
										<span class="badge-text"><?php echo '' === $row['badge'] ? '&nbsp;' : wp_kses_post( $row['badge'] ); ?></span>
									</div>
								<?php endif; ?>
								<div class="cell-content">
									<?php
									foreach ( $row['cells'][ $vertical_line ] ?? array() as $cell ) {
										require __DIR__ . '/partials/table-field.php';
									}
									?>
								</div>
							</div>
						</td>
						<?php $vertical_after_badge = '' !== $row['badge']; ?>
					<?php endforeach; ?>
				</tr>
			<?php endfor; ?>
		</tbody>
	</table>
</div>
<?php require __DIR__ . '/table-mobile.php'; ?>
