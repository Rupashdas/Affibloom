<?php
/**
 * One detected link (F22 R21; Tasks O2, O4; Lasso's rows/domain-link-row.php): the URL (a new tab), the post to its
 * editor with Filter by Content and Open in New Window, the Link (its context, which opens the preview), the Type icon
 * and the Monetize switch (R64, Task O10: on when monetized here, on and disabled when tied by its own address), with
 * Edit in post (N1-B) among the post's actions.
 *
 * @package Affibloom
 * @var array{link: \Affibloom\Domain\Opportunity\DetectedLink} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Opportunity\AmazonDomains;
use Affibloom\Domain\Opportunity\OpportunityQuery;
use Affibloom\Interface\Admin\OpportunitiesScreen;

$detected   = $view['link'];
$place      = $detected->place;
$edit       = (string) get_edit_post_link( $detected->host_id, 'raw' );
$host_title = '' !== $detected->host_title ? $detected->host_title : __( '(no title)', 'affibloom' );
?>
<tr data-location-id="<?php echo esc_attr( (string) $place->id ); ?>">
	<td class="affibloom-col-url">
		<a class="affibloom-opportunity__main" href="<?php echo esc_url( $place->url ); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo esc_attr( $place->url ); ?>"><?php echo esc_html( $place->url ); ?></a>
		<?php if ( $detected->assigned > 0 ) : ?>
			<?php /* translators: %s: the product's name. */ ?>
			<span class="affibloom-opportunity__assigned"><?php echo esc_html( sprintf( __( 'Monetized with %s', 'affibloom' ), $detected->assigned_name ) ); ?></span>
		<?php endif; ?>
	</td>
	<td class="affibloom-col-content">
		<?php if ( '' !== $edit ) : ?>
			<a class="affibloom-opportunity__main" href="<?php echo esc_url( $edit ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $host_title ); ?></a>
		<?php else : ?>
			<span class="affibloom-opportunity__main"><?php echo esc_html( $host_title ); ?></span>
		<?php endif; ?>
		<span class="affibloom-opportunity__actions">
			<a class="affibloom-opportunity__action" href="<?php echo esc_url( OpportunitiesScreen::url( new OpportunityQuery( '', '', '', 1, 0, OpportunityQuery::POST_LINKS ), 1, '', null, $detected->host_id ) ); ?>"><?php esc_html_e( 'Filter by Content', 'affibloom' ); ?></a>
			<a class="affibloom-opportunity__action" href="<?php echo esc_url( $detected->host_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open in New Window', 'affibloom' ); ?></a>
			<?php $in_post = OpportunitiesScreen::edit_in_post_url( $detected->host_id, (int) $place->id ); ?>
			<?php if ( '' !== $in_post ) : ?>
				<a class="affibloom-opportunity__action" href="<?php echo esc_url( $in_post ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Edit in post', 'affibloom' ); ?></a>
			<?php endif; ?>
		</span>
	</td>
	<td class="affibloom-col-link">
		<button type="button" class="affibloom-location__context<?php echo $place->is_display() ? ' affibloom-location__context--code' : ''; ?>" data-location-preview="<?php echo esc_attr( (string) $place->id ); ?>"
			title="<?php echo esc_attr( $place->context() ); ?>"><?php echo esc_html( $place->context() ); ?></button>
	</td>
	<td class="affibloom-col-type"><?php $this->render( 'admin/partials/location-type', array( 'place' => $place ) ); ?></td>
	<td class="affibloom-col-monetize">
		<?php
		$this->render(
			'admin/partials/monetize-switch',
			array(
				'row'    => $place->id,
				'url'    => $place->url,
				'state'  => $detected->assigned > 0 ? 'on' : ( $detected->tied ? 'content' : 'off' ),
				'amazon' => AmazonDomains::in_url( $place->url ),
			)
		);
		?>
	</td>
</tr>
