<?php
/**
 * Link Opportunities' list region (F22 R4, R7, R10, R14; Task O1): "N Found", the table (URL, Content, Suggestion,
 * Type, Monetize) with Lasso's tooltips, ten rows a page and the pager; or "No opportunities found.". URL, Content and
 * Suggestion sort, ascending first (R7). GET /opportunities/links renders this same partial (D3).
 *
 * @package Affibloom
 * @var array{list: \Affibloom\Domain\Opportunity\OpportunityPage, query: \Affibloom\Domain\Opportunity\OpportunityQuery, url: string} $view
 */

defined( 'ABSPATH' ) || exit;

$list    = $view['list'];
$query   = $view['query'];
$links   = paginate_links(
	array(
		// The list's own address, never the current request's: inside the route that is the API's.
		'base'      => add_query_arg( 'paged', '%#%', $view['url'] ),
		'format'    => '',
		'current'   => $list->current(),
		'total'     => $list->pages(),
		'end_size'  => 2,
		'mid_size'  => 1,
		'prev_text' => '<span class="screen-reader-text">' . esc_html__( 'Previous page', 'affibloom' ) . '</span>',
		'next_text' => '<span class="screen-reader-text">' . esc_html__( 'Next page', 'affibloom' ) . '</span>',
	)
);
$columns = array(
	// [ class, label, tooltip, the column it sorts ('' none) ].
	array( 'url', __( 'URL', 'affibloom' ), __( 'This is a URL found on your site that may be able to be monetized.', 'affibloom' ), 'url' ),
	array( 'content', __( 'Content', 'affibloom' ), __( 'Where we found this URL. Click link to edit.', 'affibloom' ), 'post_title' ),
	array( 'suggestion', __( 'Suggestion', 'affibloom' ), __( 'The name of a product with the same domain as this URL. Click link to edit.', 'affibloom' ), 'suggestion' ),
	array( 'type', __( 'Type', 'affibloom' ), __( 'Click to see a snippet of this link as it appears on your site.', 'affibloom' ), '' ),
	array( 'monetize', __( 'Monetize', 'affibloom' ), '', '' ),
);
?>
<p class="affibloom-found" data-found>
	<?php
	/* translators: %s: how many opportunities the list holds. */
	echo esc_html( sprintf( __( '%s Found', 'affibloom' ), number_format_i18n( $list->total ) ) );
	?>
</p>
<?php if ( 0 === $list->total ) : ?>
	<div class="affibloom-card affibloom-empty">
		<span class="affibloom-chip"><?php $this->render( 'admin/partials/icon', array( 'name' => 'bulb' ) ); ?></span>
		<p class="affibloom-empty__message"><?php esc_html_e( 'No opportunities found.', 'affibloom' ); ?></p>
	</div>
<?php else : ?>
	<div class="affibloom-card affibloom-card--table">
		<div class="affibloom-table-scroll">
			<table class="affibloom-table affibloom-table--opportunities">
				<thead>
					<tr>
						<?php foreach ( $columns as $column ) : ?>
							<?php
							$applied = $column[3] === $query->orderby ? $query->order : '';
							$aria    = '' === $applied ? 'none' : ( 'asc' === $applied ? 'ascending' : 'descending' );
							?>
							<th scope="col" class="affibloom-col-<?php echo esc_attr( $column[0] ); ?>"<?php echo '' === $column[3] ? '' : ' aria-sort="' . esc_attr( $aria ) . '"'; ?>>
								<?php if ( '' !== $column[3] ) : ?>
									<?php [ $next_column, $next_order ] = $query->next( $column[3] ); ?>
									<button type="button" class="affibloom-sort" data-list-orderby="<?php echo esc_attr( $next_column ); ?>" data-list-sort="<?php echo esc_attr( $next_order ); ?>">
										<span><?php echo esc_html( $column[1] ); ?></span>
										<?php if ( '' !== $applied ) : ?>
											<?php $this->render( 'admin/partials/icon', array( 'name' => 'asc' === $applied ? 'chevron-up' : 'chevron-down' ) ); ?>
										<?php endif; ?>
									</button>
								<?php else : ?>
									<span class="affibloom-th"><?php echo esc_html( $column[1] ); ?></span>
								<?php endif; ?>
								<?php
								if ( '' !== $column[2] ) {
									$this->render(
										'admin/partials/help',
										array(
											'id'    => 'affibloom-opportunities-' . $column[0] . '-help',
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
					<?php foreach ( $list->items as $opportunity ) : ?>
						<?php
						$this->render(
							'admin/partials/opportunity-link-row',
							array(
								'opportunity' => $opportunity,
								'filtered'    => $query->product > 0,
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
						/* translators: 1: first row shown, 2: last row shown, 3: rows in the whole list. */
						__( '%1$s-%2$s of %3$s', 'affibloom' ),
						number_format_i18n( $list->first() ),
						number_format_i18n( $list->last() ),
						number_format_i18n( $list->total )
					)
				);
				?>
			</span>
			<?php if ( $links ) : ?>
				<nav class="affibloom-pagination" aria-label="<?php esc_attr_e( 'Opportunities', 'affibloom' ); ?>">
					<?php echo wp_kses_post( $links ); ?>
				</nav>
			<?php endif; ?>
		</div>
	</div>
<?php endif; ?>
