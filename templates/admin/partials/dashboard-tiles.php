<?php
/**
 * The Dashboard's counts (U9) in its page head (Meadow, Task MD4; design system 0b.4), in two parts:
 *
 * - "stats": Lasso's three attention counters (Broken links, Out of stock, Opportunities) as stat cards right of the
 *   title. Broken links and Out of stock are the list's alert tabs (Task 31B); Opportunities waits for F22 (O8).
 * - "summary": Products (live) and Clicks (30 days) as one quiet line under the subtitle.
 *
 * The `.affibloom-tile*` class names stay on both, so the tasks that fill them need no change.
 *
 * @package Affibloom
 * @var array{part: string, library: int, counts?: array{broken: int, out_of_stock: int, warm_up: bool, amazon_api: bool}|null, tab?: string, base?: array<string, string>} $view
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
		<span class="affibloom-tile affibloom-tile--line affibloom-tile--soon">
			<span class="affibloom-tile__label"><?php esc_html_e( 'Clicks (30 days)', 'affibloom' ); ?></span>
			<span class="affibloom-tile__number"><?php $this->render( 'admin/partials/soon', array( 'variant' => 'cell' ) ); ?></span>
		</span>
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
 * Amazon API the out-of-stock card says the check needs it. Opportunities stays a placeholder until F22.
 */
$alert_tiles = array(
	array( 'broken-links', 'unlink', __( 'Broken links', 'affibloom' ), __( 'See broken URLs', 'affibloom' ), $counts['broken'] ?? null ),
	array( 'out-of-stock', 'box', __( 'Out of stock', 'affibloom' ), __( 'See out-of-stock products', 'affibloom' ), $counts['out_of_stock'] ?? null ),
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
		unset( $args['filter'], $args['order_by'], $args['order_type'], $args['paged'] );
		if ( ! $active ) {
			$args += array(
				'filter'     => $tile[0],
				'order_by'   => 'detection_date',
				'order_type' => 'asc',
			);
		}
		$href = add_query_arg( array_map( 'rawurlencode', array( 'page' => \Affibloom\Interface\Admin\AdminMenu::SLUG ) + $args ), admin_url( 'admin.php' ) );
		?>
		<a class="affibloom-tile affibloom-tile--alert affibloom-tile--<?php echo esc_attr( $tile[0] ); ?> affibloom-filter-<?php echo esc_attr( 'broken-links' === $tile[0] ? 'broken' : 'out-of-stock' ); ?>"
			href="<?php echo esc_url( $href ); ?>" data-alert-tab="<?php echo esc_attr( $tile[0] ); ?>" data-tip="<?php echo esc_attr( $tip ); ?>"
			data-base-tip="<?php echo esc_attr( $tile[3] ); ?>"<?php echo 'out-of-stock' === $tile[0] && ! $counts['amazon_api'] ? ' data-amazon-tip="' . esc_attr( $amazon_line ) . '"' : ''; ?>
			<?php echo $active ? 'aria-current="true"' : ''; ?> aria-busy="<?php echo $warm ? 'true' : 'false'; ?>">
			<span class="affibloom-chip"><?php $this->render( 'admin/partials/icon', array( 'name' => $tile[1] ) ); ?></span>
			<span class="affibloom-tile__number">
				<span data-alert-count="<?php echo esc_attr( $tile[0] ); ?>"><?php echo esc_html( number_format_i18n( $tile[4] ) ); ?></span>
				<span class="affibloom-tile__warm" data-alert-warm<?php echo $warm ? '' : ' hidden'; ?>><?php $this->render( 'admin/partials/icon', array( 'name' => 'clock' ) ); ?></span>
			</span>
			<?php // The visible label and the count make the link's name: "Broken links (3)" (U2). ?>
			<span class="affibloom-tile__label"><?php echo esc_html( $tile[2] ); ?><span class="screen-reader-text"> (<span data-alert-count="<?php echo esc_attr( $tile[0] ); ?>"><?php echo esc_html( number_format_i18n( $tile[4] ) ); ?></span>)</span></span>
		</a>
	<?php endforeach; ?>
	<div class="affibloom-tile affibloom-tile--soon">
		<span class="affibloom-chip"><?php $this->render( 'admin/partials/icon', array( 'name' => 'bulb' ) ); ?></span>
		<span class="affibloom-tile__number"><span aria-hidden="true">—</span></span>
		<span class="affibloom-tile__label"><?php esc_html_e( 'Opportunities', 'affibloom' ); ?></span>
		<span class="affibloom-tile__note"><?php esc_html_e( 'Coming soon', 'affibloom' ); ?></span>
	</div>
</div>
