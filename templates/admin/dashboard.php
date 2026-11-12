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
		<h1 class="affibloom-page__title"><?php esc_html_e( 'Dashboard', 'affibloom' ); ?></h1>

		<div class="affibloom-card affibloom-empty">
			<span class="affibloom-empty__icon" aria-hidden="true">
				<svg width="18" height="18" viewBox="0 0 18 18" focusable="false"><path d="M9 3v12M3 9h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
			</span>
			<p class="affibloom-empty__message"><?php esc_html_e( 'No products yet.', 'affibloom' ); ?></p>
		</div>
	</div>
</div>
