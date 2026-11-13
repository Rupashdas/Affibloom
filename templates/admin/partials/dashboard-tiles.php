<?php
/**
 * The overview tiles (U9) above the list: Lasso's three counters (broken, out of stock, opportunities) and the
 * Aurora design's Products and Clicks (30 days). Products is live; the others are placeholders until their data
 * exists. Out of stock waits for the Amazon Creators API credentials, not for a task, so it says that instead
 * (deviation 52).
 *
 * @package Affibloom
 * @var array{library: int} $view
 */

defined( 'ABSPATH' ) || exit;

$tiles = array(
	// [ icon, number or null for a placeholder, label, note under a placeholder ].
	array( 'link', $view['library'], __( 'Products', 'affibloom' ), '' ),
	array( 'unlink', null, __( 'Broken links', 'affibloom' ), __( 'Coming soon', 'affibloom' ) ),
	array( 'box', null, __( 'Out of stock', 'affibloom' ), __( 'Needs the Amazon API', 'affibloom' ) ),
	array( 'bulb', null, __( 'Opportunities', 'affibloom' ), __( 'Coming soon', 'affibloom' ) ),
	array( 'cursor', null, __( 'Clicks (30 days)', 'affibloom' ), __( 'Coming soon', 'affibloom' ) ),
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
