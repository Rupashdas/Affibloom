<?php
/**
 * Content Opportunities' list region (F22 R24, R26, R28, D7; Task O3; Lasso's opportunities/content.php and
 * rows/content-opportunity-row.php): "N Found", the table Last Updated ("Nov 9th, 2026"), Content, Permalink (the path,
 * which opens the post), Links, newest first; all four sort. A post's title opens its links (Task O4).
 * With none, Lasso's "Looks like we're coming up empty." GET /opportunities/content renders this same partial (D3).
 *
 * @package Affibloom
 * @var array{list: \Affibloom\Domain\Opportunity\OpportunityPage, query: \Affibloom\Domain\Opportunity\OpportunityQuery, url: string} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Opportunity\OpportunityQuery;
use Affibloom\Interface\Admin\OpportunitiesScreen;

$list    = $view['list'];
$columns = array(
	// [ class, label, tooltip, the column it sorts ('' none) ].
	array( 'updated', __( 'Last Updated', 'affibloom' ), '', 'post_modified' ),
	array( 'content', __( 'Content', 'affibloom' ), __( 'Where we found these links. Click name to view its links.', 'affibloom' ), 'detection_title' ),
	array( 'permalink', __( 'Permalink', 'affibloom' ), __( 'The slug for this piece of content. Click to view it on your site.', 'affibloom' ), 'detection_slug' ),
	array( 'links', __( 'Links', 'affibloom' ), __( 'Number of links discovered in this piece of content.', 'affibloom' ), 'count' ),
);
$site    = untrailingslashit( (string) preg_replace( '#^https?://#', '', home_url() ) );
?>
<p class="affibloom-found" data-found>
	<?php
	/* translators: %s: how many posts the list holds. */
	echo esc_html( sprintf( __( '%s Found', 'affibloom' ), number_format_i18n( $list->total ) ) );
	?>
</p>
<?php if ( 0 === $list->total ) : ?>
	<div class="affibloom-card affibloom-empty">
		<span class="affibloom-chip"><?php $this->render( 'admin/partials/icon', array( 'name' => 'bulb' ) ); ?></span>
		<p class="affibloom-empty__message"><?php esc_html_e( "Looks like we're coming up empty.", 'affibloom' ); ?></p>
	</div>
<?php else : ?>
	<div class="affibloom-card affibloom-card--table">
		<div class="affibloom-table-scroll">
			<table class="affibloom-table affibloom-table--content">
				<?php
				$this->render(
					'admin/partials/opportunity-table-head',
					array(
						'columns' => $columns,
						'query'   => $view['query'],
					)
				);
				?>
				<tbody>
					<?php foreach ( $list->items as $content_row ) : ?>
						<?php
						$timestamp = strtotime( $content_row->modified );
						// The permalink without the site's address and scheme (Lasso's short_detection_slug, R24).
						$short = (string) preg_replace( '#^https?://' . preg_quote( $site, '#' ) . '#', '', $content_row->url );
						?>
						<tr>
							<td class="affibloom-col-updated"><?php echo esc_html( false === $timestamp ? '' : date_i18n( 'M jS, Y', $timestamp ) ); ?></td>
							<td class="affibloom-col-content">
								<a class="affibloom-opportunity__main" href="<?php echo esc_url( OpportunitiesScreen::url( new OpportunityQuery( '', '', '', 1, 0, OpportunityQuery::POST_LINKS ), 1, '', null, $content_row->host_id ) ); ?>"><?php echo esc_html( '' !== $content_row->title ? $content_row->title : __( '(no title)', 'affibloom' ) ); ?></a>
							</td>
							<td class="affibloom-col-permalink">
								<a class="affibloom-opportunity__main" href="<?php echo esc_url( $content_row->url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( '' !== $short ? $short : '/' ); ?></a>
							</td>
							<td class="affibloom-col-links"><?php echo esc_html( number_format_i18n( $content_row->links ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		$this->render(
			'admin/partials/list-pager',
			array(
				'page'  => $list,
				'url'   => $view['url'],
				'label' => __( 'Content', 'affibloom' ),
			)
		);
		?>
	</div>
<?php endif; ?>
