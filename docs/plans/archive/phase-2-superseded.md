# Phase 2 — superseded tasks (history only)

These are the superseded task sections of the original Phase 2 file, moved here word for word on 2026-11-14 when the
planning pass rewrote Phase 2 for the work still to do. They are history only: do not work from them. What replaced
them is in `phase-2-settings-and-product-library.md`: Task 10 (Settings → Display and Amazon) by Tasks 10A-10F; Task 11
("Add A New Link") by Tasks 11A-11E; Task 12 (Amazon links) by Tasks 12A-12E; Task 13 (Dashboard list) by Tasks
13A-13E; Task 14 (product page, Details tab) by Tasks 14A-14H; Task 15 (saving the Details tab) by Tasks 15A-15D; Task
16 (Dashboard quick edit) by Tasks 16A-16C; Task 17 (deleting a product) by Tasks 17A-17D. In the original file the
in-body superseded sections (Tasks 11, 12, 14, 15, 16, 17) come first, then the "Appendix: superseded task text"
(Tasks 10, 13, 14, 15, 16, 17), each exactly as it was.

---

### Task 11: "Add A New Link" — `POST /affibloom/v1/products`

> **Superseded 2026-11-13** by Tasks 11A-11E (see the task list above and `docs/features/F18/`). The text below is
> kept for reference only; do not build from it. Its user action, its `test_an_invalid_link_is_reported_not_created`
> and its toast contradict Lasso's behaviour (F18 requirements, sections 3, 5 and 10).

**Roadmap:** 2.2, 2.4 · **Features:** F1, F3 (AC1, AC2)

**Why:** in Lasso, every product starts by pasting a link into "Add A New Link". This is the first point at which a product exists, so the product, its storage and the post type are all created here.

**User action:** an editor clicks "Add New Link" on the Dashboard, pastes `https://mizuno.pxf.io/c/123/456/789?u=https%3A%2F%2Fmizunousa.com%2Fwave`, and presses Enter. The modal closes, the Dashboard reloads, and a toast says "1 product added." A link without `https://`, or one with spaces in it, is refused inside the modal with a message.

**Request path:**
```
Dashboard → [Add New Link] → #affibloom-add-modal (admin.js)
  → POST /wp-json/affibloom/v1/products   { urls: [ "https://…" ] }   (wp.apiFetch sends the REST nonce)
  → ProductsController::create()      permission: Capabilities::PRODUCTS; args: 1–50 strings
  → ProductService::create_from_urls()
       for each line:
         AffiliateUrl::from_string()          → InvalidField( url, invalid_url ) → "invalid"
         Product::draft( site name, url, Other, Settings defaults )
         ProductRepository::find_by_url_key() → existing → "duplicates" (unless allow_duplicates)
         ProductRepository::save()            → wp_insert_post( affibloom_product ) + _affibloom_* meta
  ← 201 { data: { created: [ {id, name, url} ], duplicates: [ … ], invalid: [ {input, message} ] } }
  → admin.js: no problems → reload with ?affibloom_notice=created&count=1; otherwise show them in the modal
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\InvalidField` | `AffiliateUrl`, and later every validator |
| `Domain\Product\{Product, ProductType, AffiliateUrl, ProductRepository}` | `ProductService`, `PostTypeProductRepository` |
| `Product::draft()`, `with()`, `url_key()` | `ProductService`, the repository |
| `Infrastructure\WordPress\ProductPostType` | `init` |
| `PostTypeProductRepository::save()`, `get()`, `find_by_url_key()` | `ProductService` |
| `Application\Products\ProductService::create_from_urls()` | `ProductsController::create()` |
| `Interface\Rest\{ProductsController, ProductResource}`, `Interface\Messages` | `rest_api_init`, the controller |
| `Interface\Admin\Notice` (taken out of `SettingsScreen`: second use) | `SettingsScreen`, `DashboardScreen` |
| `templates/admin/partials/add-modal.php`, button in `dashboard.php` | `DashboardScreen` |
| `assets/admin/admin.js`: modal, add link | `AdminAssets` |

**Files:**
- Create:
  - `src/Domain/InvalidField.php`
  - `src/Domain/Product/{Product,ProductType,AffiliateUrl,ProductRepository}.php`
  - `src/Infrastructure/WordPress/{ProductPostType,PostTypeProductRepository}.php`
  - `src/Application/Products/ProductService.php`
  - `src/Interface/Rest/{ProductsController,ProductResource}.php`
  - `src/Interface/{Messages,Admin/Notice}.php`
  - `templates/admin/partials/add-modal.php`
  - `assets/admin/admin.js`
  - `tests/Unit/Domain/AffiliateUrlTest.php`
  - `tests/Integration/ProductsCreateTest.php`
- Modify:
  - `src/Plugin.php`
  - `src/Interface/Admin/{AdminAssets,AdminMenu,DashboardScreen,SettingsScreen,SettingsController}.php`
  - `templates/admin/dashboard.php`, `assets/admin/admin.css`

- [ ] **Step 1: Failing tests (outside, then one level in)**

```bash
npm run test:e2e -- acceptance/F3
```
Expected: fails, because there is no "Add New Link" button.

`tests/Integration/ProductsCreateTest.php`:

```php
<?php
namespace Affibloom\Tests\Integration;

use WP_REST_Request;
use WP_UnitTestCase;

final class ProductsCreateTest extends WP_UnitTestCase {
	private function post( array $urls, string $role = 'editor' ) {
		wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );
		$request = new WP_REST_Request( 'POST', '/affibloom/v1/products' );
		$request->set_body_params( array( 'urls' => $urls ) );
		return rest_get_server()->dispatch( $request );
	}

	public function test_an_editor_adds_an_other_merchant_link_kept_exactly(): void {
		$url      = 'https://mizuno.pxf.io/c/123/456/789?u=https%3A%2F%2Fmizunousa.com%2Fwave';
		$response = $this->post( array( $url ) );

		self::assertSame( 201, $response->get_status() );
		$created = $response->get_data()['data']['created'][0];
		self::assertSame( $url, $created['url'] );
		self::assertSame( 'affibloom_product', get_post_type( $created['id'] ) );
		self::assertSame( '1', get_post_meta( $created['id'], '_affibloom_nofollow', true ) ); // site default
	}

	public function test_an_invalid_link_is_reported_not_created(): void {
		$data = $this->post( array( 'mizunousa.com/wave' ) )->get_data()['data'];

		self::assertSame( array(), $data['created'] );
		self::assertSame( 'mizunousa.com/wave', $data['invalid'][0]['input'] );
	}

	public function test_the_same_link_twice_is_a_duplicate(): void {
		$this->post( array( 'https://example.com/a' ) );
		$data = $this->post( array( 'https://example.com/a/' ) )->get_data()['data'];

		self::assertCount( 1, $data['duplicates'] );
	}

	public function test_an_author_may_not_add_products(): void {
		self::assertSame( 403, $this->post( array( 'https://example.com/b' ), 'author' )->get_status() );
	}
}
```

```bash
npm run test:integration
```
Expected: 404 `rest_no_route`.

- [ ] **Step 2: Route**

`src/Interface/Rest/ProductsController.php`:

```php
<?php
/**
 * REST routes for products: affibloom/v1/products.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Application\Products\ProductService;
use Affibloom\Interface\Admin\Capabilities;
use Affibloom\Interface\Messages;
use Affibloom\Module;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST routes for products: affibloom/v1/products.
 */
final class ProductsController implements Module {

	public const NAMESPACE = 'affibloom/v1';

	/**
	 * Builds the controller.
	 *
	 * @param ProductService  $products Product use cases.
	 * @param ProductResource $product_resource Product → array.
	 */
	public function __construct( private ProductService $products, private ProductResource $product_resource ) {}

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	/**
	 * Registers the routes.
	 */
	public function routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/products',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => array(
						'urls' => array(
							'type'     => 'array',
							'required' => true,
							'minItems' => 1,
							'maxItems' => 50,
							'items'    => array(
								'type'      => 'string',
								'maxLength' => 2000,
							),
						),
					),
				),
			)
		);
	}

	/**
	 * Only people who manage products may change them.
	 */
	public function can_manage(): bool {
		return current_user_can( Capabilities::PRODUCTS );
	}

	/**
	 * POST /products: one product per link; duplicates and invalid links are reported, not created.
	 *
	 * @param WP_REST_Request $request Request with "urls".
	 */
	public function create( WP_REST_Request $request ): WP_REST_Response {
		$result = $this->products->create_from_urls( array_map( 'strval', (array) $request['urls'] ) );

		$data = array(
			'created'    => array_map( array( $this->product_resource, 'summary' ), $result['created'] ),
			'duplicates' => array_map(
				fn( array $duplicate ): array => array(
					'input'    => $duplicate['input'],
					'existing' => $this->product_resource->summary( $duplicate['existing'] ),
				),
				$result['duplicates']
			),
			'invalid'    => array_map(
				static fn( array $invalid ): array => array(
					'input'   => $invalid['input'],
					'message' => Messages::for_reason( $invalid['reason'] ),
				),
				$result['invalid']
			),
		);

		return new WP_REST_Response( array( 'data' => $data ), $result['created'] ? 201 : 200 );
	}
}
```

- [ ] **Step 3: Service (what the controller calls)**

`src/Application/Products/ProductService.php`:

```php
<?php
/**
 * Product use cases.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Settings\SettingsRepository;

/**
 * Product use cases.
 */
final class ProductService {

	/**
	 * Builds the service.
	 *
	 * @param ProductRepository  $products Product storage.
	 * @param SettingsRepository $settings Site settings (link defaults).
	 */
	public function __construct(
		private ProductRepository $products,
		private SettingsRepository $settings,
	) {}

	/**
	 * Creates one product per link, as "Add A New Link" does.
	 *
	 * @param list<string> $inputs One pasted line each.
	 * @return array{
	 *     created: list<Product>,
	 *     duplicates: list<array{input: string, existing: Product}>,
	 *     invalid: list<array{input: string, reason: string}>
	 * }
	 */
	public function create_from_urls( array $inputs ): array {
		$settings = $this->settings->get();
		$result   = array(
			'created'    => array(),
			'duplicates' => array(),
			'invalid'    => array(),
		);

		foreach ( $inputs as $input ) {
			$input = trim( $input );
			if ( '' === $input ) {
				continue;
			}

			try {
				$url = AffiliateUrl::from_string( $input );
			} catch ( InvalidField $error ) {
				$result['invalid'][] = array(
					'input'  => $input,
					'reason' => $error->reason,
				);
				continue;
			}

			$draft = Product::draft( $url->site_name(), $url, ProductType::Other, $settings );

			if ( ! $settings->bool( 'allow_duplicates' ) ) {
				$existing = $this->products->find_by_url_key( $draft->url_key() );
				if ( null !== $existing ) {
					$result['duplicates'][] = array(
						'input'    => $input,
						'existing' => $existing,
					);
					continue;
				}
			}

			$result['created'][] = $this->products->save( $draft );
		}

		return $result;
	}
}
```

The name of a new product is the merchant's site name ("Pxf", "Mizunousa"), as Lasso does before its cloud fills in a title. The editor changes it on the product page (Task 14).

- [ ] **Step 4: Domain (what the service asks for)**

`src/Domain/InvalidField.php`:

```php
<?php
/**
 * A field value the rules do not accept. "reason" is a code; the Interface layer words it.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain;

use DomainException;

/**
 * A field value the rules do not accept. "reason" is a code; the Interface layer words it.
 */
final class InvalidField extends DomainException {

	/**
	 * Builds the error.
	 *
	 * @param string $field  Field name, e.g. "url".
	 * @param string $reason Reason code, e.g. "invalid_url".
	 */
	public function __construct( public readonly string $field, public readonly string $reason ) {
		parent::__construct( $field . ': ' . $reason );
	}
}
```

`src/Domain/Product/ProductType.php`:

```php
<?php
/**
 * Amazon product or any other merchant.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

/**
 * Amazon product or any other merchant.
 */
enum ProductType: string {
	case Amazon = 'amazon';
	case Other  = 'other';
}
```

`src/Domain/Product/AffiliateUrl.php`:

```php
<?php
/**
 * An absolute http(s) link, kept exactly as given (tracking parameters included).
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

use Affibloom\Domain\InvalidField;

/**
 * An absolute http(s) link, kept exactly as given (tracking parameters included).
 */
final class AffiliateUrl {

	/**
	 * Use from_string().
	 *
	 * @param string $value The link.
	 */
	private function __construct( private readonly string $value ) {}

	/**
	 * Accepts an absolute http or https link of at most 2,000 characters, with no spaces.
	 *
	 * @param string $raw   Input.
	 * @param string $field Field name for the error.
	 * @throws InvalidField When the link is not acceptable.
	 */
	public static function from_string( string $raw, string $field = 'url' ): self {
		$url   = trim( $raw );
		$parts = parse_url( $url ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Domain code does not call WordPress.

		$valid = '' !== $url
			&& strlen( $url ) <= 2000
			&& ! preg_match( '/\s/', $url )
			&& is_array( $parts )
			&& ! empty( $parts['host'] )
			&& in_array( strtolower( $parts['scheme'] ?? '' ), array( 'http', 'https' ), true );

		if ( ! $valid ) {
			throw new InvalidField( $field, 'invalid_url' );
		}
		return new self( $url );
	}

	/**
	 * The link.
	 */
	public function value(): string {
		return $this->value;
	}

	/**
	 * Lower-case host without a leading "www.".
	 */
	public function host(): string {
		$host = strtolower( (string) parse_url( $this->value, PHP_URL_HOST ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
		return str_starts_with( $host, 'www.' ) ? substr( $host, 4 ) : $host;
	}

	/**
	 * Key for spotting duplicates: lower case, no trailing slash.
	 */
	public function key(): string {
		return rtrim( strtolower( $this->value ), '/' );
	}

	/**
	 * "shop.mizunousa.com" → "Mizunousa"; "example.co.uk" → "Example".
	 */
	public function site_name(): string {
		$labels = explode( '.', $this->host() );
		$count  = count( $labels );
		if ( $count < 2 ) {
			return ucfirst( $labels[0] );
		}

		$name = $labels[ $count - 2 ];
		if ( $count >= 3 && 2 === strlen( $labels[ $count - 1 ] ) && in_array( $name, array( 'co', 'com', 'net', 'org', 'ac' ), true ) ) {
			$name = $labels[ $count - 3 ];
		}
		return ucfirst( $name );
	}
}
```

`src/Domain/Product/Product.php`:

```php
<?php
/**
 * An affiliate product: what a box shows and where its buttons go.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

use Affibloom\Domain\Settings\Settings;

/**
 * An affiliate product: what a box shows and where its buttons go.
 */
final class Product {

	/**
	 * Builds a product. Use draft() for a new one.
	 *
	 * @param int|null     $id              Post ID; null before it is saved.
	 * @param string       $name            Name shown as the box title.
	 * @param AffiliateUrl $url             Primary destination.
	 * @param ProductType  $type            Amazon or other merchant.
	 * @param bool         $new_tab         Primary links open in a new tab.
	 * @param bool         $nofollow        Primary links carry rel="nofollow".
	 * @param bool         $sponsored       Links carry rel="sponsored".
	 * @param bool         $show_disclosure The box shows the disclosure.
	 */
	public function __construct(
		public readonly ?int $id,
		public readonly string $name,
		public readonly AffiliateUrl $url,
		public readonly ProductType $type,
		public readonly bool $new_tab,
		public readonly bool $nofollow,
		public readonly bool $sponsored,
		public readonly bool $show_disclosure,
	) {}

	/**
	 * A new, unsaved product with the site's link defaults, as Lasso copies them at creation.
	 *
	 * @param string       $name     Name.
	 * @param AffiliateUrl $url      Primary destination.
	 * @param ProductType  $type     Amazon or other.
	 * @param Settings     $defaults Site settings.
	 */
	public static function draft( string $name, AffiliateUrl $url, ProductType $type, Settings $defaults ): self {
		return new self(
			id: null,
			name: $name,
			url: $url,
			type: $type,
			new_tab: $defaults->bool( 'new_tab' ),
			nofollow: $defaults->bool( 'nofollow' ),
			sponsored: $defaults->bool( 'sponsored' ),
			show_disclosure: $defaults->bool( 'show_disclosure' ),
		);
	}

	/**
	 * A copy with some properties changed.
	 *
	 * @param array<string, mixed> $changes Property name => new value.
	 */
	public function with( array $changes ): self {
		return new self( ...array_merge( get_object_vars( $this ), $changes ) );
	}

	/**
	 * Key used to spot the same destination twice.
	 */
	public function url_key(): string {
		return $this->url->key();
	}
}
```

`src/Domain/Product/ProductRepository.php`:

```php
<?php
/**
 * Where products are kept.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

/**
 * Where products are kept.
 */
interface ProductRepository {

	/**
	 * Inserts or updates; returns the product with its ID.
	 *
	 * @param Product $product Product to store.
	 */
	public function save( Product $product ): Product;

	/**
	 * A published product, or null.
	 *
	 * @param int $id Post ID.
	 */
	public function get( int $id ): ?Product;

	/**
	 * The published product with this url_key(), or null.
	 *
	 * @param string $key Key from Product::url_key().
	 */
	public function find_by_url_key( string $key ): ?Product;
}
```

- [ ] **Step 5: Infrastructure (where the repository writes)**

`src/Infrastructure/WordPress/ProductPostType.php`:

