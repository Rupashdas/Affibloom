# Affibloom Phase 3: superseded tasks (history only)

Moved here on 2026-11-14 when Phase 3 was rewritten as "Product displays" (features F5-F8, F31, F30, part of C1 and
F16's preview, `docs/features/`). This file is history only: do not work from it. The text below is the old Phase 3
from its "Tasks in this phase" list to its exit check, word for word: the list, the Task 18S placeholder, the Groups
sweep note, old Tasks 18-22 and the old exit check. Replacements: old **Task 18** (a reader opens a page with a
product box) is replaced by **Tasks 18A-18K** (markup, stylesheet, asset loading, baselines, the 8 themes, layout,
density, element visibility, classic rendering); old **Task 19** (second button) by **Task 19A**; old **Task 20**
(badge and description) by **Tasks 20A-20D** (every per-box override and the product schema); old **Task 21** (star
rating) by **Tasks 21A-21C** (rating, custom fields and Pros & Cons, Copy to Clipboard); old **Task 22** (Prime badge,
variants, preview) by **Tasks 22A-22E** (price and Prime, disclosure, timestamp, brag, SiteStripe) with its preview
split into **Tasks 10D and 14E**. The old **Task 18S** placeholder is replaced by **Tasks 18S1-18S7 and 18S9-18S14**
(F31; 18S8, the table shortcode, is superseded by F25 Task T12 in Phase 7). The Groups sweep note (group displays
need a task) is replaced by **Tasks DT1-DT10** (F30) and 18S7. The old "Decisions from Task 6" bullets, the F29 bullet
and the ADR-8 note stayed in the new phase file, as did the repoint and retired-product notes of old Task 18.

---

## Tasks in this phase

- [ ] Task 18: A reader opens a page with a product box
- [ ] Task 19: Second button in the box
- [ ] Task 20: Per-box badge and description
- [ ] Task 21: Star rating in the box
- [ ] Task 22: Prime badge, all variants matched, and the preview on the product page
- [ ] **Task 18S (to be written): Affibloom's own shortcode for placing a box.** Added by the user's gate 4
  answer for F19 on 2026-11-13 (`progress.md` deviation 48): *"we are going to keep the shortcode
  functionalities as well."* It runs after Task 18, because it renders the same box, and it is what the
  Dashboard row's `Shortcode` button copies (Task 14's Actions cell). It is separate from the `[lasso]`
  compatibility handler (ADR-3, Task 27), which exists only for content Lasso left behind. Its gates 1-2 are
  written before it is, as F16, F17, F18, F2 and F19 were; the roadmap's Screen map and
  `docs/spec/interface.md`, which both say Affibloom has no shortcode of its own, are corrected then.

---


Gate 4 for F5–F8: approve Tasks 18–22 before Task 18 starts.

> **From the Groups sweep (2026-11-14, F23 R24, D5, section 5): Group displays need a task in this phase,** after
> Tasks 18-22 and 18S: the List, Grid and Gallery display types (F16's segment) drawing a group's products in its order
> (`GroupService::members()`, F23 D12), for Affibloom's shortcode and for `[lasso type="grid|list|gallery"
> category="<slug>"]` (Task 27). Its gates 1-2 come first.

### Task 18: A reader opens a page with a product box

> **From Task 15C (2026-11-13, F1 D14):** a product retired by a repoint (`_affibloom_repointed_to` set, the status
> `affibloom_retired` since Task 17C) is drawn as the product it points to, as Lasso's front end resolves `lasso_repointed_to_lasso_id`;
> follow the pointer until a published product (guard against a loop), else draw nothing.
>
> **From the Task 17 sweep (2026-11-13, F1 X10, D41, D43):** a retired product's status is `affibloom_retired` (17C),
> not the trash. Lasso follows the pointer whatever the status, up to 50 hops, and stops at a target that no longer
> exists; a product deleted for good (17A) may be such a target, so the resolver stops at the last product that
> exists and draws it only if it is published.

**Roadmap:** 3.1 · **Features:** F5 (AC2), F9 (AC2)

**Why:** the box is what readers see. It must look like today's Lasso box (Lab theme) and carry the right link attributes. Its CSS must load only where a box appears.

**User action:** a reader opens a post that contains `<!-- wp:affibloom/product-box {"productId":123} /-->`. They see:
- the product image, floated right on desktop and full width on mobile, linked to the product;
- the title, linked;
- the description;
- the "Buy Now" button, with `rel="nofollow noopener"` and `target="_blank"` under the default settings;
- the disclosure in small italics.

A page without a box loads no Affibloom CSS.

**Request path:**
```
GET /some-guide/
  → wp_enqueue_scripts: BoxAssets::enqueue_for_page()   has_block( affibloom/product-box ) → affibloom-box CSS + colour variables
  → the_content → render_block → ProductBoxBlock::render( attributes )
       ProductRepository::get( productId )   → null → '' (nothing shown)
       BoxHtml::render( product )
         BoxBuilder::build( product ) → BoxView { name, image, title_link, description, button1, disclosure }
              LinkAttributes::for_primary( new_tab, nofollow, sponsored )
              Settings: primary_button_text, disclosure_text, link_from_display_title
         Template 'box' → templates/box.php
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `blocks/product-box/block.json` (server side only, for now) | `ProductBoxBlock::register_block()` |
| `Interface\Block\ProductBoxBlock` | `init`, `render_block` |
| `Interface\Front\BoxHtml` | `ProductBoxBlock` (and, later, the legacy shortcode and the product page) |
| `Interface\Front\BoxAssets` | `init`, `wp_enqueue_scripts`, `BoxHtml` |
| `Application\Box\{BoxBuilder, BoxView, BoxButton}` | `BoxHtml` |
| `Domain\Link\LinkAttributes::for_primary()` | `BoxBuilder` |
| `templates/box.php`, `assets/front/box.css` | `BoxHtml`, `BoxAssets` |

**Files:**
- Create:
  - `blocks/product-box/block.json`
  - `src/Interface/Block/ProductBoxBlock.php`
  - `src/Interface/Front/{BoxHtml,BoxAssets}.php`
  - `src/Application/Box/{BoxBuilder,BoxView,BoxButton}.php`
  - `src/Domain/Link/LinkAttributes.php`
  - `templates/box.php`, `assets/front/box.css`
  - `tests/Unit/Domain/LinkAttributesTest.php`, `tests/Unit/Application/BoxBuilderTest.php`
  - `tests/Integration/ProductBoxBlockTest.php`
