<?php
/**
 * Dashboard screen.
 *
 * @package Affibloom
 * @var array<string, mixed> $view
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap affibloom-admin">
	<?php $this->render( 'admin/partials/header', array( 'current' => 'dashboard' ) ); ?>

	<div class="affibloom-page">
		<div class="affibloom-page__head">
			<h1 class="affibloom-page__title"><?php esc_html_e( 'Dashboard', 'affibloom' ); ?></h1>
		</div>

		<div class="affibloom-card affibloom-empty">
			<p><?php esc_html_e( 'No products yet.', 'affibloom' ); ?></p>
		</div>
	</div>
</div>
