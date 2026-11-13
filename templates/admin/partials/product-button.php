<?php
/**
 * One button block on the product page (F29, Task 14H): its link and text side by side (Lasso's 8 / 4 columns), then
 * its two toggles, in a group named "Button <n>" with Move up and Move down. The default button ("main") is the
 * product's own link, under Lasso's primary labels and 14A's ids, marked "Main link", and cannot be removed; an added
 * button has Remove. product-buttons.js shows the actions and renumbers the names when the order changes.
 *
 * @package Affibloom
 * @var array{main: bool, index: int|string, key: string, number: int|string, url: string, text: string, new_tab: bool, nofollow: bool, placeholder: string, choices: \Affibloom\Interface\Admin\IconChoices} $view
 */

defined( 'ABSPATH' ) || exit;

$main = $view['main'];
if ( $main ) {
	$ids         = array( 'affibloom-url', 'affibloom-button-text', 'affibloom-new-tab', 'affibloom-nofollow' );
	$field_names = array( 'url', 'button_text', 'new_tab', 'nofollow' );
} else {
	// An added button's ids carry a key of their own, so they stay unique however the blocks are moved.
	$ids         = array_map( static fn( string $part ): string => 'affibloom-extra-' . $view['key'] . '-' . $part, array( 'url', 'text', 'new-tab', 'nofollow' ) );
	$field_names = array_map( static fn( string $part ): string => 'buttons[' . $view['index'] . '][' . $part . ']', array( 'url', 'text', 'new_tab', 'nofollow' ) );
}
/* translators: %s: the button's place in the box, e.g. 2. */
$legend  = sprintf( __( 'Button %s', 'affibloom' ), (string) $view['number'] );
$fields  = array(
	array(
		'id'          => $ids[0],
		'name'        => $field_names[0],
		'label'       => $main ? __( 'Primary Destination URL', 'affibloom' ) : __( 'Additional Destination URL', 'affibloom' ),
		'value'       => $view['url'],
		'help'        => $main
			? __( 'The actual URL you want people to go to when they click a link', 'affibloom' )
			: __( 'Another URL you want people to go to when they click an extra button in displays', 'affibloom' ),
		'placeholder' => $main ? 'https://www.example.com/affiliate-id' : 'https://www.example.com/affiliate-id2',
	),
	array(
		'id'          => $ids[1],
		'name'        => $field_names[1],
		'label'       => __( 'Button Text', 'affibloom' ),
		'value'       => $view['text'],
		'help'        => $main
			? __( 'This text will appear in the primary button', 'affibloom' )
			: __( 'This text will appear in this extra button', 'affibloom' ),
		'placeholder' => $view['placeholder'],
		'choices'     => $view['choices'],
	),
);
$toggles = array(
	array( $ids[2], $field_names[2], __( 'New Window / Tab', 'affibloom' ), $view['new_tab'], __( 'When enabled, users who click this link will have it loaded in a new tab.', 'affibloom' ) ),
	array( $ids[3], $field_names[3], __( 'NoFollow / NoIndex', 'affibloom' ), $view['nofollow'], __( "When enabled, this link will be set to nofollow. This indicates to Google that it's an affiliate link.", 'affibloom' ) ),
);
?>
<fieldset class="affibloom-product__button affibloom-button-block" data-button<?php echo $main ? ' data-main-button' : ' data-extra-button'; ?>>
	<legend class="affibloom-button-block__legend"><?php echo esc_html( $legend ); ?></legend>
	<?php if ( $main ) : ?>
		<?php // The product's own link stays its main link wherever its button sits (F29 D6). ?>
		<span class="affibloom-button-block__tag" title="<?php esc_attr_e( "This button's link is the product's main link. The first button in the list is the box's primary button.", 'affibloom' ); ?>"><?php esc_html_e( 'Main link', 'affibloom' ); ?></span>
	<?php endif; ?>
	<span class="affibloom-button-block__actions" hidden>
		<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-move="up"><?php esc_html_e( 'Move up', 'affibloom' ); ?></button>
		<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-move="down"><?php esc_html_e( 'Move down', 'affibloom' ); ?></button>
		<?php if ( ! $main ) : ?>
			<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-remove><?php esc_html_e( 'Remove', 'affibloom' ); ?></button>
		<?php endif; ?>
	</span>
	<div class="affibloom-product__pair">
		<?php
		foreach ( $fields as $field ) {
			$this->render( 'admin/partials/field-text', $field );
		}
		?>
	</div>
	<div class="affibloom-product__toggles">
		<?php
		foreach ( $toggles as [ $toggle_id, $toggle_name, $toggle_label, $toggle_on, $toggle_help ] ) {
			$this->render(
				'admin/partials/switch-row',
				array(
					'id'      => $toggle_id,
					'name'    => $toggle_name,
					'label'   => $toggle_label,
					'checked' => $toggle_on,
					'help'    => $toggle_help,
				)
			);
		}
		?>
	</div>
</fieldset>