```php
<?php
/**
 * The private post type products are stored in.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use Affibloom\Module;

/**
 * The private post type products are stored in.
 */
final class ProductPostType implements Module {

	public const NAME = 'affibloom_product';

	/**
	 * Hooks into WordPress.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_type' ) );
	}

	/**
	 * Registers the post type: no public URL, no core screens (the plugin has its own).
	 */
	public function register_type(): void {
		register_post_type(
			self::NAME,
			array(
				'label'            => __( 'Products', 'affibloom' ),
				'public'           => false,
				'show_ui'          => false,
				'show_in_rest'     => false,
				'rewrite'          => false,
				'query_var'        => false,
				'supports'         => array( 'title' ),
				'can_export'       => true,
				'delete_with_user' => false,
				'capability_type'  => 'post',
				'map_meta_cap'     => true,
			)
		);
	}
}
```

`src/Infrastructure/WordPress/PostTypeProductRepository.php`:

```php
<?php
/**
 * Products as affibloom_product posts with _affibloom_* meta.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\WordPress;

use RuntimeException;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Product;
use Affibloom\Domain\Product\ProductRepository;
use Affibloom\Domain\Product\ProductType;
use WP_Post;

/**
 * Products as affibloom_product posts with _affibloom_* meta.
 */
final class PostTypeProductRepository implements ProductRepository {

	/**
	 * Inserts or updates the post and its meta.
	 *
	 * @param Product $product Product to store.
	 * @throws RuntimeException When WordPress refuses the post.
	 */
	public function save( Product $product ): Product {
		$post = array(
			'post_type'   => ProductPostType::NAME,
			'post_status' => 'publish',
			'post_title'  => $product->name,
		);
		if ( null !== $product->id ) {
			$post['ID'] = $product->id;
		}

		$id = null === $product->id ? wp_insert_post( wp_slash( $post ), true ) : wp_update_post( wp_slash( $post ), true );
		if ( is_wp_error( $id ) ) {
			throw new RuntimeException( esc_html( $id->get_error_message() ) );
		}

		foreach ( $this->to_meta( $product ) as $key => $value ) {
			update_post_meta( (int) $id, $key, wp_slash( $value ) );
		}

		return $product->with( array( 'id' => (int) $id ) );
	}

	/**
	 * A published product, or null.
	 *
	 * @param int $id Post ID.
	 */
	public function get( int $id ): ?Product {
		$post = get_post( $id );
		if ( ! $post instanceof WP_Post || ProductPostType::NAME !== $post->post_type || 'publish' !== $post->post_status ) {
			return null;
		}
		return $this->hydrate( $post );
	}

	/**
	 * The published product with this key, or null.
	 *
	 * @param string $key Key from Product::url_key().
	 */
	public function find_by_url_key( string $key ): ?Product {
		$ids = get_posts(
			array(
				'post_type'      => ProductPostType::NAME,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
				'meta_key'       => '_affibloom_url_key', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- a few hundred rows.
				'meta_value'     => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		return $ids ? $this->get( (int) $ids[0] ) : null;
	}

	/**
	 * Meta to store for a product.
	 *
	 * @param Product $product Product.
	 * @return array<string, string>
	 */
	private function to_meta( Product $product ): array {
		return array(
			'_affibloom_url'             => $product->url->value(),
			'_affibloom_url_key'         => $product->url_key(),
			'_affibloom_type'            => $product->type->value,
			'_affibloom_new_tab'         => $product->new_tab ? '1' : '0',
			'_affibloom_nofollow'        => $product->nofollow ? '1' : '0',
			'_affibloom_sponsored'       => $product->sponsored ? '1' : '0',
			'_affibloom_show_disclosure' => $product->show_disclosure ? '1' : '0',
		);
	}

	/**
	 * Builds a product from its post. All meta comes from one cached read.
	 *
	 * @param WP_Post $post Product post.
	 */
	private function hydrate( WP_Post $post ): Product {
		$meta  = get_post_meta( $post->ID );
		$value = static fn( string $key, string $fallback = '' ): string => isset( $meta[ $key ][0] ) ? (string) $meta[ $key ][0] : $fallback;

		return new Product(
			id: $post->ID,
			name: $post->post_title,
			url: AffiliateUrl::from_string( $value( '_affibloom_url' ) ),
			type: ProductType::tryFrom( $value( '_affibloom_type' ) ) ?? ProductType::Other,
			new_tab: '1' === $value( '_affibloom_new_tab', '1' ),
			nofollow: '1' === $value( '_affibloom_nofollow', '1' ),
			sponsored: '1' === $value( '_affibloom_sponsored', '0' ),
			show_disclosure: '1' === $value( '_affibloom_show_disclosure', '1' ),
		);
	}
}
```

- [ ] **Step 6: Unit test of the domain**

`tests/Unit/Domain/AffiliateUrlTest.php`:

```php
<?php
namespace Affibloom\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\AffiliateUrl;

final class AffiliateUrlTest extends TestCase {
	public function test_tracking_parameters_are_kept_exactly(): void {
		$url = 'https://mizuno.pxf.io/c/123/456/789?u=https%3A%2F%2Fmizunousa.com%2Fwave&subId1=vv';
		self::assertSame( $url, AffiliateUrl::from_string( "  $url " )->value() );
	}

	/** @dataProvider invalid */
	public function test_invalid_links_are_refused( string $input ): void {
		$this->expectException( InvalidField::class );
		AffiliateUrl::from_string( $input );
	}

	public static function invalid(): array {
		return array( array( '' ), array( 'mizunousa.com/wave' ), array( 'ftp://x.com/a' ), array( 'https://x.com/a b' ), array( 'javascript:alert(1)' ) );
	}

	public function test_site_name(): void {
		self::assertSame( 'Mizunousa', AffiliateUrl::from_string( 'https://shop.mizunousa.com/x' )->site_name() );
		self::assertSame( 'Example', AffiliateUrl::from_string( 'https://www.example.co.uk/x' )->site_name() );
	}

	public function test_duplicate_key_ignores_case_and_trailing_slash(): void {
		self::assertSame( AffiliateUrl::from_string( 'https://Example.com/A/' )->key(), AffiliateUrl::from_string( 'https://example.com/a' )->key() );
	}
}
```

- [ ] **Step 7: Messages, notices and the modal**

`src/Interface/Messages.php`:

```php
<?php
/**
 * Words for the reason codes the Domain and Application layers use.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface;

/**
 * Words for the reason codes the Domain and Application layers use.
 */
final class Messages {

	/**
	 * The message shown for a reason code.
	 *
	 * @param string $reason Code from InvalidField or ValidationFailed.
	 */
	public static function for_reason( string $reason ): string {
		return match ( $reason ) {
			'invalid_url' => __( 'This is not a valid link. Paste the full address, starting with https://.', 'affibloom' ),
			default       => __( 'This value is not valid.', 'affibloom' ),
		};
	}
}
```

`src/Interface/Rest/ProductResource.php`:

```php
<?php
/**
 * Products as REST arrays.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Rest;

use Affibloom\Domain\Product\Product;

/**
 * Products as REST arrays.
 */
final class ProductResource {

	/**
	 * The short form used in lists and after creating.
	 *
	 * @param Product $product A saved product.
	 * @return array{id: int, name: string, url: string}
	 */
	public function summary( Product $product ): array {
		return array(
			'id'   => (int) $product->id,
			'name' => $product->name,
			'url'  => $product->url->value(),
		);
	}
}
```

`src/Interface/Admin/Notice.php` takes the `match` out of `SettingsScreen::notice()`. This is its second use, so it moves into a class now:

```php
<?php
/**
 * The toast message for ?affibloom_notice=… after a redirect.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

/**
 * The toast message for ?affibloom_notice=… after a redirect.
 */
final class Notice {

	/**
	 * Message for the current request, or '' when there is none.
	 */
	public static function from_request(): string {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- display only.
		$code  = isset( $_GET['affibloom_notice'] ) ? sanitize_key( wp_unslash( $_GET['affibloom_notice'] ) ) : '';
		$count = isset( $_GET['count'] ) ? absint( $_GET['count'] ) : 0;
		// phpcs:enable

		return match ( $code ) {
			'saved'        => __( 'Settings saved.', 'affibloom' ),
			'partly-saved' => __( 'Some values were not valid and kept their previous setting. Everything else was saved.', 'affibloom' ),
			/* translators: %s: number of products. */
			'created'      => sprintf( _n( '%s product added.', '%s products added.', $count, 'affibloom' ), number_format_i18n( $count ) ),
			default        => '',
		};
	}
}
```

In `SettingsScreen`, delete `notice()` and pass `'notice' => Notice::from_request()`.

`DashboardScreen::render()`:

```php
		$this->template->render( 'admin/dashboard', array( 'notice' => Notice::from_request() ) );
```

`templates/admin/partials/add-modal.php`. Wording from Lasso's `modals/url-add.php`, without the Marketplace tab and the licence counter:

```php
<?php
/**
 * "Add A New Link" modal.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="affibloom-add-modal" class="affibloom-modal" role="dialog" aria-modal="true" aria-labelledby="affibloom-add-title" hidden>
	<div class="affibloom-modal__backdrop" data-affibloom-close></div>
	<div class="affibloom-modal__panel">
		<button type="button" class="affibloom-modal__close" data-affibloom-close aria-label="<?php esc_attr_e( 'Close', 'affibloom' ); ?>">&times;</button>
		<form class="affibloom-add-form" novalidate>
			<h2 id="affibloom-add-title" class="affibloom-modal__title"><?php esc_html_e( 'Add A New Link', 'affibloom' ); ?></h2>
			<p class="affibloom-modal__intro"><?php esc_html_e( 'Enter the destination URL your link will redirect to.', 'affibloom' ); ?></p>

			<label class="affibloom-label" for="affibloom-add-urls"><?php esc_html_e( 'URL', 'affibloom' ); ?></label>
			<textarea id="affibloom-add-urls" name="urls" rows="3" placeholder="https://www.example.com/affiliate-id" required></textarea>
			<p class="affibloom-hint"><?php esc_html_e( 'Enter each new link on a separate line (Shift + Enter)', 'affibloom' ); ?></p>

			<div class="affibloom-modal__messages" aria-live="polite"></div>

			<div class="affibloom-modal__actions">
				<button type="submit" id="affibloom-add-submit" class="affibloom-button affibloom-button--primary"><?php esc_html_e( 'Add Link', 'affibloom' ); ?></button>
			</div>
		</form>
	</div>
</div>
```

`templates/admin/dashboard.php`: replace the page head and add the modal and toast before the closing `</div>` of `.affibloom-admin`:

```php
		<div class="affibloom-page__head">
			<h1 class="affibloom-page__title"><?php esc_html_e( 'Dashboard', 'affibloom' ); ?></h1>
			<button type="button" id="affibloom-add-new" class="affibloom-button affibloom-button--primary" data-affibloom-open="affibloom-add-modal">
				<?php esc_html_e( 'Add New Link', 'affibloom' ); ?>
			</button>
		</div>
```

```php
	<?php $this->render( 'admin/partials/add-modal' ); ?>
	<?php $this->render( 'admin/partials/toast', array( 'message' => $view['notice'] ) ); ?>
```

`assets/admin/admin.js`:

```js
/* global wp */
/**
 * Affibloom admin: modals and "Add A New Link". Plain script, no build step.
 */
( function () {
	'use strict';

	const { __, _n, sprintf } = wp.i18n;
	const FOCUSABLE = 'a[href], button:not([disabled]), textarea, input:not([type=hidden]), select';

	// ── Modal: [data-affibloom-open="id"] opens, [data-affibloom-close] and Esc close. ──
	function openModal( modal ) {
		modal.affibloomOpener = document.activeElement;
		modal.hidden = false;
		const first = modal.querySelector( 'textarea, input:not([type=hidden]), select, .affibloom-modal__panel button:not([data-affibloom-close])' );
		( first || modal.querySelector( FOCUSABLE ) )?.focus();
	}

	function closeModal( modal ) {
		modal.hidden = true;
		modal.affibloomOpener?.focus();
	}

	document.addEventListener( 'click', ( event ) => {
		const opener = event.target.closest( '[data-affibloom-open]' );
		if ( opener ) {
			const modal = document.getElementById( opener.dataset.affibloomOpen );
			if ( modal ) {
				event.preventDefault();
				openModal( modal );
			}
			return;
		}
		const closer = event.target.closest( '[data-affibloom-close]' );
		if ( closer ) {
			closeModal( closer.closest( '.affibloom-modal' ) );
		}
	} );

	document.addEventListener( 'keydown', ( event ) => {
		const modal = document.querySelector( '.affibloom-modal:not([hidden])' );
		if ( ! modal ) {
			return;
		}
		if ( event.key === 'Escape' ) {
			closeModal( modal );
			return;
		}
		if ( event.key !== 'Tab' ) {
			return;
		}
		// Keep focus inside the open modal.
		const items = [ ...modal.querySelectorAll( FOCUSABLE ) ].filter( ( el ) => el.offsetParent !== null );
		const first = items[ 0 ];
		const last = items[ items.length - 1 ];
		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	} );

	function message( text, kind ) {
		const p = document.createElement( 'p' );
		p.className = `affibloom-message affibloom-message--${ kind }`;
		p.textContent = text;
		return p;
	}

	// ── "Add A New Link": one link per line; Enter submits, Shift+Enter adds a line. ──
	function initAddLink() {
		const modal = document.getElementById( 'affibloom-add-modal' );
		if ( ! modal ) {
			return;
		}
		const form = modal.querySelector( 'form' );
		const field = form.querySelector( '#affibloom-add-urls' );
		const submit = form.querySelector( '#affibloom-add-submit' );
		const messages = form.querySelector( '.affibloom-modal__messages' );

		field.addEventListener( 'keydown', ( event ) => {
			if ( event.key === 'Enter' && ! event.shiftKey ) {
				event.preventDefault();
				form.requestSubmit();
			}
		} );

		form.addEventListener( 'submit', async ( event ) => {
			event.preventDefault();
			const urls = field.value.split( /\r?\n/ ).map( ( line ) => line.trim() ).filter( Boolean );
			if ( ! urls.length ) {
				field.focus();
				return;
			}
			submit.disabled = true;
			messages.replaceChildren();
			try {
				const { data } = await wp.apiFetch( { path: '/affibloom/v1/products', method: 'POST', data: { urls } } );
				showResult( data, messages );
			} catch ( error ) {
				messages.append( message( error.message || __( 'Something went wrong. Please try again.', 'affibloom' ), 'error' ) );
			} finally {
				submit.disabled = false;
			}
		} );
	}

	function showResult( data, messages ) {
		if ( ! data.duplicates.length && ! data.invalid.length ) {
			goToCreated( data.created );
			return;
		}
		data.duplicates.forEach( ( duplicate ) => messages.append( duplicateNotice( duplicate ) ) );
		data.invalid.forEach( ( invalid ) => messages.append( message( `${ invalid.input }: ${ invalid.message }`, 'error' ) ) );
		if ( data.created.length ) {
			messages.append(
				message(
					/* translators: %s: number of links. */
					sprintf( _n( '%s link was added.', '%s links were added.', data.created.length, 'affibloom' ), data.created.length ),
					'success'
				)
			);
		}
	}

	/** After adding: back to the Dashboard with a toast. */
	function goToCreated( created ) {
		window.location.assign( wp.url.addQueryArgs( window.location.href, { affibloom_notice: 'created', count: created.length } ) );
	}

	function duplicateNotice( duplicate ) {
		const box = document.createElement( 'div' );
		box.className = 'affibloom-duplicate';
		const title = document.createElement( 'strong' );
		title.textContent = __( 'This Product Already Exists', 'affibloom' );
		/* translators: %s: product name. */
		box.append( title, message( sprintf( __( 'You already have this link as "%s".', 'affibloom' ), duplicate.existing.name ), 'info' ) );
		return box;
	}

	document.addEventListener( 'DOMContentLoaded', () => {
		initAddLink();
	} );
}() );
```

`AdminAssets::enqueue()`, after the style:

```php
		wp_enqueue_script( 'affibloom-admin', $this->plugin_url . 'assets/admin/admin.js', array( 'wp-api-fetch', 'wp-i18n', 'wp-url' ), $this->version, true );
		wp_set_script_translations( 'affibloom-admin', 'affibloom' );
```

`wp-api-fetch` in wp-admin already carries the REST root and nonce, so the script needs no settings of its own.

`assets/admin/admin.css`, append:

```css
/* Modal */
.affibloom-modal { position: fixed; inset: 0; z-index: 100001; display: grid; place-items: center; }
.affibloom-modal[hidden] { display: none; }
.affibloom-modal__backdrop { position: absolute; inset: 0; background: rgba(29, 23, 51, 0.55); }
.affibloom-modal__panel {
	position: relative;
	width: min(560px, calc(100vw - 32px));
	max-height: calc(100vh - 64px);
	overflow: auto;
	padding: 32px;
	border-radius: 12px;
	background: var(--affibloom-white);
	box-shadow: 0 20px 50px rgba(29, 23, 51, 0.3);
}
.affibloom-modal__close { position: absolute; top: 12px; right: 16px; border: 0; background: none; font-size: 26px; line-height: 1; color: var(--affibloom-muted); cursor: pointer; }
.affibloom-modal__title { margin: 0 0 6px; font-size: 22px; color: var(--affibloom-ink); }
.affibloom-modal__intro, .affibloom-hint { margin: 0 0 12px; color: var(--affibloom-muted); }
.affibloom-hint { margin-top: 6px; font-size: 12px; }
.affibloom-modal__actions { margin-top: 20px; text-align: right; }
.affibloom-message { margin: 8px 0 0; }
.affibloom-message--error { color: #b32d2e; }
.affibloom-message--success { color: var(--affibloom-green-dark); }
.affibloom-duplicate { margin-top: 12px; padding: 12px 14px; border-left: 4px solid var(--affibloom-purple); background: var(--affibloom-purple-tint); border-radius: 6px; }
```

