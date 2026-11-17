<?php
/**
 * The Settings head: the page title, its subtitle (U10) and General / Display / Amazon as a segmented control under
 * them (Meadow, Task MD3; it was a gradient band, the name stays for its three callers).
 *
 * Shared by the Settings tabs and the Display screen (extracted on its second use).
 *
 * @package Affibloom
 * @var array{tab: string, tabs: list<string>} $view
 */

defined( 'ABSPATH' ) || exit;

$labels = array(
	'general' => __( 'General', 'affibloom' ),
	'display' => __( 'Display', 'affibloom' ),
	'amazon'  => __( 'Amazon', 'affibloom' ),
);
$items  = array();
foreach ( $view['tabs'] as $tab_key ) {
	$items[] = array(
		'label'   => $labels[ $tab_key ] ?? $tab_key,
		'url'     => admin_url( 'admin.php?page=affibloom-settings&tab=' . $tab_key ),
		'current' => $tab_key === $view['tab'],
	);
}

$this->render(
	'admin/partials/page-head',
	array(
		'title'    => __( 'Settings', 'affibloom' ),
		'subtitle' => __( 'Defaults for new links, the product box and Amazon.', 'affibloom' ),
		'tabs'     => array(
			'label' => __( 'Settings sections', 'affibloom' ),
			'items' => $items,
		),
	)
);