- Modify: `src/Plugin.php`

- [ ] **Step 1: Failing tests**

```bash
npm run test:e2e -- acceptance/F5 -g AC2 acceptance/F9 -g AC2
```

`tests/Unit/Domain/LinkAttributesTest.php`:

```php
<?php
namespace Affibloom\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Affibloom\Domain\Link\LinkAttributes;

final class LinkAttributesTest extends TestCase {
	/** @dataProvider primary */
	public function test_primary_follows_lassos_rules( bool $new_tab, bool $nofollow, bool $sponsored, string $rel, string $target ): void {
		$link = LinkAttributes::for_primary( $new_tab, $nofollow, $sponsored );
		self::assertSame( array( $rel, $target ), array( $link->rel, $link->target ) );
	}

	public static function primary(): array {
		return array(
			'all on'           => array( true, true, true, 'nofollow noopener sponsored', '_blank' ),
			'defaults'         => array( true, true, false, 'nofollow noopener', '_blank' ),
			'same tab'         => array( false, true, false, 'nofollow', '_self' ),
			'sponsored only'   => array( false, false, true, 'sponsored', '_self' ),
			'nothing'          => array( false, false, false, '', '_self' ),
		);
	}
}
```

`tests/Integration/ProductBoxBlockTest.php`:

```php
<?php
namespace Affibloom\Tests\Integration;

use WP_UnitTestCase;

final class ProductBoxBlockTest extends WP_UnitTestCase {
	public function test_the_block_renders_the_product_with_its_link_attributes(): void {
		$id = self::factory()->post->create(
			array(
				'post_type'  => 'affibloom_product',
				'post_title' => 'Mizuno Wave Momentum 3',
				'meta_input' => array(
					'_affibloom_url'         => 'https://www.amazon.com/dp/B0C1234567?tag=vbvault-20',
					'_affibloom_new_tab'     => '1',
					'_affibloom_nofollow'    => '1',
					'_affibloom_description' => '<p>Light and fast.</p>',
				),
			)
		);

		$html = do_blocks( '<!-- wp:affibloom/product-box {"productId":' . $id . '} /-->' );

		self::assertStringContainsString( 'Mizuno Wave Momentum 3', $html );
		self::assertStringContainsString( 'href="https://www.amazon.com/dp/B0C1234567?tag=vbvault-20"', $html );
		self::assertStringContainsString( 'rel="nofollow noopener"', $html );
		self::assertStringContainsString( '>Buy Now<', $html );
		self::assertStringContainsString( '<p>Light and fast.</p>', $html );
	}

	public function test_a_missing_product_renders_nothing(): void {
		self::assertSame( '', trim( do_blocks( '<!-- wp:affibloom/product-box {"productId":999999} /-->' ) ) );
	}
}
```

- [ ] **Step 2: Entry point: the block**

`blocks/product-box/block.json`:

```json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "affibloom/product-box",
  "title": "Product box",
  "category": "widgets",
  "icon": "cart",
  "description": "Shows an affiliate product as a box with buttons.",
  "keywords": [ "affiliate", "product", "amazon", "lasso" ],
  "textdomain": "affibloom",
  "attributes": {
    "productId": { "type": "integer", "default": 0 }
  },
  "supports": { "html": false }
}
```

`src/Interface/Block/ProductBoxBlock.php`:

```php
<?php
/**
 * The product box block (dynamic: rendered on the server on every view).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Block;

use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Interface\Front\BoxHtml;
use Affibloom\Module;

/**
 * The product box block (dynamic: rendered on the server on every view).
 */
final class ProductBoxBlock implements Module {

	public const NAME = 'affibloom/product-box';

	/**
	 * Builds the block.
	 *
	 * @param string            $block_dir Folder holding block.json.
	 * @param ProductRepository $products  Product storage.
	 * @param BoxHtml           $html      Box renderer.
	 */
	public function __construct(
		private string $block_dir,
		private ProductRepository $products,
		private BoxHtml $html,
	) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_block' ) );
	}

	/**
	 * Registers the block from its block.json.
	 */
	public function register_block(): void {
		register_block_type( $this->block_dir, array( 'render_callback' => array( $this, 'render' ) ) );
	}

	/**
	 * The box for the chosen product, or nothing when the product is gone.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 */
	public function render( array $attributes ): string {
		$product = $this->products->get( (int) ( $attributes['productId'] ?? 0 ) );
		if ( null === $product ) {
			return '';
		}
		return $this->html->render( $product );
	}
}
```

- [ ] **Step 3: Interface: from product to HTML**

`src/Interface/Front/BoxHtml.php`:

```php
<?php
/**
 * Turns a product into box HTML. Every caller (block, [lasso], preview) goes through here.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Front;

use Affibloom\Application\Box\BoxBuilder;
use Affibloom\Domain\Product\Product;
use Affibloom\Interface\Template;

/**
 * Turns a product into box HTML. Every caller (block, [lasso], preview) goes through here.
 */
final class BoxHtml {

	/**
	 * Builds the renderer.
	 *
	 * @param BoxBuilder $builder  Builds the box's values.
	 * @param Template   $template Renders templates/box.php.
	 * @param BoxAssets  $assets   Makes sure the CSS is on the page.
	 */
	public function __construct(
		private BoxBuilder $builder,
		private Template $template,
		private BoxAssets $assets,
	) {}

	/**
	 * The box's HTML.
	 *
	 * @param Product $product A published product.
	 */
	public function render( Product $product ): string {
		$this->assets->enqueue();
		return $this->template->capture( 'box', array( 'box' => $this->builder->build( $product ) ) );
	}
}
```

`src/Interface/Front/BoxAssets.php`:

