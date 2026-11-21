<?php
/**
 * Settings → Amazon, "Amazon import status" under the Auto-Monetization switch while it is on (F35 R33; Task AM5;
 * Lasso's amazon-auto-monetize-settings-card.php): the status block, kept current by amazon-import-status.js.
 *
 * @package Affibloom
 * @var array{status: \Affibloom\Domain\Monetize\AmazonImportStatus} $view
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="affibloom-import-card" role="group" aria-labelledby="affibloom-import-card-title">
	<p class="affibloom-label" id="affibloom-import-card-title"><?php esc_html_e( 'Amazon import status', 'affibloom' ); ?></p>
	<div class="affibloom-import__body" data-affibloom-amazon-status aria-live="polite">
		<?php $this->render( 'admin/partials/amazon-import-status', array( 'status' => $view['status'] ) ); ?>
	</div>
</div>