- [ ] **Step 8: Wire it up**

`src/Plugin.php`. A shared product repository, and the new modules:

```php
	/**
	 * Product storage, shared by every reader in a request.
	 *
	 * @var PostTypeProductRepository|null
	 */
	private ?PostTypeProductRepository $product_repository = null;

	/**
	 * The product repository.
	 */
	private function product_repository(): PostTypeProductRepository {
		$this->product_repository ??= new PostTypeProductRepository();
		return $this->product_repository;
	}
```

In `modules()`, add:

```php
			new ProductPostType(),
			new ProductsController(
				new ProductService( $this->product_repository(), $this->settings_repository() ),
				new ProductResource()
			),
```

- [ ] **Step 9: Run and look**

```bash
composer lint && composer analyse && composer test:unit && npm run test:integration
npm run test:e2e -- acceptance/F3
```
Expected: all pass. By hand:
1. Add an Impact link. Expected: a toast saying "1 product added."
2. Add the same link again. Expected: "This Product Already Exists" inside the modal.
3. Add `mizunousa.com`. Expected: the invalid-link message.
4. Press Esc. Expected: the modal closes, and focus returns to "Add New Link".

- [ ] **Step 10: Commit**

```bash
git add -A
git commit -m "Add products through \"Add A New Link\"

One product per pasted line, with the site's link defaults. Invalid
links and duplicates are reported inside the modal, as in Lasso."
```

---

### Task 12: Adding an Amazon link or ASIN — SUPERSEDED 2026-11-13

> Replaced by Tasks 12A-12E above (deviation 42), all done. Its steps stay unticked: they were not carried out as
> written. Kept for the record; its `/dp/<ASIN>` form, ASIN-only key, and the `no_tracking_id` and `short_link_unresolved` refusals are not Lasso's (F2 requirements, section 9).


**Roadmap:** 2.3 · **Feature:** F2 (AC1–AC4)

**Why:** 242 of the 263 products are Amazon products. Every Amazon link must end up as one clean product link that carries the site's tag and no one else's, whatever form the editor pasted it in.

**User action:** in the same modal, the editor pastes any Amazon product link (`/dp/`, `/gp/product/`, `/gp/aw/d/`, a link with `ref=` and other parameters, another affiliate's `tag`), a bare ASIN such as `B0C1234567`, or an `amzn.to` short link. The product is created with `https://www.amazon.com/dp/B0C1234567?tag=vbvault-20` and the name "Amazon", which the editor then changes, as in Lasso. Without a tracking ID in Settings, the modal asks for one.

**Request path:**
```
POST /affibloom/v1/products  (unchanged)
  → ProductService::create_from_urls()
       AmazonLinkService::normalize( input )
         amzn.to / a.co   → ShortLinkResolver::resolve() (HEAD requests, up to 5 redirects)
         AmazonUrl::parse() → ASIN + marketplace host → https://<host>/dp/<ASIN>?tag=<tracking_id>
         an Amazon page with no product → kept, with the tag replaced
         anything else    → AffiliateUrl as before
       Product::draft( "Amazon", url, Amazon, … ) with asin; url_key "amazon:<ASIN>"
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Amazon\AmazonUrl` (`parse`, `url`, `retag`, `is_amazon_host`, `is_short_link`) | `AmazonLinkService`, `WpShortLinkResolver` |
| `Application\Products\{AmazonLinkService, NormalizedLink, ShortLinkResolver}` | `ProductService` |
| `Infrastructure\Http\WpShortLinkResolver` | `AmazonLinkService`, through the interface |
| `Product::$asin`, Amazon `url_key()`, meta `_affibloom_asin` | the repository, the duplicate check |
| reasons `no_tracking_id`, `short_link_unresolved` in `Messages` | the modal |

**Files:**
- Create:
  - `src/Domain/Amazon/AmazonUrl.php`
  - `src/Application/Products/{AmazonLinkService,NormalizedLink,ShortLinkResolver}.php`
  - `src/Infrastructure/Http/WpShortLinkResolver.php`
  - `tests/Unit/Domain/AmazonUrlTest.php`, `tests/Unit/Application/AmazonLinkServiceTest.php`
- Modify:
  - `src/Domain/Product/Product.php`
  - `src/Application/Products/ProductService.php`
  - `src/Infrastructure/WordPress/PostTypeProductRepository.php`
  - `src/Interface/Messages.php`, `src/Plugin.php`

- [ ] **Step 1: Failing tests**

```bash
npm run test:e2e -- acceptance/F2
```
Expected: AC1–AC3 fail on the URL, because it is kept as pasted.

`tests/Unit/Domain/AmazonUrlTest.php`. Add every distinct Amazon URL form found in `tests/fixtures/inventory/products.json` (`meta.lasso_custom_redirect`, `amazon.base_url`) to the provider:

```php
<?php
namespace Affibloom\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Affibloom\Domain\Amazon\AmazonUrl;

final class AmazonUrlTest extends TestCase {
	/** @dataProvider product_links */
	public function test_finds_the_asin_and_marketplace( string $input, string $asin, string $host ): void {
		$url = AmazonUrl::parse( $input );

		self::assertNotNull( $url );
		self::assertSame( $asin, $url->asin );
		self::assertSame( $host, $url->host );
	}

	public static function product_links(): array {
		return array(
			'dp with slug and ref' => array( 'https://www.amazon.com/Mizuno-Wave/dp/B0C1234567/ref=sr_1_3?keywords=x', 'B0C1234567', 'www.amazon.com' ),
			'gp/product'           => array( 'https://amazon.com/gp/product/B0C1234567?th=1', 'B0C1234567', 'www.amazon.com' ),
			'mobile gp/aw/d'       => array( 'https://www.amazon.com/gp/aw/d/B0C1234567', 'B0C1234567', 'www.amazon.com' ),
			'other tag'            => array( 'https://www.amazon.com/dp/B0C1234567?tag=someone-20', 'B0C1234567', 'www.amazon.com' ),
			'uk marketplace'       => array( 'https://www.amazon.co.uk/dp/B0C1234567', 'B0C1234567', 'www.amazon.co.uk' ),
			'lower-case asin'      => array( 'https://www.amazon.com/dp/b0c1234567', 'B0C1234567', 'www.amazon.com' ),
			'bare asin'            => array( 'B0C1234567', 'B0C1234567', 'www.amazon.com' ),
			'isbn-10'              => array( '014044913X', '014044913X', 'www.amazon.com' ),
		);
	}

	/** @dataProvider not_products */
	public function test_returns_null_when_there_is_no_product( string $input ): void {
		self::assertNull( AmazonUrl::parse( $input ) );
	}

	public static function not_products(): array {
		return array( array( 'https://www.amazon.com/s?k=volleyball' ), array( 'https://www.amazonx.com/dp/B0C1234567' ), array( 'BESTSHOE10' ) );
	}

	public function test_builds_one_clean_tagged_link(): void {
		self::assertSame( 'https://www.amazon.com/dp/B0C1234567?tag=vbvault-20', AmazonUrl::parse( 'B0C1234567' )->url( 'vbvault-20' ) );
	}

	public function test_retag_replaces_any_other_tag(): void {
		self::assertSame(
			'https://www.amazon.com/shop/x?ref=a&tag=vbvault-20',
			AmazonUrl::retag( 'https://www.amazon.com/shop/x?ref=a&tag=someone-20', 'vbvault-20' )
		);
	}
}
```

```bash
composer test:unit
```
Expected: the class is not found.

- [ ] **Step 2: Service: normalise before anything else**

`ProductService`: add `private AmazonLinkService $links` as the third constructor argument. In `create_from_urls()`, replace the `try { $url = AffiliateUrl::from_string … }` block and the `$draft` line with:

```php
			try {
				$link = $this->links->normalize( $input );
			} catch ( InvalidField $error ) {
				$result['invalid'][] = array(
					'input'  => $input,
					'reason' => $error->reason,
				);
				continue;
			}

			// Lasso names a new Amazon product "Amazon" until it has data; the editor renames it.
			$name  = ProductType::Amazon === $link->type ? 'Amazon' : $link->url->site_name();
			$draft = Product::draft( $name, $link->url, $link->type, $settings )->with( array( 'asin' => $link->asin ) );
```

and remove the now unused `use Affibloom\Domain\Product\AffiliateUrl;`.

`src/Application/Products/NormalizedLink.php`:

```php
<?php
/**
 * A pasted link after Amazon handling: the link to store, its type and ASIN.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\ProductType;

/**
 * A pasted link after Amazon handling: the link to store, its type and ASIN.
 */
final class NormalizedLink {

	/**
	 * Builds the result.
	 *
	 * @param AffiliateUrl $url  Link to store.
	 * @param ProductType  $type Amazon when it is an Amazon product.
	 * @param string       $asin ASIN, or '' for other links.
	 */
	public function __construct(
		public readonly AffiliateUrl $url,
		public readonly ProductType $type,
		public readonly string $asin,
	) {}
}
```

`src/Application/Products/ShortLinkResolver.php`:

```php
<?php
/**
 * Follows a short link (amzn.to) to where it ends.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

/**
 * Follows a short link (amzn.to) to where it ends.
 */
interface ShortLinkResolver {

	/**
	 * The final URL, or null if it could not be reached.
	 *
	 * @param string $url Short link.
	 */
	public function resolve( string $url ): ?string;
}
```

`src/Application/Products/AmazonLinkService.php`:

```php
<?php
/**
 * Turns whatever was pasted into the link to store, applying the site's Amazon tag.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

use Affibloom\Domain\Amazon\AmazonUrl;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Settings\SettingsRepository;

/**
 * Turns whatever was pasted into the link to store, applying the site's Amazon tag.
 */
final class AmazonLinkService {

	/**
	 * Builds the service.
	 *
	 * @param SettingsRepository $settings Site settings (tracking ID).
	 * @param ShortLinkResolver  $resolver Follows amzn.to links.
	 */
	public function __construct(
		private SettingsRepository $settings,
		private ShortLinkResolver $resolver,
	) {}

	/**
	 * The link to store for a pasted value.
	 *
	 * @param string $input Pasted link or ASIN.
	 * @param string $field Field name for errors ("url" or "url2").
	 * @throws InvalidField When the input is not a usable link, or Amazon has no tag set.
	 */
	public function normalize( string $input, string $field = 'url' ): NormalizedLink {
		$input = trim( $input );

		if ( AmazonUrl::is_short_link( $input ) ) {
			$resolved = $this->resolver->resolve( $input );
			if ( null === $resolved ) {
				throw new InvalidField( $field, 'short_link_unresolved' );
			}
			$input = $resolved;
		}

		$amazon = AmazonUrl::parse( $input );
		if ( null !== $amazon ) {
			return new NormalizedLink(
				AffiliateUrl::from_string( $amazon->url( $this->tag( $field ) ), $field ),
				ProductType::Amazon,
				$amazon->asin
			);
		}

		$url = AffiliateUrl::from_string( $input, $field );
		if ( AmazonUrl::is_amazon_host( $url->host() ) ) {
			// An Amazon page that is not one product (a store, a list): kept, with only the site's tag.
			return new NormalizedLink(
				AffiliateUrl::from_string( AmazonUrl::retag( $url->value(), $this->tag( $field ) ), $field ),
				ProductType::Other,
				''
			);
		}

		return new NormalizedLink( $url, ProductType::Other, '' );
	}

	/**
	 * The site's tracking ID.
	 *
	 * @param string $field Field name for the error.
	 * @throws InvalidField When no tracking ID is set.
	 */
	private function tag( string $field ): string {
		$tag = $this->settings->get()->string( 'tracking_id' );
		if ( '' === $tag ) {
			throw new InvalidField( $field, 'no_tracking_id' );
		}
		return $tag;
	}
}
```

- [ ] **Step 3: Domain: reading Amazon links**

`src/Domain/Amazon/AmazonUrl.php`:

```php
<?php
/**
 * An Amazon product: its ASIN and marketplace host.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Amazon;

/**
 * An Amazon product: its ASIN and marketplace host.
 */
final class AmazonUrl {

	/** Amazon marketplaces, with optional www./smile./m. in front. */
	private const HOST = '/^(?:www\.|smile\.|m\.)?amazon\.(com|ca|com\.mx|com\.br|co\.uk|de|fr|it|es|nl|se|pl|com\.tr|ae|sa|eg|in|co\.jp|sg|com\.au|com\.be)$/';

	/** Path forms that carry an ASIN. */
	private const PATH = '#/(?:dp|gp/product|gp/aw/d|exec/obidos/asin|o/asin|product-reviews|gp/offer-listing)/([A-Za-z0-9]{10})(?:[/?&]|$)#';

	/** A bare ASIN (B0…) or ISBN-10, so ordinary 10-letter words are not taken for one. */
	private const BARE = '/^(?:B0[A-Za-z0-9]{8}|\d{9}[\dXx])$/';

	/** Hosts of Amazon's short links. */
	private const SHORT = array( 'amzn.to', 'amzn.com', 'a.co', 'amzn.eu', 'amzn.asia' );

	/**
	 * Use parse().
	 *
	 * @param string $asin ASIN, upper case.
	 * @param string $host Marketplace host, e.g. "www.amazon.com".
	 */
	private function __construct( public readonly string $asin, public readonly string $host ) {}

	/**
	 * The product in a link or bare ASIN, or null when there is none.
	 *
	 * @param string $input Link or ASIN.
	 */
	public static function parse( string $input ): ?self {
		$input = trim( $input );
		if ( preg_match( self::BARE, $input ) ) {
			return new self( strtoupper( $input ), 'www.amazon.com' );
		}

		$parts = parse_url( $input ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Domain code does not call WordPress.
		if ( ! is_array( $parts ) || empty( $parts['host'] ) || ! preg_match( self::HOST, strtolower( $parts['host'] ), $host ) ) {
			return null;
		}
		$marketplace = 'www.amazon.' . $host[1];

		if ( preg_match( self::PATH, $parts['path'] ?? '', $match ) ) {
			return new self( strtoupper( $match[1] ), $marketplace );
		}

		parse_str( $parts['query'] ?? '', $query );
		if ( isset( $query['asin'] ) && is_string( $query['asin'] ) && preg_match( '/^[A-Za-z0-9]{10}$/', $query['asin'] ) ) {
			return new self( strtoupper( $query['asin'] ), $marketplace );
		}

		return null;
	}

	/**
	 * Whether a host (without "www.") is an Amazon marketplace.
	 *
	 * @param string $host Host.
	 */
	public static function is_amazon_host( string $host ): bool {
		return 1 === preg_match( self::HOST, strtolower( $host ) );
	}

	/**
	 * Whether the input is an Amazon short link that must be followed first.
	 *
	 * @param string $input Link.
	 */
	public static function is_short_link( string $input ): bool {
		$host = strtolower( (string) parse_url( trim( $input ), PHP_URL_HOST ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
		return in_array( preg_replace( '/^www\./', '', $host ), self::SHORT, true );
	}

	/**
	 * The one link stored for this product: https://<host>/dp/<ASIN>?tag=<tag>.
	 *
	 * @param string $tag Associates tracking ID.
	 */
	public function url( string $tag ): string {
		return 'https://' . $this->host . '/dp/' . $this->asin . '?tag=' . rawurlencode( $tag );
	}

	/**
	 * Any Amazon link with its tag set to ours, other parameters kept.
	 *
	 * @param string $url Link.
	 * @param string $tag Associates tracking ID.
	 */
	public static function retag( string $url, string $tag ): string {
		$fragment = '';
		$hash     = strpos( $url, '#' );
		if ( false !== $hash ) {
			$fragment = substr( $url, $hash );
			$url      = substr( $url, 0, $hash );
		}

		$pieces = explode( '?', $url, 2 );
		parse_str( $pieces[1] ?? '', $params );
		$params['tag'] = $tag;

		return $pieces[0] . '?' . http_build_query( $params, '', '&', PHP_QUERY_RFC3986 ) . $fragment;
	}
}
```

`Product.php`: add the last constructor parameter, and use it in `url_key()`:

```php
		public readonly string $asin = '',
```

```php
	public function url_key(): string {
		return ProductType::Amazon === $this->type && '' !== $this->asin ? 'amazon:' . $this->asin : $this->url->key();
	}
```

The key ignores the marketplace on purpose: the same ASIN on `.com` and `.co.uk` is the same product for this site.

- [ ] **Step 4: Infrastructure**

`src/Infrastructure/Http/WpShortLinkResolver.php`:

```php
<?php
/**
 * Follows short links with HEAD requests, one redirect at a time.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Infrastructure\Http;

use Affibloom\Application\Products\ShortLinkResolver;
use Affibloom\Domain\Amazon\AmazonUrl;
use WP_Http;

/**
 * Follows short links with HEAD requests, one redirect at a time.
 */
final class WpShortLinkResolver implements ShortLinkResolver {

	/**
	 * The URL the short link ends at, or null.
	 *
	 * @param string $url Short link.
	 */
	public function resolve( string $url ): ?string {
		for ( $hop = 0; $hop < 5; $hop++ ) {
			$response = wp_safe_remote_head(
				$url,
				array(
					'redirection' => 0,
					'timeout'     => 8,
				)
			);
			if ( is_wp_error( $response ) ) {
				return null;
			}

			$code     = (int) wp_remote_retrieve_response_code( $response );
			$location = wp_remote_retrieve_header( $response, 'location' );
			$location = is_array( $location ) ? (string) end( $location ) : (string) $location;

			if ( $code < 300 || $code >= 400 || '' === $location ) {
				return $hop > 0 ? $url : null;
			}

			$url = WP_Http::make_absolute_url( $location, $url );
			if ( null !== AmazonUrl::parse( $url ) ) {
				return $url;
			}
		}
		return null;
	}
}
```

`PostTypeProductRepository`: add `'_affibloom_asin' => $product->asin,` to `to_meta()`, and `asin: $value( '_affibloom_asin' ),` to `hydrate()`.

`Messages::for_reason()`, add:

```php
			'no_tracking_id'        => __( 'Add your Amazon tracking ID under Settings → Amazon first.', 'affibloom' ),
			'short_link_unresolved' => __( 'This short link could not be opened. Paste the full Amazon link instead.', 'affibloom' ),
```

`Plugin::modules()`: the service now takes the link service:

```php
			new ProductsController(
				new ProductService(
					$this->product_repository(),
					$this->settings_repository(),
					new AmazonLinkService( $this->settings_repository(), new WpShortLinkResolver() )
				),
				new ProductResource()
			),
```

- [ ] **Step 5: Unit test of the service**

`tests/Unit/Application/AmazonLinkServiceTest.php` covers four cases:
- a tagged product link;
- a bare ASIN;
- a short link resolved through a fake `ShortLinkResolver`;
- missing tag, which throws `no_tracking_id`.

It uses an in-memory `SettingsRepository`, as in `SettingsServiceTest`:

```php
<?php
namespace Affibloom\Tests\Unit\Application;

use PHPUnit\Framework\TestCase;
use Affibloom\Application\Products\AmazonLinkService;
use Affibloom\Application\Products\ShortLinkResolver;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\ProductType;
use Affibloom\Domain\Settings\Settings;
use Affibloom\Domain\Settings\SettingsRepository;

final class AmazonLinkServiceTest extends TestCase {
	private function service( string $tag, ?string $resolved = null ): AmazonLinkService {
		$settings = new class( $tag ) implements SettingsRepository {
			public function __construct( private string $tag ) {}
			public function get(): Settings {
				return Settings::from_array( array( 'tracking_id' => $this->tag ) );
			}
			public function save( Settings $settings ): void {}
		};
		$resolver = new class( $resolved ) implements ShortLinkResolver {
			public function __construct( private ?string $to ) {}
			public function resolve( string $url ): ?string {
				return $this->to;
			}
		};
		return new AmazonLinkService( $settings, $resolver );
	}

	public function test_a_product_link_is_cleaned_and_tagged(): void {
		$link = $this->service( 'vbvault-20' )->normalize( 'https://www.amazon.com/x/dp/B0C1234567/ref=a?tag=other-20' );

		self::assertSame( 'https://www.amazon.com/dp/B0C1234567?tag=vbvault-20', $link->url->value() );
		self::assertSame( ProductType::Amazon, $link->type );
		self::assertSame( 'B0C1234567', $link->asin );
	}

	public function test_a_short_link_is_followed(): void {
		$link = $this->service( 'vbvault-20', 'https://www.amazon.com/dp/B0C7654321?psc=1' )->normalize( 'https://amzn.to/3abcdE' );

		self::assertSame( 'B0C7654321', $link->asin );
	}

	public function test_an_amazon_link_needs_a_tracking_id(): void {
		$this->expectException( InvalidField::class );
		$this->service( '' )->normalize( 'B0C1234567' );
	}

	public function test_other_links_are_untouched(): void {
		$link = $this->service( '' )->normalize( 'https://mizuno.pxf.io/c/1?u=x' );

		self::assertSame( ProductType::Other, $link->type );
	}
}
```

- [ ] **Step 6: Run and look**

```bash
composer lint && composer analyse && composer test:unit && npm run test:integration
npm run test:e2e -- acceptance/F2 acceptance/F3
```
Expected: all pass. By hand:
1. Clear the tracking ID in Settings → Amazon, then add `B0C1234567`. Expected: "Add your Amazon tracking ID…".
2. Set the tracking ID back, then add the same link again. Expected: it is created.
3. Add it once more as an `/gp/product/` link. Expected: "This Product Already Exists".

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "Store every Amazon link as one clean link with the site's tag

Any product link form, a bare ASIN or an amzn.to short link becomes
https://<marketplace>/dp/<ASIN>?tag=<tracking id>. Duplicates are
found by ASIN."
```

---

### Task 14: Product page — the Details tab — SUPERSEDED 2026-11-13

> Replaced by Tasks 14A-14E below (deviation 57), written from a sweep of Lasso's product page
> (`docs/features/F1/requirements.md` sections 2-8 and `analysis.md`, gates 1-2 re-run). The old text, which left
> out Permalink, Groups, custom fields, Display Theme, Price, the Share kit and three of the four tabs, is in the
> appendix. **Gate 4 given by the user 2026-11-13**, with layout D1: Lasso's two cards in Aurora's shell.

---

### Task 15: Saving the Details tab — SUPERSEDED 2026-11-13

> Replaced by Tasks 15A-15D below (deviation 70), written from a sweep of Lasso's save path
> (`docs/features/F1/requirements.md` section 9 and `analysis.md` "Saving", gates 1-2 re-run). The old text, kept in
> the appendix, refused fields Lasso never refuses (lengths, the second URL, a rating that is now a custom field),
> named the toast in the wrong words, left out every field 14A-14H added, the save dialog, the duplicate offer and
> the unsaved-changes guard. **Gate 4 given by the user 2026-11-13** ("approved. go for it.").

---

### Task 16: Dashboard quick edit — SUPERSEDED 2026-11-13

> Replaced by Tasks 16A-16C below (deviation 76), written from a sweep of Lasso's Dashboard row and script
> (`docs/features/F1/requirements.md` section 10 and `analysis.md` "Dashboard quick edit", gates 1-2 re-run). The old
> text, kept in the appendix, opened a second row under the product's with a typed image link; Lasso turns the row
> itself into the form and takes the image only from the media library. It left out Groups, the link line, one row at
> a time, the save dialog, the list reload after a save, the unsaved-changes guard and the duplicate case, and used
> Lasso's toast words, which U6 replaced. **Gate 4 given by the user 2026-11-13** ("Approve, start 16A").

---

### Task 17: Deleting a product — SUPERSEDED 2026-11-13

> Replaced by Tasks 17A-17D below (deviation 82), written from a sweep of Lasso's delete paths
> (`docs/features/F1/requirements.md` section 11 and `analysis.md` "Deleting", gates 1-2 re-run). The old text, kept
> in the appendix, put a trash button in every Dashboard row and sent every delete to the trash. Lasso has no row
> delete: its product page's "Delete This Link" deletes **for good** and its Dashboard deletes the **ticked rows** into
> the trash; a trashed record comes back when its link is added again; retired records have their own status, not
> the trash (which WordPress empties after 30 days). **The user's answers, 2026-11-13:** D33 "Lasso's split"
> (permanent on the page, trash from the Dashboard); D34 "Block only" (Task 36 blocks a product in use; Lasso's force
> delete, which rewrites posts, is not built). **Gate 4 given by the user 2026-11-13** ("Approve, start 17A").

---

## Appendix: superseded task text

### Task 10: Settings → Display and Amazon tabs — SUPERSEDED 2026-11-13

> **Do not build from this section.** The full-Lasso scope rule (`progress.md` deviation 24) replaced it: the
> Display tab is a visual editor with ~50 controls per display type, and the Amazon tab has ~20. The task text
> below covers 12 of them and is kept only for the parts already built (Steps 1-4: the `text`, `icon_text`,
> `color` and `amazon_tag` cleaners, `Settings::string()`, the Aurora field and help-tip components, which all
> stay). The replacement is Tasks 10A-10F, written from `docs/features/F16/requirements.md` and `analysis.md`
> once the user approves them at gate 4.

### Task 10 (superseded text): Settings → Display and Amazon tabs

**Roadmap:** 2.1 · **Features:** F9, F2

**Why:** the box takes its default button labels, its disclosure and its colours from Display, and every Amazon link takes its tag from Amazon. Both tabs sit on the same screen, and save through the same handler, as General.

**User action:** an administrator opens Display, sets the default button texts, the disclosure text and the six box colours, and saves. Then opens Amazon, enters the tracking ID `vbvault-20` and the "Show Prime Logo In Displays" toggle, and saves. An invalid tracking ID such as `vbvault` is refused, keeps the old value and shows the partly-saved toast.

**Request path:** the same as Task 9, with `tab=display` or `tab=amazon`.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| Display keys in `Settings::DEFAULTS` | `SettingsService` now; `BoxBuilder` and `BoxAssets` from Task 18 |
| Amazon keys (`tracking_id`, `show_prime`) | `SettingsService` now; `AmazonLinkService` (Task 12), `BoxBuilder` (Task 22) |
| cleaners `color`, `text`, `amazon_tag` | `SettingsService::update_tab()` |
| `display` and `amazon` sections in `settings.php` | `SettingsScreen` |

**Files:**
- Modify:
  - `src/Domain/Settings/Settings.php`, `src/Application/Settings/SettingsService.php`
  - `templates/admin/settings.php`, `assets/admin/admin.css`
  - `tests/Unit/Application/SettingsServiceTest.php`

- [x] **Step 1: Failing unit tests**

Add to `SettingsServiceTest`:

```php
	public function test_an_invalid_tracking_id_keeps_the_old_value(): void {
		$repo = $this->repository();
		$svc  = new SettingsService( $repo );
		$svc->update_tab( 'amazon', array( 'tracking_id' => 'vbvault-20' ) );

		$refused = $svc->update_tab( 'amazon', array( 'tracking_id' => 'vbvault' ) );

		self::assertSame( array( 'tracking_id' ), $refused );
		self::assertSame( 'vbvault-20', $repo->get()->string( 'tracking_id' ) );
	}

	public function test_colours_must_be_hex(): void {
		$repo    = $this->repository();
		$refused = ( new SettingsService( $repo ) )->update_tab( 'display', array( 'color_main' => 'purple', 'color_button' => '#22BAA0' ) );

		self::assertContains( 'color_main', $refused );
		self::assertSame( '#22baa0', $repo->get()->string( 'color_button' ) );
	}
```

`sanitize_hex_color()` and `sanitize_text_field()` are WordPress functions, so this test now needs them. Make the test class extend `Affibloom\Tests\Unit\TestCase` (Brain Monkey) and stub them in `setUp()`:

```php
	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\Functions\when( 'sanitize_text_field' )->alias( static fn( $s ) => trim( strip_tags( (string) $s ) ) );
		\Brain\Monkey\Functions\when( 'sanitize_hex_color' )->alias(
			static fn( $c ) => preg_match( '/^#([A-Fa-f0-9]{3}){1,2}$/', (string) $c ) ? (string) $c : null
		);
	}
