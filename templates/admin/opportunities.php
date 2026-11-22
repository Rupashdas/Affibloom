<?php
/**
 * Opportunities (F22 R1-R23, D1-D8; Tasks O1, O2; Lasso's opportunities/*.php): the tab's intro box, the title (Link
 * Opportunities, "Link › <product>", Domain Opportunities, "Detected Links › <domain>", the filtered ones with a way
 * back), the sub-tabs, Search, and the list region. The list partial is the one GET /opportunities/* renders (D3).
 *
 * @package Affibloom
 * @var array{query: \Affibloom\Domain\Opportunity\OpportunityQuery, domain: string, filter: \Affibloom\Domain\Opportunity\ContentFilter, post: int, list: \Affibloom\Domain\Opportunity\OpportunityPage, product: \Affibloom\Domain\Product\Product|null, intro: string, tabs: array{label: string, items: list<array{label: string, url?: string, current?: bool}>}} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Opportunity\OpportunityQuery;
use Affibloom\Interface\Admin\OpportunitiesScreen;

$query       = $view['query'];
$product     = $view['product'];
$list_domain = $view['domain'];
$intros      = array(
	'links'   => array(
		__( 'Turn Any Existing Link Into a Product Link', 'affibloom' ),
		__( 'These are all of your external links that point to the same domain as an existing product you created. Choose to monetize the link with a product or dismiss the Suggestion.', 'affibloom' ),
	),
	'content' => array(
		__( 'Identify And Fix Your Under-Monetized Content', 'affibloom' ),
		__( 'Publishing a post that gets traffic just isn’t enough. They need to drive income. Find out which posts are under-monetized and increase your monthly revenue.', 'affibloom' ),
	),
	'domains' => array(
		__( 'Find Site-Wide Monetization Opportunities', 'affibloom' ),
		__( 'You’ll be shocked how many times you linked to a domain you didn’t know had an affiliate program. Sign up for that program, add your new affiliate URL to Affibloom, and instantly monetize all those links.', 'affibloom' ),
	),
);
// Each list: its title, its way back, its REST route, the arguments every request carries, and its partial.
$lists = array(
	OpportunityQuery::LINKS        => array(
		/* translators: %s: product name. */
		null === $product ? __( 'Link Opportunities', 'affibloom' ) : sprintf( __( 'Link › %s', 'affibloom' ), $product->name ),
		null === $product ? null : array( OpportunitiesScreen::url( new OpportunityQuery() ), __( 'Link Opportunities', 'affibloom' ) ),
		'/affibloom/v1/opportunities/links',
		$query->product > 0 ? array( 'product' => $query->product ) : array(),
		'admin/partials/opportunity-links-list',
	),
	OpportunityQuery::DOMAINS      => array( __( 'Domain Opportunities', 'affibloom' ), null, '/affibloom/v1/opportunities/domains', array(), 'admin/partials/opportunity-domains-list' ),
	OpportunityQuery::DOMAIN_LINKS => array(
		/* translators: %s: a domain. */
		sprintf( __( 'Detected Links › %s', 'affibloom' ), $list_domain ),
		array( OpportunitiesScreen::url( new OpportunityQuery( '', '', '', 1, 0, OpportunityQuery::DOMAINS ) ), __( 'Domain Opportunities', 'affibloom' ) ),
		'/affibloom/v1/opportunities/domains/' . rawurlencode( $list_domain ) . '/links',
		array(),
		'admin/partials/opportunity-domain-links-list',
	),
	OpportunityQuery::CONTENT      => array( __( 'Content Opportunities', 'affibloom' ), null, '/affibloom/v1/opportunities/content', array(), 'admin/partials/opportunity-content-list' ),
	OpportunityQuery::POST_LINKS   => array(
		/* translators: %s: a post's title. */
		sprintf( __( 'Content › %s', 'affibloom' ), $view['post'] > 0 ? get_the_title( $view['post'] ) : __( 'All content', 'affibloom' ) ),
		array( OpportunitiesScreen::url( new OpportunityQuery( '', '', '', 1, 0, OpportunityQuery::CONTENT ) ), __( 'Content Opportunities', 'affibloom' ) ),
		'/affibloom/v1/opportunities/posts/' . $view['post'] . '/links',
		array(),
		'admin/partials/opportunity-post-links-list',
	),
);
[ $page_title, $list_back, $list_path, $list_args, $partial ] = $lists[ $query->kind ];

