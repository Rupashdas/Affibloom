<?php
/**
 * The title (F5 R12): a link with Link from Display Title on, else a span; nothing without a name.
 *
 * @package Affibloom
 * @var \Affibloom\Application\Box\BoxView $view
 */

defined( 'ABSPATH' ) || exit;

if ( '' === $view->title ) {
	return;
}

$attributes = $view->link;
if ( $view->title_linked ) :
	?>
	<a class="affibloom-title"<?php require __DIR__ . '/attributes.php'; ?>>
		<?php echo esc_html( $view->title ); ?>
	</a>
<?php else : ?>
	<span class="affibloom-title"><?php echo esc_html( $view->title ); ?></span>
	<?php
endif;