```

```bash
composer test:unit
```
Expected: the two new tests fail with `Unknown settings tab.`

- [x] **Step 2: Domain: the new keys and their defaults**

`Settings::DEFAULTS`, add after `allow_duplicates`:

```php
		// Display.
		'primary_button_text'     => 'Buy Now',
		'secondary_button_text'   => 'Learn More',
		'disclosure_text'         => 'We earn a commission if you make a purchase, at no additional cost to you.',
		'link_from_display_title' => true,
		'color_main'              => '#5e36ca',
		'color_title'             => '#000000',
		'color_background'        => '#ffffff',
		'color_button'            => '#22baa0',
		'color_secondary_button'  => '#22baa0',
		'color_button_text'       => '#ffffff',
		// Amazon.
		'tracking_id'             => '',
		'show_prime'              => true,
```

These are only the defaults for a fresh install. The site's real values (Lasso's `display_color_*`, `primary_button_text`, `disclosure_text`, `amazon_tracking_id` and so on) are imported in Task 26.

- [x] **Step 3: Service: the fields per tab and three cleaners**

`SettingsService::FIELDS`, add:

```php
		'display' => array(
			'primary_button_text'     => 'text',
			'secondary_button_text'   => 'text',
			'disclosure_text'         => 'text',
			'link_from_display_title' => 'bool',
			'color_main'              => 'color',
			'color_title'             => 'color',
			'color_background'        => 'color',
			'color_button'            => 'color',
			'color_secondary_button'  => 'color',
			'color_button_text'       => 'color',
		),
		'amazon'  => array(
			'tracking_id' => 'amazon_tag',
			'show_prime'  => 'bool',
		),
```

`clean()`: replace the `match` with:

```php
		return match ( $type ) {
			'bool'       => in_array( $raw, array( true, 1, '1', 'on', 'true', 'yes' ), true ),
			'text'       => is_string( $raw ) && strlen( trim( $raw ) ) <= 500 ? sanitize_text_field( $raw ) : null,
			'color'      => is_string( $raw ) && sanitize_hex_color( $raw ) ? strtolower( (string) sanitize_hex_color( $raw ) ) : null,
			// Associates tags look like "name-20"; the two digits vary by marketplace. Empty means "not set yet".
			'amazon_tag' => is_string( $raw ) && preg_match( '/^(|[A-Za-z0-9][A-Za-z0-9-]{0,62}-\d{2})$/', trim( $raw ) ) ? trim( $raw ) : null,
			default      => throw new LogicException( "No cleaner for type $type." ),
		};
```

- [x] **Step 4: Templates**

`templates/admin/settings.php`, extend `$labels`:

```php
	'display' => __( 'Display', 'affibloom' ),
	'amazon'  => __( 'Amazon', 'affibloom' ),
```

and after the `general` section:

```php
			<?php if ( 'display' === $view['tab'] ) : ?>
				<section class="affibloom-section">
					<h2 class="affibloom-section__title"><?php esc_html_e( 'Colors', 'affibloom' ); ?></h2>
					<div class="affibloom-grid">
						<?php
						$colors = array(
							'color_main'             => array( __( 'Badge', 'affibloom' ), __( 'This is the color of your badge background.', 'affibloom' ) ),
							'color_title'            => array( __( 'Title', 'affibloom' ), __( 'This is the color of the title text of your display.', 'affibloom' ) ),
							'color_background'       => array( __( 'Background', 'affibloom' ), __( 'This is the color of the inside of your display.', 'affibloom' ) ),
							'color_button_text'      => array( __( 'Button Text', 'affibloom' ), __( 'This is text color for your badges and buttons.', 'affibloom' ) ),
							'color_button'           => array( __( 'Primary Button', 'affibloom' ), __( "This is the color of your display's main CTA button.", 'affibloom' ) ),
							'color_secondary_button' => array( __( 'Secondary Button', 'affibloom' ), __( "This is the color of your display's secondary CTA button.", 'affibloom' ) ),
						);
						foreach ( $colors as $key => [ $label, $help ] ) :
							?>
							<div class="affibloom-field affibloom-field--stack">
								<label class="affibloom-label" for="affibloom-setting-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
								<?php $this->render( 'admin/partials/help', array( 'text' => $help ) ); ?>
								<input type="color" class="affibloom-color" id="affibloom-setting-<?php echo esc_attr( $key ); ?>"
									name="settings[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $settings->string( $key ) ); ?>">
							</div>
						<?php endforeach; ?>
					</div>
				</section>

				<section class="affibloom-section">
					<h2 class="affibloom-section__title"><?php esc_html_e( 'Buttons and Disclosure', 'affibloom' ); ?></h2>
					<?php
					$texts = array(
						'primary_button_text'   => array( __( 'Primary Button Text', 'affibloom' ), __( 'If you leave your display button text blank, this is what it will default to.', 'affibloom' ) ),
						'secondary_button_text' => array( __( 'Secondary Button Text', 'affibloom' ), __( 'If you set a secondary button for your display and leave it blank, this is what it will default to.', 'affibloom' ) ),
						'disclosure_text'       => array( __( 'Disclosure Text', 'affibloom' ), __( 'This is the default disclosure text used with your displays.', 'affibloom' ) ),
					);
					foreach ( $texts as $key => [ $label, $help ] ) :
						?>
						<div class="affibloom-field affibloom-field--stack">
							<label class="affibloom-label" for="affibloom-setting-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
							<?php $this->render( 'admin/partials/help', array( 'text' => $help ) ); ?>
							<input type="text" class="affibloom-input" id="affibloom-setting-<?php echo esc_attr( $key ); ?>"
								name="settings[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $settings->string( $key ) ); ?>" maxlength="500">
						</div>
					<?php endforeach; ?>
					<?php
					$this->render(
						'admin/partials/toggle',
						array(
							'id'      => 'affibloom-setting-link_from_display_title',
							'name'    => 'settings[link_from_display_title]',
							'label'   => __( 'Link Display Titles', 'affibloom' ),
							'checked' => $settings->bool( 'link_from_display_title' ),
							'help'    => __( 'When enabled, your Display titles will be your clickable link. Toggle this off to have Display titles only appear as text.', 'affibloom' ),
						)
					);
					?>
				</section>
			<?php endif; ?>

			<?php if ( 'amazon' === $view['tab'] ) : ?>
				<section class="affibloom-section">
					<h2 class="affibloom-section__title"><?php esc_html_e( 'Amazon Associates Accounts', 'affibloom' ); ?></h2>
					<div class="affibloom-field affibloom-field--stack">
						<label class="affibloom-label" for="affibloom-setting-tracking_id"><?php esc_html_e( 'Tracking ID for This Site', 'affibloom' ); ?></label>
						<input type="text" class="affibloom-input" id="affibloom-setting-tracking_id" name="settings[tracking_id]"
							value="<?php echo esc_attr( $settings->string( 'tracking_id' ) ); ?>" placeholder="tracking-20" pattern="[A-Za-z0-9][A-Za-z0-9\-]{0,62}-[0-9]{2}">
					</div>
				</section>
				<section class="affibloom-section">
					<h2 class="affibloom-section__title"><?php esc_html_e( 'Amazon Preferences', 'affibloom' ); ?></h2>
					<?php
					$this->render(
						'admin/partials/toggle',
						array(
							'id'      => 'affibloom-setting-show_prime',
							'name'    => 'settings[show_prime]',
							'label'   => __( 'Show Prime Logo In Displays', 'affibloom' ),
							'checked' => $settings->bool( 'show_prime' ),
							'help'    => __( 'Show the blue Prime logo beside the price for all eligible Amazon products in your Displays.', 'affibloom' ),
						)
					);
					?>
				</section>
			<?php endif; ?>