```php
<?php
/**
 * Box CSS and the colour variables, only on pages that show a box.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Front;

use Affibloom\Domain\Settings\SettingsRepository;
use Affibloom\Interface\Block\ProductBoxBlock;
use Affibloom\Module;
use WP_Post;

/**
 * Box CSS and the colour variables, only on pages that show a box.
 */
final class BoxAssets implements Module {

	public const HANDLE = 'affibloom-box';

	/**
	 * Builds the module.
	 *
	 * @param string             $plugin_url Plugin URL with trailing slash.
	 * @param string             $version    Plugin version.
	 * @param SettingsRepository $settings   Colours come from Display settings.
	 */
	public function __construct(
		private string $plugin_url,
		private string $version,
		private SettingsRepository $settings,
	) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_style' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_for_page' ) );
	}

	/**
	 * Registers the stylesheet with the colour variables attached.
	 */
	public function register_style(): void {
		wp_register_style( self::HANDLE, $this->plugin_url . 'assets/front/box.css', array(), $this->version );
		wp_add_inline_style( self::HANDLE, $this->variables() );
	}

	/**
	 * In the <head> of a single post or page that contains a box, so it never loads late.
	 */
	public function enqueue_for_page(): void {
		$post = get_post();
		if ( is_singular() && $post instanceof WP_Post && $this->has_box( $post ) ) {
			wp_enqueue_style( self::HANDLE );
		}
	}

	/**
	 * Called by every box as it renders; covers boxes outside the main content.
	 */
	public function enqueue(): void {
		wp_enqueue_style( self::HANDLE );
	}

	/**
	 * Whether a post's content contains a box.
	 *
	 * @param WP_Post $post Post.
	 */
	private function has_box( WP_Post $post ): bool {
		return has_block( ProductBoxBlock::NAME, $post );
	}

	/**
	 * Display colours as CSS variables.
	 */
	private function variables(): string {
		$settings = $this->settings->get();
		$vars     = array(
			'--affibloom-main'             => 'color_main',
			'--affibloom-title'            => 'color_title',
			'--affibloom-background'       => 'color_background',
			'--affibloom-button'           => 'color_button',
			'--affibloom-secondary-button' => 'color_secondary_button',
			'--affibloom-button-text'      => 'color_button_text',
		);

		$css = '';
		foreach ( $vars as $var => $key ) {
			$css .= $var . ':' . sanitize_hex_color( $settings->string( $key ) ) . ';';
		}
		return ':root{' . $css . '}';
	}
}
```

- [ ] **Step 4: Application: the box's values**

`src/Application/Box/BoxButton.php`:

```php
<?php
/**
 * One box button (or the image and title links, which share the primary button's values).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Box;

/**
 * One box button (or the image and title links, which share the primary button's values).
 */
final class BoxButton {

	/**
	 * Builds the button.
	 *
	 * @param string $url    Destination.
	 * @param string $text   Label.
	 * @param string $rel    rel attribute; '' for none.
	 * @param string $target "_blank" or "_self".
	 */
	public function __construct(
		public readonly string $url,
		public readonly string $text,
		public readonly string $rel,
		public readonly string $target,
	) {}
}
```

`src/Application/Box/BoxView.php`:

```php
<?php
/**
 * Everything the box template prints, already decided.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Box;

/**
 * Everything the box template prints, already decided.
 */
final class BoxView {

	/**
	 * Builds the view.
	 *
	 * @param int       $product_id  Product ID.
	 * @param string    $name        Title.
	 * @param bool      $title_link  Whether the title is a link.
	 * @param string    $image_url   Image; '' for none.
	 * @param string    $image_alt   Image alt text.
	 * @param string    $description HTML; '' for none.
	 * @param BoxButton $button1     Primary button (also used by image and title links).
	 * @param string    $disclosure  Disclosure text; '' when hidden.
	 */
	public function __construct(
		public readonly int $product_id,
		public readonly string $name,
		public readonly bool $title_link,
		public readonly string $image_url,
		public readonly string $image_alt,
		public readonly string $description,
		public readonly BoxButton $button1,
		public readonly string $disclosure,
	) {}
}
```

`src/Application/Box/BoxBuilder.php`:

```php
<?php
/**
 * Decides what a box shows: product values, then site defaults.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Box;

use Affibloom\Domain\Link\LinkAttributes;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Settings\SettingsRepository;

/**
 * Decides what a box shows: product values, then site defaults.
 */
final class BoxBuilder {

	/**
	 * Builds the builder.
	 *
	 * @param SettingsRepository $settings Display defaults.
	 */
	public function __construct( private SettingsRepository $settings ) {}

	/**
	 * The box's values for a product.
	 *
	 * @param Product $product Product.
	 */
	public function build( Product $product ): BoxView {
		$settings = $this->settings->get();
		$link     = LinkAttributes::for_primary( $product->new_tab, $product->nofollow, $product->sponsored );

		$disclosure = '';
		if ( $product->show_disclosure ) {
			$disclosure = '' !== $product->disclosure ? $product->disclosure : $settings->string( 'disclosure_text' );
		}

		return new BoxView(
			product_id: (int) $product->id,
			name: $product->name,
			title_link: $settings->bool( 'link_from_display_title' ),
			image_url: $product->image_url,
			image_alt: $product->name,
			description: $product->description,
			button1: new BoxButton(
				$product->url->value(),
				'' !== $product->button_text ? $product->button_text : $settings->string( 'primary_button_text' ),
				$link->rel,
				$link->target
			),
			disclosure: $disclosure,
		);
	}
}
```

`src/Domain/Link/LinkAttributes.php`:

```php
<?php
/**
 * The rel and target of product links, by Lasso's rules (class-lasso-affiliate-link.php).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Link;

/**
 * The rel and target of product links, by Lasso's rules (class-lasso-affiliate-link.php).
 */
final class LinkAttributes {

	/**
	 * Use the named constructors.
	 *
	 * @param string $rel    Space-separated rel values; '' for none.
	 * @param string $target "_blank" or "_self".
	 */
	private function __construct( public readonly string $rel, public readonly string $target ) {}

	/**
	 * Primary button, image and title: nofollow, then noopener for a new tab, then sponsored.
	 *
	 * @param bool $new_tab   Open in a new tab.
	 * @param bool $nofollow  Add nofollow.
	 * @param bool $sponsored Add sponsored.
	 */
	public static function for_primary( bool $new_tab, bool $nofollow, bool $sponsored ): self {
		$rel = array();
		if ( $nofollow ) {
			$rel[] = 'nofollow';
		}
		if ( $new_tab ) {
			$rel[] = 'noopener';
		}
		if ( $sponsored ) {
			$rel[] = 'sponsored';
		}
		return new self( implode( ' ', $rel ), $new_tab ? '_blank' : '_self' );
	}
}
```

