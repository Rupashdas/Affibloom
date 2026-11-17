<?php
/**
 * The Dashboard's counts (U9) in its page head (Meadow, Task MD4; design system 0b.4), in two parts:
 *
 * - "stats": Lasso's three attention counters (Broken links, Out of stock, Opportunities) as stat cards right of the
 *   title. Each shows its number, or a dash and a note until its data exists; Out of stock waits for the Amazon
 *   Creators API credentials, not for a task, so it says that instead (deviation 52). They become buttons that filter
 *   the list in the tasks that bring their filters (31B, O8).
 * - "summary": Products (live) and Clicks (30 days) as one quiet line under the subtitle.
 *
 * The `.affibloom-tile*` class names stay on both, so the tasks that fill them need no change.
 *
 * @package Affibloom
 * @var array{part: string, library: int} $view
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

$tiles = array(
	// [ icon, number or null for a placeholder, label, note under a placeholder ].
	array( 'unlink', null, __( 'Broken links', 'affibloom' ), __( 'Coming soon', 'affibloom' ) ),
	array( 'box', null, __( 'Out of stock', 'affibloom' ), __( 'Needs the Amazon API', 'affibloom' ) ),
	array( 'bulb', null, __( 'Opportunities', 'affibloom' ), __( 'Coming soon', 'affibloom' ) ),
);
?>
<div class="affibloom-tiles">
	<?php foreach ( $tiles as $tile ) : ?>
		<div class="affibloom-tile<?php echo null === $tile[1] ? ' affibloom-tile--soon' : ''; ?>">
			<span class="affibloom-chip"><?php $this->render( 'admin/partials/icon', array( 'name' => $tile[0] ) ); ?></span>
			<span class="affibloom-tile__number">
				<?php if ( null === $tile[1] ) : ?>
					<span aria-hidden="true">—</span>
				<?php else : ?>
					<?php echo esc_html( number_format_i18n( $tile[1] ) ); ?>
				<?php endif; ?>
			</span>
			<span class="affibloom-tile__label"><?php echo esc_html( $tile[2] ); ?></span>
			<?php if ( '' !== $tile[3] ) : ?>
				<span class="affibloom-tile__note"><?php echo esc_html( $tile[3] ); ?></span>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>
