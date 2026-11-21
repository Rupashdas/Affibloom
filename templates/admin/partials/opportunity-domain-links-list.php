<?php
/**
 * Detected Links' list region (F22 R20, R21, D7; Task O2; Lasso's opportunities/domain-links.php): "N Found", the table
 * URL, Content (with Filter by Content and Open in New Window under it), Link (the text, image address or display, which
 * opens the preview), Type, Monetize; URL and Content sort; no dismiss. GET /opportunities/domains/{d}/links renders
 * this same partial (D3). Filter by Content and Monetize come with O4 and O10.
 *
 * @package Affibloom
 * @var array{list: \Affibloom\Domain\Opportunity\OpportunityPage, query: \Affibloom\Domain\Opportunity\OpportunityQuery, url: string} $view
 */

defined( 'ABSPATH' ) || exit;

$list    = $view['list'];
$columns = array(
	// [ class, label, tooltip, the column it sorts ('' none) ].
	array( 'url', __( 'URL', 'affibloom' ), __( 'This is a URL found on your site that may be able to be monetized.', 'affibloom' ), 'url' ),
	array( 'content', __( 'Content', 'affibloom' ), __( 'Where we found this URL. Click link to edit.', 'affibloom' ), 'post_title' ),
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
						<?php $this->render( 'admin/partials/opportunity-detected-row', array( 'link' => $detected ) ); ?>
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
				'label' => __( 'Detected Links', 'affibloom' ),
			)
		);
		?>
	</div>
<?php endif; ?>
