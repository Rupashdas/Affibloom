<?php
/**
 * Product page, Details tab: Lasso's link details page (url-details.php and dashboard/header.php) in Aurora's shell.
 * The band holds the name, the link under it and Lasso's four tabs; below, Lasso's two cards (the link on the left,
 * the display on the right) and its footer, the arrangement the user chose at gate 4 (F1 requirements, D1).
 *
 * Built in Task 14A: the band, the left card and the ways in. Placeholders until their tasks: the right card (14B),
 * custom fields (14C), Groups (the Groups area), Permalink (link cloaking), the Locations, Opportunities and Variants
 * tabs (their areas), Save Changes (Task 15) and Delete This Link (Task 17).
 *
 * @package Affibloom
 * @var array{product: \Affibloom\Domain\Product\Product, button_text: string, button_text2: string, duplicate: bool, choices: \Affibloom\Interface\Admin\IconChoices} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Product\ProductType;
use Affibloom\Interface\Admin\ProductScreen;

$product = $view['product'];
// Lasso hides Permalink and Link Cloaking for an Amazon link, a search page included (url-details.php 194, 491).
$is_amazon = ProductType::Amazon === $product->type;
// D5: the link under the title is the destination until link cloaking exists (Lasso's rule when cloaking is off).
$destination   = $product->url->value();
$soon_tabs     = array(
	__( 'Locations', 'affibloom' ),
	__( 'Opportunities', 'affibloom' ),
	__( 'Variants', 'affibloom' ),
);
$new_tab_help  = __( 'When enabled, users who click this link will have it loaded in a new tab.', 'affibloom' );
$nofollow_help = __( "When enabled, this link will be set to nofollow. This indicates to Google that it's an affiliate link.", 'affibloom' );
?>
<div class="wrap affibloom-admin">
	<?php $this->render( 'admin/partials/header', array( 'current' => 'dashboard' ) ); ?>

	<div class="affibloom-page affibloom-page--wide affibloom-product-page">
		<div class="affibloom-band">
			<div class="affibloom-band__inner">
				<h1 class="affibloom-page__title affibloom-product__title" id="affibloom-product-title">
					<?php echo esc_html( '' !== $product->name ? $product->name : __( 'Enter a URL Name', 'affibloom' ) ); ?>
				</h1>
				<a class="affibloom-product-url" href="<?php echo esc_url( $destination ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $destination ); ?></a>

				<nav class="affibloom-tabs" aria-label="<?php esc_attr_e( 'Product sections', 'affibloom' ); ?>">
					<a class="affibloom-tabs__tab is-active" aria-current="page" href="<?php echo esc_url( ProductScreen::url( (int) $product->id ) ); ?>"><?php esc_html_e( 'Details', 'affibloom' ); ?></a>
					<?php foreach ( $soon_tabs as $tab_label ) : ?>
						<span class="affibloom-tabs__tab is-disabled" aria-disabled="true" aria-describedby="affibloom-soon-note">
							<?php echo esc_html( $tab_label ); ?>
							<?php $this->render( 'admin/partials/soon', array( 'variant' => 'chip' ) ); ?>
						</span>
					<?php endforeach; ?>
				</nav>
			</div>
		</div>

		<div class="affibloom-page__body affibloom-product">
			<?php if ( $view['duplicate'] ) : ?>
				<?php // Lasso's warning after "Add A New Link" finds this product (url-details.php 2597-2600). ?>
				<div class="affibloom-notice affibloom-notice--warning" role="status">
					<span class="affibloom-chip affibloom-chip--warning"><?php $this->render( 'admin/partials/icon', array( 'name' => 'warning' ) ); ?></span>
					<p class="affibloom-notice__text"><?php esc_html_e( 'This product already exists. Please update the Primary URL or add a new link.', 'affibloom' ); ?></p>
				</div>
			<?php endif; ?>

			<?php // data-not-saved: Enter in a field must not send the form until Task 15 saves it (admin.js). ?>
			<form id="affibloom-product-form" class="affibloom-product__form" data-product-id="<?php echo esc_attr( (string) $product->id ); ?>" data-not-saved novalidate>
				<div class="affibloom-card affibloom-product__card affibloom-product__card--link">
					<?php
					$this->render(
						'admin/partials/field-text',
						array(
							'id'          => 'affibloom-name',
							'name'        => 'name',
							'label'       => __( 'Name', 'affibloom' ),
							'value'       => $product->name,
							'help'        => __( 'This title will only be shown in displays', 'affibloom' ),
							'placeholder' => __( 'URL Name Goes Here', 'affibloom' ),
						)
					);

					if ( ! $is_amazon ) {
						$this->render(
							'admin/partials/field-text',
							array(
								'id'          => 'affibloom-permalink',
								'name'        => 'permalink',
								'label'       => __( 'Permalink', 'affibloom' ),
								'value'       => '',
								'help'        => __( 'This slug will be used to cloak the original target URL', 'affibloom' ),
								'placeholder' => 'affiliate-name',
								'soon'        => true,
							)
						);
					}
					?>

					<?php // Each button: its link and text side by side (Lasso's 8 / 4 columns), then its two toggles. ?>
					<div class="affibloom-product__button">
						<div class="affibloom-product__pair">
							<?php
							$this->render(
								'admin/partials/field-text',
								array(
									'id'          => 'affibloom-url',
									'name'        => 'url',
									'label'       => __( 'Primary Destination URL', 'affibloom' ),
									'value'       => $destination,
									'help'        => __( 'The actual URL you want people to go to when they click a link', 'affibloom' ),
									'placeholder' => 'https://www.example.com/affiliate-id',
								)
							);
							$this->render(
								'admin/partials/field-text',
								array(
									'id'          => 'affibloom-button-text',
									'name'        => 'button_text',
									'label'       => __( 'Button Text', 'affibloom' ),
									'value'       => $product->button_text,
									'help'        => __( 'This text will appear in the primary button', 'affibloom' ),
									'placeholder' => $view['button_text'],
									'choices'     => $view['choices'],
								)
							);
							?>
						</div>
						<div class="affibloom-product__toggles">
							<?php
							$this->render(
								'admin/partials/switch-row',
								array(
									'id'      => 'affibloom-new-tab',
									'name'    => 'new_tab',
									'label'   => __( 'New Window / Tab', 'affibloom' ),
									'checked' => $product->new_tab,
									'help'    => $new_tab_help,
								)
							);
							$this->render(
								'admin/partials/switch-row',
								array(
									'id'      => 'affibloom-nofollow',
									'name'    => 'nofollow',
									'label'   => __( 'NoFollow / NoIndex', 'affibloom' ),
									'checked' => $product->nofollow,
									'help'    => $nofollow_help,
								)
							);
							?>
						</div>
					</div>

					<div class="affibloom-product__button">
						<div class="affibloom-product__pair">
							<?php
							$this->render(
								'admin/partials/field-text',
								array(
									'id'          => 'affibloom-url2',
									'name'        => 'url2',
									'label'       => __( 'Secondary Destination URL', 'affibloom' ),
									'value'       => $product->url2,
									'help'        => __( 'A secondary URL you want people to go to when they click an optional second button in displays', 'affibloom' ),
									'placeholder' => 'https://www.example.com/affiliate-id2',
								)
							);
							$this->render(
								'admin/partials/field-text',
								array(
									'id'          => 'affibloom-button-text2',
									'name'        => 'button_text2',
									'label'       => __( 'Button Text', 'affibloom' ),
									'value'       => $product->button_text2,
									'help'        => __( 'This text will appear in the optional secondary button', 'affibloom' ),
									'placeholder' => $view['button_text2'],
									'choices'     => $view['choices'],
								)
							);
							?>
						</div>
						<div class="affibloom-product__toggles">
							<?php
							$this->render(
								'admin/partials/switch-row',
								array(
									'id'      => 'affibloom-new-tab2',
									'name'    => 'new_tab2',
									'label'   => __( 'New Window / Tab', 'affibloom' ),
									'checked' => $product->new_tab2,
									'help'    => $new_tab_help,
								)
							);
							$this->render(
								'admin/partials/switch-row',
								array(
									'id'      => 'affibloom-nofollow2',
									'name'    => 'nofollow2',
									'label'   => __( 'NoFollow / NoIndex', 'affibloom' ),
									'checked' => $product->nofollow2,
									'help'    => $nofollow_help,
								)
							);
							?>
						</div>
					</div>

					<?php // Groups: a placeholder until the Groups area (Lasso: a multi-select that can create a group). ?>
					<div class="affibloom-field">
						<div class="affibloom-field__head">
							<label class="affibloom-label" for="affibloom-groups"><?php esc_html_e( 'Groups', 'affibloom' ); ?></label>
							<?php
							$this->render(
								'admin/partials/help',
								array(
									'id'    => 'affibloom-groups-help',
									'text'  => __( 'Group this URL for better organization and to use in group displays', 'affibloom' ),
									'label' => __( 'Groups', 'affibloom' ),
								)
							);
							$this->render( 'admin/partials/soon', array( 'variant' => 'chip' ) );
							?>
						</div>
						<select class="affibloom-input" id="affibloom-groups" aria-describedby="affibloom-groups-help" disabled>
							<option><?php esc_html_e( 'Select a group', 'affibloom' ); ?></option>
						</select>
					</div>

					<?php // Custom fields (Primary Rating, Pros, Cons and the editor's own) arrive with Task 14C. ?>
					<div class="affibloom-product__fields">
						<button type="button" class="affibloom-button affibloom-button--secondary" aria-disabled="true" aria-describedby="affibloom-soon-note">
							<?php $this->render( 'admin/partials/icon', array( 'name' => 'plus' ) ); ?>
							<span><?php esc_html_e( 'Add Custom Field', 'affibloom' ); ?></span>
						</button>
						<?php $this->render( 'admin/partials/soon', array( 'variant' => 'chip' ) ); ?>
					</div>
				</div>

				<?php // The preview and the display fields (theme, image, badge, price, description, disclosure): Task 14B. ?>
				<div class="affibloom-card affibloom-product__card affibloom-product__card--display affibloom-empty">
					<span class="affibloom-chip"><?php $this->render( 'admin/partials/icon', array( 'name' => 'box' ) ); ?></span>
					<p class="affibloom-empty__message"><?php esc_html_e( 'Preview and display fields', 'affibloom' ); ?></p>
					<p class="affibloom-empty__text"><?php esc_html_e( 'Coming soon: the box preview, theme, image, badge, price, description and disclosure.', 'affibloom' ); ?></p>
				</div>
			</form>

			<?php // Lasso's footer: Delete This Link on the left, Save Changes on the right ("Learn About This Page" is Lasso's help site). ?>
			<div class="affibloom-product__footer">
				<button type="button" class="affibloom-link-button affibloom-link-button--danger" id="affibloom-delete" aria-disabled="true" aria-describedby="affibloom-soon-note">
					<?php $this->render( 'admin/partials/icon', array( 'name' => 'trash' ) ); ?>
					<span><?php esc_html_e( 'Delete This Link', 'affibloom' ); ?></span>
				</button>
				<?php // A plain button until Task 15 saves the form: it must not send it anywhere yet. ?>
				<button type="button" id="affibloom-save" class="affibloom-button affibloom-button--primary" aria-disabled="true" aria-describedby="affibloom-soon-note">
					<?php esc_html_e( 'Save Changes', 'affibloom' ); ?>
				</button>
			</div>
			<span id="affibloom-soon-note" hidden><?php esc_html_e( 'Coming soon', 'affibloom' ); ?></span>
		</div>
	</div>
</div>
