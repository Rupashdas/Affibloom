<?php
/**
 * Lasso's saved-view bar (dashboard.php, lasso-dashboard-views-bar): "Saved view", the "All links" pill and one
 * pill per saved view with its ×. Each pill is a real link to the list it names, so it works without JavaScript;
 * the script loads it in place. The pill showing the current list is marked (aria-current), as Lasso marks the
 * active view.
 *
 * @package Affibloom
 * @var array{views: \Affibloom\Domain\Dashboard\SavedViews, term: string, link_kind: \Affibloom\Domain\Product\LinkKind, per_page: int, group: int, filter?: string, placement?: \Affibloom\Domain\Product\Placement, group_ids: list<int>} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Application\Products\ProductService;
use Affibloom\Domain\Product\LinkKind;
use Affibloom\Domain\Product\Placement;
use Affibloom\Interface\Admin\DashboardUrl;

$saved_active = null;
$placement    = $view['placement'] ?? Placement::Any;
foreach ( $view['views']->all() as $saved ) {
	if ( $saved->shows( $view['term'], $view['link_kind'], $view['per_page'], $view['group'], $view['filter'] ?? '', $placement ) ) {
		$saved_active = $saved->id;
		break;
	}
}
$all_active = null === $saved_active && '' === $view['term'] && LinkKind::All === $view['link_kind'] && 0 === $view['group'] && Placement::Any === $placement;
// A view whose group has been deleted shows all groups (F23 edge case 5).
$view_group = static fn( int $group ): int => ( $group <= 0 || in_array( $group, $view['group_ids'], true ) ) ? $group : 0;
?>
<div class="affibloom-views" data-views>
	<span class="affibloom-views__label" id="affibloom-views-label"><?php esc_html_e( 'Saved view', 'affibloom' ); ?></span>
	<ul class="affibloom-views__list" aria-labelledby="affibloom-views-label">
		<li class="affibloom-views__item">
			<a class="affibloom-pill" href="<?php echo esc_url( DashboardUrl::to( '', LinkKind::All, 1, ProductService::PER_PAGE_DEFAULT ) ); ?>"
				data-view-s="" data-view-link-type="" data-view-per-page="0" data-view-group="0" data-view-filter="" data-view-placements=""
				<?php echo $all_active ? 'aria-current="true"' : ''; ?>><?php esc_html_e( 'All links', 'affibloom' ); ?></a>
		</li>
		<?php
		foreach ( $view['views']->all() as $saved ) :
			/* translators: %s: the saved view's name. */
			$remove_label = sprintf( __( 'Delete saved view %s', 'affibloom' ), $saved->name );
			?>
			<li class="affibloom-views__item">
				<?php $pill_url = DashboardUrl::to( $saved->term, $saved->link_kind, 1, $saved->per_page, $view_group( $saved->group ), 0, null, $saved->placement ); ?>
				<a class="affibloom-pill" href="<?php echo esc_url( '' === $saved->filter ? $pill_url : add_query_arg( 'filter', $saved->filter, $pill_url ) ); ?>"
					data-view-s="<?php echo esc_attr( $saved->term ); ?>"
					data-view-link-type="<?php echo esc_attr( $saved->link_kind->value ); ?>"
					data-view-per-page="<?php echo esc_attr( (string) $saved->per_page ); ?>"
					data-view-group="<?php echo esc_attr( (string) $view_group( $saved->group ) ); ?>"
					data-view-filter="<?php echo esc_attr( $saved->filter ); ?>"
					data-view-placements="<?php echo esc_attr( $saved->placement->value ); ?>"
					<?php echo $saved_active === $saved->id ? 'aria-current="true"' : ''; ?>><?php echo esc_html( $saved->name ); ?></a>
				<button type="button" class="affibloom-pill__remove" data-affibloom-open="affibloom-view-delete"
					data-view-id="<?php echo esc_attr( $saved->id ); ?>" data-view-name="<?php echo esc_attr( $saved->name ); ?>"
					aria-haspopup="dialog" aria-label="<?php echo esc_attr( $remove_label ); ?>">
					<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
				</button>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
