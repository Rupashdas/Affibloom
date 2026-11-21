<?php
/**
 * The Tables list region (F25 R6-R9, D14; Task T1; Lasso's tables.php and education/list-table-empty.php): the card with
 * Name, Products, Theme, Style, Locations and Clone, ten a page, "x-y of n" and the pager; or, with no tables or no
 * match, Lasso's "Create Beautiful Comparison Tables" (its help link left out, D14). GET /tables renders this same
 * partial for the in-place list.
 *
 * @package Affibloom
 * @var array{tables: \Affibloom\Domain\Table\TablePage, locations: array<int, int>, term: string, url: string} $view
 */

defined( 'ABSPATH' ) || exit;

$tables  = $view['tables'];
$links   = paginate_links(
	array(
		// The list's own address, never the current request's: inside the REST route that is the API's.
		'base'      => add_query_arg( 'paged', '%#%', $view['url'] ),
		'format'    => '',
		'current'   => $tables->current(),
		'total'     => $tables->pages(),
		'end_size'  => 2,
		'mid_size'  => 1,
		'prev_text' => '<span class="screen-reader-text">' . esc_html__( 'Previous page', 'affibloom' ) . '</span>',
		'next_text' => '<span class="screen-reader-text">' . esc_html__( 'Next page', 'affibloom' ) . '</span>',
	)
);
$columns = array(
	// Each column: its class, its label and Lasso's tooltip in D19's wording.
	array( 'name', __( 'Name', 'affibloom' ), '' ),
	array( 'products', __( 'Products', 'affibloom' ), __( 'These are the products used in the table.', 'affibloom' ) ),
	array( 'theme', __( 'Theme', 'affibloom' ), '' ),
	array( 'style', __( 'Style', 'affibloom' ), __( 'Is your product data displayed across rows or in columns.', 'affibloom' ) ),
	array( 'locations', __( 'Locations', 'affibloom' ), __( 'Where this table is used on your site.', 'affibloom' ) ),
	array( 'clone', __( 'Clone', 'affibloom' ), __( 'Make a copy of this table.', 'affibloom' ) ),
);
?>
<?php if ( 0 === $tables->total ) : ?>
	<div class="affibloom-card affibloom-empty">
		<span class="affibloom-chip"><?php $this->render( 'admin/partials/icon', array( 'name' => 'table' ) ); ?></span>
		<p class="affibloom-empty__message"><?php esc_html_e( 'Create Beautiful Comparison Tables', 'affibloom' ); ?></p>
		<p class="affibloom-empty__text"><?php esc_html_e( 'Compare similar products and share alternatives. These tables are great way to drive conversions across multiple products and views of related reviews.', 'affibloom' ); ?></p>
	</div>
<?php else : ?>
	<div class="affibloom-card affibloom-card--table">
		<div class="affibloom-table-scroll">
			<table class="affibloom-table affibloom-table--tables">
				<thead>
					<tr>
						<?php foreach ( $columns as $column ) : ?>
							<th scope="col" class="affibloom-col-<?php echo esc_attr( $column[0] ); ?>">
								<span class="affibloom-th"><?php echo esc_html( $column[1] ); ?></span>
								<?php
								if ( '' !== $column[2] ) {
									$this->render(
										'admin/partials/help',
										array(
											'id'    => 'affibloom-tables-' . $column[0] . '-help',
											'text'  => $column[2],
											'label' => $column[1],
										)
									);
								}
								?>
							</th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $tables->items as $table ) : ?>
						<?php
						$this->render(
							'admin/partials/table-row',
							array(
								'table'     => $table,
								'locations' => $view['locations'][ (int) $table->id ] ?? 0,
							)
						);
						?>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<div class="affibloom-pager">
			<span class="affibloom-pager__range">
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: first table shown, 2: last table shown, 3: tables in the whole list. */
						__( '%1$s-%2$s of %3$s', 'affibloom' ),
						number_format_i18n( $tables->first() ),
						number_format_i18n( $tables->last() ),
						number_format_i18n( $tables->total )
					)
				);
				?>
			</span>
			<?php if ( $links ) : ?>
				<nav class="affibloom-pagination" aria-label="<?php esc_attr_e( 'Tables', 'affibloom' ); ?>">
					<?php echo wp_kses_post( $links ); ?>
				</nav>
			<?php endif; ?>
		</div>
	</div>
<?php endif; ?>
