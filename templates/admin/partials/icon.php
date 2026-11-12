<?php
/**
 * One of Affibloom's 20×20 outline icons, inline and hidden from screen readers (the text next to it carries
 * the meaning). Drawn for Affibloom (docs/ui/design-system.md, "Icons").
 *
 * @package Affibloom
 * @var array{name: string} $view
 */

defined( 'ABSPATH' ) || exit;

$icon_paths = array(
	'home'     => '<path d="M3.5 9 10 3.5 16.5 9M5.5 7.5V16h9V7.5"/>',
	'settings' => '<circle cx="10" cy="10" r="2.5"/><path d="M10 2.5v2M10 15.5v2M2.5 10h2M15.5 10h2M4.7 4.7l1.4 1.4M13.9 13.9l1.4 1.4M4.7 15.3l1.4-1.4M13.9 6.1l1.4-1.4"/>',
	'new-tab'  => '<path d="M11 4h5v5M16 4l-7 7M14 12v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h3"/>',
	'nofollow' => '<path d="M8.5 11.5a3 3 0 0 0 4.2 0l2.6-2.6a3 3 0 0 0-4.2-4.2l-.8.8M11.5 8.5a3 3 0 0 0-4.2 0L4.7 11.1a3 3 0 0 0 4.2 4.2l.8-.8M4 4l12 12"/>',
	'tag'      => '<path d="M3.5 10.2V4.5a1 1 0 0 1 1-1h5.7l6.3 6.3a1 1 0 0 1 0 1.4l-5.7 5.7a1 1 0 0 1-1.4 0z"/><circle cx="7.2" cy="7.2" r="1.2"/>',
	'info'     => '<circle cx="10" cy="10" r="7"/><path d="M10 9v4.5M10 6.5v.01"/>',
	'plus'     => '<path d="M10 4v12M4 10h12"/>',
	'check'    => '<path d="M5 10.5 8.5 14 15 6.5"/>',
);

if ( ! isset( $icon_paths[ $view['name'] ] ) ) {
	return;
}
?>
<svg class="affibloom-icon" viewBox="0 0 20 20" aria-hidden="true" focusable="false">
<?php
echo wp_kses(
	$icon_paths[ $view['name'] ],
	array(
		'path'   => array( 'd' => true ),
		'circle' => array(
			'cx' => true,
			'cy' => true,
			'r'  => true,
		),
	)
);
?>
</svg>
