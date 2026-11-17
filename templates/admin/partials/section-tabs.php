<?php
/**
 * A screen's section tabs under its title (Settings, a product, a group, a field): a segmented control whose current
 * tab is marked with aria-current. A tab without a URL is one a later task brings: shown, disabled, with "Soon".
 * Extracted from the four screens' bands by Task MD3 (page-head.php renders it).
 *
 * @package Affibloom
 * @var array{label: string, items: list<array{label: string, url?: string, current?: bool, count?: int}>} $view
 */

defined( 'ABSPATH' ) || exit;
?>
<nav class="affibloom-tabs" aria-label="<?php echo esc_attr( $view['label'] ); ?>">
	<?php foreach ( $view['items'] as $tab_item ) : ?>
		<?php if ( isset( $tab_item['url'] ) ) : ?>
			<?php $tab_current = ! empty( $tab_item['current'] ); ?>
			<a class="affibloom-tabs__tab<?php echo $tab_current ? ' is-active' : ''; ?>" href="<?php echo esc_url( $tab_item['url'] ); ?>"<?php echo $tab_current ? ' aria-current="page"' : ''; ?>>
				<?php echo esc_html( $tab_item['label'] ); ?>
				<?php if ( ! empty( $tab_item['count'] ) ) : ?>
					<span class="affibloom-tabs__count"><?php echo esc_html( number_format_i18n( $tab_item['count'] ) ); ?></span>
				<?php endif; ?>
			</a>
		<?php else : ?>
			<span class="affibloom-tabs__tab is-disabled" aria-disabled="true" aria-describedby="affibloom-soon-note">
				<?php echo esc_html( $tab_item['label'] ); ?>
				<?php $this->render( 'admin/partials/soon', array( 'variant' => 'chip' ) ); ?>
			</span>
		<?php endif; ?>
	<?php endforeach; ?>
</nav>
