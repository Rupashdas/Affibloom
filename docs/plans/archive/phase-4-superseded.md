# Affibloom Phase 4: superseded tasks (history only)

Moved here on 2026-11-14 when Phase 4 was rewritten as "Editors" (feature F4, `docs/features/F4/`). This file is history
only: do not work from it. The text below is the old Phase 4 Tasks 23 and 24, word for word, with the old phase's exit
check at the end. Replacements: old **Task 23** (insert a product box) is replaced by **Tasks 23A-23C** (chooser,
product search, insert and preview; its `@wordpress/scripts` build step was carried into 23A Step 2, and its `GET
/products?search=` route into 23B). Old **Task 24** (sidebar: badge, description, edit link) is replaced by **Task 24A**
(Customize Display sidebar, which holds badge and description among all the other overrides) and **Task 24D** (Edit
Display, the product link). The old "Decisions from Task 6" bullet (U1) and the ADR-8 note stayed in the new phase file.

---

### Task 23: Inserting a product box in the editor

**Roadmap:** 4.1, 4.2 · **Feature:** F4 (AC1, AC2)

**Why:** this is how every box on the site is added. The flow follows Lasso's block: insert, "Choose a Display Type" (Single is the only one), search, Add.

**User action:**
1. An editor inserts the "Product box" block and clicks "Add product box".
2. The "Choose a Display Type" modal opens; the editor picks Single.
3. The editor types "mizuno" in "Search products" and clicks Add on a result.
4. The editor sees the real box in the editor. A toolbar button, "Change product", opens the search again. Publishing shows the box on the page.

**Request path:**
```
Editor → blocks/product-box/edit.js
  → modal step "type" (Lasso's step; U1 would start at "search")
  → GET /affibloom/v1/products?search=mizuno     permission Capabilities::INSERT_BOXES (edit_posts)
       ProductsController::index() → ProductService::search( term, 20 )
  ← { data: [ { id, name, url, host, image_url, edit_url } ] }
  → setAttributes( { productId } ) → <ServerSideRender> → /wp/v2/block-renderer → ProductBoxBlock::render()
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `@wordpress/scripts` build; `blocks/product-box/{index.js,edit.js,editor.css}` | the block editor |
| `editorScript`, `editorStyle` in `block.json` | WordPress |
| `GET /products` (search), `ProductsController::index()` | `edit.js` |
| `ProductService::search()` | `index()` |
| `Capabilities::INSERT_BOXES` | the search route |
| `image_url` in `ProductResource::summary()` | the search results |

**Files:**
- Create: `blocks/product-box/{index.js,edit.js,editor.css}`, `tests/E2E/block.spec.ts`
- Modify:
  - `package.json`, `blocks/product-box/block.json`
  - `ProductBoxBlock.php` (register from `build/product-box`)
  - `ProductsController.php`, `ProductService.php`, `ProductResource.php`, `Capabilities.php`, `Plugin.php`
  - `.github/workflows/ci.yml` (build before the tests)

- [ ] **Step 1: Failing test**

```bash
npm run test:e2e -- acceptance/F4
```

- [ ] **Step 2: Build tooling**

```bash
npm install --save-dev @wordpress/scripts
```

`package.json`, `scripts`:

```json
    "build": "wp-scripts build --webpack-src-dir=blocks --output-path=build",
    "start": "wp-scripts start --webpack-src-dir=blocks --output-path=build",
    "lint:js": "wp-scripts lint-js blocks assets",
    "test:js": "wp-scripts test-unit-js blocks"
```

The CI job runs `npm run build` after `npm ci`. Add the step. `build/` stays gitignored. The release zip (Task 39) is built from a fresh build.

`ProductBoxBlock` is now registered from the built folder: `Plugin` passes `dirname( $this->file ) . '/build/product-box'`.

`block.json`, add:

```json
  "editorScript": "file:./index.js",
  "editorStyle": [ "affibloom-box", "file:./index.css" ]
```

`affibloom-box` is the front-end stylesheet that `BoxAssets` registered, colour variables included. The editor iframe gets it through this line.

- [ ] **Step 3: Route: search**

`Capabilities.php`:

```php
	/** Insert product boxes in the editor (search products). Authors and up. */
	public const INSERT_BOXES = 'edit_posts';