```

`assets/admin/admin.css`, append:

```css
.affibloom-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 8px 24px; }
.affibloom-field--stack { flex-wrap: wrap; }
.affibloom-field--stack > .affibloom-input,
.affibloom-field--stack > textarea,
.affibloom-field--stack > .affibloom-color { flex-basis: 100%; }
.affibloom-label { font-weight: 600; }
.affibloom-input,
.affibloom-admin textarea {
	width: 100%;
	max-width: 560px;
	padding: 8px 12px;
	border: 1px solid var(--affibloom-border);
	border-radius: 6px;
	font: 14px/1.4 var(--affibloom-font);
}
.affibloom-color { width: 56px; height: 36px; padding: 2px; border: 1px solid var(--affibloom-border); border-radius: 6px; }
```

- [ ] **Step 5: Run and look**

```bash
composer lint && composer analyse && composer test:unit
```
Open the Display and Amazon tabs. Check their looks against the design system (ADR-8), and their fields, labels, help texts and order against their Lasso screenshots. On the Amazon tab, save `vbvault`. Expected: the toast reads "Some values were not valid…" and the field still holds the previous value. Save `vbvault-20`. Expected: "Settings saved."

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "Add the Display and Amazon settings tabs

Default button labels, disclosure text and box colours on Display;
the site's Associates tracking ID and the Prime logo toggle on Amazon."
```

---

---

### Task 13 (superseded text): Dashboard list — search and pages

> Replaced by Tasks 13A and 13B above (deviation 48). Kept for the record. Its 20 per page, its `date DESC`
> ordering, its `WP_Query` `s` search and its name-and-host row are not Lasso's (F19 requirements, section 7).


**Roadmap:** 2.2 · **Feature:** F1 (AC3)

**Why:** the Dashboard is Lasso's home screen: every product in one list, newest first, with a search box.

**User action:** an editor opens the Dashboard and sees the products in a table with Lasso's columns and column order, styled by the design system (ADR-8), 20 per page with page links, or the page size confirmed in Task 6. Typing "Mizuno" in the search box and pressing Enter shows only the products whose name contains it.

**Request path:**
```
GET admin.php?page=affibloom[&s=mizuno][&paged=2]
  → DashboardScreen::render()
       ProductService::list( term, page )
         ProductRepository::search( term, page, PER_PAGE )   WP_Query: affibloom_product, s, date DESC
  ← templates/admin/dashboard.php → partials/product-row.php for each product; paginate_links()
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Domain\Product\ProductPage` | `ProductRepository::search()`, `DashboardScreen` |
| `ProductRepository::search()` (and its implementation) | `ProductService::list()` |
| `ProductService::list()`, `PER_PAGE` | `DashboardScreen` |
| `templates/admin/partials/product-row.php`, search form, table, pagination | `dashboard.php` |

**Columns, as the Screen map says:** Lasso shows Image | Link Name | Groups | Alert | Locations | Clicks | Actions. Each column arrives with the task that first has its data:
- Link Name: this task.
- Image and Actions: Task 14.
- Alert: Task 31.
- Clicks: Task 34.
- Locations: Task 36.
- Groups is left out (see the decisions table).

**Files:**
- Create: `src/Domain/Product/ProductPage.php`, `templates/admin/partials/product-row.php`, `tests/Integration/ProductSearchTest.php`
- Modify:
  - `src/Domain/Product/ProductRepository.php`
  - `src/Infrastructure/WordPress/PostTypeProductRepository.php`
  - `src/Application/Products/ProductService.php`
  - `src/Interface/Admin/DashboardScreen.php`, `src/Plugin.php`
  - `templates/admin/dashboard.php`, `assets/admin/admin.css`

- [ ] **Step 1: Failing tests**

```bash
npm run test:e2e -- acceptance/F1 -g AC3
```

`tests/Integration/ProductSearchTest.php`:

```php
<?php
namespace Affibloom\Tests\Integration;

use Affibloom\Infrastructure\WordPress\PostTypeProductRepository;
use WP_UnitTestCase;

final class ProductSearchTest extends WP_UnitTestCase {
	public function test_search_by_name_newest_first_and_paged(): void {
		foreach ( array( 'Mizuno Wave', 'Asics Sky', 'Mizuno Lightning' ) as $i => $name ) {
			self::factory()->post->create(
				array(
					'post_type'  => 'affibloom_product',
					'post_title' => $name,
					'post_date'  => '2026-01-0' . ( $i + 1 ) . ' 00:00:00',
					'meta_input' => array( '_affibloom_url' => "https://example.com/$i" ),
				)
			);
		}

		$page = ( new PostTypeProductRepository() )->search( 'mizuno', 1, 1 );

		self::assertSame( 2, $page->total );
		self::assertSame( 2, $page->pages() );
		self::assertSame( 'Mizuno Lightning', $page->items[0]->name );
	}
}
```

- [ ] **Step 2: Screen**

`DashboardScreen`: the constructor takes `private ProductService $products` after `$template`. `render()` becomes:

```php
	public function render(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- a read-only list filter.
		$term = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		// phpcs:enable

		$this->template->render(
			'admin/dashboard',
			array(
				'products' => $this->products->list( $term, $page ),
				'term'     => $term,
				'notice'   => Notice::from_request(),
			)
		);
	}
```

- [ ] **Step 3: Service, domain, repository**

`ProductService`:

```php
	/** Products per Dashboard page (Task 6 confirms Lasso's number). */
	public const PER_PAGE = 20;

	/**
	 * One page of the Dashboard list, newest first.
	 *
	 * @param string $term Search text; '' for all.
	 * @param int    $page Page number, from 1.
	 */
	public function list( string $term, int $page ): ProductPage {
		return $this->products->search( $term, $page, self::PER_PAGE );
	}
```

`src/Domain/Product/ProductPage.php`:

```php
<?php
/**
 * One page of products and the total behind it.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

/**
 * One page of products and the total behind it.
 */
final class ProductPage {

	/**
	 * Builds the page.
	 *
	 * @param list<Product> $items    Products on this page.
	 * @param int           $total    Products across all pages.
	 * @param int           $page     This page's number, from 1.
	 * @param int           $per_page Products per page.
	 */
	public function __construct(
		public readonly array $items,
		public readonly int $total,
		public readonly int $page,
		public readonly int $per_page,
	) {}

	/**
	 * Number of pages.
	 */
	public function pages(): int {
		return max( 1, (int) ceil( $this->total / max( 1, $this->per_page ) ) );
	}
}
```

`ProductRepository`, add:

```php
	/**
	 * Published products whose name matches, newest first.
	 *
	 * @param string $term     Search text; '' for all.
	 * @param int    $page     Page number, from 1.
	 * @param int    $per_page Products per page.
	 */
	public function search( string $term, int $page, int $per_page ): ProductPage;
```

`PostTypeProductRepository`, add (WP_Query primes the meta cache, so building each product costs no extra query):

```php
	/**
	 * Published products whose name matches, newest first.
	 *
	 * @param string $term     Search text.
	 * @param int    $page     Page number.
	 * @param int    $per_page Products per page.
	 */
	public function search( string $term, int $page, int $per_page ): ProductPage {
		$query = new \WP_Query(
			array(
				'post_type'      => ProductPostType::NAME,
				'post_status'    => 'publish',
				's'              => $term,
				'paged'          => max( 1, $page ),
				'posts_per_page' => $per_page,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		$items = array_map( fn( WP_Post $post ): Product => $this->hydrate( $post ), $query->posts );
		return new ProductPage( array_values( $items ), (int) $query->found_posts, max( 1, $page ), $per_page );
	}
```

`Plugin::modules()`: pass the service to the Dashboard. The service is now needed twice, so build it once in `modules()`, as `$products = new ProductService( … );`, and use `$products` for both the screen and the controller.

- [ ] **Step 4: Templates**

`templates/admin/partials/product-row.php`:

```php
<?php
/**
 * One Dashboard row.
 *
 * @package Affibloom
 * @var array{product: \Affibloom\Domain\Product\Product} $view
 */

defined( 'ABSPATH' ) || exit;

$product = $view['product'];
?>
<tr data-product-id="<?php echo esc_attr( (string) $product->id ); ?>">
	<td class="affibloom-col-name">
		<strong class="affibloom-row-name"><?php echo esc_html( $product->name ); ?></strong>
		<span class="affibloom-row-host"><?php echo esc_html( $product->url->host() ); ?></span>
	</td>
</tr>
```

`templates/admin/dashboard.php`: replace the empty-state card with:

```php
		<form class="affibloom-toolbar" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" role="search">
			<input type="hidden" name="page" value="affibloom">
			<label class="screen-reader-text" for="affibloom-search"><?php esc_html_e( 'Search', 'affibloom' ); ?></label>
			<input type="search" id="affibloom-search" class="affibloom-input affibloom-search" name="s" value="<?php echo esc_attr( $view['term'] ); ?>"
				placeholder="<?php esc_attr_e( 'Search', 'affibloom' ); ?>">
		</form>

		<?php $products = $view['products']; ?>
		<?php if ( ! $products->items ) : ?>
			<div class="affibloom-card affibloom-empty">
				<p><?php echo '' === $view['term'] ? esc_html__( 'No products yet.', 'affibloom' ) : esc_html__( 'No products match your search.', 'affibloom' ); ?></p>
			</div>
		<?php else : ?>
			<div class="affibloom-card affibloom-card--table">
				<table class="affibloom-table">
					<thead>
						<tr>
							<th scope="col" class="affibloom-col-name"><?php esc_html_e( 'Link Name', 'affibloom' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $products->items as $product ) : ?>
							<?php $this->render( 'admin/partials/product-row', array( 'product' => $product ) ); ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<?php
			$links = paginate_links(
				array(
					'base'    => add_query_arg( 'paged', '%#%' ),
					'format'  => '',
					'current' => $products->page,
					'total'   => $products->pages(),
				)
			);
			if ( $links ) {
				echo '<nav class="affibloom-pagination" aria-label="' . esc_attr__( 'Pages', 'affibloom' ) . '">' . wp_kses_post( $links ) . '</nav>';
			}
			?>
		<?php endif; ?>
```

`assets/admin/admin.css`, append:

```css
/* Toolbar and table */
.affibloom-toolbar { display: flex; gap: 12px; align-items: center; margin: 0 0 16px; }
.affibloom-search { max-width: 320px; border-radius: 999px; padding-left: 16px; }
.affibloom-card--table { padding: 0; overflow: hidden; }
.affibloom-table { width: 100%; border-collapse: collapse; }
.affibloom-table th {
	padding: 12px 16px;
	background: var(--affibloom-bg);
	border-bottom: 1px solid var(--affibloom-border-light);
	color: var(--affibloom-muted);
	font-size: 12px;
	font-weight: 700;
	text-align: left;
	text-transform: uppercase;
	letter-spacing: 0.04em;
}
.affibloom-table td { padding: 12px 16px; border-bottom: 1px solid var(--affibloom-border-light); vertical-align: middle; }
.affibloom-table tbody tr:hover { background: var(--affibloom-purple-tint); }
.affibloom-row-name { display: block; color: var(--affibloom-ink); }
.affibloom-row-host { display: block; color: var(--affibloom-muted); font-size: 12px; }
.affibloom-pagination { margin-top: 16px; display: flex; gap: 6px; }
.affibloom-pagination .page-numbers { padding: 6px 12px; border-radius: 6px; background: var(--affibloom-white); border: 1px solid var(--affibloom-border-light); text-decoration: none; }
.affibloom-pagination .current { background: var(--affibloom-purple); color: var(--affibloom-white); border-color: var(--affibloom-purple); }
```

- [ ] **Step 5: Run and look**

```bash
composer lint && composer analyse && npm run test:integration
npm run test:e2e -- acceptance/F1 -g AC3
```
Expected: they pass. Add 25 products (`for i in $(seq 25); do npm run wp -- post create --post_type=affibloom_product --post_status=publish --post_title="Item $i" --meta_input='{"_affibloom_url":"https://example.com/'$i'"}'; done`). Expected: page 2 shows 5 items. Rename one to "Mizuno Wave Momentum 3" and search for "Momentum". Expected: only that product. WordPress search matches every word separately, so searching for "Item 2" would also find "Item 12".

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "List products on the Dashboard with search and pages

Newest first, 20 per page. Columns arrive with the task that has
their data; Link Name comes first."
```

---

### Task 14 (superseded text): Product page — the Details tab

> Replaced by Tasks 14A-14E above (deviation 57). Kept for the record. It left out Permalink, Groups, custom fields
> (it had a plain 0-5 Rating where Lasso has the Primary Rating field), Display Theme, Price, the Share kit and three of
> the four tabs, and its Dashboard and add-flow steps predate 13C-13E and `6d9a5ef`.


**Roadmap:** 2.2 · **Feature:** F1

**Why:** Lasso's product page is where an editor sees and edits everything about a product. This task builds the page and shows every field. Task 15 saves them.

**User action:** an editor clicks a product's name on the Dashboard, or adds a single link, and lands on its page. The page shows the header, the product name as the title, the tabs (Details), and the Details form in Lasso's field order:
- Name.
- Primary Destination URL, its Button Text, and the New Window / Tab and NoFollow / NoIndex toggles.
- The same four fields for the secondary button.
- Description and Rating.
- Show Disclosure and Disclosure Text.
- In the right-hand column: the image with "Update Image", Badge Text, and "Show Prime Logo" for Amazon products.

**Request path:**
```
GET admin.php?page=affibloom-product&id=123
  → AdminMenu (hidden submenu, Capabilities::PRODUCTS) → ProductScreen::render()
       ProductRepository::get( 123 ) → null → "This product does not exist." (404)
  ← templates/admin/product.php   (wp_editor for Description; wp.media enqueued for the image)
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `ProductScreen::render()`, `ProductScreen::url()` | the hidden submenu page; the Dashboard row, `ProductResource`, admin.js |
| `Product` fields `button_text`, `url2`, `button_text2`, `new_tab2`, `nofollow2`, `image_url`, `badge`, `description`, `rating`, `disclosure`, `prime` | the Details form (Task 15 saves them; Phase 3 renders them) |
| `Domain\Product\Rating` | `Product::$rating`, the repository |
| `hydrate()` for those fields | `ProductRepository::get()` |
| Dashboard Image column, name link, Actions column (Edit) | `product-row.php` |
| `edit_url` in `ProductResource::summary()` | admin.js (single add → product page; duplicate → link) |

**Files:**
- Create: `src/Interface/Admin/ProductScreen.php`, `src/Domain/Product/Rating.php`, `templates/admin/product.php`, `tests/Unit/Domain/RatingTest.php`
- Modify:
  - `src/Domain/Product/Product.php`
  - `src/Infrastructure/WordPress/PostTypeProductRepository.php`
  - `src/Interface/Admin/{AdminMenu,AdminAssets}.php`
  - `src/Interface/Rest/ProductResource.php`, `src/Plugin.php`
  - `templates/admin/dashboard.php`, `templates/admin/partials/product-row.php`
  - `assets/admin/{admin.js,admin.css}`

- [ ] **Step 1: Failing test**

```bash
npm run test:e2e -- acceptance/F1 -g AC1
```
Expected: fails, because the single added link goes back to the Dashboard, not to `page=affibloom-product`.

- [ ] **Step 2: Route (a hidden page in the menu)**

`AdminMenu`: the constructor takes `private ProductScreen $product` as its second argument. In `register()`, add `add_action( 'admin_head', array( $this, 'hide_product_page' ) );`. In `add_pages()`, before Settings:

```php
		add_submenu_page(
			self::SLUG,
			__( 'Product', 'affibloom' ),
			__( 'Product', 'affibloom' ),
			Capabilities::PRODUCTS,
			ProductScreen::SLUG,
			array( $this->product, 'render' )
		);
```

and the new method:

```php
	/**
	 * The product page is reached from the Dashboard, never from the menu, as in Lasso.
	 * Removing it after admin_menu keeps the page reachable and the menu highlighted.
	 */
	public function hide_product_page(): void {
		remove_submenu_page( self::SLUG, ProductScreen::SLUG );
	}
```

- [ ] **Step 3: Screen**

`src/Interface/Admin/ProductScreen.php`:

