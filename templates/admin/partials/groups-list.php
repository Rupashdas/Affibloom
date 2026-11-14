<?php
/**
 * The Groups list region: the card with the table and its footer, or an empty state. GET /groups renders this same
 * partial for the in-place list (F23 D13).
 *
 * Empty with no search is Lasso's education panel (`admin/views/education/groups.php`: its heading and text; the link
 * to Lasso's help site is left out, as the Learn link is); empty after a search says so.
 *
 * @package Affibloom
 * @var array{groups: \Affibloom\Domain\Group\GroupPage, term: string, url: string} $view
 */

defined( 'ABSPATH' ) || exit;

$groups    = $view['groups'];
$searching = '' !== $view['term'];
$links     = paginate_links(
	array(
		// The list's own address (GroupsUrl), never the current request's: inside the REST route that is the API's.
		'base'      => add_query_arg( 'paged', '%#%', $view['url'] ),
		'format'    => '',
		'current'   => $groups->current(),
		'total'     => $groups->pages(),
		'end_size'  => 2,
		'mid_size'  => 1,
		'prev_text' => '<span class="screen-reader-text">' . esc_html__( 'Previous page', 'affibloom' ) . '</span>',
		'next_text' => '<span class="screen-reader-text">' . esc_html__( 'Next page', 'affibloom' ) . '</span>',
	)
);
?>
<?php if ( 0 === $groups->total ) : ?>
	<div class="affibloom-card affibloom-empty">
		<span class="affibloom-chip">
			<?php $this->render( 'admin/partials/icon', array( 'name' => $searching ? 'info' : 'layers' ) ); ?>
		</span>
		<?php if ( $searching ) : ?>
			<p class="affibloom-empty__message"><?php esc_html_e( 'No groups match your search.', 'affibloom' ); ?></p>
		<?php else : ?>
			<p class="affibloom-empty__message"><?php esc_html_e( 'Create Multi-Product Displays & Organize Your Links', 'affibloom' ); ?></p>
			<p class="affibloom-empty__text"><?php esc_html_e( 'When you add links into groups, you can display them across your site as Lists, Grids, or Galleries. You can also use groups to categorize similar links making them easier to find.', 'affibloom' ); ?></p>
		<?php endif; ?>
	</div>
<?php else : ?>
	<div class="affibloom-card affibloom-card--table">
		<div class="affibloom-table-scroll">
			<table class="affibloom-table affibloom-table--groups">
				<thead>
					<tr>
						<th scope="col" class="affibloom-col-name"><span class="affibloom-th"><?php esc_html_e( 'Name', 'affibloom' ); ?></span></th>
						<th scope="col" class="affibloom-col-description"><span class="affibloom-th"><?php esc_html_e( 'Description', 'affibloom' ); ?></span></th>
						<th scope="col" class="affibloom-col-links"><span class="affibloom-th"><?php esc_html_e( 'Links', 'affibloom' ); ?></span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $groups->items as $group ) : ?>
						<?php $this->render( 'admin/partials/group-row', array( 'group' => $group ) ); ?>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<div class="affibloom-pager">
			<span class="affibloom-pager__range">
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: first group shown, 2: last group shown, 3: groups in the whole list. */
						__( '%1$s-%2$s of %3$s', 'affibloom' ),
						number_format_i18n( $groups->first() ),
						number_format_i18n( $groups->last() ),
						number_format_i18n( $groups->total )
					)
				);
				?>
			</span>
			<?php if ( $links ) : ?>
				<nav class="affibloom-pagination" aria-label="<?php esc_attr_e( 'Groups', 'affibloom' ); ?>">
					<?php echo wp_kses_post( $links ); ?>
				</nav>
			<?php endif; ?>
		</div>
	</div>
<?php endif; ?>
