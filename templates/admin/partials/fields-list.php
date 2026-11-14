<?php
/**
 * The Fields list region: the card with the table and its footer, or an empty state. GET /fields/list renders this same
 * partial for the in-place list (F26 D15). Empty is Lasso's own sentence (R11).
 *
 * @package Affibloom
 * @var array{listing: \Affibloom\Application\Fields\FieldListing, term: string, url: string} $view
 */

defined( 'ABSPATH' ) || exit;

$fields = $view['listing']->page;
$links  = paginate_links(
	array(
		// The list's own address (FieldsUrl), never the current request's: inside the REST route that is the API's.
		'base'      => add_query_arg( 'paged', '%#%', $view['url'] ),
		'format'    => '',
		'current'   => $fields->page,
		'total'     => $fields->pages,
		'end_size'  => 2,
		'mid_size'  => 1,
		'prev_text' => '<span class="screen-reader-text">' . esc_html__( 'Previous page', 'affibloom' ) . '</span>',
		'next_text' => '<span class="screen-reader-text">' . esc_html__( 'Next page', 'affibloom' ) . '</span>',
	)
);
?>
<?php if ( 0 === $fields->total ) : ?>
	<div class="affibloom-card affibloom-empty">
		<span class="affibloom-chip"><?php $this->render( 'admin/partials/icon', array( 'name' => 'info' ) ); ?></span>
		<p class="affibloom-empty__message"><?php esc_html_e( "Looks like we're coming up empty.", 'affibloom' ); ?></p>
	</div>
<?php else : ?>
	<div class="affibloom-card affibloom-card--table">
		<div class="affibloom-table-scroll">
			<table class="affibloom-table affibloom-table--fields">
				<thead>
					<tr>
						<th scope="col" class="affibloom-col-name"><span class="affibloom-th"><?php esc_html_e( 'Name', 'affibloom' ); ?></span></th>
						<th scope="col" class="affibloom-col-type"><span class="affibloom-th"><?php esc_html_e( 'Type', 'affibloom' ); ?></span></th>
						<th scope="col" class="affibloom-col-usage"><span class="affibloom-th"><?php esc_html_e( 'Usage', 'affibloom' ); ?></span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $fields->items as $field ) : ?>
						<?php
						$this->render(
							'admin/partials/field-list-row',
							array(
								'field' => $field,
								'usage' => $view['listing']->usage->of( $field->id ),
							)
						);
						?>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<div class="affibloom-pager">
			<span class="affibloom-pager__range">
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: first field shown, 2: last field shown, 3: fields in the whole list. */
						__( '%1$s-%2$s of %3$s', 'affibloom' ),
						number_format_i18n( $fields->first() ),
						number_format_i18n( $fields->last() ),
						number_format_i18n( $fields->total )
					)
				);
				?>
			</span>
			<?php if ( $links ) : ?>
				<nav class="affibloom-pagination" aria-label="<?php esc_attr_e( 'Fields', 'affibloom' ); ?>">
					<?php echo wp_kses_post( $links ); ?>
				</nav>
			<?php endif; ?>
		</div>
	</div>
<?php endif; ?>
