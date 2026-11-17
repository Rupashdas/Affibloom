<?php
/**
 * The Dashboard's search under its head (design system 0b.4; MD4 sizes it): the list's other arguments kept, and
 * Lasso's shortcut hint, which the script shows as ⌘⇧L on a Mac. Moved out of the Dashboard's band by Task MD3.
 *
 * @package Affibloom
 * @var array{args: array<string, string>, term: string, placeholder: string, placeholder_text: string} $view
 */

defined( 'ABSPATH' ) || exit;
?>
<form class="affibloom-page__search affibloom-page__search--wide" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" role="search">
	<?php
	$this->render(
		'admin/partials/list-args',
		array(
			'args'   => $view['args'],
			'except' => array( 's' ),
		)
	);
	?>
	<label class="screen-reader-text" for="affibloom-search"><?php esc_html_e( 'Search', 'affibloom' ); ?></label>
	<input type="search" id="affibloom-search" class="affibloom-input affibloom-search" name="s"
		value="<?php echo esc_attr( $view['term'] ); ?>" placeholder="<?php echo esc_attr( $view['placeholder'] ); ?>"
		data-placeholder="<?php echo esc_attr( $view['placeholder_text'] ); ?>"
		aria-describedby="affibloom-search-kbd">
	<kbd id="affibloom-search-kbd" class="affibloom-search-kbd">Ctrl+Shift+L</kbd>
</form>
