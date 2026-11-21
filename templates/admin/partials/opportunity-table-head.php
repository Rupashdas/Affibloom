<?php
/**
 * An Opportunities table's header row (F22 R7, R10, R19, R21, R26; Tasks O1-O3): each column's label, a sort button
 * for the list's sortable columns (in-place-list.js reads data-list-orderby and data-list-sort; the caret shows the
 * applied order) and Lasso's tooltip. Taken out of the Link Opportunities list on its second use (Task O2).
 *
 * @package Affibloom
 * @var array{columns: list<array{0: string, 1: string, 2: string, 3: string}>, query: \Affibloom\Domain\Opportunity\OpportunityQuery} $view
 */

defined( 'ABSPATH' ) || exit;

$query = $view['query'];
?>
<thead>
	<tr>
		<?php foreach ( $view['columns'] as $column ) : ?>
			<?php
			$applied = '' !== $column[3] && $column[3] === $query->orderby ? $query->order : '';
			$aria    = '' === $applied ? 'none' : ( 'asc' === $applied ? 'ascending' : 'descending' );
			?>
			<th scope="col" class="affibloom-col-<?php echo esc_attr( $column[0] ); ?>"<?php echo '' === $column[3] ? '' : ' aria-sort="' . esc_attr( $aria ) . '"'; ?>>
				<?php if ( '' !== $column[3] ) : ?>
					<?php [ $next_column, $next_order ] = $query->next( $column[3] ); ?>
					<button type="button" class="affibloom-sort" data-list-orderby="<?php echo esc_attr( $next_column ); ?>" data-list-sort="<?php echo esc_attr( $next_order ); ?>">
						<span><?php echo esc_html( $column[1] ); ?></span>
						<?php if ( '' !== $applied ) : ?>
							<?php $this->render( 'admin/partials/icon', array( 'name' => 'asc' === $applied ? 'chevron-up' : 'chevron-down' ) ); ?>
						<?php endif; ?>
					</button>
				<?php elseif ( '' !== $column[1] ) : ?>
					<span class="affibloom-th"><?php echo esc_html( $column[1] ); ?></span>
				<?php else : ?>
					<span class="screen-reader-text"><?php echo esc_html( $column[2] ); ?></span>
				<?php endif; ?>
				<?php
				if ( '' !== $column[2] && '' !== $column[1] ) {
					$this->render(
						'admin/partials/help',
						array(
							'id'    => 'affibloom-opportunities-' . $column[0] . '-help',
							'text'  => $column[2],
							'label' => $column[1],
						)
					);
				}
				?>
			</th>
		<?php endforeach; ?>
	</tr>
</thead>
