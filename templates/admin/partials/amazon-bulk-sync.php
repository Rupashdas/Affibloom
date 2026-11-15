<?php
/**
 * The bulk Amazon sync banner in the notice region (C1I; Lasso's amazon-bulk-sync-pending.php, R41): "Bulk Amazon
 * sync", the pending count, Sync Amazon data (disabled while a run is going, and without credentials with "Needs the
 * Amazon API", D12), the run's summary, which amazon-bulk-sync.js fills, and × for this person.
 *
 * @package Affibloom
 * @var array{notice: \Affibloom\Domain\Shell\ShellNotice} $view
 */

defined( 'ABSPATH' ) || exit;

[ $sync_pending, $sync_running, $sync_can ] = array_map( 'intval', $view['notice']->args + array( 0, 0, 0 ) );
?>
<section class="affibloom-notice affibloom-notice--update affibloom-bulk-sync" aria-labelledby="affibloom-bulk-sync-title" data-bulk-sync
	data-running="<?php echo esc_attr( (string) $sync_running ); ?>"
	data-starting="<?php esc_attr_e( 'Starting…', 'affibloom' ); ?>"
	data-syncing="<?php esc_attr_e( 'Syncing…', 'affibloom' ); ?>"
	data-sync="<?php esc_attr_e( 'Sync Amazon data', 'affibloom' ); ?>"
	data-complete="<?php esc_attr_e( 'Sync complete.', 'affibloom' ); ?>"
	data-succeeded="<?php esc_attr_e( 'Succeeded:', 'affibloom' ); ?>"
	data-skipped="<?php esc_attr_e( 'Skipped:', 'affibloom' ); ?>"
	data-failed="<?php esc_attr_e( 'Failed:', 'affibloom' ); ?>"
	data-failures="<?php esc_attr_e( 'Failures:', 'affibloom' ); ?>">
	<span class="affibloom-chip"><?php $this->render( 'admin/partials/icon', array( 'name' => 'refresh' ) ); ?></span>
	<div class="affibloom-bulk-sync__body">
		<p class="affibloom-notice__text" id="affibloom-bulk-sync-title"><?php esc_html_e( 'Bulk Amazon sync', 'affibloom' ); ?></p>
		<p class="affibloom-bulk-sync__count">
			<?php
			/* translators: %d: how many Amazon products have no Amazon data yet. */
			echo esc_html( sprintf( _n( '%d Amazon link is ready to sync product data.', '%d Amazon links are ready to sync product data.', $sync_pending, 'affibloom' ), $sync_pending ) );
			?>
		</p>
		<?php if ( 1 !== $sync_can ) : ?>
			<p class="affibloom-bulk-sync__reason" id="affibloom-bulk-sync-reason"><?php esc_html_e( 'Needs the Amazon API', 'affibloom' ); ?></p>
		<?php endif; ?>
		<p class="affibloom-bulk-sync__summary" data-bulk-sync-summary aria-live="polite" hidden></p>
	</div>
	<div class="affibloom-bulk-sync__actions">
		<button type="button" class="affibloom-button affibloom-button--primary affibloom-button--small" data-bulk-sync-start
			<?php disabled( 1 !== $sync_can || 1 === $sync_running || $sync_pending <= 0 ); ?>
			<?php echo 1 !== $sync_can ? 'aria-describedby="affibloom-bulk-sync-reason"' : ''; ?>><?php esc_html_e( 'Sync Amazon data', 'affibloom' ); ?></button>
		<button type="button" class="affibloom-notice__close" data-bulk-sync-dismiss aria-label="<?php esc_attr_e( 'Dismiss', 'affibloom' ); ?>">
			<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
		</button>
	</div>
</section>
