<?php
/**
 * A group's page, or "Add a New Group" (Lasso's `admin/views/groups/{header,group-details}.php`; F23 R5-R6): the band
 * holds the heading and, for an existing group, its tabs; the card holds Name, Slug (once the group exists) and
 * Description, with Save Changes in its footer.
 *
 * Task G1 builds the new-group form, which saves through POST /groups (assets/admin/groups.js), and the Details tab a
 * save lands on, shown read only with its Save Changes switched off. Task G2 makes the Details tab editable and adds
 * Delete This Group; Task G4 adds the Links tab.
 *
 * @package Affibloom
 * @var array{group: ?\Affibloom\Domain\Group\Group, notice: string} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\GroupScreen;

$group    = $view['group'];
$is_new   = null === $group;
$heading  = $is_new ? __( 'Add a New Group', 'affibloom' ) : $group->name;
$name     = $is_new ? '' : $group->name;
$readonly = ! $is_new; // Until Task G2.
?>
<div class="wrap affibloom-admin">
	<?php $this->render( 'admin/partials/header', array( 'current' => 'groups' ) ); ?>

	<div class="affibloom-page">
		<div class="affibloom-band">
			<div class="affibloom-band__inner">
				<h1 class="affibloom-page__title" id="affibloom-group-title"><?php echo esc_html( $heading ); ?></h1>
				<?php if ( ! $is_new ) : ?>
					<nav class="affibloom-tabs" aria-label="<?php esc_attr_e( 'Group sections', 'affibloom' ); ?>">
						<a class="affibloom-tabs__tab is-active" aria-current="page" href="<?php echo esc_url( GroupScreen::url( $group->id, 'details' ) ); ?>"><?php esc_html_e( 'Details', 'affibloom' ); ?></a>
					</nav>
				<?php endif; ?>
			</div>
		</div>

		<div class="affibloom-page__body affibloom-group">
			<?php // Save Changes, or Enter in the name, sends the form through groups.js (POST /groups). ?>
			<form id="affibloom-group-form" class="affibloom-card" novalidate
				data-name-required="<?php esc_attr_e( 'Name is required.', 'affibloom' ); ?>"
				data-saving-title="<?php esc_attr_e( 'Updating Group', 'affibloom' ); ?>"
				data-saving-text="<?php esc_attr_e( 'Saving your changes now.', 'affibloom' ); ?>"
				data-failed-text="<?php esc_attr_e( "Try again. For some reason your group didn't save.", 'affibloom' ); ?>">
				<div class="affibloom-card__body affibloom-group__fields">
					<div class="affibloom-field">
						<div class="affibloom-field__head">
							<label class="affibloom-label" for="affibloom-group-name"><?php esc_html_e( 'Name', 'affibloom' ); ?></label>
						</div>
						<input type="text" class="affibloom-input" id="affibloom-group-name" name="name" value="<?php echo esc_attr( $name ); ?>"
							placeholder="<?php esc_attr_e( 'Group Name Goes Here', 'affibloom' ); ?>" <?php echo $readonly ? 'readonly' : ''; ?>>
					</div>

					<?php if ( ! $is_new ) : ?>
						<?php // Lasso shows the slug once the group exists, and never lets it change (R6). ?>
						<div class="affibloom-field">
							<div class="affibloom-field__head">
								<label class="affibloom-label" for="affibloom-group-slug"><?php esc_html_e( 'Slug', 'affibloom' ); ?></label>
							</div>
							<input type="text" class="affibloom-input" id="affibloom-group-slug" value="<?php echo esc_attr( $group->slug ); ?>" readonly>
						</div>
					<?php endif; ?>

					<div class="affibloom-field">
						<div class="affibloom-field__head">
							<label class="affibloom-label" for="affibloom-group-description"><?php esc_html_e( 'Description', 'affibloom' ); ?></label>
						</div>
						<textarea class="affibloom-input affibloom-input--area" id="affibloom-group-description" name="description" rows="4"<?php echo $readonly ? ' readonly' : ''; ?>><?php echo esc_textarea( $is_new ? '' : $group->description ); ?></textarea>
					</div>
				</div>

				<div class="affibloom-card__foot">
					<?php if ( $is_new ) : ?>
						<button type="submit" class="affibloom-button affibloom-button--primary"><?php esc_html_e( 'Save Changes', 'affibloom' ); ?></button>
					<?php else : ?>
						<?php $this->render( 'admin/partials/soon', array( 'variant' => 'chip' ) ); ?>
						<button type="button" class="affibloom-button affibloom-button--primary" aria-disabled="true" aria-describedby="affibloom-soon-note"><?php esc_html_e( 'Save Changes', 'affibloom' ); ?></button>
					<?php endif; ?>
				</div>
			</form>
			<span id="affibloom-soon-note" hidden><?php esc_html_e( 'Coming soon', 'affibloom' ); ?></span>
		</div>
	</div>
	<?php $this->render( 'admin/partials/save-modal' ); ?>
	<?php $this->render( 'admin/partials/toast', array( 'message' => $view['notice'] ) ); ?>
	<?php $this->render( 'admin/partials/toast-template' ); ?>
</div>
