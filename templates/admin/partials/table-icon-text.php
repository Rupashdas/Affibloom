<?php
/**
 * A badge or button text in the table builder, drawn as readers see it (F28 D3; deviation 151): the icon's picture
 * from the library, then the words. A class the library does not have shows a warning sign; a text holding other HTML
 * shows its words only. Nothing here prints the stored `<span>` itself.
 *
 * @package Affibloom
 * @var array{text: \Affibloom\Domain\Icons\IconText|null, raw: string, icons: array<string, string>, fallback?: string} $view
 */

defined( 'ABSPATH' ) || exit;

$icon_text = $view['text'];
$words     = null === $icon_text ? trim( wp_strip_all_tags( $view['raw'] ) ) : $icon_text->words;
$class     = null === $icon_text ? '' : $icon_text->css_class;
$words     = '' === $words ? (string) ( $view['fallback'] ?? '' ) : $words;
?>
<?php if ( '' !== $class && isset( $view['icons'][ $class ] ) ) : ?>
	<img class="affibloom-table-icon" src="<?php echo esc_url( $view['icons'][ $class ] ); ?>" alt="" width="16" height="16">
<?php elseif ( '' !== $class ) : ?>
	<span class="affibloom-table-icon affibloom-table-icon--unknown" title="<?php /* translators: %s: an icon class the library does not have. */ echo esc_attr( sprintf( __( 'Unknown icon: %s', 'affibloom' ), $class ) ); ?>"><?php $this->render( 'admin/partials/icon', array( 'name' => 'warning' ) ); ?></span>
<?php endif; ?>
<span class="affibloom-table-icon-text__words"><?php echo esc_html( $words ); ?></span>
