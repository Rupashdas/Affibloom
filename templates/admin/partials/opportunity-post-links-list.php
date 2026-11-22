<?php
/**
 * A post's links (F22 R29-R31, D7; Task O4; Lasso's opportunities/content-links.php and rows/content-link-row.php):
 * "N Found", the table URL (with Filter by URL and Open in New Window under it), Link (the text or image address,
 * which opens the preview), Type and Monetize (on for a tied link; Task O10's switch); product links first.
 * GET /opportunities/posts/{id}/links renders this same partial (D3).
 *
 * @package Affibloom
 * @var array{list: \Affibloom\Domain\Opportunity\OpportunityPage, query: \Affibloom\Domain\Opportunity\OpportunityQuery, url: string} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Opportunity\AmazonDomains;
use Affibloom\Domain\Opportunity\OpportunityQuery;
use Affibloom\Interface\Admin\OpportunitiesScreen;

$list    = $view['list'];
$columns = array(
	// [ class, label, tooltip, the column it sorts ('' none) ].
	array( 'url', __( 'URL', 'affibloom' ), __( 'This is a URL found on your site that may be able to be monetized.', 'affibloom' ), '' ),
	array( 'link', __( 'Link', 'affibloom' ), __( 'Click to see a snippet of this link as it appears on your site.', 'affibloom' ), '' ),
	array( 'type', __( 'Type', 'affibloom' ), '', '' ),
	array( 'monetize', __( 'Monetize', 'affibloom' ), '', '' ),
);
?>
<p class="affibloom-found" data-found>
	<?php
	/* translators: %s: how many links the list holds. */
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
					<?php foreach ( $list->items as $detected ) : ?>
						<?php $place = $detected->place; ?>
						<tr data-location-id="<?php echo esc_attr( (string) $place->id ); ?>">
							<td class="affibloom-col-url">
								<a class="affibloom-opportunity__main" href="<?php echo esc_url( $place->url ); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo esc_attr( $place->url ); ?>"><?php echo esc_html( $place->url ); ?></a>
								<span class="affibloom-opportunity__actions">
									<?php if ( '' !== $detected->domain ) : ?>
										<a class="affibloom-opportunity__action" href="<?php echo esc_url( OpportunitiesScreen::url( new OpportunityQuery( '', '', '', 1, 0, OpportunityQuery::DOMAIN_LINKS ), 1, $detected->domain ) ); ?>"><?php esc_html_e( 'Filter by URL', 'affibloom' ); ?></a>
									<?php endif; ?>
									<a class="affibloom-opportunity__action" href="<?php echo esc_url( $place->url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open in New Window', 'affibloom' ); ?></a>
									<?php $in_post = OpportunitiesScreen::edit_in_post_url( $detected->host_id, (int) $place->id ); ?>
									<?php if ( '' !== $in_post ) : ?>
										<a class="affibloom-opportunity__action" href="<?php echo esc_url( $in_post ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Edit in post', 'affibloom' ); ?></a>
									<?php endif; ?>
								</span>
								<?php if ( $detected->assigned > 0 ) : ?>
									<?php /* translators: %s: the product's name. */ ?>
									<span class="affibloom-opportunity__assigned"><?php echo esc_html( sprintf( __( 'Monetized with %s', 'affibloom' ), $detected->assigned_name ) ); ?></span>
								<?php endif; ?>
							</td>
							<td class="affibloom-col-link">
								<button type="button" class="affibloom-location__context" data-location-preview="<?php echo esc_attr( (string) $place->id ); ?>"
									title="<?php echo esc_attr( $place->context() ); ?>"><?php echo esc_html( $place->context() ); ?></button>
							</td>
							<td class="affibloom-col-type"><?php $this->render( 'admin/partials/location-type', array( 'place' => $place ) ); ?></td>
							<td class="affibloom-col-monetize">
								<?php
								// On for a tied link (R30); Monetize and Unmonetize (Task O10). A link tied by its own
								// address can be turned off when it has an original URL to go back to (N3, E17; GF6).
								$this->render(
									'admin/partials/monetize-switch',
									array(
										'row'    => $place->id,
										'url'    => $place->url,
										'state'  => $detected->assigned > 0 || $detected->restorable ? 'on' : ( $detected->tied ? 'content' : 'off' ),
										'amazon' => AmazonDomains::in_url( $place->url ),
									)
								);
								?>
							</td>
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
				'label' => __( 'Links', 'affibloom' ),
			)
		);
		?>
	</div>
<?php endif; ?>