```php
<?php
/**
 * The product page: Lasso's link details page.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Interface\Admin;

use Affibloom\Domain\Product\ProductRepository;

/**
 * The product page: Lasso's link details page.
 */
final class ProductScreen {

	public const SLUG = 'affibloom-product';

	/**
	 * Builds the screen.
	 *
	 * @param Template          $template Template renderer.
	 * @param ProductRepository $products Product storage.
	 */
	public function __construct( private Template $template, private ProductRepository $products ) {}

	/**
	 * Admin URL of a product's page.
	 *
	 * @param int    $id  Product ID.
	 * @param string $tab "details" or "locations".
	 */
	public static function url( int $id, string $tab = 'details' ): string {
		$args = array(
			'page' => self::SLUG,
			'id'   => $id,
		);
		if ( 'details' !== $tab ) {
			$args['tab'] = $tab;
		}
		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/**
	 * Prints the page for ?id=…
	 */
	public function render(): void {
		$id      = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
		$product = $this->products->get( $id );
		if ( null === $product ) {
			wp_die( esc_html__( 'This product does not exist.', 'affibloom' ), '', array( 'response' => 404 ) );
		}

		$this->template->render(
			'admin/product',
			array(
				'product' => $product,
				'tab'     => 'details',
				'notice'  => Notice::from_request(),
			)
		);
	}
}
```

`AdminAssets::enqueue()`, at the end. The image picker needs the media library, on the product page only:

```php
		if ( str_contains( $hook_suffix, ProductScreen::SLUG ) ) {
			wp_enqueue_media();
		}
```

- [ ] **Step 4: Domain: the rest of the product's fields**

`src/Domain/Product/Rating.php`:

```php
<?php
/**
 * A star rating from 0 to 5, one decimal.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Domain\Product;

use Affibloom\Domain\InvalidField;

/**
 * A star rating from 0 to 5, one decimal.
 */
final class Rating {

	/**
	 * Use from_string().
	 *
	 * @param float $value 0–5.
	 */
	private function __construct( public readonly float $value ) {}

	/**
	 * A rating, or null for an empty value.
	 *
	 * @param string $raw   Input such as "4.5".
	 * @param string $field Field name for the error.
	 * @throws InvalidField When it is not a number from 0 to 5.
	 */
	public static function from_string( string $raw, string $field = 'rating' ): ?self {
		$raw = trim( $raw );
		if ( '' === $raw ) {
			return null;
		}
		if ( ! is_numeric( $raw ) || (float) $raw < 0 || (float) $raw > 5 ) {
			throw new InvalidField( $field, 'invalid_rating' );
		}
		return new self( round( (float) $raw, 1 ) );
	}

	/**
	 * "4.5", or "4" for 4.0.
	 */
	public function __toString(): string {
		return rtrim( rtrim( number_format( $this->value, 1, '.', '' ), '0' ), '.' );
	}
}
```

`Product.php`: add these constructor parameters after `asin`. Their defaults are the values for "not set".

```php
		public readonly string $button_text = '',
		public readonly ?AffiliateUrl $url2 = null,
		public readonly string $button_text2 = '',
		public readonly bool $new_tab2 = true,
		public readonly bool $nofollow2 = true,
		public readonly string $image_url = '',
		public readonly string $badge = '',
		public readonly string $description = '',
		public readonly ?Rating $rating = null,
		public readonly string $disclosure = '',
		public readonly bool $prime = false,
```

Add their `@param` lines to the docblock:
- `button_text`, `button_text2`: '' means the site's default label from Display.
- `disclosure`: '' means the site's default text.

In `draft()`, the secondary button starts with the same defaults as the primary one, as Lasso's `open_new_tab2` and `enable_nofollow2` do:

```php
			new_tab2: $defaults->bool( 'new_tab' ),
			nofollow2: $defaults->bool( 'nofollow' ),
```

`PostTypeProductRepository::hydrate()`: read them. A product saved before this task has no `_affibloom_new_tab2` yet, so it falls back to the primary value.

```php
			asin: $value( '_affibloom_asin' ),
			button_text: $value( '_affibloom_button_text' ),
			url2: '' === $value( '_affibloom_url2' ) ? null : AffiliateUrl::from_string( $value( '_affibloom_url2' ), 'url2' ),
			button_text2: $value( '_affibloom_button_text2' ),
			new_tab2: '1' === $value( '_affibloom_new_tab2', $value( '_affibloom_new_tab', '1' ) ),
			nofollow2: '1' === $value( '_affibloom_nofollow2', $value( '_affibloom_nofollow', '1' ) ),
			image_url: $value( '_affibloom_image_url' ),
			badge: $value( '_affibloom_badge' ),
			description: $value( '_affibloom_description' ),
			rating: Rating::from_string( $value( '_affibloom_rating' ) ),
			disclosure: $value( '_affibloom_disclosure' ),
			prime: '1' === $value( '_affibloom_prime', '0' ),
```

`tests/Unit/Domain/RatingTest.php`:

```php
<?php
namespace Affibloom\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\Rating;

final class RatingTest extends TestCase {
	public function test_formats(): void {
		self::assertSame( '4.5', (string) Rating::from_string( '4.5' ) );
		self::assertSame( '4', (string) Rating::from_string( '4.0' ) );
		self::assertNull( Rating::from_string( ' ' ) );
	}

	public function test_out_of_range(): void {
		$this->expectException( InvalidField::class );
		Rating::from_string( '5.5' );
	}
}
```

- [ ] **Step 5: Template (the response)**

`templates/admin/product.php`:

```php
<?php
/**
 * Product page, Details tab.
 *
 * @package Affibloom
 * @var array{product: \Affibloom\Domain\Product\Product, tab: string, notice: string} $view
 */

defined( 'ABSPATH' ) || exit;

use Affibloom\Domain\Product\ProductType;
use Affibloom\Interface\Admin\ProductScreen;

$product = $view['product'];
$text    = static function ( string $id, string $field, string $label, string $value, string $help, int $max ) {
	?>
	<div class="affibloom-field affibloom-field--stack">
		<label class="affibloom-label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		<?php if ( '' !== $help ) : ?>
			<span class="affibloom-help" tabindex="0" role="img" aria-label="<?php echo esc_attr( $help ); ?>" data-tooltip="<?php echo esc_attr( $help ); ?>">?</span>
		<?php endif; ?>
		<input type="text" class="affibloom-input" id="<?php echo esc_attr( $id ); ?>" data-field="<?php echo esc_attr( $field ); ?>"
			value="<?php echo esc_attr( $value ); ?>" maxlength="<?php echo esc_attr( (string) $max ); ?>">
	</div>
	<?php
};
$toggle  = function ( string $id, string $field, string $label, bool $checked, string $help ) {
	$this->render(
		'admin/partials/toggle',
		array(
			'id'      => $id,
			'name'    => $field,
			'label'   => $label,
			'checked' => $checked,
			'help'    => $help,
		)
	);
};
?>
<div class="wrap affibloom-admin">
	<?php $this->render( 'admin/partials/header', array( 'current' => 'dashboard' ) ); ?>

	<div class="affibloom-page">
		<p class="affibloom-back"><a href="<?php echo esc_url( admin_url( 'admin.php?page=affibloom' ) ); ?>">&larr; <?php esc_html_e( 'Dashboard', 'affibloom' ); ?></a></p>
		<div class="affibloom-page__head">
			<h1 class="affibloom-page__title"><?php echo esc_html( $product->name ); ?></h1>
			<button type="submit" form="affibloom-product-form" id="affibloom-save" class="affibloom-button affibloom-button--primary"><?php esc_html_e( 'Save Changes', 'affibloom' ); ?></button>
		</div>

		<nav class="affibloom-tabs" aria-label="<?php esc_attr_e( 'Product sections', 'affibloom' ); ?>">
			<a class="affibloom-tabs__tab is-active" aria-current="page" href="<?php echo esc_url( ProductScreen::url( (int) $product->id ) ); ?>"><?php esc_html_e( 'Details', 'affibloom' ); ?></a>
		</nav>

		<form id="affibloom-product-form" class="affibloom-product" data-product-id="<?php echo esc_attr( (string) $product->id ); ?>" novalidate>
			<div class="affibloom-product__main affibloom-card">
				<?php
				$text( 'affibloom-name', 'name', __( 'Name', 'affibloom' ), $product->name, __( 'This title will only be shown in displays', 'affibloom' ), 200 );
				$text( 'affibloom-url', 'url', __( 'Primary Destination URL', 'affibloom' ), $product->url->value(), __( 'The actual URL you want people to go to when they click a link', 'affibloom' ), 2000 );
				$text( 'affibloom-button-text', 'button_text', __( 'Button Text', 'affibloom' ), $product->button_text, __( 'This text will appear in the primary button', 'affibloom' ), 60 );
				$toggle( 'affibloom-new-tab', 'new_tab', __( 'New Window / Tab', 'affibloom' ), $product->new_tab, __( 'When enabled, users who click this link will have it loaded in a new tab.', 'affibloom' ) );
				$toggle( 'affibloom-nofollow', 'nofollow', __( 'NoFollow / NoIndex', 'affibloom' ), $product->nofollow, __( "When enabled, this link will be set to nofollow. This indicates to Google that it's an affiliate link.", 'affibloom' ) );
				?>
				<hr class="affibloom-divider">
				<?php
				$text( 'affibloom-url2', 'url2', __( 'Secondary Destination URL', 'affibloom' ), $product->url2 ? $product->url2->value() : '', __( 'A secondary URL you want people to go to when they click an optional second button in displays', 'affibloom' ), 2000 );
				$text( 'affibloom-button-text2', 'button_text2', __( 'Button Text', 'affibloom' ), $product->button_text2, __( 'This text will appear in the optional secondary button', 'affibloom' ), 60 );
				$toggle( 'affibloom-new-tab2', 'new_tab2', __( 'New Window / Tab', 'affibloom' ), $product->new_tab2, __( 'When enabled, users who click this link will have it loaded in a new tab.', 'affibloom' ) );
				$toggle( 'affibloom-nofollow2', 'nofollow2', __( 'NoFollow / NoIndex', 'affibloom' ), $product->nofollow2, __( "When enabled, this link will be set to nofollow. This indicates to Google that it's an affiliate link.", 'affibloom' ) );
				?>
				<hr class="affibloom-divider">
				<div class="affibloom-field affibloom-field--stack">
					<label class="affibloom-label" for="affibloom_description"><?php esc_html_e( 'Description', 'affibloom' ); ?></label>
					<span class="affibloom-help" tabindex="0" role="img" data-tooltip="<?php esc_attr_e( "Share more details about the product or service you're linking to. A short paragraph of 1-4 sentences typically performs best.", 'affibloom' ); ?>" aria-label="<?php esc_attr_e( "Share more details about the product or service you're linking to. A short paragraph of 1-4 sentences typically performs best.", 'affibloom' ); ?>">?</span>
					<div class="affibloom-editor" data-field-editor="description">
						<?php
						wp_editor(
							$product->description,
							'affibloom_description',
							array(
								'textarea_name' => 'description',
								'media_buttons' => false,
								'teeny'         => true,
								'textarea_rows' => 6,
							)
						);
						?>
					</div>
				</div>
				<div class="affibloom-field affibloom-field--stack">
					<label class="affibloom-label" for="affibloom-rating"><?php esc_html_e( 'Rating', 'affibloom' ); ?></label>
					<input type="number" class="affibloom-input affibloom-input--short" id="affibloom-rating" data-field="rating" min="0" max="5" step="0.1"
						value="<?php echo esc_attr( $product->rating ? (string) $product->rating : '' ); ?>">
				</div>
				<?php
				$toggle( 'affibloom-show-disclosure', 'show_disclosure', __( 'Show Disclosure', 'affibloom' ), $product->show_disclosure, __( 'When enabled, this link will show the disclosure.', 'affibloom' ) );
				$text( 'affibloom-disclosure', 'disclosure', __( 'Disclosure Text', 'affibloom' ), $product->disclosure, __( 'Leave empty to use the disclosure text from Settings.', 'affibloom' ), 500 );
				?>
			</div>

			<aside class="affibloom-product__side">
				<div class="affibloom-card affibloom-image-card">
					<img id="affibloom-image-preview" class="affibloom-image-preview" alt=""
						src="<?php echo esc_url( '' !== $product->image_url ? $product->image_url : includes_url( 'images/media/default.svg' ) ); ?>">
					<input type="hidden" id="affibloom-image-url" data-field="image_url" value="<?php echo esc_attr( $product->image_url ); ?>">
					<button type="button" id="affibloom-image-button" class="affibloom-button affibloom-button--secondary"><?php esc_html_e( 'Update Image', 'affibloom' ); ?></button>
				</div>
				<div class="affibloom-card">
					<?php
					$text( 'affibloom-badge', 'badge', __( 'Badge Text', 'affibloom' ), $product->badge, __( 'This text will appear as a badge in displays for this URL.', 'affibloom' ), 60 );
					if ( ProductType::Amazon === $product->type ) {
						$toggle( 'affibloom-prime', 'prime', __( 'Show Prime Logo', 'affibloom' ), $product->prime, __( 'Show the blue Prime logo for this Amazon product.', 'affibloom' ) );
					}
					?>
				</div>
			</aside>
		</form>
	</div>

	<?php $this->render( 'admin/partials/toast', array( 'message' => $view['notice'] ) ); ?>
</div>
```

Each field carries a `data-field` attribute naming its REST field, which is what admin.js sends in Task 15. Toggles use the field name as their `name`. Task 15 reads them by that name.

`templates/admin/partials/toggle.php`: add `data-field="<?php echo esc_attr( $view['name'] ); ?>"` to the `<input>`. In Settings the name is `settings[...]`, and admin.js never reads Settings forms, so the attribute does no harm there.

- [ ] **Step 6: Dashboard: Image, the name link, Actions**

`templates/admin/partials/product-row.php`, the new row:

```php
<tr data-product-id="<?php echo esc_attr( (string) $product->id ); ?>">
	<td class="affibloom-col-image">
		<?php if ( '' !== $product->image_url ) : ?>
			<img class="affibloom-thumb" src="<?php echo esc_url( $product->image_url ); ?>" alt="" loading="lazy" width="48" height="48">
		<?php else : ?>
			<span class="affibloom-thumb affibloom-thumb--empty" aria-hidden="true"></span>
		<?php endif; ?>
	</td>
	<td class="affibloom-col-name">
		<a class="affibloom-row-name" href="<?php echo esc_url( \Affibloom\Interface\Admin\ProductScreen::url( (int) $product->id ) ); ?>"><?php echo esc_html( $product->name ); ?></a>
		<span class="affibloom-row-host"><?php echo esc_html( $product->url->host() ); ?></span>
	</td>
	<td class="affibloom-col-actions">
		<a class="affibloom-icon-button" href="<?php echo esc_url( \Affibloom\Interface\Admin\ProductScreen::url( (int) $product->id ) ); ?>"
			aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name. */ __( 'Edit %s', 'affibloom' ), $product->name ) ); ?>">
			<span class="dashicons dashicons-edit" aria-hidden="true"></span>
		</a>
	</td>
</tr>
```

`dashboard.php` header row: `Image` before `Link Name`, and `Actions` after it, each as a `<th scope="col">` with classes `affibloom-col-image` and `affibloom-col-actions`. Image has a visually hidden label: `<span class="screen-reader-text">Image</span>`.

`ProductResource::summary()`, add:

```php
			'edit_url' => ProductScreen::url( (int) $product->id ),
```

`admin.js`:

```js
	/** After adding: a single new product opens its page, as in Lasso; several go back to the Dashboard. */
	function goToCreated( created ) {
		if ( created.length === 1 ) {
			window.location.assign( created[ 0 ].edit_url );
			return;
		}
		window.location.assign( wp.url.addQueryArgs( window.location.href, { affibloom_notice: 'created', count: created.length } ) );
	}
```

and in `duplicateNotice()`, link the existing product:

```js
		const link = document.createElement( 'a' );
		link.href = duplicate.existing.edit_url;
		link.textContent = __( 'Open the existing product', 'affibloom' );
		box.append( link );
```

`Plugin::modules()`: `new AdminMenu( new DashboardScreen( … ), new ProductScreen( $this->template(), $this->product_repository() ), new SettingsScreen( … ) )`.

`assets/admin/admin.css`, append:

```css
/* Product page */
.affibloom-back { margin: 0 0 8px; }
.affibloom-back a { color: var(--affibloom-muted); text-decoration: none; }
.affibloom-product { display: grid; grid-template-columns: minmax(0, 2fr) minmax(260px, 1fr); gap: 24px; align-items: start; }
.affibloom-product__side { display: grid; gap: 24px; }
.affibloom-divider { margin: 20px 0; border: 0; border-top: 1px solid var(--affibloom-border-light); }
.affibloom-editor { flex-basis: 100%; }
.affibloom-input--short { max-width: 120px; }
.affibloom-image-card { display: grid; gap: 12px; justify-items: center; }
.affibloom-image-preview { width: 100%; max-width: 260px; aspect-ratio: 1; object-fit: contain; border-radius: 12px; background: var(--affibloom-bg); }
.affibloom-field-error { flex-basis: 100%; margin: 4px 0 0; color: #b32d2e; font-size: 13px; }
[aria-invalid="true"] { border-color: #b32d2e !important; }

/* Dashboard columns */
.affibloom-col-image { width: 64px; }
.affibloom-col-actions { width: 120px; text-align: right; white-space: nowrap; }
.affibloom-thumb { display: block; width: 48px; height: 48px; object-fit: contain; border-radius: 8px; background: var(--affibloom-bg); }
.affibloom-icon-button { display: inline-grid; place-items: center; width: 32px; height: 32px; border-radius: 50%; color: var(--affibloom-muted); background: none; border: 0; cursor: pointer; text-decoration: none; }
.affibloom-icon-button:hover { background: var(--affibloom-purple-tint); color: var(--affibloom-purple); }
@media (max-width: 960px) { .affibloom-product { grid-template-columns: 1fr; } }
```

