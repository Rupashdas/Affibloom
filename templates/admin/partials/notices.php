<?php
/**
 * The notice region under the header of every Affibloom page (F38 D8, Task ON2; Lasso's #lasso_notifications): the
 * providers' notices, newest last, and the template affibloomNotice() (admin.js) fills for a script's notice. Saved
 * and failed messages after an action stay toasts.
 *
 * @package Affibloom
 * @var array{notices: list<\Affibloom\Domain\Shell\ShellNotice>} $view
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="affibloom-notices-bar">
	<div id="affibloom-notices" class="affibloom-notices" aria-live="polite">
		<?php
		foreach ( $view['notices'] as $shell_notice ) {
			$this->render( 'admin/partials/shell-notice', array( 'notice' => $shell_notice ) );
		}
		?>
	</div>
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
