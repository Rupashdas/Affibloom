<?php
/**
 * The toast's JS twin (Task 15A): admin.js's affibloomToast() fills a copy with its message; the error one has a
 * cross. Moved out of the product page when the Dashboard's quick edit became its second user (Task 16A). A toast may
 * carry one action, such as "Opportunity dismissed." with Undo (Task O7).
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;
?>
<template id="affibloom-toast-template">
	<div class="affibloom-toast" role="status" aria-live="polite">
		<span class="affibloom-toast__icon" data-toast-icon="success"><?php $this->render( 'admin/partials/icon', array( 'name' => 'check' ) ); ?></span>
		<span class="affibloom-toast__icon" data-toast-icon="error" hidden><?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?></span>
		<span class="affibloom-toast__message"></span>
		<button type="button" class="affibloom-toast__action" data-toast-action hidden></button>
	</div>
</template>