- [ ] **Step 7: Run and look**

```bash
composer lint && composer analyse && composer test:unit
npm run test:e2e -- acceptance/F1 -g AC1
```
Expected: it passes. Open a product beside `baselines/admin/product-details.png`. The fields should appear in the same order, with the same labels and help texts; the look and the page layout follow the design system (ADR-8), not the screenshot. Lasso's Permalink, Groups, Display Theme, Price and custom field builder are absent on purpose.

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "Add the product page with Lasso's Details layout

Every product field is shown in Lasso's order. A single new link now
opens its own page, and the Dashboard shows the image and an edit
action."
```


### Task 15 (superseded text): Saving the Details tab — `PATCH /affibloom/v1/products/{id}`

> **Do not build from this section.** Replaced by Tasks 15A-15D (deviation 70).

> **From Task 14B (2026-11-13):** WordPress's editor normalises some stored descriptions when it opens them (`rgb(20, 20, 20)` → `#141414`, `&nbsp;` → a space, attributes reordered; 2 of the 5 longest live ones). Save the description (and the price) only when the editor changed it (`tinymce.get(...).isDirty()` or a comparison with what was loaded), so an untouched product keeps Lasso's markup byte for byte. Also from 14H: the buttons list (`buttons[i][…]`) and `primary_position` (F29).

**Roadmap:** 2.2 · **Feature:** F1 (AC2), F9 (AC1)

**Why:** editing a product is what editors do most. Saving must validate every field the same way the REST route does for any other caller, and must re-apply the Amazon rules when the link changes.

**User action:** an editor changes the name, sets a second button URL and its text, chooses an image, writes a description and a rating, and clicks "Save Changes". A toast says "Yee haw! Your product saved." (Lasso's wording; U6 would change it). The title updates, and a pasted Amazon link shows in its clean, tagged form. An invalid secondary URL shows its error under the field, and nothing is saved.

**Request path:**
```
[Save Changes] → admin.js collects every [data-field] (+ TinyMCE content)
  → PATCH /affibloom/v1/products/123  { name, url, button_text, url2, …, rating, prime }
  → ProductsController::update()   permission Capabilities::PRODUCTS; args typed
  → ProductService::update( 123, input )
       ProductRepository::get()             → ProductNotFound (404)
       ProductInput::changes( input )       → ValidationFailed { field: reason } (422)
            url / url2 → AmazonLinkService::normalize()
       url changed → find_by_url_key()      → ValidationFailed { url: duplicate }
       ProductRepository::save( product->with( changes ) )
  ← 200 { data: ProductResource::full( product ) }
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `Application\Products\ProductInput` | `ProductService::update()` |
| `Application\Products\{ProductNotFound, ValidationFailed}` | `ProductService`, `ProductsController` |
| `ProductService::update()` | `ProductsController::update()` |
| route `PATCH /products/{id}` and its args | admin.js (this task and Task 16) |
| `ProductResource::full()` | the PATCH response |
| `to_meta()` for every field | `save()` |
| reasons `required`, `too_long`, `invalid_rating`, `duplicate` | `Messages` |
| admin.js: product form, image picker, field errors, toast | the product page |

**Files:**
- Create:
  - `src/Application/Products/{ProductInput,ProductNotFound,ValidationFailed}.php`
  - `tests/Unit/Application/ProductInputTest.php`, `tests/Integration/ProductsUpdateTest.php`
- Modify:
  - `ProductService.php`, `ProductsController.php`, `ProductResource.php`
  - `PostTypeProductRepository.php`, `Messages.php`, `Plugin.php`
  - `assets/admin/admin.js`

- [ ] **Step 1: Failing tests**

```bash
npm run test:e2e -- acceptance/F1 -g AC2
```

`tests/Integration/ProductsUpdateTest.php`:

```php
<?php
namespace Affibloom\Tests\Integration;

use WP_REST_Request;
use WP_UnitTestCase;

final class ProductsUpdateTest extends WP_UnitTestCase {
	private int $id;

	public function set_up(): void {
		parent::set_up();
		update_option( 'affibloom_settings', array( 'tracking_id' => 'vbvault-20' ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->id = self::factory()->post->create(
			array(
				'post_type'  => 'affibloom_product',
				'post_title' => 'Amazon',
				'meta_input' => array(
					'_affibloom_url'  => 'https://www.amazon.com/dp/B0C1234567?tag=vbvault-20',
					'_affibloom_type' => 'amazon',
					'_affibloom_asin' => 'B0C1234567',
				),
			)
		);
	}

	private function patch( array $body ) {
		$request = new WP_REST_Request( 'PATCH', "/affibloom/v1/products/{$this->id}" );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );
		return rest_get_server()->dispatch( $request );
	}

	public function test_saves_and_returns_every_field(): void {
		$response = $this->patch(
			array(
				'name'         => 'Mizuno Wave Momentum 3',
				'url2'         => 'https://www.amazon.com/dp/B0C7654321',
				'button_text2' => "Buy Now Women's",
				'rating'       => '4.5',
				'description'  => '<p>Light.</p><script>x</script>',
			)
		);

		self::assertSame( 200, $response->get_status() );
		$data = $response->get_data()['data'];
		self::assertSame( 'Mizuno Wave Momentum 3', get_the_title( $this->id ) );
		self::assertSame( 'https://www.amazon.com/dp/B0C7654321?tag=vbvault-20', $data['url2'] );
		self::assertSame( '4.5', $data['rating'] );
		self::assertSame( '<p>Light.</p>x', $data['description'] );
	}

	public function test_invalid_fields_save_nothing(): void {
		$response = $this->patch( array( 'name' => 'New', 'url2' => 'not a link', 'rating' => '9' ) );

		self::assertSame( 422, $response->get_status() );
		self::assertSame( array( 'url2', 'rating' ), array_keys( $response->get_data()['data']['fields'] ) );
		self::assertSame( 'Amazon', get_the_title( $this->id ) );
	}

	public function test_unknown_product_is_404(): void {
		$request = new WP_REST_Request( 'PATCH', '/affibloom/v1/products/999999' );
		self::assertSame( 404, rest_get_server()->dispatch( $request )->get_status() );
	}
}
```

- [ ] **Step 2: Route**

In `ProductsController::routes()`, add:

```php
		register_rest_route(
			self::NAMESPACE,
			'/products/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update' ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => $this->field_args(),
				),
			)
		);
```

and:

```php
	/**
	 * Argument types for product fields; ProductInput applies the real rules.
	 *
	 * @return array<string, array<string, string>>
	 */
	private function field_args(): array {
		$args = array();
		foreach ( array( 'name', 'url', 'button_text', 'url2', 'button_text2', 'image_url', 'badge', 'description', 'rating', 'disclosure' ) as $field ) {
			$args[ $field ] = array( 'type' => 'string' );
		}
		foreach ( array( 'new_tab', 'nofollow', 'new_tab2', 'nofollow2', 'show_disclosure', 'prime' ) as $field ) {
			$args[ $field ] = array( 'type' => 'boolean' );
		}
		return $args;
	}

	/**
	 * PATCH /products/{id}: changes only the fields sent.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update( WP_REST_Request $request ) {
		$input = array_intersect_key( $request->get_params(), $this->field_args() );

		try {
			$product = $this->products->update( (int) $request['id'], $input );
		} catch ( ProductNotFound $error ) {
			return new WP_Error( 'affibloom_not_found', __( 'This product does not exist.', 'affibloom' ), array( 'status' => 404 ) );
		} catch ( ValidationFailed $error ) {
			return $this->invalid( $error );
		}

		return new WP_REST_Response( array( 'data' => $this->product_resource->full( $product ) ) );
	}

	/**
	 * A 422 error with a message per field.
	 *
	 * @param ValidationFailed $error The failed fields.
	 */
	private function invalid( ValidationFailed $error ): WP_Error {
		return new WP_Error(
			'affibloom_invalid',
			__( 'Some fields need attention.', 'affibloom' ),
			array(
				'status' => 422,
				'fields' => array_map( array( Messages::class, 'for_reason' ), $error->errors ),
			)
		);
	}
```

with `use WP_Error;`, `use Affibloom\Application\Products\ProductNotFound;` and `use Affibloom\Application\Products\ValidationFailed;`.

- [ ] **Step 3: Service**

`src/Application/Products/ProductNotFound.php`:

```php
<?php
/**
 * No published product with that ID.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

use RuntimeException;

/**
 * No published product with that ID.
 */
final class ProductNotFound extends RuntimeException {}
```

`src/Application/Products/ValidationFailed.php`:

```php
<?php
/**
 * One or more fields were refused; nothing was saved.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

use RuntimeException;

/**
 * One or more fields were refused; nothing was saved.
 */
final class ValidationFailed extends RuntimeException {

