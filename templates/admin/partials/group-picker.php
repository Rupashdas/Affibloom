<?php
/**
 * The group picker (F23 D8): chips for the chosen groups, a box that filters the site's groups as you type ("Select a
 * group"), Enter or "Create “<name>”" for a new name, × or Backspace to drop one. Lasso uses select2; Affibloom loads
 * none, so this is one small component for its three users: the product page, the Dashboard's quick edit and the bulk Add
 * to Group. assets/admin/group-picker.js reads the site's groups from the page's `affibloom-group-choices` JSON
 * (partials/group-choices.php), so the list is printed once however many pickers the page has.
 *
 * Roles: the box is a combobox over a listbox; each chip's × is a button named for its group.
 *
 * @package Affibloom
 * @var array{id: string, groups: list<array{id: int, name: string}>, label_id: string, described_by?: string} $view
 */

defined( 'ABSPATH' ) || exit;

$picker_id = $view['id'];
?>
<div class="affibloom-picker" data-group-picker
	data-remove-label="<?php /* translators: %s: group name. */ echo esc_attr( __( 'Remove %s', 'affibloom' ) ); ?>"
	data-create-label="<?php /* translators: %s: the group name typed. */ echo esc_attr( __( 'Create “%s”', 'affibloom' ) ); ?>"
	data-placeholder="<?php esc_attr_e( 'Select a group', 'affibloom' ); ?>">
	<ul class="affibloom-picker__chips" data-picker-chips>
		<?php foreach ( $view['groups'] as $chosen ) : ?>
			<li class="affibloom-picker__chip" data-value="<?php echo esc_attr( (string) $chosen['id'] ); ?>" data-name="<?php echo esc_attr( $chosen['name'] ); ?>">
				<span><?php echo esc_html( $chosen['name'] ); ?></span>
				<button type="button" class="affibloom-picker__remove" aria-label="<?php /* translators: %s: group name. */ echo esc_attr( sprintf( __( 'Remove %s', 'affibloom' ), $chosen['name'] ) ); ?>">
					<?php $this->render( 'admin/partials/icon', array( 'name' => 'close' ) ); ?>
				</button>
			</li>
		<?php endforeach; ?>
		<li class="affibloom-picker__entry">
			<input type="text" class="affibloom-picker__input" id="<?php echo esc_attr( $picker_id ); ?>" role="combobox" autocomplete="off"
				aria-autocomplete="list" aria-expanded="false" aria-controls="<?php echo esc_attr( $picker_id ); ?>-list"
				<?php echo isset( $view['described_by'] ) ? 'aria-describedby="' . esc_attr( $view['described_by'] ) . '"' : ''; ?>
				placeholder="<?php echo array() === $view['groups'] ? esc_attr__( 'Select a group', 'affibloom' ) : ''; ?>">
		</li>
	</ul>
	<ul class="affibloom-picker__list" id="<?php echo esc_attr( $picker_id ); ?>-list" role="listbox" hidden></ul>
</div>
