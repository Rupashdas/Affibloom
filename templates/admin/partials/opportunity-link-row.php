<?php
/**
 * One Link Opportunity (F22 R11, R12, D7; Task O1; Lasso's rows/link-opportunity-row.php): the URL (a new tab) with
 * Filter by URL and Open in New Window under it on hover or focus; the post to its editor with Filter by Content and
 * Open in New Window; the Suggestion to the product's page with Filter by Link (not when the list is already one
 * product's); the Type icon, which opens the preview; the Monetize switch, which comes with O10 and is shown disabled
 * until then; the × asks "Dismiss This Opportunity" (R44, Task O7). On the product page's tab the filter links are left
 * out (R35).
 *
 * @package Affibloom
 * @var array{opportunity: \Affibloom\Domain\Opportunity\Opportunity, filtered: bool, product_page?: bool} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Opportunity\OpportunityQuery;
use Affibloom\Interface\Admin\OpportunitiesScreen;
use Affibloom\Interface\Admin\ProductScreen;

$opportunity = $view['opportunity'];
$edit        = (string) get_edit_post_link( $opportunity->host_id, 'raw' );
$host_title  = '' !== $opportunity->host_title ? $opportunity->host_title : __( '(no title)', 'affibloom' );
/* translators: %s: the link's address. */
$monetize_label = sprintf( __( 'Monetize %s', 'affibloom' ), $opportunity->place->url );
/* translators: %s: the link's address. */
$dismiss_label = sprintf( __( 'Dismiss %s', 'affibloom' ), $opportunity->place->url );
?>
<tr data-location-id="<?php echo esc_attr( (string) $opportunity->place->id ); ?>">
	<td class="affibloom-col-url">
		<a class="affibloom-opportunity__main" href="<?php echo esc_url( $opportunity->place->url ); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo esc_attr( $opportunity->place->url ); ?>"><?php echo esc_html( $opportunity->place->url ); ?></a>
		<span class="affibloom-opportunity__actions">
			<?php if ( ! ( $view['product_page'] ?? false ) ) : ?>
			<a class="affibloom-opportunity__action" href="<?php echo esc_url( OpportunitiesScreen::url( new OpportunityQuery( '', '', '', 1, 0, OpportunityQuery::DOMAIN_LINKS ), 1, $opportunity->domain ) ); ?>"><?php esc_html_e( 'Filter by URL', 'affibloom' ); ?></a>
			<?php endif; ?>
			<a class="affibloom-opportunity__action" href="<?php echo esc_url( $opportunity->place->url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open in New Window', 'affibloom' ); ?></a>
		</span>
	</td>
	<td class="affibloom-col-content">
		<?php if ( '' !== $edit ) : ?>
			<a class="affibloom-opportunity__main" href="<?php echo esc_url( $edit ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $host_title ); ?></a>
		<?php else : ?>
			<span class="affibloom-opportunity__main"><?php echo esc_html( $host_title ); ?></span>
		<?php endif; ?>
		<span class="affibloom-opportunity__actions">
			<?php if ( ! ( $view['product_page'] ?? false ) ) : ?>
			<a class="affibloom-opportunity__action" href="<?php echo esc_url( OpportunitiesScreen::url( new OpportunityQuery( '', '', '', 1, 0, OpportunityQuery::POST_LINKS ), 1, '', null, $opportunity->host_id ) ); ?>"><?php esc_html_e( 'Filter by Content', 'affibloom' ); ?></a>
			<?php endif; ?>
			<a class="affibloom-opportunity__action" href="<?php echo esc_url( $opportunity->host_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open in New Window', 'affibloom' ); ?></a>
		</span>
	</td>
	<td class="affibloom-col-suggestion">
		<a class="affibloom-opportunity__main" href="<?php echo esc_url( ProductScreen::url( $opportunity->product_id ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $opportunity->product_name ); ?></a>
		<?php if ( ! $view['filtered'] && ! ( $view['product_page'] ?? false ) ) : ?>
			<span class="affibloom-opportunity__actions">
				<a class="affibloom-opportunity__action" href="<?php echo esc_url( OpportunitiesScreen::url( new OpportunityQuery( '', '', '', 1, $opportunity->product_id ) ) ); ?>"><?php esc_html_e( 'Filter by Link', 'affibloom' ); ?></a>
			</span>
		<?php endif; ?>
	</td>
	<td class="affibloom-col-type"><?php $this->render( 'admin/partials/location-type', array( 'place' => $opportunity->place ) ); ?></td>
	<td class="affibloom-col-monetize">
		<?php // Monetize turns on with Task O10; until then the switch is shown, off and disabled. ?>
		<span class="affibloom-toggle" title="<?php esc_attr_e( 'Coming soon', 'affibloom' ); ?>">
			<input type="checkbox" class="affibloom-toggle__input" role="switch" disabled aria-label="<?php echo esc_attr( $monetize_label ); ?>" aria-describedby="affibloom-soon-note">
			<span class="affibloom-toggle__track" aria-hidden="true"></span>
		</span>
	</td>
	<td class="affibloom-col-dismiss">
		<button type="button" class="affibloom-icon-button" data-affibloom-open="affibloom-dismiss-opportunity"
			data-dismiss-opportunity="<?php echo esc_attr( (string) $opportunity->place->id ); ?>"
			aria-label="<?php echo esc_attr( $dismiss_label ); ?>" title="<?php esc_attr_e( 'Dismiss', 'affibloom' ); ?>">
			<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
		</button>
	</td>
</tr>
