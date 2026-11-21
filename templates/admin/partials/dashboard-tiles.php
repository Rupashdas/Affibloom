<?php
/**
 * The Dashboard's counts (U9) in its page head (Meadow, Task MD4; design system 0b.4), in two parts:
 *
 * - "stats": Lasso's three attention counters (Broken links, Out of stock, Opportunities) as stat cards right of the
 *   title. Each is one of the list's tabs: Broken links and Out of stock (Task 31B), Opportunities (F22 R50-R52, Task O8).
 * - "summary": Products (live) and Clicks (30 days: the sum over published products, F12 AC12; Task 34B) as one quiet
 *   line under the subtitle.
 *
 * The `.affibloom-tile*` class names stay on both, so the tasks that fill them need no change.
 *
 * @package Affibloom
 * @var array{part: string, library: int, clicks?: int|null, counts?: array{broken: int, out_of_stock: int, opportunities?: int, warm_up: bool, amazon_api: bool}|null, tab?: string, base?: array<string, string>} $view
 */

defined( 'ABSPATH' ) || exit;

if ( 'summary' === $view['part'] ) :
	?>
	<p class="affibloom-summary">
		<span class="affibloom-tile affibloom-tile--line">
			<span class="affibloom-tile__label"><?php esc_html_e( 'Products', 'affibloom' ); ?></span>
			<?php // Rewritten by dashboard.js after each list load (Task 13I). ?>
			<span class="affibloom-tile__number" data-library-count><?php echo esc_html( number_format_i18n( $view['library'] ) ); ?></span>
		</span>
		<span class="affibloom-summary__sep" aria-hidden="true"></span>
		<?php if ( isset( $view['clicks'] ) ) : ?>
			<span class="affibloom-tile affibloom-tile--line affibloom-tile--success">
				<span class="affibloom-tile__label"><?php esc_html_e( 'Clicks (30 days)', 'affibloom' ); ?></span>
				<span class="affibloom-tile__number" data-clicks-total><?php echo esc_html( number_format_i18n( $view['clicks'] ) ); ?></span>
			</span>
		<?php else : ?>
			<span class="affibloom-tile affibloom-tile--line affibloom-tile--soon">
				<span class="affibloom-tile__label"><?php esc_html_e( 'Clicks (30 days)', 'affibloom' ); ?></span>
				<span class="affibloom-tile__number"><?php $this->render( 'admin/partials/soon', array( 'variant' => 'cell' ) ); ?></span>
			</span>
		<?php endif; ?>
	</p>
	<?php
	return;
endif;

$counts    = $view['counts'] ?? null;
$shown_tab = $view['tab'] ?? '';
$warm      = null !== $counts && $counts['warm_up'];

/*
 * Broken links and Out of stock filter the list (F11 R23, R25, D15, D16, D7; Task 31B): each is a link to the list on
 * its tab (the tab on screen links back to the whole list), which dashboard.js loads in place; its number follows the
 * search and the filters. Lasso's tooltips; for 24 hours after activation a clock and Lasso's warm-up line; without the
 * Amazon API the out-of-stock card says the check needs it. Opportunities (F22 R50, R51, D13; Task O8) lists the products
 * with Link Opportunities, sorted by their count, biggest first.
 */
$alert_tiles = array(
	array( 'broken-links', 'unlink', __( 'Broken links', 'affibloom' ), __( 'See broken URLs', 'affibloom' ), $counts['broken'] ?? null ),
	array( 'out-of-stock', 'box', __( 'Out of stock', 'affibloom' ), __( 'See out-of-stock products', 'affibloom' ), $counts['out_of_stock'] ?? null ),
	array( 'opportunities', 'bulb', __( 'Opportunities', 'affibloom' ), __( 'See opportunities', 'affibloom' ), $counts['opportunities'] ?? null ),
);
// The sort each tab opens with: the oldest issue first, or the most opportunities first (R51).
$tab_order   = array(
	'broken-links'  => array( 'alert', 'asc' ),
	'out-of-stock'  => array( 'alert', 'asc' ),
	'opportunities' => array( 'opportunity_count', 'desc' ),
);
$warm_line   = __( 'Totals may update for up to 24 hours while links are checked.', 'affibloom' );
$amazon_line = __( 'Amazon products are checked through the Amazon API, which is not connected (Settings → Amazon).', 'affibloom' );
?>
<div class="affibloom-tiles" data-alert-tiles data-warm-line="<?php echo esc_attr( $warm_line ); ?>">
	<?php foreach ( $alert_tiles as $tile ) : ?>
		<?php
		if ( null === $tile[4] ) {
			continue;
		}
		$active = $tile[0] === $shown_tab;
		$tip    = $tile[3] . ( $warm ? "\n" . $warm_line : '' ) . ( 'out-of-stock' === $tile[0] && ! $counts['amazon_api'] ? "\n" . $amazon_line : '' );
		$args   = $view['base'] ?? array();
		unset( $args['filter'], $args['orderby'], $args['order'], $args['paged'] );
		if ( ! $active ) {
			$args += array(
				'filter'  => $tile[0],
				'orderby' => $tab_order[ $tile[0] ][0],
				'order'   => $tab_order[ $tile[0] ][1],
			);
		}
		$href = add_query_arg( array_map( 'rawurlencode', array( 'page' => \Affibloom\Interface\Admin\AdminMenu::SLUG ) + $args ), admin_url( 'admin.php' ) );
		?>
		<a class="affibloom-tile affibloom-tile--alert affibloom-tile--<?php echo esc_attr( $tile[0] ); ?> affibloom-filter-<?php echo esc_attr( 'broken-links' === $tile[0] ? 'broken' : $tile[0] ); ?>"
			href="<?php echo esc_url( $href ); ?>" data-alert-tab="<?php echo esc_attr( $tile[0] ); ?>" data-tab-orderby="<?php echo esc_attr( $tab_order[ $tile[0] ][0] ); ?>"
			data-tab-order="<?php echo esc_attr( $tab_order[ $tile[0] ][1] ); ?>" data-tip="<?php echo esc_attr( $tip ); ?>"
			data-base-tip="<?php echo esc_attr( $tile[3] ); ?>"<?php echo 'out-of-stock' === $tile[0] && ! $counts['amazon_api'] ? ' data-amazon-tip="' . esc_attr( $amazon_line ) . '"' : ''; ?>
			<?php echo $active ? 'aria-current="true"' : ''; ?> aria-busy="<?php echo $warm ? 'true' : 'false'; ?>"
			<?php // The link's name is U2's "Broken links (3)"; dashboard.js rewrites it with the count. ?>
			aria-label="<?php echo esc_attr( sprintf( '%s (%s)', $tile[2], number_format_i18n( $tile[4] ) ) ); ?>" data-alert-name="<?php echo esc_attr( $tile[2] ); ?>">
			<span class="affibloom-chip"><?php $this->render( 'admin/partials/icon', array( 'name' => $tile[1] ) ); ?></span>
			<span class="affibloom-tile__number">
				<span data-alert-count="<?php echo esc_attr( $tile[0] ); ?>"><?php echo esc_html( number_format_i18n( $tile[4] ) ); ?></span>
				<span class="affibloom-tile__warm" data-alert-warm<?php echo $warm ? '' : ' hidden'; ?>><?php $this->render( 'admin/partials/icon', array( 'name' => 'clock' ) ); ?></span>
			</span>
			<span class="affibloom-tile__label"><?php echo esc_html( $tile[2] ); ?></span>
		</a>
	<?php endforeach; ?>
</div>
