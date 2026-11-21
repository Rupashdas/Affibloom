<?php
/**
 * The builder's head actions (F25 R13; Task T3; Lasso's tables/header.php): Saved / Saving, Copy Shortcode (F31's Task
 * 18S13; hidden until the table has an id) and Create Table.
 *
 * @package Affibloom
 * @var array{saved: bool, has_id: bool} $view
 */

defined( 'ABSPATH' ) || exit;
?>
<span class="affibloom-table-page__status" data-table-status role="status" aria-live="polite"><?php echo $view['saved'] ? esc_html__( 'Saved', 'affibloom' ) : ''; ?></span>
<?php // Copy Shortcode comes with F31's Task 18S13. ?>
<button type="button" class="affibloom-button affibloom-button--secondary" data-table-copy disabled title="<?php esc_attr_e( 'Coming soon', 'affibloom' ); ?>"<?php echo $view['has_id'] ? '' : ' hidden'; ?>>
	<?php $this->render( 'admin/partials/icon', array( 'name' => 'copy' ) ); ?>
	<span><?php esc_html_e( 'Copy Shortcode', 'affibloom' ); ?></span>
</button>
<button type="button" class="affibloom-button affibloom-button--primary" data-affibloom-open="affibloom-table-create" aria-haspopup="dialog">
	<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
	<span><?php esc_html_e( 'Create Table', 'affibloom' ); ?></span>
</button>
