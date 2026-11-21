<?php
/**
 * Settings → General, the Link Index card's Amazon figures (F35 R34, D21; Task AM5; Lasso's
 * render_general_settings_stats_html): "Amazon import" with the Dashboard and queue, or "Complete" with the Dashboard;
 * "Link Index vs Dashboard"; and Lasso's explainer. Shown under R32's rule (AmazonImportStatusService::general()).
 *
 * @package Affibloom
 * @var array{status: \Affibloom\Domain\Monetize\AmazonImportStatus} $view
 */

defined( 'ABSPATH' ) || exit;

$import_status = $view['status'];
$dashboard     = number_format_i18n( $import_status->dashboard );
?>
<dl class="affibloom-facts" data-amazon-general-stats>
	<?php if ( $import_status->complete() ) : ?>
		<dt><?php esc_html_e( 'Complete', 'affibloom' ); ?></dt>
		<?php /* translators: %s: Dashboard link count. */ ?>
		<dd><?php echo esc_html( sprintf( __( '%s in Dashboard', 'affibloom' ), $dashboard ) ); ?></dd>
	<?php else : ?>
		<dt><?php esc_html_e( 'Amazon import', 'affibloom' ); ?></dt>
		<?php /* translators: 1: Dashboard link count, 2: queued product count. */ ?>
		<dd><?php echo esc_html( sprintf( __( '%1$s in Dashboard · ~%2$s queued', 'affibloom' ), $dashboard, number_format_i18n( $import_status->queue ) ) ); ?></dd>
	<?php endif; ?>
	<dt><?php esc_html_e( 'Link Index vs Dashboard', 'affibloom' ); ?></dt>
	<?php /* translators: 1: indexed placements, 2: Dashboard links. */ ?>
	<dd><?php echo esc_html( sprintf( __( '%1$s indexed placements · %2$s Dashboard links', 'affibloom' ), number_format_i18n( $import_status->indexed ), $dashboard ) ); ?></dd>
</dl>
<p class="affibloom-field__hint"><?php esc_html_e( 'Link Index counts every placement on your site. Dashboard lists unique managed links (Amazon products are deduped by ASIN + country).', 'affibloom' ); ?></p>
