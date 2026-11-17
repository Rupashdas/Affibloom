<?php
/**
 * The Fields list: the page head with the title, the search and Add New Field, then the list region (Lasso's
 * `admin/views/fields/fields.php`; F26 R7). The arrangement is Lasso's, the look Meadow's. The list partial is the one
 * GET /fields/list renders, so the page and the in-place list share one template. "Learn" (Lasso's help site) is left out.
 *
 * @package Affibloom
 * @var array{listing: \Affibloom\Application\Fields\FieldListing, term: string, url: string, notice: string} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\FieldScreen;
use Affibloom\Interface\Admin\FieldsScreen;
?>
<div class="wrap affibloom-admin">
	<?php $this->render( 'admin/partials/header' ); ?>

	<div class="affibloom-page">
		<?php
		$this->render(
			'admin/partials/page-head',
			array(
				'title'        => __( 'Fields', 'affibloom' ),
				'subtitle'     => __( 'The details a product can carry, like size, weight, pros and cons, and where each one is used.', 'affibloom' ),
				'actions'      => 'admin/partials/list-head-actions',
				'actions_args' => array(
					'slug'         => FieldsScreen::SLUG,
					'search_id'    => 'affibloom-fields-search',
					'search_label' => __( 'Search Fields', 'affibloom' ),
					'term'         => $view['term'],
					'add_url'      => FieldScreen::url(),
					'add_label'    => __( 'Add New Field', 'affibloom' ),
				),
			)
		);
		?>

		<div class="affibloom-page__body affibloom-fields">
			<div id="affibloom-fields-list" class="affibloom-list">
				<?php
				$this->render(
					'admin/partials/fields-list',
					array(
						'listing' => $view['listing'],
						'term'    => $view['term'],
						'url'     => $view['url'],
					)
				);
				?>
			</div>

			<p id="affibloom-list-status" class="screen-reader-text" role="status" aria-live="polite"></p>
			<template id="affibloom-list-error">
				<div class="affibloom-card affibloom-empty affibloom-list-error">
					<span class="affibloom-chip"><?php $this->render( 'admin/partials/icon', array( 'name' => 'refresh' ) ); ?></span>
					<p class="affibloom-empty__message"><?php esc_html_e( 'Failed to load data.', 'affibloom' ); ?></p>
					<button type="button" class="affibloom-button affibloom-button--secondary" data-retry><?php esc_html_e( 'Try again', 'affibloom' ); ?></button>
				</div>
			</template>
		</div>
	</div>
	<?php $this->render( 'admin/partials/toast', array( 'message' => $view['notice'] ) ); ?>
	<?php $this->render( 'admin/partials/toast-template' ); ?>
	<?php $this->render( 'admin/partials/footer' ); ?>
</div>
