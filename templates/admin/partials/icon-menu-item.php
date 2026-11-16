<?php
/**
 * One icon in an icon picker's menu (F28, Task 14G). Printed by each picker (icon-text-field.php) and by the REST answer
 * to Add icon (IconsController), which adds it to every picker on the page.
 *
 * @package Affibloom
 * @var array{row: array{icon: \Affibloom\Domain\Icons\Icon, src: string, missing: bool}, checked: bool} $view
 */

defined( 'ABSPATH' ) || exit;

$row = $view['row'];
?>
<button type="button" class="affibloom-menu__item" role="menuitemradio" tabindex="-1"
	aria-checked="<?php echo $view['checked'] ? 'true' : 'false'; ?>" data-icon="<?php echo esc_attr( $row['icon']->css_class ); ?>">
	<?php $this->render( 'admin/partials/icon-picture', array( 'row' => $row ) ); ?>
	<?php if ( $row['missing'] ) : ?>
		<span class="affibloom-menu__text">
			<span class="affibloom-menu__title"><?php echo esc_html( $row['icon']->name ); ?></span>
			<span class="affibloom-menu__desc"><?php esc_html_e( 'Image missing', 'affibloom' ); ?></span>
		</span>
	<?php else : ?>
		<span class="affibloom-menu__title"><?php echo esc_html( $row['icon']->name ); ?></span>
	<?php endif; ?>
</button>