$head = array(
	'title'        => $page_title,
	'actions'      => 'admin/partials/opportunities-actions',
	'actions_args' => array(
		'query'  => $query,
		'domain' => $list_domain,
		'post'   => $view['post'],
	),
	'below'        => 'admin/partials/section-tabs',
	'below_args'   => $view['tabs'],
);
if ( null !== $list_back ) {
	$head['back'] = array(
		'url'   => $list_back[0],
		'label' => $list_back[1],
	);
}
?>
<div class="wrap affibloom-admin">
	<?php $this->render( 'admin/partials/header' ); ?>

	<div class="affibloom-page affibloom-opportunities">
		<?php
		if ( '' !== $view['intro'] ) {
			$this->render(
				'admin/partials/opportunity-intro',
				array(
					'box'   => $view['intro'],
					'title' => $intros[ $view['intro'] ][0],
					'text'  => $intros[ $view['intro'] ][1],
				)
			);
		}
		$this->render( 'admin/partials/page-head', $head );
		?>

		<div class="affibloom-page__body">
			<?php
			if ( OpportunityQuery::CONTENT === $query->kind ) {
				$this->render(
					'admin/partials/opportunity-content-filters',
					array(
						'filter'  => $view['filter'],
						'exports' => $view['exports'] ?? null,
					)
				);
			}
			?>
			<div id="affibloom-opportunities-list" class="affibloom-list" data-list-path="<?php echo esc_attr( $list_path ); ?>"
				data-list-args="<?php echo esc_attr( (string) wp_json_encode( array() === $list_args ? (object) array() : $list_args ) ); ?>">
				<?php
				$this->render(
					$partial,
					array(
						'list'   => $view['list'],
						'query'  => $query,
						'domain' => $list_domain,
						'url'    => OpportunitiesScreen::url( $query, 1, $list_domain, $view['filter'], $view['post'] ),
					)
				);
				?>
			</div>

			<p id="affibloom-list-status" class="screen-reader-text" role="status" aria-live="polite"></p>
			<template id="affibloom-list-error">
				<div class="affibloom-card affibloom-empty affibloom-list-error">
					<span class="affibloom-chip"><?php $this->render( 'admin/partials/icon', array( 'name' => 'refresh' ) ); ?></span>
					<p class="affibloom-empty__message"><?php esc_html_e( 'Failed to load data.', 'affibloom' ); ?></p>
					<button type="button" class="affibloom-button affibloom-button--secondary" data-retry><?php esc_html_e( 'Try again', 'affibloom' ); ?></button>
				</div>
			</template>
		</div>
	</div>
	<?php $this->render( 'admin/partials/location-preview-modal' ); ?>
	<?php
	$this->render(
		'admin/partials/delete-modal',
		array(
			'id'            => 'affibloom-dismiss-opportunity',
			'title'         => __( 'Dismiss This Opportunity', 'affibloom' ),
			'text'          => __( 'If you dismiss this, it will no longer appear as an opportunity.', 'affibloom' ),
			'confirm_label' => __( 'Dismiss', 'affibloom' ),
			'confirm_attr'  => 'data-dismiss-confirm',
			'busy_label'    => __( 'Dismissing…', 'affibloom' ),
			'failed_text'   => __( 'Dismiss failed.', 'affibloom' ),
			'icon'          => 'close',
			'data'          => array(
				'done-text'   => __( 'Opportunity dismissed.', 'affibloom' ),
				'undo-label'  => __( 'Undo', 'affibloom' ),
				'undo-failed' => __( 'Could not undo. Please try again.', 'affibloom' ),
			),
		)
	);
	?>
	<?php $this->render( 'admin/partials/toast-template' ); ?>
	<?php $this->render( 'admin/partials/monetize-modal' ); ?>
	<?php $this->render( 'admin/partials/footer' ); ?>
</div>
