<?php
/**
 * A post's links (F22 R29-R31, D7; Task O4; Lasso's opportunities/content-links.php and rows/content-link-row.php):
 * "N Found", the table URL (with Filter by URL and Open in New Window under it), Link (the text or image address,
 * which opens the preview), Type and Monetize (on for a tied link; disabled until O10); product links first.
 * GET /opportunities/posts/{id}/links renders this same partial (D3).
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
						<?php
						$place = $detected->place;
						/* translators: %s: the link's address. */
						$monetize_label = sprintf( __( 'Monetize %s', 'affibloom' ), $place->url );
						?>
						<tr data-location-id="<?php echo esc_attr( (string) $place->id ); ?>">
							<td class="affibloom-col-url">
								<a class="affibloom-opportunity__main" href="<?php echo esc_url( $place->url ); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo esc_attr( $place->url ); ?>"><?php echo esc_html( $place->url ); ?></a>
								<span class="affibloom-opportunity__actions">
									<?php if ( '' !== $detected->domain ) : ?>
										<a class="affibloom-opportunity__action" href="<?php echo esc_url( OpportunitiesScreen::url( new OpportunityQuery( '', '', '', 1, 0, OpportunityQuery::DOMAIN_LINKS ), 1, $detected->domain ) ); ?>"><?php esc_html_e( 'Filter by URL', 'affibloom' ); ?></a>
									<?php endif; ?>
									<a class="affibloom-opportunity__action" href="<?php echo esc_url( $place->url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open in New Window', 'affibloom' ); ?></a>
								</span>
							</td>
							<td class="affibloom-col-link">
								<button type="button" class="affibloom-location__context" data-location-preview="<?php echo esc_attr( (string) $place->id ); ?>"
									title="<?php echo esc_attr( $place->context() ); ?>"><?php echo esc_html( $place->context() ); ?></button>
							</td>
							<td class="affibloom-col-type"><?php $this->render( 'admin/partials/location-type', array( 'place' => $place ) ); ?></td>
							<td class="affibloom-col-monetize">
								<?php // On for a tied link (R30); Monetize itself comes with Task O10. ?>
								<span class="affibloom-toggle" title="<?php esc_attr_e( 'Coming soon', 'affibloom' ); ?>">
									<input type="checkbox" class="affibloom-toggle__input" role="switch" disabled <?php checked( $detected->tied ); ?> aria-label="<?php echo esc_attr( $monetize_label ); ?>" aria-describedby="affibloom-soon-note">
									<span class="affibloom-toggle__track" aria-hidden="true"></span>
								</span>
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
