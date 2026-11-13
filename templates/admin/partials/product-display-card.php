<?php
/**
 * The product page's right card (F1, Task 14B): what a box shows besides the link, in Lasso's order (url-details.php
 * 299-510): the preview with the image and "Update Image", Display Theme beside the image link, Badge Text beside
 * Price, Description, Disclosure, then the toggles. Labels and help texts are Lasso's, with its product name removed.
 *
 * Placeholders until their tasks: the live preview (14E, Phase 3), Detect Opportunities (the Opportunities area),
 * Link Cloaking (link cloaking), Refresh and the Amazon price (C1). Lasso's Commission Upgrades is its cloud's and is
 * left out (deviation 52). Nothing here is stored until Save Changes (Task 15, D2).
 *
 * @package Affibloom
 * @var array{product: \Affibloom\Domain\Product\Product, theme: string, themes: list<string>, disclosure: string, own_text: string, show_price: bool, amazon_tab: string, choices: \Affibloom\Interface\Admin\IconChoices} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Product\ProductType;

$product   = $view['product'];
$is_amazon = ProductType::Amazon === $product->type;
$image     = $product->image_url;
// D9: Lasso's label follows the link; decision 11 keeps Amazon's editable, with Refresh waiting for C1.
$image_label = $is_amazon ? __( 'Amazon Image', 'affibloom' ) : __( 'Product Image', 'affibloom' );
$image_help  = $is_amazon
	? __( 'Paste the image link from Amazon and click Apply, or click the preview image to choose from your Media Library. Refresh needs the Amazon API.', 'affibloom' )
	: __( 'Paste an image URL and click Apply, or click the preview image to choose from your Media Library.', 'affibloom' );
// The toolbars are Lasso's, set by ProductEditors (other plugins may rewrite every editor's settings).
$editor = static fn( int $height ): array => array(
	'media_buttons' => false,
	'quicktags'     => false,
	'teeny'         => false,
	'editor_height' => $height,
);
?>
<div class="affibloom-card affibloom-product__card affibloom-product__card--display">
	<?php // The live preview renders the real box (Task 14E, Phase 3); until then the frame holds the image, as Lasso's does. ?>
	<div class="affibloom-image-frame" data-image-frame>
		<img class="affibloom-image-frame__image" src="<?php echo esc_url( $image ); ?>" alt="" data-image-preview<?php echo '' === $image ? ' hidden' : ''; ?>>
		<span class="affibloom-image-frame__empty" data-image-empty<?php echo '' === $image ? '' : ' hidden'; ?>>
			<?php $this->render( 'admin/partials/icon', array( 'name' => 'image' ) ); ?>
		</span>
		<button type="button" class="affibloom-button affibloom-button--small affibloom-button--secondary affibloom-image-frame__update" data-image-choose hidden
			data-media-title="<?php esc_attr_e( 'Select an Image', 'affibloom' ); ?>" data-media-button="<?php esc_attr_e( 'Select Image', 'affibloom' ); ?>">
			<?php $this->render( 'admin/partials/icon', array( 'name' => 'image' ) ); ?>
			<span><?php esc_html_e( 'Update Image', 'affibloom' ); ?></span>
		</button>
		<p class="affibloom-image-frame__note">
			<?php $this->render( 'admin/partials/soon', array( 'variant' => 'chip' ) ); ?>
			<?php esc_html_e( 'The box preview appears here.', 'affibloom' ); ?>
		</p>
	</div>

	<div class="affibloom-product__pair affibloom-product__pair--even">
		<div class="affibloom-field">
			<div class="affibloom-field__head">
				<label class="affibloom-label" for="affibloom-theme"><?php esc_html_e( 'Display Theme', 'affibloom' ); ?></label>
				<?php
				$this->render(
					'admin/partials/help',
					array(
						'id'    => 'affibloom-theme-help',
						'text'  => __( 'Choose the default display theme for this link.', 'affibloom' ),
						'label' => __( 'Display Theme', 'affibloom' ),
					)
				);
				?>
			</div>
			<select class="affibloom-input" id="affibloom-theme" name="theme" aria-describedby="affibloom-theme-help">
				<?php /* translators: %s: the Single display type's theme, e.g. Lab. */ ?>
				<option value=""><?php echo esc_html( sprintf( __( 'Default (%s)', 'affibloom' ), $view['theme'] ) ); ?></option>
				<?php foreach ( $view['themes'] as $theme_name ) : ?>
					<option value="<?php echo esc_attr( $theme_name ); ?>" <?php selected( null !== $product->theme && $product->theme->name() === $theme_name ); ?>><?php echo esc_html( $theme_name ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="affibloom-field">
			<div class="affibloom-field__head">
				<label class="affibloom-label" for="affibloom-image-url"><?php echo esc_html( $image_label ); ?></label>
				<?php
				$this->render(
					'admin/partials/help',
					array(
						'id'    => 'affibloom-image-url-help',
						'text'  => $image_help,
						'label' => $image_label,
					)
				);
				?>
			</div>
			<span class="affibloom-image-field">
				<input type="text" class="affibloom-input" id="affibloom-image-url" name="image_url" value="<?php echo esc_attr( $image ); ?>"
					aria-describedby="affibloom-image-url-help affibloom-image-url-error" data-image-url>
				<button type="button" class="affibloom-button affibloom-button--secondary" data-image-apply
					data-required="<?php esc_attr_e( 'Image URL is required.', 'affibloom' ); ?>" data-invalid="<?php esc_attr_e( 'Invalid image url.', 'affibloom' ); ?>"><?php esc_html_e( 'Apply', 'affibloom' ); ?></button>
				<?php if ( $is_amazon ) : ?>
					<button type="button" class="affibloom-button affibloom-button--secondary" disabled title="<?php esc_attr_e( 'Needs the Amazon API', 'affibloom' ); ?>"><?php esc_html_e( 'Refresh', 'affibloom' ); ?></button>
				<?php endif; ?>
			</span>
			<p class="affibloom-field__error" id="affibloom-image-url-error" data-image-error hidden></p>
			<input type="hidden" name="image_id" value="<?php echo esc_attr( (string) $product->image_id ); ?>" data-image-id>
		</div>
	</div>

	<div class="affibloom-product__pair affibloom-product__pair--even">
		<?php
		$this->render(
			'admin/partials/field-text',
			array(
				'id'          => 'affibloom-badge',
				'name'        => 'badge',
				'label'       => __( 'Badge Text', 'affibloom' ),
				'value'       => $product->badge,
				'help'        => __( 'This text will appear as a badge in displays for this URL.', 'affibloom' ),
				'placeholder' => __( 'Our Pick', 'affibloom' ),
				'choices'     => $view['choices'],
			)
		);
		?>
		<div class="affibloom-field affibloom-price">
			<div class="affibloom-field__head">
				<label class="affibloom-label" for="<?php echo $is_amazon ? 'affibloom-price' : 'affibloom_price'; ?>"><?php esc_html_e( 'Price', 'affibloom' ); ?></label>
				<?php
				$this->render(
					'admin/partials/help',
					array(
						'id'    => 'affibloom-price-help',
						'text'  => __( "This price can be any text or amount that'll only be shown in displays. Prices automatically update every 24 hours with integrations like Amazon.", 'affibloom' ),
						'label' => __( 'Price', 'affibloom' ),
					)
				);
				?>
				<span class="affibloom-price__show">
					<?php
					$this->render(
						'admin/partials/switch-row',
						array(
							'id'      => 'affibloom-show-price',
							'name'    => 'show_price',
							'label'   => __( 'Show Price', 'affibloom' ),
							'checked' => $view['show_price'],
							'help'    => __( 'Turn this on to show the price in this display.', 'affibloom' ),
						)
					);
					?>
				</span>
			</div>
			<?php if ( $is_amazon ) : ?>
				<?php // Lasso: "Can't change the price of an Amazon Product"; its price comes from the Amazon API (C1). ?>
				<input type="text" class="affibloom-input" id="affibloom-price" name="price" value="<?php echo esc_attr( wp_strip_all_tags( $product->price ) ); ?>"
					placeholder="$99.99" aria-describedby="affibloom-price-help" readonly>
				<p class="affibloom-field__hint">
					<?php esc_html_e( 'Amazon prices updated via', 'affibloom' ); ?>
					<a href="<?php echo esc_url( $view['amazon_tab'] ); ?>"><?php esc_html_e( 'Amazon API settings', 'affibloom' ); ?></a>
				</p>
			<?php else : ?>
				<?php // Lasso's one-line price editor: bold, italic, underline, strike and text colour. ?>
				<div class="affibloom-editor affibloom-editor--price">
					<?php wp_editor( $product->price, 'affibloom_price', array( 'textarea_name' => 'price' ) + $editor( 40 ) ); ?>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<div class="affibloom-field">
		<div class="affibloom-field__head">
			<label class="affibloom-label" for="affibloom_description"><?php esc_html_e( 'Description', 'affibloom' ); ?></label>
			<?php
			$this->render(
				'admin/partials/help',
				array(
					'id'    => 'affibloom-description-help',
					'text'  => __( "Share more details about the product or service you're linking to. A short paragraph of 1-4 sentences typically performs best.", 'affibloom' ),
					'label' => __( 'Description', 'affibloom' ),
				)
			);
			?>
		</div>
		<?php // Lasso's toolbar: bold, italic, underline, strike, link, bulleted list, text colour, highlight, clear. ?>
		<div class="affibloom-editor">
			<?php wp_editor( $product->description, 'affibloom_description', array( 'textarea_name' => 'description' ) + $editor( 140 ) ); ?>
		</div>
	</div>

	<div class="affibloom-field">
		<div class="affibloom-field__head">
			<label class="affibloom-label" for="affibloom-disclosure"><?php esc_html_e( 'Disclosure', 'affibloom' ); ?></label>
			<?php
			$this->render(
				'admin/partials/help',
				array(
					'id'    => 'affibloom-disclosure-help',
					'text'  => __( 'This disclosure will only be shown in displays', 'affibloom' ),
					'label' => __( 'Disclosure', 'affibloom' ),
				)
			);
			?>
		</div>
		<textarea class="affibloom-input affibloom-input--area" id="affibloom-disclosure" name="disclosure" rows="2"
			placeholder="<?php echo esc_attr( $view['disclosure'] ); ?>" aria-describedby="affibloom-disclosure-help"><?php echo esc_textarea( $view['own_text'] ); ?></textarea>
	</div>

	<div class="affibloom-product__switches">
		<?php
		$switches = array(
			array( 'affibloom-sponsored', 'sponsored', __( 'Sponsored', 'affibloom' ), $product->sponsored, __( 'When enabled, this link will be set to sponsored.', 'affibloom' ), false ),
			array( 'affibloom-show-disclosure', 'show_disclosure', __( 'Show Disclosure', 'affibloom' ), $product->show_disclosure, __( 'Turn this on to show the disclosure in displays for this link.', 'affibloom' ), false ),
			array( 'affibloom-opportunities', 'is_opportunity', __( 'Detect Opportunities', 'affibloom' ), false, __( 'When disabled, Link Opportunities will not show for this link.', 'affibloom' ), true ),
		);
		if ( ! $is_amazon ) {
			// Lasso hides Link Cloaking for Amazon links (url-details.php 491).
			$switches[] = array( 'affibloom-cloaking', 'link_cloaking', __( 'Link Cloaking', 'affibloom' ), false, __( 'The destination of this URL will be redirected from a pretty url on your domain. All non-Amazon Affiliate links should have this enabled.', 'affibloom' ), true );
		} else {
			// Not on Lasso's page (its Amazon data sets it); kept so the editor can set it until C1 (interface spec, D7).
			$switches[] = array( 'affibloom-prime', 'prime', __( 'Show Prime Logo', 'affibloom' ), $product->prime, __( 'Show the blue Prime logo for this Amazon product.', 'affibloom' ), false );
		}
		foreach ( $switches as [ $switch_id, $switch_name, $switch_label, $switch_on, $switch_help, $switch_soon ] ) {
			$this->render(
				'admin/partials/switch-row',
				array(
					'id'      => $switch_id,
					'name'    => $switch_name,
					'label'   => $switch_label,
					'checked' => $switch_on,
					'help'    => $switch_help,
					'soon'    => $switch_soon,
				)
			);
		}
		?>
	</div>
</div>
