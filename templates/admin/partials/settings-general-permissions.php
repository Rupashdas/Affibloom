<?php
/**
 * Settings → General, Permissions (F37 R31-R35, AC7; F9 R67; Task TL6; decision C3): the lowest role that may use
 * Affibloom, Lasso's select with its five roles; Administrator by default. Only administrators change it: for anyone
 * else the select is disabled, and the server keeps the stored value whatever is sent (SettingsRestController).
 *
 * @package Affibloom
 * @var array{settings: \Affibloom\Domain\Settings\Settings} $view
 */

use Affibloom\Domain\Settings\Permission;
use Affibloom\Interface\Admin\Capabilities;

defined( 'ABSPATH' ) || exit;

$roles  = array(
	Permission::Administrator->value => __( 'Administrator', 'affibloom' ),
	Permission::Editor->value        => __( 'Editor', 'affibloom' ),
	Permission::Author->value        => __( 'Author', 'affibloom' ),
	Permission::Contributor->value   => __( 'Contributor', 'affibloom' ),
	Permission::Subscriber->value    => __( 'Subscriber', 'affibloom' ),
);
$chosen = Permission::of( $view['settings']->string( 'permission' ) )->value;
$can    = current_user_can( Capabilities::PERMISSION );
?>
<div class="affibloom-card" id="affibloom-card-permissions">
	<div class="affibloom-card__head">
		<h2 class="affibloom-card__title"><?php esc_html_e( 'Permissions', 'affibloom' ); ?></h2>
	</div>
	<div class="affibloom-card__body affibloom-card__body--fields">
		<div class="affibloom-field">
			<label class="affibloom-label" for="affibloom-setting-permission"><?php esc_html_e( 'Select the minimum user role that can access Affibloom.', 'affibloom' ); ?></label>
			<select class="affibloom-input" id="affibloom-setting-permission" name="settings[permission]" <?php disabled( ! $can ); ?>
				<?php echo $can ? '' : 'aria-describedby="affibloom-setting-permission-hint"'; ?>>
				<?php foreach ( $roles as $role_key => $label ) : ?>
					<option value="<?php echo esc_attr( $role_key ); ?>" <?php selected( $chosen, $role_key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php if ( ! $can ) : ?>
				<p class="affibloom-field__hint" id="affibloom-setting-permission-hint"><?php esc_html_e( 'Only administrators can change this.', 'affibloom' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</div>