`tests/Unit/Application/BoxBuilderTest.php` covers four cases:
- an empty `button_text` gives the Display default;
- a product disclosure overrides the default;
- `show_disclosure` off gives `''`;
- `link_from_display_title` is passed through.

It uses the in-memory settings repository pattern from earlier tests.

- [ ] **Step 5: Template and CSS (the response)**

`templates/box.php`:

```php
<?php
/**
 * The product box (Lab layout: image right, title, description; buttons; disclosure).
 *
 * @package Affibloom
 * @var array{box: \Affibloom\Application\Box\BoxView} $view
 */

defined( 'ABSPATH' ) || exit;

$box   = $view['box'];
$attrs = static function ( \Affibloom\Application\Box\BoxButton $button, string $kind ) use ( $box ): string {
	$html = sprintf(
		'href="%s" target="%s" data-affibloom-id="%d" data-affibloom-btn="%s"',
		esc_url( $button->url ),
		esc_attr( $button->target ),
		$box->product_id,
		esc_attr( $kind )
	);
	if ( '' !== $button->rel ) {
		$html .= sprintf( ' rel="%s"', esc_attr( $button->rel ) );
	}
	return $html;
};
?>
<div class="affibloom-container">
	<div class="affibloom-box">
		<div class="affibloom-box__main">
			<?php if ( '' !== $box->image_url ) : ?>
				<a class="affibloom-image" <?php echo $attrs( $box->button1, 'image' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in $attrs. ?>>
					<img src="<?php echo esc_url( $box->image_url ); ?>" width="500" height="500" alt="<?php echo esc_attr( $box->image_alt ); ?>" loading="lazy" decoding="async">
				</a>
			<?php endif; ?>

			<?php if ( $box->title_link ) : ?>
				<a class="affibloom-title" <?php echo $attrs( $box->button1, 'title' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $box->name ); ?></a>
			<?php else : ?>
				<span class="affibloom-title"><?php echo esc_html( $box->name ); ?></span>
			<?php endif; ?>

			<?php if ( '' !== $box->description ) : ?>
				<div class="affibloom-description"><?php echo wp_kses_post( $box->description ); ?></div>
			<?php endif; ?>
		</div>

		<div class="affibloom-buttons">
			<a class="affibloom-button affibloom-button-1" <?php echo $attrs( $box->button1, '1' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $box->button1->text ); ?></a>
		</div>

		<?php if ( '' !== $box->disclosure ) : ?>
			<div class="affibloom-footer">
				<div class="affibloom-disclosure"><span><?php echo esc_html( $box->disclosure ); ?></span></div>
			</div>
		<?php endif; ?>
	</div>
</div>
```

The `data-affibloom-id` and `data-affibloom-btn` attributes are added now because they cost nothing. Their reader, the click script, arrives in Task 33. If the review at gate 7 prefers rule 4 strictly, move them to Task 33.

`assets/front/box.css`. The starting values come from Lasso's Lab theme rules, rewritten, not copied. Every value marked `TUNE` is adjusted in Step 6 until the visual test passes.

```css
/* Affibloom product box: the Lab layout. Colours come from Display settings as CSS variables. */

.affibloom-container {
	position: relative;
	box-sizing: border-box;
	width: 100%;
	margin: 30px auto;
	padding: 0 15px;
	color: initial;
}
.affibloom-container *,
.affibloom-container *::before,
.affibloom-container *::after { box-sizing: border-box; }

.affibloom-box {
	position: relative;
	display: flex;
	flex-wrap: wrap;
	padding: 15px; /* TUNE: Lasso's display density */
	background-color: var(--affibloom-background, #fff);
	font-family: inherit;
}
.affibloom-box__main,
.affibloom-buttons,
.affibloom-footer { flex: 0 0 100%; max-width: 100%; }

.affibloom-box a,
.affibloom-box a:hover { text-decoration: none; font-weight: bold; opacity: 1; }

/* Image, floated right on wide screens */
.affibloom-box .affibloom-image { float: right; margin: 0 0 20px 40px; box-shadow: none; }
.affibloom-box .affibloom-image img {
	display: block;
	width: auto;
	max-width: 250px; /* TUNE */
	height: auto;
	max-height: 250px; /* TUNE */
	border-radius: 12px;
}

/* Title */
.affibloom-box .affibloom-title {
	display: block;
	margin: 10px 0;
	font-size: 30px;
	line-height: 1.25;
	font-weight: bold;
	color: var(--affibloom-title, #000);
	box-shadow: none;
}

/* Description */
.affibloom-description { margin: 0 0 10px; font-size: 16px; overflow-wrap: anywhere; }
.affibloom-description p { margin: 0 0 10px; }
.affibloom-description ul,
.affibloom-description ol { margin: 0 0 10px 10px; padding: 0 0 0 40px; text-align: left; }
.affibloom-description li { margin: 0 0 5px; display: list-item; }

/* Buttons */
.affibloom-buttons { clear: both; }
.affibloom-box .affibloom-button {
	display: inline-block;
	margin: 0 10px 10px 0;
	padding: 15px 25px;
	border-radius: 5px;
	background: var(--affibloom-button, #22baa0);
	color: var(--affibloom-button-text, #fff);
	font-size: 15px;
	line-height: normal;
	text-align: center;
	box-shadow: 0 5px 10px 0 rgba(0, 0, 0, 0.2);
	transition: 0.25s ease;
}
.affibloom-box .affibloom-button:hover,
.affibloom-box .affibloom-button:focus-visible {
	color: var(--affibloom-button-text, #fff);
	box-shadow: none;
	transform: translateY(5px);
}

/* Disclosure */
.affibloom-disclosure { display: inline; font-size: 12px; font-style: italic; color: #5f6368eb; }
.affibloom-disclosure span { line-height: 1.2rem; }

/* Mobile: everything centred, image full width (Lasso's breakpoint) */
@media (max-width: 767.98px) {
	.affibloom-box__main,
	.affibloom-buttons,
	.affibloom-footer { text-align: center; }
	.affibloom-box .affibloom-image { float: none; display: block; margin: 0 0 10px; }
	.affibloom-box .affibloom-image img { width: 100%; max-width: none; max-height: none; }
}

@media (prefers-reduced-motion: reduce) {
	.affibloom-box .affibloom-button { transition: none; }
	.affibloom-box .affibloom-button:hover { transform: none; }
}
```

