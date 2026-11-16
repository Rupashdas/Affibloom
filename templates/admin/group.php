<?php
/**
 * A group's page, or "Add a New Group" (Lasso's `admin/views/groups/{header,group-details,group-urls}.php`; F23
 * R5-R6, R10, R17): the band holds the heading, the tabs (Links <n> and Details) and, on the right, Add a Link (Links
 * tab) and Copy Shortcode (F31 R32, Task 18S12). The Links tab lists the group's products in its order (Task G4); the
 * Details tab holds Name, Slug (once the group exists) and Description with Save Changes (Task G2). Delete This Group
 * is in the footer of both (R10).
 *
 * @package Affibloom
 * @var array{group: ?\Affibloom\Domain\Group\Group, tab: string, members: list<\Affibloom\Domain\Product\Product>, notice: string} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Interface\Admin\GroupScreen;

$group       = $view['group'];
$is_new      = null === $group;
$current_tab = $view['tab'];
$heading     = $is_new ? __( 'Add a New Group', 'affibloom' ) : $group->name;
$gid         = $is_new ? 0 : $group->id;
?>
<div class="wrap affibloom-admin">
	<?php $this->render( 'admin/partials/header' ); ?>

	<div class="affibloom-page">
		<div class="affibloom-band">
			<div class="affibloom-band__inner<?php echo $is_new ? ' affibloom-band__inner--group-form' : ' affibloom-band__inner--split'; ?>">
				<div class="affibloom-band__text">
					<h1 class="affibloom-page__title" id="affibloom-group-title"><?php echo esc_html( $heading ); ?></h1>
					<?php if ( ! $is_new ) : ?>
						<nav class="affibloom-tabs" aria-label="<?php esc_attr_e( 'Group sections', 'affibloom' ); ?>">
							<a class="affibloom-tabs__tab<?php echo 'links' === $current_tab ? ' is-active' : ''; ?>" <?php echo 'links' === $current_tab ? 'aria-current="page"' : ''; ?> href="<?php echo esc_url( GroupScreen::url( $gid, 'links' ) ); ?>">
								<?php
								/* translators: %s: how many products are in the group. */
								echo esc_html( sprintf( __( 'Links %s', 'affibloom' ), number_format_i18n( $group->count ) ) );
								?>
							</a>
							<a class="affibloom-tabs__tab<?php echo 'details' === $current_tab ? ' is-active' : ''; ?>" <?php echo 'details' === $current_tab ? 'aria-current="page"' : ''; ?> href="<?php echo esc_url( GroupScreen::url( $gid, 'details' ) ); ?>"><?php esc_html_e( 'Details', 'affibloom' ); ?></a>
						</nav>
					<?php endif; ?>
				</div>
				<?php if ( ! $is_new ) : ?>
					<div class="affibloom-band__actions">
						<?php if ( 'links' === $current_tab ) : ?>
							<button type="button" class="affibloom-button affibloom-button--on-band" data-affibloom-open="affibloom-add-to-group" aria-haspopup="dialog">
								<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
								<span><?php esc_html_e( 'Add a Link', 'affibloom' ); ?></span>
							</button>
						<?php endif; ?>
						<?php // F31 R32: Lasso's tooltip, which reads "Copied!" once the group's grid shortcode is copied (groups.js). ?>
						<button type="button" class="affibloom-button affibloom-button--on-band" data-copy-group-shortcode="<?php echo esc_attr( \Affibloom\Interface\ShortcodeText::group( $group->slug ) ); ?>"
							title="<?php esc_attr_e( 'Copy this Display to your clipboard.', 'affibloom' ); ?>" data-copied="<?php esc_attr_e( 'Copied!', 'affibloom' ); ?>"
							data-copy-failed="<?php esc_attr_e( 'Could not copy shortcode.', 'affibloom' ); ?>">
							<?php $this->render( 'admin/partials/icon', array( 'name' => 'copy' ) ); ?>
							<span data-copy-label aria-live="polite"><?php esc_html_e( 'Copy Shortcode', 'affibloom' ); ?></span>
						</button>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<div class="affibloom-page__body affibloom-group">
			<?php if ( $is_new || 'details' === $current_tab ) : ?>
				<?php // Save Changes, or Enter in the name, sends the form through groups.js (POST /groups, PATCH /groups/{id}). ?>
				<form id="affibloom-group-form" class="affibloom-card" novalidate data-group-id="<?php echo esc_attr( (string) $gid ); ?>"
					data-name-required="<?php esc_attr_e( 'Name is required.', 'affibloom' ); ?>"
					data-saving-title="<?php esc_attr_e( 'Updating Group', 'affibloom' ); ?>"
					data-saving-text="<?php esc_attr_e( 'Saving your changes now.', 'affibloom' ); ?>"
					data-failed-text="<?php esc_attr_e( "Try again. For some reason your group didn't save.", 'affibloom' ); ?>">
					<div class="affibloom-card__body affibloom-group__fields">
						<div class="affibloom-field">
							<div class="affibloom-field__head">
								<label class="affibloom-label" for="affibloom-group-name"><?php esc_html_e( 'Name', 'affibloom' ); ?></label>
							</div>
							<input type="text" class="affibloom-input" id="affibloom-group-name" name="name" value="<?php echo esc_attr( $is_new ? '' : $group->name ); ?>"
								placeholder="<?php esc_attr_e( 'Group Name Goes Here', 'affibloom' ); ?>">
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
							<textarea class="affibloom-input affibloom-input--area" id="affibloom-group-description" name="description" rows="4"><?php echo esc_textarea( $is_new ? '' : $group->description ); ?></textarea>
						</div>
					</div>

					<div class="affibloom-card__foot">
						<button type="submit" class="affibloom-button affibloom-button--primary"><?php esc_html_e( 'Save Changes', 'affibloom' ); ?></button>
					</div>
				</form>
			<?php else : ?>
				<?php
				/* translators: %s: how many links are selected (one). */
				$selected_one = _n( '%s link selected', '%s links selected', 1, 'affibloom' );
				/* translators: %s: how many links are selected (several). */
				$selected_many = _n( '%s link selected', '%s links selected', 2, 'affibloom' );
				?>
				<div id="affibloom-members" class="affibloom-members" data-group-id="<?php echo esc_attr( (string) $gid ); ?>"
					data-failed-text="<?php esc_attr_e( 'Could not remove links from this group.', 'affibloom' ); ?>"
					data-order-failed-text="<?php esc_attr_e( 'Could not save the order. Please try again.', 'affibloom' ); ?>"
					data-empty-text="<?php esc_attr_e( 'No links in this group yet.', 'affibloom' ); ?>">
					<div class="affibloom-selection" hidden>
						<strong class="affibloom-selection__count" aria-live="polite"
							data-one="<?php echo esc_attr( $selected_one ); ?>" data-many="<?php echo esc_attr( $selected_many ); ?>"></strong>
						<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-select-all><?php esc_html_e( 'Select All', 'affibloom' ); ?></button>
						<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary" data-clear-selection><?php esc_html_e( 'Clear Selection', 'affibloom' ); ?></button>
						<button type="button" class="affibloom-button affibloom-button--small affibloom-button--danger" data-affibloom-open="affibloom-members-remove-many" aria-haspopup="dialog">
							<?php $this->render( 'admin/partials/icon', array( 'name' => 'trash' ) ); ?>
							<span><?php esc_html_e( 'Remove', 'affibloom' ); ?></span>
						</button>
					</div>

					<div class="affibloom-card affibloom-card--table">
						<p class="affibloom-members__empty" data-members-empty <?php echo array() === $view['members'] ? '' : 'hidden'; ?>><?php esc_html_e( 'No links in this group yet.', 'affibloom' ); ?></p>
						<ul class="affibloom-members__list" data-members aria-label="<?php esc_attr_e( 'Links in this group, in display order', 'affibloom' ); ?>">
							<?php foreach ( $view['members'] as $product ) : ?>
								<?php $this->render( 'admin/partials/group-member-row', array( 'product' => $product ) ); ?>
							<?php endforeach; ?>
						</ul>
					</div>
					<p id="affibloom-members-status" class="screen-reader-text" role="status" aria-live="polite"></p>
				</div>
			<?php endif; ?>

			<?php if ( ! $is_new ) : ?>
				<div class="affibloom-group__footer">
					<button type="button" class="affibloom-link-button affibloom-link-button--danger" id="affibloom-group-delete" data-group-delete
						data-group-id="<?php echo esc_attr( (string) $gid ); ?>" data-members="<?php echo esc_attr( (string) $group->count ); ?>">
						<?php $this->render( 'admin/partials/icon', array( 'name' => 'trash' ) ); ?>
						<span><?php esc_html_e( 'Delete This Group', 'affibloom' ); ?></span>
					</button>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<?php $this->render( 'admin/partials/save-modal' ); ?>
	<?php if ( ! $is_new ) : ?>
		<?php
		// Lasso's two answers to Delete This Group (R8): the question, or "Hold Up" when the group has links.
		$this->render(
			'admin/partials/delete-modal',
			array(
				'id'    => 'affibloom-group-delete-modal',
				'title' => __( 'Delete This Group?', 'affibloom' ),
				'text'  => __( "If deleted, you won't be able to get it back.", 'affibloom' ),
			)
		);
		$this->render( 'admin/partials/group-holdup-modal' );
		if ( 'links' === $current_tab ) {
			$this->render(
				'admin/partials/delete-modal',
				array(
					'id'            => 'affibloom-members-remove-one',
					'title'         => __( 'Remove This Link From Group?', 'affibloom' ),
					'text'          => __( 'This will remove the link from this group, but the link will remain in Affibloom and in other Groups (if applicable).', 'affibloom' ),
					'confirm_label' => __( 'Remove', 'affibloom' ),
					'confirm_attr'  => 'data-remove-confirm',
					'busy_label'    => __( 'Removing…', 'affibloom' ),
				)
			);
			$this->render(
				'admin/partials/delete-modal',
				array(
					'id'            => 'affibloom-members-remove-many',
					'title'         => __( 'Remove Selected Links From Group?', 'affibloom' ),
					'text'          => __( 'This will remove the selected links from this group, but the links will remain in Affibloom and other Groups (if applicable).', 'affibloom' ),
					'confirm_label' => __( 'Remove', 'affibloom' ),
					'confirm_attr'  => 'data-remove-confirm',
					'busy_label'    => __( 'Removing…', 'affibloom' ),
				)
			);
			$this->render( 'admin/partials/add-to-group-modal', array( 'group' => $gid ) );
		}
		?>
	<?php endif; ?>
	<?php $this->render( 'admin/partials/toast', array( 'message' => $view['notice'] ) ); ?>
	<?php $this->render( 'admin/partials/toast-template' ); ?>
	<?php $this->render( 'admin/partials/footer' ); ?>
</div>
