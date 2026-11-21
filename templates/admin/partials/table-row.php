<?php
/**
 * One row of the Tables list (F25 R7; Task T1; Lasso's table-row.php): the name (bold) to the builder; each product's
 * name to its page, in table order (a product no longer published is left out, D15), or "No products in this table
 * yet."; the theme; Horizontal (Row) or Vertical (Column); the Locations count; Clone, which comes with Task T11.
 *
 * @package Affibloom
 * @var array{table: \Affibloom\Domain\Table\Table, locations: int} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Table\TableStyle;
use Affibloom\Interface\Admin\ProductScreen;
use Affibloom\Interface\Admin\TablesScreen;

$table    = $view['table'];
$products = array_values( array_filter( $table->product_ids(), static fn( int $id ): bool => 'publish' === get_post_status( $id ) ) );
/* translators: %s: the table's name. */
$clone_label = sprintf( __( 'Clone %s', 'affibloom' ), $table->name );
?>
<tr data-table-id="<?php echo esc_attr( (string) $table->id ); ?>">
	<td class="affibloom-col-name">
		<a class="affibloom-row__name" href="<?php echo esc_url( TablesScreen::table_url( (int) $table->id ) ); ?>" title="<?php echo esc_attr( $table->name ); ?>"><?php echo esc_html( $table->name ); ?></a>
	</td>
	<td class="affibloom-col-products">
		<?php if ( array() === $products ) : ?>
			<span class="affibloom-table-products__none" title="<?php esc_attr_e( 'Click the table to add a product', 'affibloom' ); ?>"><?php esc_html_e( 'No products in this table yet.', 'affibloom' ); ?></span>
		<?php else : ?>
			<ul class="affibloom-table-products">
				<?php foreach ( $products as $product_id ) : ?>
					<li><a href="<?php echo esc_url( ProductScreen::url( $product_id ) ); ?>"><?php echo esc_html( wp_specialchars_decode( get_the_title( $product_id ), ENT_QUOTES ) ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</td>
	<td class="affibloom-col-theme"><?php echo esc_html( $table->theme ); ?></td>
	<td class="affibloom-col-style"><?php echo esc_html( TableStyle::Row === $table->style ? __( 'Horizontal', 'affibloom' ) : __( 'Vertical', 'affibloom' ) ); ?></td>
	<td class="affibloom-col-locations">
		<?php if ( $view['locations'] > 0 ) : ?>
			<?php // R40 (Task T10): a count above 0 opens "Table Locations". ?>
			<?php /* translators: %s: the table's name. */ ?>
			<button type="button" class="affibloom-link-button" data-table-locations="<?php echo esc_attr( (string) $table->id ); ?>" aria-haspopup="dialog" aria-label="<?php echo esc_attr( sprintf( __( 'Locations of %s', 'affibloom' ), $table->name ) ); ?>"><?php echo esc_html( number_format_i18n( $view['locations'] ) ); ?></button>
		<?php else : ?>
			<?php echo esc_html( number_format_i18n( $view['locations'] ) ); ?>
		<?php endif; ?>
	</td>
	<td class="affibloom-col-clone">
		<?php // Clone comes with Task T11; until then the icon is shown, disabled. ?>
		<button type="button" class="affibloom-icon-button" disabled aria-label="<?php echo esc_attr( $clone_label ); ?>" aria-describedby="affibloom-soon-note" title="<?php esc_attr_e( 'Coming soon', 'affibloom' ); ?>">
			<?php $this->render( 'admin/partials/icon', array( 'name' => 'copy' ) ); ?>
		</button>
	</td>
</tr>
