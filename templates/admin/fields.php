<?php
/**
 * The Fields list: the band with the title, the search and Add New Field, then the list region (Lasso's
 * `admin/views/fields/fields.php`; F26 R7). The arrangement is Lasso's, the look Aurora's. The list partial is the one
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
	<?php $this->render( 'admin/partials/header', array( 'current' => 'fields' ) ); ?>

	<div class="affibloom-page">
		<div class="affibloom-band">
			<div class="affibloom-band__inner affibloom-band__inner--split">
				<div class="affibloom-band__text">
					<h1 class="affibloom-page__title"><?php esc_html_e( 'Fields', 'affibloom' ); ?></h1>
					<p class="affibloom-page__subtitle"><?php esc_html_e( 'The details a product can carry, like size, weight, pros and cons, and where each one is used.', 'affibloom' ); ?></p>
				</div>
				<div class="affibloom-band__actions">
					<form class="affibloom-band__search" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" role="search">
						<input type="hidden" name="page" value="<?php echo esc_attr( FieldsScreen::SLUG ); ?>">
						<label class="screen-reader-text" for="affibloom-fields-search"><?php esc_html_e( 'Search Fields', 'affibloom' ); ?></label>
						<input type="search" id="affibloom-fields-search" class="affibloom-input affibloom-search" name="s"
							value="<?php echo esc_attr( $view['term'] ); ?>" placeholder="<?php esc_attr_e( 'Search Fields', 'affibloom' ); ?>">
					</form>
					<a class="affibloom-button affibloom-button--on-band" href="<?php echo esc_url( FieldScreen::url() ); ?>">
						<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
						<span><?php esc_html_e( 'Add New Field', 'affibloom' ); ?></span>
					</a>
				</div>
			</div>
		</div>

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
</div>
