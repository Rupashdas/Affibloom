<?php
/**
 * A group shown as a list (F30 R21; Lasso's list.php under Affibloom's names): the `<ol>` or `<ul>` its bullets ask
 * for, one card per product (list-card.php). An empty group is the empty list.
 *
 * @package Affibloom
 * @var \Affibloom\Application\Box\ListView $group_list
 */

defined( 'ABSPATH' ) || exit;

$attributes = $group_list->wrapper;
$list_tag   = 'ul' === $group_list->tag ? 'ul' : 'ol';
?>
<<?php echo esc_html( $list_tag ); ?> id="<?php echo esc_attr( $group_list->anchor ); ?>" class="<?php echo esc_attr( $group_list->classes ); ?>"<?php require __DIR__ . '/partials/attributes.php'; ?>>
<?php
foreach ( $group_list->cards as $view ) {
	require __DIR__ . '/list-card.php';
}
?>
</<?php echo esc_html( $list_tag ); ?>>
