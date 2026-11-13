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
	'home'         => '<path d="M3.5 9 10 3.5 16.5 9M5.5 7.5V16h9V7.5"/>',
	'settings'     => '<circle cx="10" cy="10" r="2.5"/><path d="M10 2.5v2M10 15.5v2M2.5 10h2M15.5 10h2M4.7 4.7l1.4 1.4M13.9 13.9l1.4 1.4M4.7 15.3l1.4-1.4M13.9 6.1l1.4-1.4"/>',
	'new-tab'      => '<path d="M11 4h5v5M16 4l-7 7M14 12v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h3"/>',
	'nofollow'     => '<path d="M8.5 11.5a3 3 0 0 0 4.2 0l2.6-2.6a3 3 0 0 0-4.2-4.2l-.8.8M11.5 8.5a3 3 0 0 0-4.2 0L4.7 11.1a3 3 0 0 0 4.2 4.2l.8-.8M4 4l12 12"/>',
	'tag'          => '<path d="M3.5 10.2V4.5a1 1 0 0 1 1-1h5.7l6.3 6.3a1 1 0 0 1 0 1.4l-5.7 5.7a1 1 0 0 1-1.4 0z"/><circle cx="7.2" cy="7.2" r="1.2"/>',
	'info'         => '<circle cx="10" cy="10" r="7"/><path d="M10 9v4.5M10 6.5v.01"/>',
	'link'         => '<path d="M8.5 11.5a3 3 0 0 0 4.2 0l2.6-2.6a3 3 0 0 0-4.2-4.2l-.8.8M11.5 8.5a3 3 0 0 0-4.2 0L4.7 11.1a3 3 0 0 0 4.2 4.2l.8-.8"/>',
	'star'         => '<path d="m10 3.2 2.1 4.3 4.7.7-3.4 3.3.8 4.7-4.2-2.2-4.2 2.2.8-4.7-3.4-3.3 4.7-.7z"/>',
	'desktop'      => '<path d="M3 4.5h14v9H3zM7.5 16.5h5M10 13.5v3"/>',
	'phone'        => '<path d="M6.5 2.5h7v15h-7zM9.2 15h1.6"/>',
	'plus'         => '<path d="M10 4v12M4 10h12"/>',
	'check'        => '<path d="M5 10.5 8.5 14 15 6.5"/>',
	'close'        => '<path d="M5.5 5.5l9 9M14.5 5.5l-9 9"/>',
	'chevron-down' => '<path d="M6 8l4 4 4-4"/>',
	'layers'       => '<path d="M10 3.5 3.5 7 10 10.5 16.5 7z"/><path d="M3.5 10 10 13.5 16.5 10M3.5 13 10 16.5 16.5 13"/>',
	'pencil'       => '<path d="M12.5 4.5l3 3L7 16H4v-3z"/><path d="M11 6l3 3"/>',
	'refresh'      => '<path d="M15.5 8A6 6 0 0 0 4.7 6.5M4.5 12a6 6 0 0 0 10.8 1.5"/><path d="M15.5 3.5V8H11M4.5 16.5V12H9"/>',
	// The Dashboard's table, tiles and selection bar (Task 13C).
	'image'        => '<path d="M4.5 4h11A1.5 1.5 0 0 1 17 5.5v9a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 3 14.5v-9A1.5 1.5 0 0 1 4.5 4z"/><circle cx="7.5" cy="8.5" r="1.5"/><path d="M17 12.5 13.5 9 6 16"/>',
	'unlink'       => '<path d="M8.5 11.5l-1.6 1.6a2.6 2.6 0 0 1-3.7-3.7l1.6-1.6M11.5 8.5l1.6-1.6a2.6 2.6 0 0 1 3.7 3.7l-1.6 1.6M7 3v2M3 7h2M13 17v-2M17 13h-2"/>',
	'box'          => '<path d="M3.5 6.5 10 3l6.5 3.5v7L10 17l-6.5-3.5z"/><path d="M3.5 6.5 10 10l6.5-3.5M10 10v7"/>',
	'bulb'         => '<path d="M7.5 14.5h5M8.3 17h3.4M10 3a5 5 0 0 0-3 9c.6.5 1 1.2 1 2h4c0-.8.4-1.5 1-2a5 5 0 0 0-3-9z"/>',
	'cursor'       => '<path d="M5 4l10.5 5-4.7 1.2L9.6 15z"/><path d="M11.2 11.2 15 15"/>',
	'copy'         => '<path d="M8.5 7h7A1.5 1.5 0 0 1 17 8.5v7a1.5 1.5 0 0 1-1.5 1.5h-7A1.5 1.5 0 0 1 7 15.5v-7A1.5 1.5 0 0 1 8.5 7z"/><path d="M13 7V4.5A1.5 1.5 0 0 0 11.5 3h-7A1.5 1.5 0 0 0 3 4.5v7A1.5 1.5 0 0 0 4.5 13H7"/>',
	'trash'        => '<path d="M4 6h12M8 6V4.5h4V6M5.5 6l.8 10h7.4l.8-10M8.5 9v4.5M11.5 9v4.5"/>',
	'folder'       => '<path d="M3 5.5a1 1 0 0 1 1-1h4l1.5 2H16a1 1 0 0 1 1 1V15a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/>',
	// The Dashboard's Filters button (Task 13E).
	'filter'       => '<path d="M3.5 4.5h13l-5 6v5l-3 1.5v-6.5z"/>',
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
