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
				<?php
				$this->render(
					'admin/partials/opportunity-table-head',
					array(
						'columns' => $columns,
						'query'   => $query,
					)
				);
				?>
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
		<?php
		$this->render(
			'admin/partials/list-pager',
			array(
				'page'  => $list,
				'url'   => $view['url'],
				'label' => __( 'Opportunities', 'affibloom' ),
			)
		);
		?>
	</div>
<?php endif; ?>
