<?php
/**
 * The Dashboard tour (F38 R59-R63, D18, AC12; Task ON8): Lasso's steps and words (its name taken out; the Help and Chat
 * steps not built, decisions B2, B10), each pointing at a part of the page; tour.js shows them one at a time and drops
 * any whose part is not on the page. Printed only while the tour is due.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;

// Each step: what it points at (a CSS selector), its text, and whether the Filters panel is open for it (R61).
$tour_steps = array(
	array( '.affibloom-header__action[data-affibloom-open="affibloom-add-modal"]', __( 'Create your first link here. Paste any URL to start customizing and sharing it.', 'affibloom' ), false ),
	array( '.affibloom-header__nav a[href$="page=affibloom"]', __( 'Your Dashboard lists every link. Filter, search, and open any link to edit it.', 'affibloom' ), false ),
	array( '#affibloom-search', __( 'Search by link name or URL. Use the keyboard shortcut shown next to the box for a faster jump.', 'affibloom' ), false ),
	array( '.affibloom-filters__toggle', __( 'Open Filters to narrow links by group, type, placements, and comparison tables. Changes apply as you toggle.', 'affibloom' ), true ),
	array( '#affibloom-filters-panel', __( 'Pick group, link type, placements, or a comparison table here. Save a view when you find a filter set you reuse.', 'affibloom' ), true ),
	array( '.affibloom-header__nav a[href$="page=affibloom-opportunities"]', __( "Opportunities identify links and domains in your content that aren't monetized yet.", 'affibloom' ), false ),
	array( '.affibloom-header__nav a[href$="page=affibloom-tables"]', __( 'Tables are comparison layouts you can embed on posts. Build once, reuse across your site.', 'affibloom' ), false ),
	array( '.affibloom-header__nav a[href$="page=affibloom-groups"]', __( 'Create Groups to organize related links so you can manage and display them together.', 'affibloom' ), false ),
);
?>
<div class="affibloom-tour" data-tour hidden>
	<div class="affibloom-tour__shade" data-tour-shade></div>
	<div class="affibloom-tour__spot" data-tour-spot></div>
	<div class="affibloom-tour__tip" role="dialog" aria-labelledby="affibloom-tour-count" aria-describedby="affibloom-tour-text" data-tour-tip>
		<?php /* translators: 1: the step's number, 2: the number of steps. */ ?>
		<p class="affibloom-tour__count" id="affibloom-tour-count" data-tour-count data-format="<?php esc_attr_e( 'Step %1$d of %2$d', 'affibloom' ); ?>"></p>
		<p class="affibloom-tour__text" id="affibloom-tour-text" data-tour-text aria-live="polite"></p>
		<div class="affibloom-tour__dots" data-tour-dots aria-hidden="true"></div>
		<div class="affibloom-tour__actions">
			<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-tour-skip><?php esc_html_e( 'Skip', 'affibloom' ); ?></button>
			<span class="affibloom-tour__spacer"></span>
			<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-tour-back><?php esc_html_e( 'Back', 'affibloom' ); ?></button>
			<button type="button" class="affibloom-button affibloom-button--small affibloom-button--primary" data-tour-next data-next="<?php esc_attr_e( 'Next', 'affibloom' ); ?>" data-done="<?php esc_attr_e( 'Done', 'affibloom' ); ?>"><?php esc_html_e( 'Next', 'affibloom' ); ?></button>
		</div>
	</div>
	<ol hidden>
		<?php foreach ( $tour_steps as [ $target, $text, $filters ] ) : ?>
			<li data-tour-step data-target="<?php echo esc_attr( $target ); ?>" <?php echo $filters ? 'data-filters' : ''; ?>><?php echo esc_html( $text ); ?></li>
		<?php endforeach; ?>
	</ol>
</div>