`src/Plugin.php`:

```php
		$box_assets = new BoxAssets( plugin_dir_url( $this->file ), AFFIBLOOM_VERSION, $this->settings_repository() );
		$box_html   = new BoxHtml( new BoxBuilder( $this->settings_repository() ), $this->template(), $box_assets );
```

and in the module list:

```php
			$box_assets,
			new ProductBoxBlock( dirname( $this->file ) . '/blocks/product-box', $this->product_repository(), $box_html ),
```

- [ ] **Step 6: Visual check against Lasso (first pass)**

Create the "amazon" variant's product from `tests/fixtures/baselines/boxes.json` (the first entry the Task 5 `amazon` filter picked). Put it in a draft post, and open it at 1280 and 390 wide, beside `variants/amazon-desktop.png` and `amazon-mobile.png`. Adjust every `TUNE` value, plus any spacing that differs, until the two look the same. The automated pixel comparison comes in Task 22, when all parts of the box exist.

- [ ] **Step 7: Run, commit**

```bash
composer lint && composer analyse && composer test:unit && npm run test:integration
npm run test:e2e -- acceptance/F5 -g AC2 acceptance/F9 -g AC2
git add -A && git commit -m "Render the product box block in the Lab layout

Image, linked title, description, primary button with Lasso's rel and
target rules, and the disclosure. The CSS loads only on pages that
contain a box."
```

---

### Task 19: Second button in the box

**Roadmap:** 3.2 · **Feature:** F6

**Why:** 78 boxes carry a second button, mostly the women's version of a shoe.

**User action:** a reader sees a second button next to the first, with its own label (for example "Buy Now Women's") and its own link. A product with no second URL shows one button. A second URL that points at the page being read is not shown, as in Lasso.

**Request path:** as in Task 18. In `BoxBuilder::build()`, a product with `url2` gets `button2`, built by `LinkAttributes::for_secondary()`. In `BoxHtml`, `button2` is dropped when `url_to_postid( url2 )` is the current post.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `LinkAttributes::for_secondary()` | `BoxBuilder` |
| `BoxView::$button2` | `templates/box.php` |
| the "points at this page" check in `BoxHtml` | `BoxHtml::render()` |

**Files:**
- Modify: `LinkAttributes.php`, `BoxView.php`, `BoxBuilder.php`, `BoxHtml.php`, `templates/box.php`, `box.css`, and their tests

- [ ] **Step 1: Failing tests**

```bash
npm run test:e2e -- acceptance/F6
```

Add to `LinkAttributesTest`:

```php
	public function test_secondary_adds_sponsored_only_with_nofollow(): void {
		self::assertSame( 'noopener', LinkAttributes::for_secondary( true, false, true )->rel );
		self::assertSame( 'nofollow noopener sponsored', LinkAttributes::for_secondary( true, true, true )->rel );
	}
```

- [ ] **Step 2: Domain, Application, Interface**

`LinkAttributes`:

```php
	/**
	 * Secondary button: as the primary one, but sponsored only when nofollow is on too (Lasso's rel2).
	 *
	 * @param bool $new_tab   Open in a new tab.
	 * @param bool $nofollow  Add nofollow.
	 * @param bool $sponsored The product's sponsored setting.
	 */
	public static function for_secondary( bool $new_tab, bool $nofollow, bool $sponsored ): self {
		return self::for_primary( $new_tab, $nofollow, $sponsored && $nofollow );
	}
```

`BoxView`: add a last parameter, `public readonly ?BoxButton $button2 = null,`. Also add a method, used by `BoxHtml`:

```php
	/**
	 * A copy without the second button.
	 */
	public function without_button2(): self {
		return new self( ...array_merge( get_object_vars( $this ), array( 'button2' => null ) ) );
	}
```

`BoxBuilder::build()`, before `return`:

```php
		$button2 = null;
		if ( null !== $product->url2 ) {
			$link2   = LinkAttributes::for_secondary( $product->new_tab2, $product->nofollow2, $product->sponsored );
			$button2 = new BoxButton(
				$product->url2->value(),
				'' !== $product->button_text2 ? $product->button_text2 : $settings->string( 'secondary_button_text' ),
				$link2->rel,
				$link2->target
			);
		}
```

and pass `button2: $button2,`.

`BoxHtml::render()`:

```php
		$box = $this->builder->build( $product );

		// Lasso does not link a page to itself: drop a second button that points here.
		if ( null !== $box->button2 && get_the_ID() && url_to_postid( $box->button2->url ) === get_the_ID() ) {
			$box = $box->without_button2();
		}

		return $this->template->capture( 'box', array( 'box' => $box ) );
```

`templates/box.php`, after the first button:

```php
			<?php if ( null !== $box->button2 ) : ?>
				<a class="affibloom-button affibloom-button-2" <?php echo $attrs( $box->button2, '2' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $box->button2->text ); ?></a>
			<?php endif; ?>
```

`box.css`:

```css
.affibloom-box .affibloom-button-2 { background: var(--affibloom-secondary-button, #22baa0); }
```

- [ ] **Step 3: Run, look at the "second-button" variant, commit**

```bash
composer test:unit && npm run test:integration && npm run test:e2e -- acceptance/F6
```
Compare the box with `variants/second-button-desktop.png` and `-mobile.png`.

```bash
git add -A && git commit -m "Show the second button in the box

With Lasso's rel rule for it, and never when it points at the page
being read."
```

---

### Task 20: Per-box badge and description

**Roadmap:** 3.3 · **Feature:** F7

**Why:** every box on the site sets its own badge ("Best Overall", "Best for Beginners") and often its own description, without changing the product.

**User action:** a reader sees a badge in the box's corner. The badge is either the one set on this box, or the product's Badge Text. The description is either the one set on this box, or the product's. A box set to hide the badge or the description shows neither.

