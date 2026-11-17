<?php
/**
 * The Groups list: the page head with the title, the search and Add New Group, then the list region (Lasso's
 * `admin/views/groups/groups.php`; F23 R2). The arrangement is Lasso's, the look Meadow's. The list partial is the one
 * GET /groups renders, so the page and the in-place list share one template.
 *
 * @package Affibloom
 * @var array{groups: \Affibloom\Domain\Group\GroupPage, term: string, url: string, new: string, notice: string} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\GroupsScreen;
?>
<div class="wrap affibloom-admin">
	<?php $this->render( 'admin/partials/header' ); ?>

	<div class="affibloom-page">
		<?php
		$this->render(
			'admin/partials/page-head',
			array(
				'title'        => __( 'Groups', 'affibloom' ),
				'subtitle'     => __( 'Keep related products together and show them as a list, grid or gallery.', 'affibloom' ),
				'actions'      => 'admin/partials/list-head-actions',
				'actions_args' => array(
					'slug'         => GroupsScreen::SLUG,
					'search_id'    => 'affibloom-groups-search',
					'search_label' => __( 'Search Groups', 'affibloom' ),
					'term'         => $view['term'],
					'add_url'      => $view['new'],
					'add_label'    => __( 'Add New Group', 'affibloom' ),
				),
			)
		);
		?>

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
	<?php $this->render( 'admin/partials/footer' ); ?>
</div>
