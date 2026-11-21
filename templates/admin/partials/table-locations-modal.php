<?php
/**
 * Lasso's "Table Locations" (F25 R40, D13; Task T10): a large dialog with no buttons; the heading row "Content" (help
 * "This is the post or page this table was found in.") and "Action"; a row per post: its title (to its editor, new
 * tab), its address (new tab) and a view icon; six a page. tables.js fills it from GET /tables/{id}/locations and keeps
 * each table's page in the browser.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="affibloom-table-locations" class="affibloom-modal affibloom-table-locations" hidden
	data-failed="<?php esc_attr_e( 'The locations could not be loaded.', 'affibloom' ); ?>"
	<?php /* translators: %s: the post's title. */ ?>
	data-view-label="<?php esc_attr_e( 'View %s', 'affibloom' ); ?>"
	data-prev-label="<?php esc_attr_e( 'Previous page', 'affibloom' ); ?>" data-next-label="<?php esc_attr_e( 'Next page', 'affibloom' ); ?>">
	<div class="affibloom-modal__backdrop" data-affibloom-close></div>
	<div class="affibloom-modal__panel" role="dialog" aria-modal="true" aria-labelledby="affibloom-table-locations-title">
		<div class="affibloom-modal__head">
			<h2 id="affibloom-table-locations-title" class="affibloom-modal__title"><?php esc_html_e( 'Table Locations', 'affibloom' ); ?></h2>
			<button type="button" class="affibloom-modal__close" data-affibloom-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">
				<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
			</button>
		</div>
		<div class="affibloom-modal__body">
			<table class="affibloom-table affibloom-table-locations__list">
				<thead>
					<tr>
						<th scope="col">
							<span class="affibloom-th"><?php esc_html_e( 'Content', 'affibloom' ); ?></span>
							<?php
							$this->render(
								'admin/partials/help',
								array(
									'id'    => 'affibloom-table-locations-content-help',
									'text'  => __( 'This is the post or page this table was found in.', 'affibloom' ),
									'label' => __( 'Content', 'affibloom' ),
								)
							);
							?>
						</th>
						<th scope="col" class="affibloom-table-locations__action"><?php esc_html_e( 'Action', 'affibloom' ); ?></th>
					</tr>
				</thead>
				<tbody data-locations-rows aria-live="polite"></tbody>
			</table>
			<p class="affibloom-field__error" role="alert" data-locations-error hidden></p>
			<nav class="affibloom-add-pages" data-locations-pages hidden></nav>
		</div>
	</div>
</div>
