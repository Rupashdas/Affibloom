<?php
/**
 * The head actions of a list screen (Groups, Fields; design system 0b.4): its search, then its "Add New" button, in one
 * row level with the title. Moved out of the two screens' bands by Task MD3.
 *
 * @package Affibloom
 * @var array{slug: string, search_id: string, search_label: string, term: string, add_url: string, add_label: string} $view
 */

defined( 'ABSPATH' ) || exit;
?>
<form class="affibloom-page__search" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" role="search">
	<input type="hidden" name="page" value="<?php echo esc_attr( $view['slug'] ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $view['search_id'] ); ?>"><?php echo esc_html( $view['search_label'] ); ?></label>
	<input type="search" id="<?php echo esc_attr( $view['search_id'] ); ?>" class="affibloom-input affibloom-search" name="s"
		value="<?php echo esc_attr( $view['term'] ); ?>" placeholder="<?php echo esc_attr( $view['search_label'] ); ?>">
</form>
<a class="affibloom-button affibloom-button--primary" href="<?php echo esc_url( $view['add_url'] ); ?>">
	<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
	<span><?php echo esc_html( $view['add_label'] ); ?></span>
</a>
