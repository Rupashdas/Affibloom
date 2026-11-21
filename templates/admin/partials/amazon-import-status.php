<?php
/**
 * The Amazon import's status block (F35 R31, D19; Task AM4; Lasso's amazon-auto-monetize-status-inner.php): the
 * heading, the Dashboard and queue line, the current batch while a run is going, and the placements with the index
 * versus Dashboard tip. GET /amazon-import renders this same partial, so the poller redraws it as the page drew it.
 *
 * @package Affibloom
 * @var array{status: \Affibloom\Domain\Monetize\AmazonImportStatus} $view
 */

defined( 'ABSPATH' ) || exit;

$import_status = $view['status'];
?>
<p class="affibloom-notice__text affibloom-import__title" data-amazon-complete="<?php echo $import_status->complete() ? '1' : '0'; ?>">
	<?php echo esc_html( $import_status->complete() ? __( 'Amazon import complete', 'affibloom' ) : __( 'Amazon import in progress', 'affibloom' ) ); ?>
</p>
<p class="affibloom-import__line">
	<?php
	if ( $import_status->complete() ) {
		/* translators: %s: how many Amazon products the Dashboard lists. */
		echo esc_html( sprintf( __( '%s unique Amazon links in Dashboard', 'affibloom' ), number_format_i18n( $import_status->dashboard ) ) );
	} else {
		/* translators: 1: Amazon products in the Dashboard, 2: unique Amazon products still queued. */
		echo esc_html( sprintf( __( '%1$s in Dashboard · ~%2$s unique Amazon products still queued', 'affibloom' ), number_format_i18n( $import_status->dashboard ), number_format_i18n( $import_status->queue ) ) );
	}
	?>
</p>
<?php if ( $import_status->running && $import_status->total > 0 ) : ?>
	<p class="affibloom-import__line">
		<?php
		/* translators: 1: links handled in the current batch, 2: the batch's size. */
		echo esc_html( sprintf( __( 'Current batch: %1$s / %2$s', 'affibloom' ), number_format_i18n( $import_status->completed ), number_format_i18n( $import_status->total ) ) );
		?>
	</p>
	<span class="affibloom-import__bar" aria-hidden="true"><span style="width: <?php echo esc_attr( (string) $import_status->percent() ); ?>%"></span></span>
<?php endif; ?>
<p class="affibloom-import__line affibloom-import__line--muted">
	<?php
	/* translators: %s: placements in the link index. */
	echo esc_html( sprintf( __( '%s placements indexed', 'affibloom' ), number_format_i18n( $import_status->indexed ) ) );
	$this->render(
		'admin/partials/help',
		array(
			'id'    => 'affibloom-import-placements-help',
			'text'  => __( 'Link Index counts every placement on your site. Dashboard lists unique managed links (Amazon products are deduped by ASIN + country).', 'affibloom' ),
			'label' => __( 'Placements', 'affibloom' ),
		)
	);
	?>
</p>