**Request path:**
```
ProductBoxBlock::render( attributes )
  → BoxOptions::from_block( { badge, hideBadge, description, hideDescription } )
  → BoxHtml::render( product, options ) → BoxBuilder::build( product, options )
       badge       = options->badge       ?? product->badge
       description = options->description ?? product->description
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| block attributes `badge`, `hideBadge`, `description`, `hideDescription` | the block; the editor sidebar in Task 24 |
| `Application\Box\BoxOptions` (`none()`, `from_block()`) | `ProductBoxBlock`, the product page preview (Task 22), the legacy shortcode (Task 27) |
| `BoxView::$badge`, badge markup and CSS | `templates/box.php` |

**Files:**
- Create: `src/Application/Box/BoxOptions.php`, `tests/Unit/Application/BoxOptionsTest.php`
- Modify:
  - `blocks/product-box/block.json`
  - `ProductBoxBlock.php`, `BoxHtml.php`, `BoxBuilder.php`, `BoxView.php`
  - `templates/box.php`, `box.css`

- [ ] **Step 1: Failing tests**

```bash
npm run test:e2e -- acceptance/F7
```

`tests/Unit/Application/BoxOptionsTest.php`:

```php
<?php
namespace Affibloom\Tests\Unit\Application;

use PHPUnit\Framework\TestCase;
use Affibloom\Application\Box\BoxOptions;

final class BoxOptionsTest extends TestCase {
	public function test_empty_means_use_the_product(): void {
		$options = BoxOptions::from_block( array( 'badge' => '' ) );
		self::assertNull( $options->badge );
		self::assertNull( $options->description );
	}

	public function test_hide_wins_over_text(): void {
		$options = BoxOptions::from_block( array( 'badge' => 'Best Overall', 'hideBadge' => true ) );
		self::assertSame( '', $options->badge );
	}

	public function test_text_overrides(): void {
		self::assertSame( 'Best Overall', BoxOptions::from_block( array( 'badge' => 'Best Overall' ) )->badge );
	}
}
```

- [ ] **Step 2: Block attributes**

`block.json`, `attributes`, add:

```json
    "badge": { "type": "string", "default": "" },
    "hideBadge": { "type": "boolean", "default": false },
    "description": { "type": "string", "default": "" },
    "hideDescription": { "type": "boolean", "default": false }
```

- [ ] **Step 3: Options and their use**

`src/Application/Box/BoxOptions.php`:

```php
<?php
/**
 * What one box changes for its product. null = use the product's value; '' = hide.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Box;

/**
 * What one box changes for its product. null = use the product's value; '' = hide.
 */
final class BoxOptions {

	/**
	 * Builds the options.
	 *
	 * @param string|null $badge       Badge override.
	 * @param string|null $description Description override (HTML allowed).
	 */
	public function __construct(
		public readonly ?string $badge = null,
		public readonly ?string $description = null,
	) {}

	/**
	 * No overrides.
	 */
	public static function none(): self {
		return new self();
	}

	/**
	 * From the product box block's attributes.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 */
	public static function from_block( array $attributes ): self {
		return new self(
			badge: self::pick( $attributes['badge'] ?? '', ! empty( $attributes['hideBadge'] ) ),
			description: self::pick( $attributes['description'] ?? '', ! empty( $attributes['hideDescription'] ) ),
		);
	}

	/**
	 * Hidden → '', text → text, empty → null.
	 *
	 * @param mixed $text   Override text.
	 * @param bool  $hidden Hide flag.
	 */
	private static function pick( mixed $text, bool $hidden ): ?string {
		if ( $hidden ) {
			return '';
		}
		$text = trim( (string) $text );
		return '' === $text ? null : $text;
	}
}
```

`ProductBoxBlock::render()`: `return $this->html->render( $product, BoxOptions::from_block( $attributes ) );`

`BoxHtml::render( Product $product, BoxOptions $options )` passes `$options` to `build()`.

`BoxBuilder::build( Product $product, BoxOptions $options )`:

```php
			description: $options->description ?? $product->description,
			badge: $options->badge ?? $product->badge,
```

`BoxView`: add `public readonly string $badge = '',`.

`templates/box.php`, the first child of `.affibloom-box`:

```php
		<?php if ( '' !== $box->badge ) : ?>
			<div class="affibloom-badge"><span class="affibloom-badge-text"><?php echo esc_html( $box->badge ); ?></span></div>
		<?php endif; ?>
