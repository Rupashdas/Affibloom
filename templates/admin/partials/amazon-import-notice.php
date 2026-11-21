<?php
/**
 * The Amazon import banner in the notice region (F35 R30, D19; Task AM4; Lasso's #lasso-amazon-auto-monetize-banner):
 * the status block, which amazon-import-status.js redraws every 15 seconds and hides at completion, and × for this
 * person (POST /amazon-import/dismiss).
 *
 * @package Affibloom
 * @var array{notice: \Affibloom\Domain\Shell\ShellNotice} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Monetize\AmazonImportStatus;

[ $import_queue, $import_dashboard, $import_indexed, $import_running, $import_total, $import_completed, $import_context ] = array_map( 'intval', $view['notice']->args + array( 0, 0, 0, 0, 0, 0, 0 ) );
?>
<?php // The Dashboard's first-day explanation (R32, Task AM5) is complete from the start: the poller leaves it. ?>
<section class="affibloom-notice affibloom-notice--update affibloom-import" data-amazon-import-notice<?php echo 1 === $import_context ? ' data-amazon-context' : ''; ?> aria-label="<?php esc_attr_e( 'Amazon import', 'affibloom' ); ?>">
	<span class="affibloom-chip"><?php $this->render( 'admin/partials/icon', array( 'name' => 'refresh' ) ); ?></span>
	<div class="affibloom-import__body" data-affibloom-amazon-status aria-live="polite">
		<?php $this->render( 'admin/partials/amazon-import-status', array( 'status' => new AmazonImportStatus( $import_queue, $import_dashboard, $import_indexed, 1 === $import_running, $import_total, $import_completed ) ) ); ?>
	</div>
	<button type="button" class="affibloom-notice__close" data-amazon-import-dismiss aria-label="<?php esc_attr_e( 'Dismiss', 'affibloom' ); ?>">
		<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
	</button>
</section>
