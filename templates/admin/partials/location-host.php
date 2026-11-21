<?php
/**
 * A host post on the Locations tab (F12 L7, AC26; Task 36B): its title to its editor (new tab), its status when it is
 * not published, a group row's "(N locations)" beside them, and its permalink. Shared by the single row and the group
 * row.
 *
 * @package Affibloom
 * @var array{host: \Affibloom\Domain\LinkIndex\LocationHost, places?: int} $view
 */

defined( 'ABSPATH' ) || exit;

$host        = $view['host'];
$edit        = (string) get_edit_post_link( $host->id, 'raw' );
$host_title  = '' !== $host->title ? $host->title : __( '(no title)', 'affibloom' );
$host_status = $host->is_published() ? null : get_post_status_object( $host->status );
?>
<span class="affibloom-location__title">
	<?php if ( '' !== $edit ) : ?>
		<a href="<?php echo esc_url( $edit ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $host_title ); ?></a>
	<?php else : ?>
		<?php echo esc_html( $host_title ); ?>
	<?php endif; ?>
	<?php if ( ! $host->is_published() ) : ?>
		<span class="affibloom-location__status"><?php echo esc_html( null !== $host_status ? (string) $host_status->label : $host->status ); ?></span>
	<?php endif; ?>
	<?php if ( isset( $view['places'] ) ) : ?>
		<span class="affibloom-location__places">
			<?php
			/* translators: %s: how many places in this post use the product. */
			echo esc_html( sprintf( _n( '(%s location)', '(%s locations)', $view['places'], 'affibloom' ), number_format_i18n( $view['places'] ) ) );
			?>
		</span>
	<?php endif; ?>
</span>
<a class="affibloom-location__permalink" href="<?php echo esc_url( $host->url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $host->url ); ?></a>