```

`box.css`:

```css
/* Badge: Lab places it on the right edge */
.affibloom-badge {
	position: absolute;
	top: -18px;
	right: -10px;
	z-index: 5;
	display: inline-block;
	max-width: 100%;
	padding: 10px 15px;
	background-color: var(--affibloom-main, #5e36ca);
	color: var(--affibloom-button-text, #fff);
	font-size: 1rem;
	line-height: 1rem;
	font-weight: 700;
	box-shadow: 2px 2px 2px 0 rgba(0, 0, 0, 0.14);
}
.affibloom-badge-text { display: block; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
.affibloom-badge::after {
	content: "";
	position: absolute;
	right: 0;
	bottom: -7px;
	border-style: solid;
	border-width: 0 0 7px 10px;
	border-color: transparent transparent transparent var(--affibloom-main, #5e36ca);
	filter: brightness(50%);
}
```

- [ ] **Step 4: Run, look at the "badge" variant, commit**

```bash
composer test:unit && npm run test:e2e -- acceptance/F7
git add -A && git commit -m "Let each box set or hide its badge and description

The block keeps its own badge and description; empty means the
product's values."
```

---

### Task 21: Star rating in the box

**Roadmap:** 3.4 · **Feature:** F8

**Why:** 3 boxes show stars today.

**User action:** a reader sees five stars, filled to the product's rating, with the number next to them (for example "4.5"). A product without a rating shows no stars.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `BoxView::$rating` | `templates/box.php` |
| stars markup and CSS | the box |

**Files:** modify `BoxView.php`, `BoxBuilder.php`, `templates/box.php`, `box.css`, `BoxBuilderTest.php`.

- [ ] **Step 1:** `npm run test:e2e -- acceptance/F8`. It fails.

- [ ] **Step 2:**
- `BoxView`: add `public readonly string $rating = '',`.
- `BoxBuilder`: add `rating: $product->rating ? (string) $product->rating : '',`.
- `templates/box.php`: after the title.

```php
			<?php if ( '' !== $box->rating ) : ?>
				<div class="affibloom-stars" style="--rating: <?php echo esc_attr( $box->rating ); ?>"
					role="img" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: rating, e.g. 4.5. */ __( 'Rated %s out of 5', 'affibloom' ), $box->rating ) ); ?>">
					<span class="affibloom-stars-value" aria-hidden="true"><?php echo esc_html( $box->rating ); ?></span>
				</div>
			<?php endif; ?>
```

`box.css`:

```css
/* Stars: five glyphs filled to --rating out of 5 */
.affibloom-stars {
	--rating: 0;
	--percent: calc(var(--rating) / 5 * 100%);
	display: inline-block;
	margin: 0 0 10px;
	line-height: 1;
}
.affibloom-stars::before {
	content: "★★★★★";
	font-family: Times, serif;
	letter-spacing: 1px;
	background: linear-gradient(90deg, #fc0 var(--percent), #e2e2e2 var(--percent));
	-webkit-background-clip: text;
	background-clip: text;
	-webkit-text-fill-color: transparent;
}
.affibloom-stars-value { margin-left: 4px; font-size: 14px; }
```

If any of the three rated boxes in `boxes.json` shows a "Rating:" label (Lasso's `show_field_name`), add a `rating_label` field to the product page and the box in this task, and record why.

- [ ] **Step 3:** compare with `variants/rating-*.png`; run the tests; commit with `git commit -m "Show the star rating in the box"`.

---

### Task 22: Prime badge, all variants matched, and the preview on the product page

**Roadmap:** 3.1 (exit), 2.3 (Prime) · **Features:** F2, F5 (AC1)

**Why:** this task finishes the box. It adds the Prime logo, and every baseline variant must now pass an automated pixel comparison on desktop and mobile. The product page also gets Lasso's live preview of the box, the box renderer's second caller.

**User action:**
1. A reader sees the blue Prime logo on Amazon products marked Prime, when "Show Prime Logo In Displays" is on.
2. An editor sees the box preview on the product page. After "Save Changes", the preview shows the saved values.

**Request path:**
```
BoxBuilder: prime = product.type is Amazon && product.prime && settings.show_prime
ProductScreen::render() → BoxHtml::render( product, BoxOptions::none() ) → 'preview' in the view
PATCH /products/{id}?preview=1 → response data.preview_html → admin.js replaces the preview
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `BoxView::$prime`, Prime markup, `assets/front/prime.png` | the box |
| preview card in `product.php`, `affibloom-box` CSS on the product page | `ProductScreen` |
| `preview_html` in the PATCH response | admin.js |
| `tests/Visual/compare.ts`, `tests/Visual/box-variants.spec.ts` | `npm run test:e2e` (`@local`) |

**Files:**
- Create: `assets/front/prime.png`, `tests/Visual/compare.ts`, `tests/Visual/box-variants.spec.ts`
- Modify:
  - `BoxView.php`, `BoxBuilder.php`, `templates/box.php`, `box.css`
  - `ProductScreen.php`, `ProductsController.php`, `AdminAssets.php`, `Plugin.php`
  - `templates/admin/product.php`, `admin.js`

- [ ] **Step 1: The pixel comparison (failing)**

```bash
npm install --save-dev pixelmatch pngjs @types/pngjs
```

`tests/Visual/compare.ts`:

```ts
import { readFileSync, writeFileSync } from 'node:fs';
import { PNG } from 'pngjs';
import pixelmatch from 'pixelmatch';

/** Share of differing pixels between a screenshot and a stored baseline (0–1). Writes a diff image. */
export function diffRatio( actual: Buffer, baselinePath: string, diffPath: string ): number {
	const a = PNG.sync.read( actual );
	const b = PNG.sync.read( readFileSync( baselinePath ) );
	const width = Math.max( a.width, b.width );
	const height = Math.max( a.height, b.height );
	const pad = ( img: PNG ) => {
		const out = new PNG( { width, height } );
		PNG.bitblt( img, out, 0, 0, img.width, img.height, 0, 0 );
		return out;
	};
	const diff = new PNG( { width, height } );
	const changed = pixelmatch( pad( a ).data, pad( b ).data, diff.data, width, height, { threshold: 0.1 } );
	writeFileSync( diffPath, PNG.sync.write( diff ) );
	return changed / ( width * height );
}
```

`tests/Visual/box-variants.spec.ts`:

```ts
import { test, expect } from '@playwright/test';
import { readFileSync, existsSync, mkdirSync } from 'node:fs';
import { createProduct, newPostWith, wp } from '../E2E/utils';
import { diffRatio } from './compare';

const BASE = 'tests/fixtures/baselines';
const boxes = JSON.parse( readFileSync( `${ BASE }/boxes.json`, 'utf8' ) );
const VARIANTS = [ 'amazon', 'other', 'second-button', 'badge', 'rating', 'no-description', 'prime' ];
const TOLERANCE = 0.01; // the agreed visual tolerance (roadmap decision), confirmed in Task 6

mkdirSync( 'test-results/visual', { recursive: true } );

for ( const variant of VARIANTS ) {
	test( `@local box variant "${ variant }" matches Lasso`, async ( { page } ) => {
		test.skip( ! existsSync( `${ BASE }/variants/${ variant }-desktop.png` ), 'variant not on the site' );
		const html = readFileSync( `${ BASE }/variants/${ variant }.html`, 'utf8' );
		const record = boxes.find( ( b ) => html.includes( `data-lasso-lid="${ b.lasso_id }"` ) ) ?? boxes[ 0 ];

		const id = createProduct( {
			name: record.title,
			url: record.button1.href,
			button_text: record.button1.text,
			url2: record.button2?.href ?? '',
			button_text2: record.button2?.text ?? '',
			badge: record.badge,
			description: `<p>${ record.description }</p>`,
			image_url: record.image,
			rating: record.rating,
			prime: record.prime ? '1' : '0',
			type: /amazon\./.test( record.button1.href ) ? 'amazon' : 'other',
			show_disclosure: record.disclosure ? '1' : '0',
			disclosure: record.disclosure,
		} );
		wp( 'plugin deactivate lasso' );
		const path = newPostWith( `<!-- wp:affibloom/product-box {"productId":${ id }} /-->` );

		for ( const [ size, width ] of [ [ 'desktop', 1280 ], [ 'mobile', 390 ] ] as const ) {
			await page.setViewportSize( { width, height: 900 } );
			await page.goto( path );
			await page.waitForLoadState( 'networkidle' );
			const shot = await page.locator( '.affibloom-container' ).first().screenshot();
			const ratio = diffRatio( shot, `${ BASE }/variants/${ variant }-${ size }.png`, `test-results/visual/${ variant }-${ size }-diff.png` );
			expect( ratio, `diff image: test-results/visual/${ variant }-${ size }-diff.png` ).toBeLessThanOrEqual( TOLERANCE );
		}
		wp( 'plugin activate lasso' );
	} );
}
```

```bash
bash tools/reset-local.sh
npm run test:e2e -- box-variants
```
Expected: "prime" fails, because there is no logo yet. Others pass or fail on small spacing; each diff image shows where.

- [ ] **Step 2: Prime**

The Prime logo is Amazon's trademark. Download it from the brand resources in Amazon Associates Central and save it as `assets/front/prime.png`. Do not copy Lasso's file.

- `BoxView`: add `public readonly bool $prime = false,`.
- `BoxBuilder`: add `prime: ProductType::Amazon === $product->type && $product->prime && $settings->bool( 'show_prime' ),`, with `use Affibloom\Domain\Product\ProductType;`.
- `templates/box.php`: after the stars.

```php
			<?php if ( $box->prime ) : ?>
				<div class="affibloom-price"><i class="affibloom-prime" role="img" aria-label="<?php esc_attr_e( 'Amazon Prime', 'affibloom' ); ?>"></i></div>
			<?php endif; ?>
```

`box.css`:

```css
.affibloom-price { margin: 0 0 10px; font-size: 16px; font-weight: bold; vertical-align: middle; }
.affibloom-prime {
	display: inline-block;
	width: 50px;
	height: 15px;
	background: url("prime.png") center / contain no-repeat;
}
```

- [ ] **Step 3: Tune until every variant passes**

Run `npm run test:e2e -- box-variants` and look at each diff image. Adjust `box.css` until all variants are within the tolerance, on both sizes. Record in `docs/ui/tokens.md` the final values and why each changed. If a difference cannot be closed (for example font hinting), record it with the measured ratio. You approve it there.

- [ ] **Step 4: Preview on the product page**

`ProductScreen`: the constructor takes `private BoxHtml $box_html`. `render()` adds `'preview' => $this->box_html->render( $product, BoxOptions::none() ),` to the view.

`templates/admin/product.php`: as the first card in `.affibloom-product__side`:

```php
				<div class="affibloom-card affibloom-preview">
					<h2 class="affibloom-section__title"><?php esc_html_e( 'Preview', 'affibloom' ); ?></h2>
					<div id="affibloom-preview"><?php echo $view['preview']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped by templates/box.php. ?></div>
				</div>
```

`AdminAssets::enqueue()`: on the product page, also `wp_enqueue_style( BoxAssets::HANDLE );`.

`ProductsController::update()`: the controller takes `private BoxHtml $box_html`. When the request has `preview=1`, add the HTML:

```php
		$data = $this->product_resource->full( $product );
		if ( $request->get_param( 'preview' ) ) {
			$data['preview_html'] = $this->box_html->render( $product, BoxOptions::none() );
		}
		return new WP_REST_Response( array( 'data' => $data ) );
```

Add `'preview' => array( 'type' => 'boolean' )` to the PATCH args. It is not in `field_args()`, so it never reaches `ProductInput`.

`admin.js`, in `initProductForm()`: the path becomes `` `/affibloom/v1/products/${ form.dataset.productId }?preview=1` ``. After a save, `document.getElementById( 'affibloom-preview' ).innerHTML = data.preview_html;`. The HTML comes from the plugin's own escaped template, through an authenticated request.

`.affibloom-preview .affibloom-container { margin: 0; padding: 0; }` in admin.css keeps the preview inside its card.

- [ ] **Step 5: Run everything, commit**

```bash
composer lint && composer analyse && composer test:unit && npm run test:integration
npm run test:e2e
git add -A && git commit -m "Add the Prime logo, match every box variant, preview on product page

Every baseline variant is within the agreed tolerance on desktop and
mobile; the product page shows the box as readers will see it."
```

**Phase 3 exit check:** every variant passes. A page without a box loads no plugin CSS or JS (F5 AC2). F6–F8 pass.

---

# Dropped 2026-11-14 by the user's decision B9

### Task 22D: Brag

**Feature:** F5 (R44; D8; AC23)

**Why:** Brag mode ends each box with the brag link when the user turns it on (F5 R44).

**Depends on:** 18A, N3.

**User action:** Brag mode on with a URL ends every box with the brag link.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| brag partial, `assets/front/images/brag.png` | `BoxBuilder` |

**Files:** create the brag partial under `templates/front/`, `assets/front/images/brag.png`; modify `src/Application/Box/BoxBuilder.php`, `tests/E2E/acceptance/F5.spec.ts` (AC23).

- [ ] **Step 1: Failing test (AC23).**
- [ ] **Step 2: Partial.**
- [ ] **Step 3: Run and look.** Brag mode on with a URL, then off, on a post with a box.
- [ ] **Step 4: Commit** — "Show the brag link in boxes".

---

---

# Dropped 2026-11-14 by the user's decision B5

### Task DT10: The AI text generator (dormant; only if the user chooses N2 option b)

**Feature:** F30 (R44-R49; N2 b; AC15)

**Why:** Lasso's editors could generate, edit and insert AI text; Affibloom can do the same only through an AI
provider and key the user chooses.

**Depends on:** the user's N2 answer and provider choice; DT9; Phase 4's display modal (F4) as its screen, or the
generator ships as the REST route plus F4's tab later.

**User action:** in the display modal's **AI Text** tab the editor types a prompt (1,000 characters), **Generate**
shows the text in an editor (bold, italic, underline, strike, link, bullet list, colours, clear), **Refresh** keeps
edits or asks again (R47), **Add** stores the edited text and inserts the display. Without a key the tab says "Add an
AI provider key in Settings to generate text." and generates nothing.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `POST /affibloom/v1/ai-text` in `Interface\Rest\AiTextController` (`INSERT_BOXES`) | the AI Text tab (F4) |
| `AiTextService::generate(), save()`; `Application\Display\AiTextProvider` and one adapter; the key field in Settings | the route |

**Files:** the above; tests F30 **AC15** with a stubbed provider; route refusals (422, 409, 502).

- [ ] **Step 1: Failing tests.**
- [ ] **Step 2: Route, service, provider.**
- [ ] **Step 3: The key field.**
- [ ] **Step 4: Run and look.**
- [ ] **Step 5: Commit** — "Generate AI text through the site's own provider key".