```

`ProductsController::routes()`, add to the `/products` array:

```php
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'index' ),
					'permission_callback' => static fn(): bool => current_user_can( Capabilities::INSERT_BOXES ),
					'args'                => array(
						'search' => array(
							'type'    => 'string',
							'default' => '',
						),
					),
				),
```

```php
	/**
	 * GET /products?search=…: products for the block's picker.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function index( WP_REST_Request $request ): WP_REST_Response {
		$products = $this->products->search( sanitize_text_field( (string) $request['search'] ), 20 );
		return new WP_REST_Response( array( 'data' => array_map( array( $this->product_resource, 'summary' ), $products ) ) );
	}
```

`ProductService`:

```php
	/**
	 * Up to $limit products matching a name, newest first.
	 *
	 * @param string $term  Search text.
	 * @param int    $limit Maximum results.
	 * @return list<Product>
	 */
	public function search( string $term, int $limit ): array {
		return $this->products->search( $term, 1, $limit )->items;
	}
```

`ProductResource::summary()`, add `'image_url' => $product->image_url,`.

- [ ] **Step 4: The editor code**

`blocks/product-box/index.js`:

```js
import { registerBlockType } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit';
import './editor.css';

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null, // dynamic: the server renders the box on every view
} );
```

`blocks/product-box/edit.js`:

```js
import { __, sprintf } from '@wordpress/i18n';
import { useBlockProps, BlockControls } from '@wordpress/block-editor';
import { Button, Modal, Placeholder, SearchControl, Spinner, ToolbarButton, ToolbarGroup } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import ServerSideRender from '@wordpress/server-side-render';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

/** Lasso's flow: Choose a Display Type → search → Add. If U1 is approved, start at 'search'. */
const FIRST_STEP = 'type';

function ProductPicker( { onClose, onPick } ) {
	const [ step, setStep ] = useState( FIRST_STEP );
	const [ term, setTerm ] = useState( '' );
	const [ results, setResults ] = useState( null );

	useEffect( () => {
		if ( step !== 'search' ) {
			return undefined;
		}
		const controller = new AbortController();
		setResults( null );
		const timer = setTimeout( () => {
			apiFetch( { path: addQueryArgs( '/affibloom/v1/products', { search: term } ), signal: controller.signal } )
				.then( ( response ) => setResults( response.data ) )
				.catch( ( error ) => {
					if ( error.name !== 'AbortError' ) {
						setResults( [] );
					}
				} );
		}, 250 );
		return () => {
			clearTimeout( timer );
			controller.abort();
		};
	}, [ step, term ] );

	return (
		<Modal
			title={ step === 'type' ? __( 'Choose a Display Type', 'affibloom' ) : __( 'Search products', 'affibloom' ) }
			onRequestClose={ onClose }
			className="affibloom-block-modal"
		>
			{ step === 'type' ? (
				<div className="affibloom-display-types">
					<Button variant="secondary" className="affibloom-display-type" onClick={ () => setStep( 'search' ) }>
						{ __( 'Single', 'affibloom' ) }
					</Button>
				</div>
			) : (
				<>
					<SearchControl label={ __( 'Search products', 'affibloom' ) } value={ term } onChange={ setTerm } __nextHasNoMarginBottom />
					{ results === null && <Spinner /> }
					{ results?.length === 0 && <p>{ __( 'No products found.', 'affibloom' ) }</p> }
					{ results?.length > 0 && (
						<ul className="affibloom-results">
							{ results.map( ( product ) => (
								<li key={ product.id } className="affibloom-result">
									{ product.image_url ? <img src={ product.image_url } alt="" width="40" height="40" /> : <span className="affibloom-result__noimg" /> }
									<span className="affibloom-result__name">
										{ product.name }
										<small>{ product.host }</small>
									</span>
									<Button
										variant="primary"
										onClick={ () => onPick( product.id ) }
										/* translators: %s: product name. */
										label={ sprintf( __( 'Add %s', 'affibloom' ), product.name ) }
									>
										{ __( 'Add', 'affibloom' ) }
									</Button>
								</li>
							) ) }
						</ul>
					) }
				</>
			) }
		</Modal>
	);
}

