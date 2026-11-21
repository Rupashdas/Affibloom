<?php
/**
 * Opportunities, Link Opportunities (F22 R1-R16, D1-D8; Task O1; Lasso's opportunities/links.php): the intro box, the
 * title ("Link Opportunities", or "Link › <product>" with a way back when filtered by a product), the sub-tabs, Search,
 * and the list region. The list partial is the one GET /opportunities/links renders (D3).
 *
 * @package Affibloom
 * @var array{query: \Affibloom\Domain\Opportunity\OpportunityQuery, list: \Affibloom\Domain\Opportunity\OpportunityPage, product: \Affibloom\Domain\Product\Product|null, intro: bool, tabs: array{label: string, items: list<array{label: string, url?: string, current?: bool}>}, all_links: string} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\OpportunitiesScreen;

$query   = $view['query'];
$product = $view['product'];
/* translators: %s: product name. */
$page_title = null === $product ? __( 'Link Opportunities', 'affibloom' ) : sprintf( __( 'Link › %s', 'affibloom' ), $product->name );
$head       = array(
	'title'        => $page_title,
	'actions'      => 'admin/partials/opportunities-actions',
	'actions_args' => array( 'query' => $query ),
	'below'        => 'admin/partials/section-tabs',
	'below_args'   => $view['tabs'],
);
if ( null !== $product ) {
	$head['back'] = array(
		'url'   => $view['all_links'],
		'label' => __( 'Link Opportunities', 'affibloom' ),
	);
}
?>
<div class="wrap affibloom-admin">
	<?php $this->render( 'admin/partials/header' ); ?>

	<div class="affibloom-page affibloom-opportunities">
		<?php
		if ( $view['intro'] ) {
			$this->render(
				'admin/partials/opportunity-intro',
				array(
					'box'   => 'links',
					'title' => __( 'Turn Any Existing Link Into a Product Link', 'affibloom' ),
					'text'  => __( 'These are all of your external links that point to the same domain as an existing product you created. Choose to monetize the link with a product or dismiss the Suggestion.', 'affibloom' ),
				)
			);
		}
		$this->render( 'admin/partials/page-head', $head );
		?>

		<div class="affibloom-page__body">
			<div id="affibloom-opportunities-list" class="affibloom-list" data-list-path="/affibloom/v1/opportunities/links"
				data-list-args="<?php echo esc_attr( (string) wp_json_encode( $query->product > 0 ? array( 'product' => $query->product ) : (object) array() ) ); ?>">
				<?php
				$this->render(
					'admin/partials/opportunity-links-list',
					array(
						'list'  => $view['list'],
						'query' => $query,
						'url'   => OpportunitiesScreen::links_url( $query, 1 ),
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
	<span id="affibloom-soon-note" hidden><?php esc_html_e( 'Coming soon', 'affibloom' ); ?></span>
	<?php $this->render( 'admin/partials/location-preview-modal' ); ?>
	<?php $this->render( 'admin/partials/footer' ); ?>
</div>