	/**
	 * Builds the error.
	 *
	 * @param array<string, string> $errors Field => reason code.
	 */
	public function __construct( public readonly array $errors ) {
		parent::__construct( 'Invalid fields: ' . implode( ', ', array_keys( $errors ) ) );
	}
}
```

`ProductService`: the constructor takes a fourth argument, `private ProductInput $input`. Add:

```php
	/**
	 * Changes the fields given; all or nothing.
	 *
	 * @param int                  $id    Product ID.
	 * @param array<string, mixed> $input Field => value, as sent.
	 * @throws ProductNotFound  When there is no such product.
	 * @throws ValidationFailed When a field is refused.
	 */
	public function update( int $id, array $input ): Product {
		$product = $this->products->get( $id );
		if ( null === $product ) {
			throw new ProductNotFound( (string) $id ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- not shown to users.
		}

		$changed = $product->with( $this->input->changes( $input ) );

		if ( $changed->url_key() !== $product->url_key() && ! $this->settings->get()->bool( 'allow_duplicates' ) ) {
			$existing = $this->products->find_by_url_key( $changed->url_key() );
			if ( null !== $existing && $existing->id !== $product->id ) {
				throw new ValidationFailed( array( 'url' => 'duplicate' ) );
			}
		}

		return $this->products->save( $changed );
	}
```

`src/Application/Products/ProductInput.php`:

```php
<?php
/**
 * The rules for product fields sent from a form or the REST API.
 *
 * @package Affibloom
 */

declare( strict_types=1 );

namespace Affibloom\Application\Products;

use Affibloom\Domain\InvalidField;
use Affibloom\Domain\Product\AffiliateUrl;
use Affibloom\Domain\Product\Rating;

/**
 * The rules for product fields sent from a form or the REST API.
 */
final class ProductInput {

	/** Plain-text fields and their maximum length. */
	private const TEXT = array(
		'name'         => 200,
		'button_text'  => 60,
		'button_text2' => 60,
		'badge'        => 60,
		'disclosure'   => 500,
	);

	private const BOOLEANS = array( 'new_tab', 'nofollow', 'new_tab2', 'nofollow2', 'show_disclosure', 'prime' );

	/**
	 * Builds the rules.
	 *
	 * @param AmazonLinkService $links Link normalisation (Amazon tag).
	 */
	public function __construct( private AmazonLinkService $links ) {}

	/**
	 * Clean values for Product::with(), only for the fields present.
	 *
	 * @param array<string, mixed> $input Field => raw value.
	 * @return array<string, mixed>
	 * @throws ValidationFailed With every refused field at once.
	 */
	public function changes( array $input ): array {
		$changes = array();
		$errors  = array();

		foreach ( self::TEXT as $field => $max ) {
			if ( ! array_key_exists( $field, $input ) ) {
				continue;
			}
			$value = sanitize_text_field( (string) $input[ $field ] );
			if ( 'name' === $field && '' === $value ) {
				$errors[ $field ] = 'required';
			} elseif ( mb_strlen( $value ) > $max ) {
				$errors[ $field ] = 'too_long';
			} else {
				$changes[ $field ] = $value;
			}
		}

		foreach ( self::BOOLEANS as $field ) {
			if ( array_key_exists( $field, $input ) ) {
				$changes[ $field ] = (bool) $input[ $field ];
			}
		}

		try {
			if ( array_key_exists( 'url', $input ) ) {
				$link            = $this->links->normalize( (string) $input['url'], 'url' );
				$changes['url']  = $link->url;
				$changes['type'] = $link->type;
				$changes['asin'] = $link->asin;
			}
		} catch ( InvalidField $error ) {
			$errors['url'] = $error->reason;
		}

		try {
			if ( array_key_exists( 'url2', $input ) ) {
				$raw             = trim( (string) $input['url2'] );
				$changes['url2'] = '' === $raw ? null : $this->links->normalize( $raw, 'url2' )->url;
			}
		} catch ( InvalidField $error ) {
			$errors['url2'] = $error->reason;
		}

		try {
			if ( array_key_exists( 'image_url', $input ) ) {
				$raw                  = trim( (string) $input['image_url'] );
				$changes['image_url'] = '' === $raw ? '' : AffiliateUrl::from_string( $raw, 'image_url' )->value();
			}
		} catch ( InvalidField $error ) {
			$errors['image_url'] = $error->reason;
		}

		if ( array_key_exists( 'description', $input ) ) {
			$value = trim( wp_kses_post( (string) $input['description'] ) );
			if ( strlen( $value ) > 5000 ) {
				$errors['description'] = 'too_long';
			} else {
				$changes['description'] = $value;
			}
		}

		try {
			if ( array_key_exists( 'rating', $input ) ) {
				$changes['rating'] = Rating::from_string( (string) $input['rating'] );
			}
		} catch ( InvalidField $error ) {
			$errors['rating'] = $error->reason;
		}

		if ( $errors ) {
			throw new ValidationFailed( $errors );
		}
		return $changes;
	}
}
```

`ProductInput` is a separate class from `ProductService`, so the same rules can serve other callers. Task 26 (migration) is the second caller.

- [ ] **Step 4: Storage and the response**

`PostTypeProductRepository::to_meta()`, add:

```php
			'_affibloom_button_text'  => $product->button_text,
			'_affibloom_url2'         => $product->url2 ? $product->url2->value() : '',
			'_affibloom_button_text2' => $product->button_text2,
			'_affibloom_new_tab2'     => $product->new_tab2 ? '1' : '0',
			'_affibloom_nofollow2'    => $product->nofollow2 ? '1' : '0',
			'_affibloom_image_url'    => $product->image_url,
			'_affibloom_badge'        => $product->badge,
			'_affibloom_description'  => $product->description,
			'_affibloom_rating'       => $product->rating ? (string) $product->rating : '',
			'_affibloom_disclosure'   => $product->disclosure,
			'_affibloom_prime'        => $product->prime ? '1' : '0',
```

`ProductResource`, add:

```php
	/**
	 * Every field, for the product page and the block.
	 *
	 * @param Product $product A saved product.
	 * @return array<string, mixed>
	 */
	public function full( Product $product ): array {
		return $this->summary( $product ) + array(
			'type'            => $product->type->value,
			'asin'            => $product->asin,
			'button_text'     => $product->button_text,
			'url2'            => $product->url2 ? $product->url2->value() : '',
			'button_text2'    => $product->button_text2,
			'new_tab'         => $product->new_tab,
			'nofollow'        => $product->nofollow,
			'new_tab2'        => $product->new_tab2,
			'nofollow2'       => $product->nofollow2,
			'image_url'       => $product->image_url,
			'badge'           => $product->badge,
			'description'     => $product->description,
			'rating'          => $product->rating ? (string) $product->rating : '',
			'show_disclosure' => $product->show_disclosure,
			'disclosure'      => $product->disclosure,
			'prime'           => $product->prime,
		);
	}
```

`Messages::for_reason()`, add:

```php
			'required'       => __( 'This field is required.', 'affibloom' ),
			'too_long'       => __( 'This is too long.', 'affibloom' ),
			'invalid_rating' => __( 'Enter a rating from 0 to 5.', 'affibloom' ),
			'duplicate'      => __( 'Another product already uses this link.', 'affibloom' ),
```

`Plugin::modules()`: build `$links = new AmazonLinkService( … )` once, and pass `new ProductInput( $links )` to the service.

`tests/Unit/Application/ProductInputTest.php`:
- every refused field is reported together;
- `url2` '' clears the second button;
- an unknown field is ignored;
- booleans come from `true`/`false`.

Stub `sanitize_text_field` and `wp_kses_post` with Brain Monkey, as in Task 10.

- [ ] **Step 5: admin.js: the form**

Add, and call `initProductForm()` in the `DOMContentLoaded` handler:

```js
	// ── Toast (the JS twin of partials/toast.php) ──
	function toast( text ) {
		const el = document.createElement( 'div' );
		el.className = 'affibloom-toast';
		el.setAttribute( 'role', 'status' );
		el.textContent = text;
		document.querySelector( '.affibloom-admin' ).append( el );
		window.setTimeout( () => el.remove(), 4500 );
	}

	// ── Product page: send every [data-field]; show field errors under each field. ──
	function collect( form ) {
		if ( window.tinymce ) {
			window.tinymce.triggerSave();
		}
		const data = {};
		form.querySelectorAll( '[data-field]' ).forEach( ( el ) => {
			data[ el.dataset.field ] = el.type === 'checkbox' ? el.checked : el.value;
		} );
		const description = form.querySelector( 'textarea[name="description"]' );
		if ( description ) {
			data.description = description.value;
		}
		return data;
	}

	function clearErrors( form ) {
		form.querySelectorAll( '.affibloom-field-error' ).forEach( ( el ) => el.remove() );
		form.querySelectorAll( '[aria-invalid]' ).forEach( ( el ) => {
			el.removeAttribute( 'aria-invalid' );
			el.removeAttribute( 'aria-describedby' );
		} );
	}

	function showErrors( form, error ) {
		const fields = error?.data?.fields;
		if ( ! fields ) {
			toast( error.message || __( 'Something went wrong. Please try again.', 'affibloom' ) );
			return;
		}
		let first = null;
		Object.entries( fields ).forEach( ( [ field, text ] ) => {
			const input = form.querySelector( `[data-field="${ field }"]` ) || form.querySelector( `[name="${ field }"]` );
			if ( ! input ) {
				return;
			}
			const note = document.createElement( 'p' );
			note.className = 'affibloom-field-error';
			note.id = `affibloom-error-${ field }`;
			note.textContent = text;
			( input.closest( '.affibloom-field' ) || input.parentElement ).append( note );
			input.setAttribute( 'aria-invalid', 'true' );
			input.setAttribute( 'aria-describedby', note.id );
			first = first || input;
		} );
		first?.focus();
	}

	function chooseImage( onSelect ) {
		const frame = wp.media( { title: __( 'Choose an image', 'affibloom' ), multiple: false, library: { type: 'image' } } );
		frame.on( 'select', () => {
			const file = frame.state().get( 'selection' ).first().toJSON();
			onSelect( file.sizes?.large?.url ?? file.url );
		} );
		frame.open();
	}

	function initProductForm() {
		const form = document.getElementById( 'affibloom-product-form' );
		if ( ! form ) {
			return;
		}
		const save = document.getElementById( 'affibloom-save' );

		document.getElementById( 'affibloom-image-button' )?.addEventListener( 'click', () =>
			chooseImage( ( url ) => {
				document.getElementById( 'affibloom-image-url' ).value = url;
				document.getElementById( 'affibloom-image-preview' ).src = url;
			} )
		);

		form.addEventListener( 'submit', async ( event ) => {
			event.preventDefault();
			save.disabled = true;
			clearErrors( form );
			try {
				const { data } = await wp.apiFetch( { path: `/affibloom/v1/products/${ form.dataset.productId }`, method: 'PATCH', data: collect( form ) } );
				document.querySelector( '.affibloom-page__title' ).textContent = data.name;
				form.querySelector( '#affibloom-url' ).value = data.url;
				form.querySelector( '#affibloom-url2' ).value = data.url2;
				toast( __( 'Yee haw! Your product saved.', 'affibloom' ) );
			} catch ( error ) {
				showErrors( form, error );
			} finally {
				save.disabled = false;
			}
		} );
	}
```

If U7 is approved, add a `beforeunload` warning after the first `input` event in the form, cleared after each successful save. If U6 is approved, change the toast to `__( 'Product saved.', 'affibloom' )`.

- [ ] **Step 6: Run and look**

```bash
composer lint && composer analyse && composer test:unit && npm run test:integration
npm run test:e2e -- acceptance/F1 acceptance/F2 acceptance/F9
```
Expected: all pass. By hand:
1. Save a product with `B0C7654321` as the secondary URL. Expected: the field shows the tagged link.
2. Enter `9` as the rating. Expected: "Enter a rating from 0 to 5." under the field, focus moves there, and the title does not change.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "Save the product page through PATCH /products/{id}

All fields are validated together and nothing is saved when one is
refused. Changed links are normalised and checked for duplicates."
```

### Task 16: Dashboard quick edit — SUPERSEDED 2026-11-13

> **Do not build from this section.** Replaced by Tasks 16A-16C (deviation 76); see the note at Task 16 above.

### Task 16 (superseded text): Dashboard quick edit

**Roadmap:** 2.2 · **Feature:** F1

**Why:** Lasso lets an editor fix a name, a link or an image straight from the Dashboard row, without opening the product.

**User action:** on a Dashboard row, the editor clicks the quick-edit icon. The row opens a small form with Name, Primary Destination URL and the image ("Update Image"). The editor changes the name and clicks "Save Changes". The row shows the new name. "Cancel" closes the form without saving.

**Request path:**
```
row [Quick edit] → admin.js shows tr.affibloom-quick-row
  → PATCH /affibloom/v1/products/{id}  { name, url, image_url }   (Task 15's route: its second caller)
  ← 200 data → admin.js updates the row's name, host and image, closes the form
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| quick-edit icon and `tr.affibloom-quick-row` in `product-row.php` | the Dashboard |
| `initQuickEdit()` in admin.js | `DOMContentLoaded` |
| `host` in `ProductResource::summary()` | `initQuickEdit()` updates the row's host line |

**Files:**
- Modify: `templates/admin/partials/product-row.php`, `src/Interface/Rest/ProductResource.php`, `assets/admin/{admin.js,admin.css}`
- Create: `tests/E2E/quick-edit.spec.ts`

- [ ] **Step 1: Failing test**

`tests/E2E/quick-edit.spec.ts`:

```ts
import { test, expect } from '@playwright/test';
import { createProduct } from './utils';

test( 'quick edit renames a product from its Dashboard row', async ( { page } ) => {
	const id = createProduct( { name: 'Old name', url: 'https://example.com/q' } );
	await page.goto( '/wp-admin/admin.php?page=affibloom' );

	const row = page.locator( `tr[data-product-id="${ id }"]` );
	await row.getByRole( 'button', { name: 'Quick edit' } ).click();
	const form = page.locator( `tr.affibloom-quick-row[data-for="${ id }"]` );
	await form.getByLabel( 'Name' ).fill( 'New name' );
	await form.getByRole( 'button', { name: 'Save Changes' } ).click();

	await expect( row.locator( '.affibloom-row-name' ) ).toHaveText( 'New name' );
	await expect( form ).toBeHidden();
} );
```

- [ ] **Step 2: The row's form**

In `product-row.php`, inside `.affibloom-col-actions`, before the edit link:

```php
		<button type="button" class="affibloom-icon-button affibloom-quick-edit" aria-expanded="false" aria-controls="affibloom-quick-<?php echo esc_attr( (string) $product->id ); ?>">
			<span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
			<span class="screen-reader-text"><?php esc_html_e( 'Quick edit', 'affibloom' ); ?></span>
		</button>
```

and after the row's closing `</tr>`:

```php
<tr class="affibloom-quick-row" id="affibloom-quick-<?php echo esc_attr( (string) $product->id ); ?>" data-for="<?php echo esc_attr( (string) $product->id ); ?>" hidden>
	<td colspan="99">
		<form class="affibloom-quick-form" novalidate>
			<div class="affibloom-field affibloom-field--stack">
				<label class="affibloom-label" for="affibloom-quick-name-<?php echo esc_attr( (string) $product->id ); ?>"><?php esc_html_e( 'Name', 'affibloom' ); ?></label>
				<input type="text" class="affibloom-input" id="affibloom-quick-name-<?php echo esc_attr( (string) $product->id ); ?>" data-field="name" value="<?php echo esc_attr( $product->name ); ?>" maxlength="200">
			</div>
			<div class="affibloom-field affibloom-field--stack">
				<label class="affibloom-label" for="affibloom-quick-url-<?php echo esc_attr( (string) $product->id ); ?>"><?php esc_html_e( 'Primary Destination URL', 'affibloom' ); ?></label>
				<input type="text" class="affibloom-input" id="affibloom-quick-url-<?php echo esc_attr( (string) $product->id ); ?>" data-field="url" value="<?php echo esc_attr( $product->url->value() ); ?>">
			</div>
			<input type="hidden" data-field="image_url" value="<?php echo esc_attr( $product->image_url ); ?>">
			<div class="affibloom-quick-form__actions">
				<button type="button" class="affibloom-button affibloom-button--secondary affibloom-quick-image"><?php esc_html_e( 'Update Image', 'affibloom' ); ?></button>
				<button type="button" class="affibloom-button affibloom-button--secondary affibloom-quick-cancel"><?php esc_html_e( 'Cancel', 'affibloom' ); ?></button>
				<button type="submit" class="affibloom-button affibloom-button--primary"><?php esc_html_e( 'Save Changes', 'affibloom' ); ?></button>
			</div>
		</form>
	</td>
</tr>
```

The Dashboard now uses the media picker too, so `AdminAssets` calls `wp_enqueue_media()` on every `affibloom` screen except Settings: change the condition to `! str_contains( $hook_suffix, SettingsScreen::SLUG )`.

- [ ] **Step 3: admin.js**

```js
	// ── Dashboard quick edit: name, link and image, saved through the product route. ──
	function initQuickEdit() {
		document.querySelectorAll( '.affibloom-quick-edit' ).forEach( ( button ) => {
			const panel = document.getElementById( button.getAttribute( 'aria-controls' ) );
			const form = panel.querySelector( 'form' );
			const row = button.closest( 'tr' );
			const close = () => {
				panel.hidden = true;
				button.setAttribute( 'aria-expanded', 'false' );
				button.focus();
			};

			button.addEventListener( 'click', () => {
				panel.hidden = ! panel.hidden;
				button.setAttribute( 'aria-expanded', String( ! panel.hidden ) );
				if ( ! panel.hidden ) {
					form.querySelector( '[data-field="name"]' ).focus();
				}
			} );
			form.querySelector( '.affibloom-quick-cancel' ).addEventListener( 'click', close );
			form.querySelector( '.affibloom-quick-image' ).addEventListener( 'click', () =>
				chooseImage( ( url ) => {
					form.querySelector( '[data-field="image_url"]' ).value = url;
				} )
			);

			form.addEventListener( 'submit', async ( event ) => {
				event.preventDefault();
				clearErrors( form );
				try {
					const { data } = await wp.apiFetch( { path: `/affibloom/v1/products/${ panel.dataset.for }`, method: 'PATCH', data: collect( form ) } );
					row.querySelector( '.affibloom-row-name' ).textContent = data.name;
					row.querySelector( '.affibloom-row-host' ).textContent = data.host;
					if ( data.image_url && row.querySelector( 'img.affibloom-thumb' ) ) {
						row.querySelector( 'img.affibloom-thumb' ).src = data.image_url;
					}
					close();
					toast( __( 'Yee haw! Your product saved.', 'affibloom' ) );
				} catch ( error ) {
					showErrors( form, error );
				}
			} );
		} );
	}
```

`collect()` reads TinyMCE only when it exists, so it works for this form too. `ProductResource::summary()`, add `'host' => $product->url->host(),`.

`admin.css`:

```css
.affibloom-quick-row td { background: var(--affibloom-purple-tint); }
.affibloom-quick-form { display: grid; grid-template-columns: 1fr 1fr; gap: 0 24px; }
.affibloom-quick-form__actions { grid-column: 1 / -1; display: flex; gap: 8px; justify-content: flex-end; }
```

- [ ] **Step 4: Run, commit**

```bash
npm run test:e2e -- quick-edit
git add -A && git commit -m "Add Dashboard quick edit for name, link and image

It saves through the same product route as the product page, so the
rules are the same."
```

### Task 17: Deleting a product — SUPERSEDED 2026-11-13

> **Do not build from this section.** Replaced by Tasks 17A-17D (deviation 82); see the note at Task 17 above.

### Task 17 (superseded text): Deleting a product — `DELETE /affibloom/v1/products/{id}`

**Roadmap:** 2.2 · **Feature:** F1 (AC4)

**Why:** a product that is no longer needed must be removable, and recoverable for a while if it was deleted by mistake. Blocking the delete of a product that is in use comes in Task 36, when "in use" can be known.

**User action:** on a Dashboard row, the editor clicks Delete. A confirmation modal names the product. On confirm, the row disappears and a toast says "Product deleted." The product goes to the trash, which a database admin can restore from.

**Request path:**
```
row [Delete] → #affibloom-delete-modal (product name filled in)
  → DELETE /affibloom/v1/products/{id}
  → ProductsController::delete() → ProductService::delete( id )
       get() → ProductNotFound (404); ProductRepository::delete() → wp_trash_post()
  ← 200 { data: { id, deleted: true } } → row removed, toast
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `ProductRepository::delete()` | `ProductService::delete()` |
| `ProductService::delete()` | `ProductsController::delete()` |
| `DELETE` on `/products/{id}` | admin.js |
| delete button, `partials/delete-modal.php`, `initDelete()` | the Dashboard |

**Files:**
- Create: `templates/admin/partials/delete-modal.php`
- Modify:
  - `ProductRepository.php`, `PostTypeProductRepository.php`
  - `ProductService.php`, `ProductsController.php`
  - `product-row.php`, `dashboard.php`, `admin.js`

- [ ] **Step 1: Failing test**

```bash
npm run test:e2e -- acceptance/F1 -g AC4
```

- [ ] **Step 2: Route, service, repository**

In `ProductsController::routes()`, add a second endpoint to the `/products/(?P<id>\d+)` array:

```php
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
```

```php
	/**
	 * DELETE /products/{id}: moves the product to the trash.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete( WP_REST_Request $request ) {
		try {
			$this->products->delete( (int) $request['id'] );
		} catch ( ProductNotFound $error ) {
			return new WP_Error( 'affibloom_not_found', __( 'This product does not exist.', 'affibloom' ), array( 'status' => 404 ) );
		}
		return new WP_REST_Response(
			array(
				'data' => array(
					'id'      => (int) $request['id'],
					'deleted' => true,
				),
			)
		);
	}
```

`ProductService`:

```php
	/**
	 * Moves a product to the trash.
	 *
	 * @param int $id Product ID.
	 * @throws ProductNotFound When there is no such product.
	 */
	public function delete( int $id ): void {
		if ( null === $this->products->get( $id ) ) {
			throw new ProductNotFound( (string) $id ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
		$this->products->delete( $id );
	}
```

`ProductRepository`: `public function delete( int $id ): void;`. In `PostTypeProductRepository`:

```php
	/**
	 * Moves the product's post to the trash; its meta stays for a restore.
	 *
	 * @param int $id Product ID.
	 */
	public function delete( int $id ): void {
		wp_trash_post( $id );
	}
```

- [ ] **Step 3: UI**

In `product-row.php`, in Actions, after the edit link:

```php
		<button type="button" class="affibloom-icon-button affibloom-delete" data-affibloom-open="affibloom-delete-modal"
			data-product-id="<?php echo esc_attr( (string) $product->id ); ?>" data-product-name="<?php echo esc_attr( $product->name ); ?>">
			<span class="dashicons dashicons-trash" aria-hidden="true"></span>
			<span class="screen-reader-text"><?php esc_html_e( 'Delete', 'affibloom' ); ?></span>
		</button>
```

`templates/admin/partials/delete-modal.php`:

```php
<?php
/**
 * Delete confirmation.
 *
 * @package Affibloom
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="affibloom-delete-modal" class="affibloom-modal" role="dialog" aria-modal="true" aria-labelledby="affibloom-delete-title" hidden>
	<div class="affibloom-modal__backdrop" data-affibloom-close></div>
	<div class="affibloom-modal__panel">
		<h2 id="affibloom-delete-title" class="affibloom-modal__title"><?php esc_html_e( 'Delete this product?', 'affibloom' ); ?></h2>
		<p class="affibloom-modal__intro affibloom-delete-name"></p>
		<div class="affibloom-modal__actions">
			<button type="button" class="affibloom-button affibloom-button--secondary" data-affibloom-close><?php esc_html_e( 'Cancel', 'affibloom' ); ?></button>
			<button type="button" class="affibloom-button affibloom-button--danger" id="affibloom-delete-confirm"><?php esc_html_e( 'Delete', 'affibloom' ); ?></button>
		</div>
	</div>
</div>
```

Render it in `dashboard.php` beside the add modal. The wording must match Lasso's `modals/url-delete.php` text recorded in Task 6.

`admin.js`:

```js
	// ── Delete: the modal names the product; the row goes on success. ──
	function initDelete() {
		const modal = document.getElementById( 'affibloom-delete-modal' );
		if ( ! modal ) {
			return;
		}
		let current = null;
		document.addEventListener( 'click', ( event ) => {
			const button = event.target.closest( '.affibloom-delete' );
			if ( button ) {
				current = button;
				modal.querySelector( '.affibloom-delete-name' ).textContent = button.dataset.productName;
			}
		} );
		modal.querySelector( '#affibloom-delete-confirm' ).addEventListener( 'click', async () => {
			if ( ! current ) {
				return;
			}
			try {
				await wp.apiFetch( { path: `/affibloom/v1/products/${ current.dataset.productId }`, method: 'DELETE' } );
				const row = current.closest( 'tr' );
				row.nextElementSibling?.classList.contains( 'affibloom-quick-row' ) && row.nextElementSibling.remove();
				row.remove();
				modal.hidden = true;
				toast( __( 'Product deleted.', 'affibloom' ) );
			} catch ( error ) {
				modal.hidden = true;
				toast( error.message );
			}
		} );
	}
```

The click listener for `.affibloom-delete` is added before the generic `[data-affibloom-open]` handler runs. Both are on `document`, and both run, so the name is set and then the modal opens.

- [ ] **Step 4: Run, look, commit**

```bash
composer lint && composer analyse && npm run test:integration
npm run test:e2e -- acceptance/F1
```
Expected: F1 AC1–AC4 pass. Now compare the whole Dashboard with `baselines/admin/dashboard.png` for its columns, labels, actions and their order. The Alert, Locations and Clicks columns are still missing (Tasks 31, 34 and 36). Everything else should be present in the same order; the look follows the design system (ADR-8).

```bash
git add -A && git commit -m "Delete products from the Dashboard, to the trash

A confirmation names the product. Blocking the delete of a product in
use arrives with the usage index."
```
