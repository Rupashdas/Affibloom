<?php
/**
 * An in-place list's footer (Tasks O1-O3): Lasso's "a-b of N" and the page links, built on the list's own address (never
 * the current request's: inside a REST route that is the API's). Taken out of the Link Opportunities list on its second
 * use (Task O2).
 *
 * @package Affibloom
 * @var array{page: \Affibloom\Domain\Opportunity\OpportunityPage, url: string, label: string} $view
 */

defined( 'ABSPATH' ) || exit;

$list_page = $view['page'];
$links     = paginate_links(
	array(
		'base'      => add_query_arg( 'paged', '%#%', $view['url'] ),
		'format'    => '',
		'current'   => $list_page->current(),
		'total'     => $list_page->pages(),
		'end_size'  => 2,
		'mid_size'  => 1,
		'prev_text' => '<span class="screen-reader-text">' . esc_html__( 'Previous page', 'affibloom' ) . '</span>',
		'next_text' => '<span class="screen-reader-text">' . esc_html__( 'Next page', 'affibloom' ) . '</span>',
	)
);
?>
<div class="affibloom-pager">
	<span class="affibloom-pager__range">
		<?php
		echo esc_html(
			sprintf(
				/* translators: 1: first row shown, 2: last row shown, 3: rows in the whole list. */
				__( '%1$s-%2$s of %3$s', 'affibloom' ),
				number_format_i18n( $list_page->first() ),
				number_format_i18n( $list_page->last() ),
				number_format_i18n( $list_page->total )
			)
		);
		?>
	</span>
	<?php if ( $links ) : ?>
		<nav class="affibloom-pagination" aria-label="<?php echo esc_attr( $view['label'] ); ?>">
			<?php echo wp_kses_post( $links ); ?>
		</nav>
	<?php endif; ?>
</div>
