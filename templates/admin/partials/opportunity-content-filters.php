<?php
/**
 * Content Opportunities' filter bar (F22 R25, R32, D17, D18; Task O3; Lasso's opportunities/content.php 14-63): Show
 * (All content / Has unmonetized links), Post type (All types / Posts / Pages), Category (All categories and every
 * category, empty ones too) and Min link clicks. Each change reloads page 1 in place (opportunities.js); without
 * JavaScript it is a form. Export CSV (R57, R58, Task O9) ends the bar, as in Lasso: off with Lasso's title while the
 * file would be empty; opportunities.js keeps it in step with the list and downloads the file.
 *
 * @package Affibloom
 * @var array{filter: \Affibloom\Domain\Opportunity\ContentFilter, exports?: int|null} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Opportunity\ContentFilter;
use Affibloom\Interface\Admin\OpportunitiesScreen;

$filter     = $view['filter'];
$categories = get_categories( array( 'hide_empty' => false ) );
?>
<form class="affibloom-filter-bar" id="affibloom-content-filters" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
	<input type="hidden" name="page" value="<?php echo esc_attr( OpportunitiesScreen::SLUG ); ?>">
	<input type="hidden" name="tab" value="content">
	<label class="affibloom-filter-bar__field">
		<span class="affibloom-filters__label"><?php esc_html_e( 'Show', 'affibloom' ); ?></span>
		<select class="affibloom-select" name="show">
			<option value="all" <?php selected( ContentFilter::ALL, $filter->show ); ?>><?php esc_html_e( 'All content', 'affibloom' ); ?></option>
			<option value="unmonetized" <?php selected( ContentFilter::UNMONETIZED, $filter->show ); ?>><?php esc_html_e( 'Has unmonetized links', 'affibloom' ); ?></option>
		</select>
	</label>
	<label class="affibloom-filter-bar__field">
		<span class="affibloom-filters__label"><?php esc_html_e( 'Post type', 'affibloom' ); ?></span>
		<select class="affibloom-select" name="content_type">
			<option value="" <?php selected( '', $filter->post_type ); ?>><?php esc_html_e( 'All types', 'affibloom' ); ?></option>
			<option value="post" <?php selected( 'post', $filter->post_type ); ?>><?php esc_html_e( 'Posts', 'affibloom' ); ?></option>
			<option value="page" <?php selected( 'page', $filter->post_type ); ?>><?php esc_html_e( 'Pages', 'affibloom' ); ?></option>
		</select>
	</label>
	<label class="affibloom-filter-bar__field">
		<span class="affibloom-filters__label"><?php esc_html_e( 'Category', 'affibloom' ); ?></span>
		<select class="affibloom-select" name="category">
			<option value="0" <?php selected( 0, $filter->category ); ?>><?php esc_html_e( 'All categories', 'affibloom' ); ?></option>
			<?php foreach ( $categories as $category ) : ?>
				<option value="<?php echo esc_attr( (string) $category->term_id ); ?>" <?php selected( $category->term_id, $filter->category ); ?>><?php echo esc_html( $category->name ); ?></option>
			<?php endforeach; ?>
		</select>
	</label>
	<label class="affibloom-filter-bar__field">
		<span class="affibloom-filters__label"><?php esc_html_e( 'Min link clicks', 'affibloom' ); ?></span>
		<input type="number" class="affibloom-input affibloom-filter-bar__number" name="min_clicks" min="0" step="1" value="<?php echo esc_attr( (string) $filter->min_clicks ); ?>">
	</label>
	<button type="submit" class="affibloom-button affibloom-button--small affibloom-button--primary affibloom-no-js"><?php esc_html_e( 'Apply', 'affibloom' ); ?></button>
	<?php if ( isset( $view['exports'] ) ) : ?>
		<?php
		$export_on  = __( 'Export unmonetized anchors to CSV', 'affibloom' );
		$export_off = __( 'No content opportunities to export for the current filters.', 'affibloom' );
		?>
		<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary affibloom-filter-bar__export" data-export-csv
			data-export-path="/affibloom/v1/opportunities/content/export" data-title-on="<?php echo esc_attr( $export_on ); ?>" data-title-off="<?php echo esc_attr( $export_off ); ?>"
			data-exporting="<?php esc_attr_e( 'Exporting', 'affibloom' ); ?>" data-failed="<?php esc_attr_e( 'Export failed.', 'affibloom' ); ?>"
			<?php /* translators: %s: number of anchors. */ ?>
			data-exported-one="<?php esc_attr_e( 'Exported 1 anchor.', 'affibloom' ); ?>" data-exported-many="<?php esc_attr_e( 'Exported %s anchors.', 'affibloom' ); ?>"
			title="<?php echo esc_attr( 0 === $view['exports'] ? $export_off : $export_on ); ?>" <?php disabled( 0, $view['exports'] ); ?>>
			<?php $this->render( 'admin/partials/icon', array( 'name' => 'download' ) ); ?>
			<span data-export-label><?php esc_html_e( 'Export CSV', 'affibloom' ); ?></span>
		</button>
	<?php endif; ?>
</form>
