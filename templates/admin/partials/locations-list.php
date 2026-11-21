<?php
/**
 * The Locations tab's list region (F12 L4-L9, L12; Task 36B): the card with its four columns, the host posts of this
 * page and the pager, or an empty state. GET /products/{id}/locations renders this same partial (D22).
 *
 * Columns and tooltips are Lasso's (url-links.php 15-57). Content sorts by the post's title: the first click ascending,
 * then each click turns it round (L8). A post with one place is one row; with several, a group row "(N locations)"
 * whose places open under it (L6). Empty with no search is Lasso's education panel (education/locations.php; its link to
 * Lasso's help site is left out); empty after a search says so.
 *
 * @package Affibloom
 * @var array{locations: \Affibloom\Domain\LinkIndex\LocationPage, clicks?: array<int, int>, term: string, order: string, url: string} $view
 */

defined( 'ABSPATH' ) || exit;

$locations  = $view['locations'];
$sort_order = $view['order'];
$next       = 'asc' === $sort_order ? 'desc' : 'asc';
$aria_sort  = array(
	''     => 'none',
	'asc'  => 'ascending',
	'desc' => 'descending',
)[ $sort_order ] ?? 'none';
$links      = paginate_links(
	array(
		// The tab's own address (ProductScreen::locations_url), never the current request's: inside the route that is the API's.
		'base'      => add_query_arg( 'paged', '%#%', $view['url'] ),
		'format'    => '',
		'current'   => $locations->current(),
		'total'     => $locations->pages(),
		'end_size'  => 2,
		'mid_size'  => 1,
		'prev_text' => '<span class="screen-reader-text">' . esc_html__( 'Previous page', 'affibloom' ) . '</span>',
		'next_text' => '<span class="screen-reader-text">' . esc_html__( 'Next page', 'affibloom' ) . '</span>',
	)
);
$columns    = array(
	'link'   => array( __( 'Link with Context', 'affibloom' ), __( 'Click to see a snippet of this link as it appears on your site.', 'affibloom' ) ),
	'type'   => array( __( 'Type', 'affibloom' ), '' ),
	'clicks' => array( __( 'Clicks', 'affibloom' ), __( 'Total clicks in the last 30 days.', 'affibloom' ) ),
);
?>
<?php if ( 0 === $locations->total ) : ?>
	<div class="affibloom-card affibloom-empty">
		<span class="affibloom-chip">
			<?php $this->render( 'admin/partials/icon', array( 'name' => '' !== $view['term'] ? 'info' : 'link' ) ); ?>
		</span>
		<?php if ( '' !== $view['term'] ) : ?>
			<p class="affibloom-empty__message"><?php esc_html_e( 'No locations match your search.', 'affibloom' ); ?></p>
		<?php else : ?>
			<p class="affibloom-empty__message"><?php esc_html_e( 'Add This Link Anywhere On Your Website', 'affibloom' ); ?></p>
			<p class="affibloom-empty__text"><?php esc_html_e( 'You can see where this link appears on your website. Affibloom will track every location right here so you know where your links are.', 'affibloom' ); ?></p>
		<?php endif; ?>
	</div>
<?php else : ?>
	<div class="affibloom-card affibloom-card--table">
		<div class="affibloom-table-scroll">
			<table class="affibloom-table affibloom-table--locations">
				<thead>
					<tr>
						<th scope="col" class="affibloom-col-content" aria-sort="<?php echo esc_attr( $aria_sort ); ?>">
							<button type="button" class="affibloom-sort" data-list-sort="<?php echo esc_attr( $next ); ?>">
								<span><?php esc_html_e( 'Content', 'affibloom' ); ?></span>
								<?php if ( '' !== $sort_order ) : ?>
									<?php $this->render( 'admin/partials/icon', array( 'name' => 'asc' === $sort_order ? 'chevron-up' : 'chevron-down' ) ); ?>
								<?php endif; ?>
							</button>
							<?php
							$this->render(
								'admin/partials/help',
								array(
									'id'    => 'affibloom-locations-content-help',
									'text'  => __( 'This is the post or page this link was found in.', 'affibloom' ),
									'label' => __( 'Content', 'affibloom' ),
								)
							);
							?>
						</th>
						<?php foreach ( $columns as $key => $column ) : ?>
							<th scope="col" class="affibloom-col-<?php echo esc_attr( $key ); ?>">
								<span class="affibloom-th"><?php echo esc_html( $column[0] ); ?></span>
								<?php
								if ( '' !== $column[1] ) {
									$this->render(
										'admin/partials/help',
										array(
											'id'    => 'affibloom-locations-' . $key . '-help',
											'text'  => $column[1],
											'label' => $column[0],
										)
									);
								}
								?>
							</th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $locations->items as $host ) : ?>
						<?php if ( count( $host->places ) > 1 ) : ?>
							<?php
							$this->render(
								'admin/partials/location-group-row',
								array(
									'host'   => $host,
									'clicks' => array_sum( array_map( static fn( $place ): int => $view['clicks'][ $place->id ] ?? 0, $host->places ) ),
								)
							);
							?>
							<?php foreach ( $host->places as $place ) : ?>
								<?php
								$this->render(
									'admin/partials/location-row',
									array(
										'host'   => $host,
										'place'  => $place,
										'nested' => true,
										'clicks' => $view['clicks'][ $place->id ] ?? 0,
									)
								);
								?>
							<?php endforeach; ?>
						<?php else : ?>
							<?php foreach ( $host->places as $place ) : ?>
								<?php
								$this->render(
									'admin/partials/location-row',
									array(
										'host'   => $host,
										'place'  => $place,
										'nested' => false,
										'clicks' => $view['clicks'][ $place->id ] ?? 0,
									)
								);
								?>
							<?php endforeach; ?>
						<?php endif; ?>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<div class="affibloom-pager">
			<span class="affibloom-pager__range">
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: first post shown, 2: last post shown, 3: posts in the whole list. */
						__( '%1$s-%2$s of %3$s', 'affibloom' ),
						number_format_i18n( $locations->first() ),
						number_format_i18n( $locations->last() ),
						number_format_i18n( $locations->total )
					)
				);
				?>
			</span>
			<?php if ( $links ) : ?>
				<nav class="affibloom-pagination" aria-label="<?php esc_attr_e( 'Locations', 'affibloom' ); ?>">
					<?php echo wp_kses_post( $links ); ?>
				</nav>
			<?php endif; ?>
		</div>
	</div>
<?php endif; ?>
