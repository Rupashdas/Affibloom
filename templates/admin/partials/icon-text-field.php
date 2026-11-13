<?php
/**
 * A button or badge text field with its icon picker (F28, Task 14G; register U11). The named field is the stored
 * text, exactly as before (D3): without JavaScript, or for a text holding other HTML, it is the only control. With
 * JavaScript (icon-picker.js) it is hidden and written from the picker and the words box beside it, so the stored form
 * stays `<span class="<class>"></span> <words>`.
 *
 * @package Affibloom
 * @var array{id: string, name: string, label: string, value: string, placeholder: string, help_id: string, text: \Affibloom\Domain\Icons\IconText|null, words_placeholder: string, current: array{icon: \Affibloom\Domain\Icons\Icon, src: string, missing: bool}|null, unknown: string, rows: list<array{icon: \Affibloom\Domain\Icons\Icon, src: string, missing: bool}>} $view
 */

defined( 'ABSPATH' ) || exit;

$text      = $view['text'];
$menu_id   = $view['id'] . '-icons';
$current   = $view['current'];
$css_class = null === $text ? '' : $text->css_class;
if ( null !== $current ) {
	$shown = $current['icon']->name;
} elseif ( '' !== $view['unknown'] ) {
	/* translators: %s: a class no icon in the set has, e.g. madel-icon. */
	$shown = sprintf( __( 'Unknown icon: %s', 'affibloom' ), $view['unknown'] );
} else {
	$shown = __( 'No icon', 'affibloom' );
}
/* translators: 1: the field's label, 2: the icon's name, "No icon" or "Unknown icon: …". */
$button_name = __( 'Icon for %1$s: %2$s', 'affibloom' );
?>
<span class="affibloom-icon-field"<?php echo null === $text ? '' : ' data-icon-field data-icon-class="' . esc_attr( $css_class ) . '"'; ?>
	data-name-format="<?php echo esc_attr( $button_name ); ?>" data-label="<?php echo esc_attr( $view['label'] ); ?>">
	<?php if ( null !== $text ) : ?>
		<button type="button" class="affibloom-icon-field__button" aria-haspopup="menu" aria-expanded="false" aria-controls="<?php echo esc_attr( $menu_id ); ?>"
			aria-label="<?php echo esc_attr( sprintf( $button_name, $view['label'], $shown ) ); ?>" title="<?php echo esc_attr( $shown ); ?>" hidden>
			<?php if ( null !== $current ) : ?>
				<?php $this->render( 'admin/partials/icon-picture', array( 'row' => $current ) ); ?>
			<?php elseif ( '' !== $view['unknown'] ) : ?>
				<span class="affibloom-icon-field__glyph affibloom-icon-field__glyph--unknown"><?php $this->render( 'admin/partials/icon', array( 'name' => 'warning' ) ); ?></span>
			<?php else : ?>
				<span class="affibloom-icon-field__glyph"><?php $this->render( 'admin/partials/icon', array( 'name' => 'image' ) ); ?></span>
			<?php endif; ?>
		</button>
		<span id="<?php echo esc_attr( $menu_id ); ?>" class="affibloom-menu affibloom-icon-menu" role="menu" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: the field's label. */ __( 'Icons for %s', 'affibloom' ), $view['label'] ) ); ?>" hidden>
			<button type="button" class="affibloom-menu__item" role="menuitemradio" aria-checked="<?php echo '' === $css_class ? 'true' : 'false'; ?>" data-icon="" tabindex="-1">
				<span class="affibloom-icon-field__glyph"><?php $this->render( 'admin/partials/icon', array( 'name' => 'image' ) ); ?></span>
				<span class="affibloom-menu__title"><?php esc_html_e( 'No icon', 'affibloom' ); ?></span>
			</button>
			<?php foreach ( $view['rows'] as $row ) : ?>
				<button type="button" class="affibloom-menu__item" role="menuitemradio" tabindex="-1"
					aria-checked="<?php echo $row['icon']->css_class === $css_class ? 'true' : 'false'; ?>" data-icon="<?php echo esc_attr( $row['icon']->css_class ); ?>">
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
			<?php endforeach; ?>
			<?php if ( '' !== $view['unknown'] ) : ?>
				<?php // D2: the class a stored text names stays a choice until another is picked. ?>
				<button type="button" class="affibloom-menu__item" role="menuitemradio" aria-checked="true" data-icon="<?php echo esc_attr( $view['unknown'] ); ?>" data-unknown tabindex="-1">
					<span class="affibloom-icon-field__glyph affibloom-icon-field__glyph--unknown"><?php $this->render( 'admin/partials/icon', array( 'name' => 'warning' ) ); ?></span>
					<span class="affibloom-menu__title"><?php echo esc_html( $shown ); ?></span>
				</button>
			<?php endif; ?>
		</span>
		<input type="text" class="affibloom-input affibloom-icon-field__words" id="<?php echo esc_attr( $view['id'] ); ?>-words"
			value="<?php echo esc_attr( $text->words ); ?>" placeholder="<?php echo esc_attr( $view['words_placeholder'] ); ?>"
			aria-describedby="<?php echo esc_attr( $view['help_id'] ); ?>" hidden>
	<?php endif; ?>
	<input type="text" class="affibloom-input" id="<?php echo esc_attr( $view['id'] ); ?>" name="<?php echo esc_attr( $view['name'] ); ?>"
		value="<?php echo esc_attr( $view['value'] ); ?>" placeholder="<?php echo esc_attr( $view['placeholder'] ); ?>"
		aria-describedby="<?php echo esc_attr( $view['help_id'] ); ?>" data-icon-raw>
</span>
