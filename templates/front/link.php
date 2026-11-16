<?php
/**
 * A product placed as a plain text link (F31 A10 link, A40; Lasso's shortcode 485-496 under Affibloom's names): the
 * product's link with its target and rel, its ID and name, and the `label` (else the box's title, else the name).
 *
 * @package Affibloom
 * @var \Affibloom\Application\Box\BoxView $view
 */

defined( 'ABSPATH' ) || exit;

$primary    = $view->buttons[0]->attributes ?? $view->link;
$attributes = array_filter(
	array(
		'href'                => $primary['href'] ?? '',
		'target'              => $primary['target'] ?? '',
		'rel'                 => $primary['rel'] ?? '',
		'data-affibloom-id'   => $primary['data-affibloom-id'] ?? '',
		'data-affibloom-name' => $primary['data-affibloom-name'] ?? '',
	),
	static fn( string $value ): bool => '' !== $value
);
?>
<a<?php require __DIR__ . '/partials/attributes.php'; ?>><?php echo esc_html( $view->inline['text'] ?? '' ); ?></a><?php
