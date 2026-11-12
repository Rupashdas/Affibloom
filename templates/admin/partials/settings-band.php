<?php
/**
 * The Settings band: the page title, its subtitle (U10) and the segmented tab strip.
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
?>
<div class="affibloom-band">
	<div class="affibloom-band__inner">
		<h1 class="affibloom-page__title"><?php esc_html_e( 'Settings', 'affibloom' ); ?></h1>
		<p class="affibloom-page__subtitle"><?php esc_html_e( 'Defaults for new links, the product box and Amazon.', 'affibloom' ); ?></p>

		<nav class="affibloom-tabs" aria-label="<?php esc_attr_e( 'Settings sections', 'affibloom' ); ?>">
			<?php foreach ( $view['tabs'] as $tab_key ) : ?>
				<a class="affibloom-tabs__tab<?php echo $tab_key === $view['tab'] ? ' is-active' : ''; ?>"
					href="<?php echo esc_url( admin_url( 'admin.php?page=affibloom-settings&tab=' . $tab_key ) ); ?>"
					<?php echo $tab_key === $view['tab'] ? 'aria-current="page"' : ''; ?>>
					<?php echo esc_html( $labels[ $tab_key ] ?? $tab_key ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
	</div>
</div>
