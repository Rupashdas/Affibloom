<?php
/**
 * Dashboard screen: the band with the title and subtitle (U10), then the products card. The overview tiles (U9)
 * are added by the tasks that provide their numbers (13, 31, 34).
 *
 * @package Affibloom
 * @var array{notice: string, notice_kind: string} $view
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap affibloom-admin">
	<?php $this->render( 'admin/partials/header', array( 'current' => 'dashboard' ) ); ?>

	<div class="affibloom-page">
		<div class="affibloom-band">
			<div class="affibloom-band__inner">
				<h1 class="affibloom-page__title"><?php esc_html_e( 'Dashboard', 'affibloom' ); ?></h1>
				<p class="affibloom-page__subtitle"><?php esc_html_e( 'Your affiliate products, their links and where they are used.', 'affibloom' ); ?></p>
			</div>
		</div>

		<div class="affibloom-page__body">
			<div class="affibloom-card affibloom-empty">
				<span class="affibloom-chip"><?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?></span>
				<p class="affibloom-empty__message"><?php esc_html_e( 'No products yet.', 'affibloom' ); ?></p>
			</div>
		</div>
	</div>
	<?php
	$this->render(
		'admin/partials/toast',
		array(
			'message' => $view['notice'],
			'kind'    => $view['notice_kind'],
		)
	);
	?>
</div>
