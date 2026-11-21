<?php
/**
 * The Tables list (F25 R4, R5, D14, D16; Task T1; Lasso's tables/header.php and tables.php): the page head with the
 * title, "Search All <n> Tables" and Create Table, then the list region. The list partial is the one GET /tables
 * renders, so the page and the in-place list share one template.
 *
 * @package Affibloom
 * @var array{tables: \Affibloom\Domain\Table\TablePage, locations: array<int, int>, all: int, term: string, url: string, notice?: string} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\TablesScreen;

/* translators: %s: how many tables there are. */
$search_label = sprintf( __( 'Search All %s Tables', 'affibloom' ), number_format_i18n( $view['all'] ) );
?>
<div class="wrap affibloom-admin">
	<?php $this->render( 'admin/partials/header' ); ?>

	<div class="affibloom-page">
		<?php
		$this->render(
			'admin/partials/page-head',
			array(
				'title'        => __( 'Tables', 'affibloom' ),
				'subtitle'     => __( 'Compare similar products side by side.', 'affibloom' ),
				'actions'      => 'admin/partials/list-head-actions',
				'actions_args' => array(
					'slug'         => TablesScreen::SLUG,
					'search_id'    => 'affibloom-tables-search',
					'search_label' => $search_label,
					'term'         => $view['term'],
					'add_url'      => '',
					'add_modal'    => 'affibloom-table-create',
					'add_label'    => __( 'Create Table', 'affibloom' ),
				),
			)
		);
		?>

		<div class="affibloom-page__body affibloom-tables">
			<div id="affibloom-tables-list" class="affibloom-list" data-list-path="/affibloom/v1/tables">
				<?php
				$this->render(
					'admin/partials/tables-list',
					array(
						'tables'    => $view['tables'],
						'locations' => $view['locations'],
						'term'      => $view['term'],
						'url'       => $view['url'],
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
	<span id="affibloom-soon-note" hidden><?php esc_html_e( 'Coming soon', 'affibloom' ); ?></span>
	<?php $this->render( 'admin/partials/toast', array( 'message' => $view['notice'] ?? '' ) ); ?>
	<?php $this->render( 'admin/partials/toast-template' ); ?>
	<?php $this->render( 'admin/partials/table-create-modal' ); ?>
	<?php $this->render( 'admin/partials/table-locations-modal' ); ?>
	<?php $this->render( 'admin/partials/table-clone-modal' ); ?>
	<?php $this->render( 'admin/partials/footer' ); ?>
</div>
