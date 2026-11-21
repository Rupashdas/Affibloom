<?php
/**
 * Domain Opportunities' list region (F22 R17-R19, D7; Task O2; Lasso's opportunities/domain.php): "N Found", the table
 * Domain, the lightbulb ("This domain has possible opportunities"), Links, biggest first; Domain and Links sort; a row
 * opens Detected Links for its domain. GET /opportunities/domains renders this same partial (D3).
 *
 * @package Affibloom
 * @var array{list: \Affibloom\Domain\Opportunity\OpportunityPage, query: \Affibloom\Domain\Opportunity\OpportunityQuery, url: string} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Opportunity\OpportunityQuery;
use Affibloom\Interface\Admin\OpportunitiesScreen;

$list    = $view['list'];
$columns = array(
	// [ class, label, tooltip (or the hidden name of an unlabelled column), the column it sorts ('' none) ].
	array( 'domain', __( 'Domain', 'affibloom' ), '', 'domain' ),
	array( 'bulb', '', __( 'Possible opportunities', 'affibloom' ), '' ),
	array( 'links', __( 'Links', 'affibloom' ), '', 'count' ),
);
$tip     = __( 'This domain has possible opportunities', 'affibloom' );
?>
<p class="affibloom-found" data-found>
	<?php
	/* translators: %s: how many domains the list holds. */
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
			<table class="affibloom-table affibloom-table--domains">
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
					<?php foreach ( $list->items as $domain_row ) : ?>
						<tr>
							<td class="affibloom-col-domain">
								<a class="affibloom-opportunity__main" href="<?php echo esc_url( OpportunitiesScreen::url( new OpportunityQuery( '', '', '', 1, 0, OpportunityQuery::DOMAIN_LINKS ), 1, $domain_row->domain ) ); ?>"><?php echo esc_html( $domain_row->domain ); ?></a>
							</td>
							<td class="affibloom-col-bulb">
								<?php if ( $domain_row->has_opportunities() ) : ?>
									<span class="affibloom-alert affibloom-alert--opportunity" tabindex="0" data-tip="<?php echo esc_attr( $tip ); ?>">
										<?php $this->render( 'admin/partials/icon', array( 'name' => 'bulb' ) ); ?>
										<span class="screen-reader-text"><?php echo esc_html( $tip . ': ' . $domain_row->monetize_with ); ?></span>
									</span>
								<?php endif; ?>
							</td>
							<td class="affibloom-col-links"><?php echo esc_html( number_format_i18n( $domain_row->links ) ); ?></td>
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
				'label' => __( 'Domains', 'affibloom' ),
			)
		);
		?>
	</div>
<?php endif; ?>