export default function Edit( { attributes, setAttributes } ) {
	const [ picking, setPicking ] = useState( false );
	const blockProps = useBlockProps();
	const pick = ( productId ) => {
		setAttributes( { productId } );
		setPicking( false );
	};

	return (
		<div { ...blockProps }>
			{ attributes.productId ? (
				<>
					<BlockControls>
						<ToolbarGroup>
							<ToolbarButton icon="update" label={ __( 'Change product', 'affibloom' ) } onClick={ () => setPicking( true ) } />
						</ToolbarGroup>
					</BlockControls>
					<ServerSideRender block="affibloom/product-box" attributes={ attributes } />
				</>
			) : (
				<Placeholder icon="cart" label={ __( 'Product box', 'affibloom' ) } instructions={ __( 'Choose a product to show as a box.', 'affibloom' ) }>
					<Button variant="primary" onClick={ () => setPicking( true ) }>
						{ __( 'Add product box', 'affibloom' ) }
					</Button>
				</Placeholder>
			) }
			{ picking && <ProductPicker onClose={ () => setPicking( false ) } onPick={ pick } /> }
		</div>
	);
}
```

`blocks/product-box/editor.css`:

```css
.affibloom-block-modal { width: min(640px, 92vw); }
.affibloom-display-types { display: flex; gap: 12px; }
.affibloom-display-type { min-width: 120px; justify-content: center; }
.affibloom-results { margin: 12px 0 0; padding: 0; list-style: none; }
.affibloom-result { display: flex; align-items: center; gap: 12px; padding: 8px 0; border-bottom: 1px solid #e2e2e2; }
.affibloom-result img, .affibloom-result__noimg { width: 40px; height: 40px; object-fit: contain; border-radius: 6px; background: #fafafd; }
.affibloom-result__name { flex: 1; }
.affibloom-result__name small { display: block; color: #6c757d; }
```

A box whose product no longer exists renders `''`. In the editor, `ServerSideRender` then shows its own empty-response notice, and the toolbar's "Change product" fixes it.

- [ ] **Step 5: Run, look, commit**

```bash
npm run build
composer lint && npm run lint:js && npm run test:integration
npm run test:e2e -- acceptance/F4
```
Compare the modal with `baselines/admin/block-display-type.png` and `block-search.png` for its labels and steps only (U1 removes the display-type step); its look follows the design system (ADR-8).

```bash
git add -A && git commit -m "Insert product boxes from the block editor, as in Lasso

Choose a Display Type, search, Add, then the real box renders in the
editor. Search is open to anyone who can edit posts."
```

---

### Task 24: Block sidebar — badge, description, edit link

**Roadmap:** 4.2 · **Features:** F4, F7

**Why:** per-box text (Task 20) needs controls in the editor, and editors need a quick way from a box to its product.

**User action:** with a product box selected, the editor sees a sidebar with two panels:
- **Product:** the product's name and an "Edit product" link, which opens the product page in a new tab.
- **Box text:** "Badge", which is empty to use the product's badge, with a "Hide badge" toggle; and "Description", with a "Hide description" toggle.

The editor preview updates as they type.

**Request path:**
```
edit.js → GET /affibloom/v1/products/{id}   (permission INSERT_BOXES) → ProductResource::full() + edit_url
InspectorControls → setAttributes( { badge, hideBadge, description, hideDescription } ) → ServerSideRender
```

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| `GET /products/{id}`, `ProductsController::show()` | `edit.js` sidebar |
| `InspectorControls` panels in `edit.js` | the editor |

**Files:** modify `ProductsController.php`, `blocks/product-box/edit.js`; create `blocks/product-box/edit.test.js`.

- [ ] **Step 1: Failing test**

`tests/E2E/block.spec.ts`: select an inserted box, type "Best Overall" in Badge, expect the editor preview's `.affibloom-badge-text` to read "Best Overall", publish, then expect the same on the page.

- [ ] **Step 2: Route**

Add a `READABLE` endpoint to `/products/(?P<id>\d+)`, with permission `INSERT_BOXES`:

```php
	/**
	 * GET /products/{id}: one product, for the block sidebar.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function show( WP_REST_Request $request ) {
		$product = $this->products->get( (int) $request['id'] );
		if ( null === $product ) {
			return new WP_Error( 'affibloom_not_found', __( 'This product does not exist.', 'affibloom' ), array( 'status' => 404 ) );
		}
		return new WP_REST_Response( array( 'data' => $this->product_resource->full( $product ) ) );
	}
```

`ProductService::get( int $id ): ?Product` returns `$this->products->get( $id )`. That is its first caller.

- [ ] **Step 3: Sidebar**

In `edit.js`, import `InspectorControls` from `@wordpress/block-editor`, and `PanelBody`, `TextControl`, `TextareaControl`, `ToggleControl` and `ExternalLink` from `@wordpress/components`. Add the sidebar inside the selected-product branch:

```js
function ProductPanel( { productId } ) {
	const [ product, setProduct ] = useState( null );
	useEffect( () => {
		apiFetch( { path: `/affibloom/v1/products/${ productId }` } )
			.then( ( response ) => setProduct( response.data ) )
			.catch( () => setProduct( false ) );
	}, [ productId ] );

	if ( product === null ) {
		return <Spinner />;
	}
	if ( product === false ) {
		return <p>{ __( 'This product no longer exists.', 'affibloom' ) }</p>;
	}
	return (
		<>
			<p><strong>{ product.name }</strong></p>
			<ExternalLink href={ product.edit_url }>{ __( 'Edit product', 'affibloom' ) }</ExternalLink>
		</>
	);
}
```

```jsx
					<InspectorControls>
						<PanelBody title={ __( 'Product', 'affibloom' ) }>
							<ProductPanel productId={ attributes.productId } />
						</PanelBody>
						<PanelBody title={ __( 'Box text', 'affibloom' ) }>
							<TextControl
								label={ __( 'Badge', 'affibloom' ) }
								help={ __( "Leave empty to use the product's badge.", 'affibloom' ) }
								value={ attributes.badge }
								onChange={ ( badge ) => setAttributes( { badge } ) }
								disabled={ attributes.hideBadge }
								__nextHasNoMarginBottom
							/>
							<ToggleControl label={ __( 'Hide badge', 'affibloom' ) } checked={ attributes.hideBadge } onChange={ ( hideBadge ) => setAttributes( { hideBadge } ) } __nextHasNoMarginBottom />
							<TextareaControl
								label={ __( 'Description', 'affibloom' ) }
								help={ __( "Leave empty to use the product's description.", 'affibloom' ) }
								value={ attributes.description }
								onChange={ ( description ) => setAttributes( { description } ) }
								disabled={ attributes.hideDescription }
								__nextHasNoMarginBottom
							/>
							<ToggleControl label={ __( 'Hide description', 'affibloom' ) } checked={ attributes.hideDescription } onChange={ ( hideDescription ) => setAttributes( { hideDescription } ) } __nextHasNoMarginBottom />
						</PanelBody>
					</InspectorControls>
```

A per-box description is plain text with line breaks, as Lasso's `description` attribute is. `BoxBuilder` passes it through `wpautop()` only when it contains no HTML tags. Add that line in `BoxBuilder::build()`, and its unit test, in this task.

`blocks/product-box/edit.test.js`: a Jest test that `ProductPicker` starts on the "Choose a Display Type" step and moves to search when "Single" is clicked. Use `@testing-library/react` from `@wordpress/scripts`.

- [ ] **Step 4: Run, commit**

```bash
npm run build && npm run test:js && npm run test:e2e -- block acceptance/F4 acceptance/F7
git add -A && git commit -m "Add the block sidebar: product link, badge and description

Each box can override or hide its badge and description; the product
can be opened from the box in one click."
```

**Phase 4 exit check:** F4 passes. A box inserted with the block matches the Lasso baseline for the same product (rerun `box-variants`, which uses the same render path).

---

# Dropped 2026-11-14 by the user's decision A10

### Task MCP1: AI-agent integration (placeholder, not scheduled)

**Feature:** F4 (R76-R79, N5)

**Why:** Lasso registers MCP routes that its cloud calls with a signing secret, one of which writes `post_content`.
Affibloom builds none of them; this placeholder records that a future AI-agent integration would be a new, read-only
design.

**Depends on:** the user's decision on N5; nothing else.

**User action:** none. Not built unless the user asks.

**What this task creates, and who calls it:**

| New | Called by |
|---|---|
| nothing | nothing |

**Files:** none until a design is approved.

- [ ] **Step 1: Failing test.** Written only when the user asks and a design exists.
- [ ] **Step 2: Run and look.** A route called and its answer seen.
- [ ] **Step 3: Commit** — "Add a read-only AI-agent integration" (message to be fixed with the design).
