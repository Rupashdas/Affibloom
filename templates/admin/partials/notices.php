<?php
/**
 * The notice region under the header of every Affibloom page (F38 D8, Task ON2; Lasso's #lasso_notifications): the
 * providers' notices, newest last, and the template affibloomNotice() (admin.js) fills for a script's notice. Saved
 * and failed messages after an action stay toasts. The region is the top of the page's column, its notices white
 * cards on the page background (Meadow, MD2; no navy strip).
 *
 * @package Affibloom
 * @var array{notices: list<\Affibloom\Domain\Shell\ShellNotice>} $view
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="affibloom-notices" class="affibloom-notices" aria-live="polite">
	<?php
	foreach ( $view['notices'] as $shell_notice ) {
		// The bulk Amazon sync banner (C1I) and the Amazon import's (F35 R30, Task AM4) have partials of their own; every
		// other notice is words.
		$shell_partial = match ( $shell_notice->key ) {
			\Affibloom\Interface\Admin\AmazonNotices::BULK_SYNC        => 'admin/partials/amazon-bulk-sync',
			\Affibloom\Interface\Admin\AmazonImportNotice::KEY         => 'admin/partials/amazon-import-notice',
			default                                                       => 'admin/partials/shell-notice',
		};
		$this->render( $shell_partial, array( 'notice' => $shell_notice ) );
	}
	?>
</div>
<?php // Lasso's words for a failure that brings none of its own (R20). ?>
<template id="affibloom-notice-template" data-fallback="<?php esc_attr_e( 'An unexpected error has occurred please try again later.', 'affibloom' ); ?>">
	<div class="affibloom-notice">
		<span class="affibloom-chip">
			<span data-notice-icon="success"><?php $this->render( 'admin/partials/icon', array( 'name' => 'check' ) ); ?></span>
			<span data-notice-icon="error"><?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?></span>
			<span data-notice-icon="warning"><?php $this->render( 'admin/partials/icon', array( 'name' => 'warning' ) ); ?></span>
		</span>
		<p class="affibloom-notice__text"></p>
		<button type="button" class="affibloom-notice__close" data-affibloom-dismiss aria-label="<?php esc_attr_e( 'Dismiss this notice', 'affibloom' ); ?>">
			<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
		</button>
	</div>
</template>
