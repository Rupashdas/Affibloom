<?php
/**
 * The Groups list: the band with the title, the search and Add New Group, then the list region (Lasso's
 * `admin/views/groups/groups.php`; F23 R2). The arrangement is Lasso's, the look Aurora's. The list partial is the one
 * GET /groups renders, so the page and the in-place list share one template.
 *
 * @package Affibloom
 * @var array{groups: \Affibloom\Domain\Group\GroupPage, term: string, url: string, new: string, notice: string} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\GroupsScreen;
?>
<div class="wrap affibloom-admin">
	<?php $this->render( 'admin/partials/header', array( 'current' => 'groups' ) ); ?>

	<div class="affibloom-page">
		<div class="affibloom-band">
			<div class="affibloom-band__inner affibloom-band__inner--split">
				<div class="affibloom-band__text">
					<h1 class="affibloom-page__title"><?php esc_html_e( 'Groups', 'affibloom' ); ?></h1>
					<p class="affibloom-page__subtitle"><?php esc_html_e( 'Keep related products together and show them as a list, grid or gallery.', 'affibloom' ); ?></p>
				</div>
				<div class="affibloom-band__actions">
					<form class="affibloom-band__search" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" role="search">
						<input type="hidden" name="page" value="<?php echo esc_attr( GroupsScreen::SLUG ); ?>">
						<label class="screen-reader-text" for="affibloom-groups-search"><?php esc_html_e( 'Search Groups', 'affibloom' ); ?></label>
						<input type="search" id="affibloom-groups-search" class="affibloom-input affibloom-search" name="s"
							value="<?php echo esc_attr( $view['term'] ); ?>" placeholder="<?php esc_attr_e( 'Search Groups', 'affibloom' ); ?>">
					</form>
					<a class="affibloom-button affibloom-button--on-band" href="<?php echo esc_url( $view['new'] ); ?>">
						<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
						<span><?php esc_html_e( 'Add New Group', 'affibloom' ); ?></span>
					</a>
				</div>
			</div>
		</div>

		<div class="affibloom-page__body affibloom-groups">
			<div id="affibloom-groups-list" class="affibloom-list">
				<?php
				$this->render(
					'admin/partials/groups-list',
					array(
						'groups' => $view['groups'],
						'term'   => $view['term'],
						'url'    => $view['url'],
					)
				);
				?>
			</div>

			<p id="affibloom-list-status" class="screen-reader-text" role="status" aria-live="polite"></p>
			<template id="affibloom-list-error">
				<div class="affibloom-card affibloom-empty affibloom-list-error">
					<span class="affibloom-chip"><?php $this->render( 'admin/partials/icon', array( 'name' => 'refresh' ) ); ?></span>
					<p class="affibloom-empty__message"><?php esc_html_e( 'The list could not be loaded.', 'affibloom' ); ?></p>
					<button type="button" class="affibloom-button affibloom-button--secondary" data-retry><?php esc_html_e( 'Try again', 'affibloom' ); ?></button>
				</div>
			</template>
		</div>
	</div>
	<?php $this->render( 'admin/partials/toast', array( 'message' => $view['notice'] ) ); ?>
	<?php $this->render( 'admin/partials/toast-template' ); ?>
</div>
