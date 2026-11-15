// The capture matrix of Task 18D (F5 D10, AC7): which Lasso Single boxes tools/baselines-themes.spec.ts draws on
// staging, under which Display settings, and the files each one becomes in tests/fixtures/baselines/themes/.
// F5 AC7 reads the same list, so the tool and the test cannot disagree about what must exist.
//
// A cell is one box: `[lasso id=… theme=… <attributes>]` in a draft post (deviation 137: Lasso's box attributes
// set the theme and the state, so no product is edited). A group is one set of Display settings; every group starts
// from staging's own settings, changes only what it names, and the tool puts staging's settings back after the run.

export const THEMES = [ 'Cactus', 'Cutter', 'Flow', 'Geek', 'Lab', 'Llama', 'Money', 'Splash' ] as const;
export type Theme = ( typeof THEMES )[ number ];

// One theme per box layout (R5): Cactus 6-box, Cutter 2-box, Lab 3-box. The layout, density and element groups
// draw these three; the CSS those groups check is keyed on the layout family (R57).
export const FAMILIES: readonly Theme[] = [ 'Cactus', 'Cutter', 'Lab' ];

/**
 * Which product a cell draws: `amazon` is an existing staging product (the `amazon` example of
 * tests/fixtures/baselines/boxes.json, read, never changed: it has Lasso's cached price, Prime and update time);
 * `fields` and `copy` are throwaway products the tool makes and deletes (a link to a staging page).
 */
export type ProductRef = 'amazon' | 'fields' | 'copy';

// Placeholders the tool fills from the amazon example's values in boxes.json.
const PROS = 'Light and quick off the floor.-br-A wide, stable base for lateral moves.';
const CONS = 'Runs half a size small.';
const SECOND = { secondary_url: '{AMAZON_URL}', secondary_text: 'Check Price' };
const LIKE_AMAZON = { title: '{AMAZON_TITLE}', description: '{AMAZON_DESCRIPTION}', image_url: '{AMAZON_IMAGE}', price: '$89.99', prime: 'true' };

export const STATES: Record< string, { product: ProductRef; atts: Record< string, string > } > = {
	plain: { product: 'amazon', atts: { badge: 'hide' } },
	badge: { product: 'amazon', atts: { badge: 'Our Pick' } },
	'two-buttons': { product: 'amazon', atts: { badge: 'hide', ...SECOND } },
	rating: { product: 'amazon', atts: { badge: 'hide', rating: '4.5' } },
	'pros-cons': { product: 'amazon', atts: { badge: 'hide', pros: PROS, cons: CONS } },
	// Every field type a Single box prints (field-row.php): text, textarea, number, rating, both lists, Copy to
	// Clipboard, plus Pros and Cons; the Primary Rating through the attribute.
	'all-fields': { product: 'fields', atts: { badge: 'hide', rating: '4.5', ...LIKE_AMAZON } },
	'no-image': { product: 'amazon', atts: { badge: 'hide', image_url: 'hide' } },
	'copy-field': { product: 'copy', atts: { badge: 'hide', ...LIKE_AMAZON } },
	// Every element present (the layout, density and element groups): badge, both buttons, rating, Pros & Cons.
	full: { product: 'amazon', atts: { badge: 'Our Pick', rating: '4.5', pros: PROS, cons: CONS, ...SECOND } },
	// As full, with the product's own badge (no attribute), for the hidden Badge element (R62).
	'full-own-badge': { product: 'amazon', atts: { rating: '4.5', pros: PROS, cons: CONS, ...SECOND } },
	sitestripe: { product: 'amazon', atts: { sitestripe: 'true' } },
};

/** The fields the tool creates (title `Affibloom capture <type>`) and the values the `fields` product gets. */
export const FIELDS: { type: string; value: string }[] = [
	{ type: 'text', value: 'Breathable mesh upper' },
	{ type: 'textarea', value: 'Cushioned for long tournament days.' },
	{ type: 'number', value: '12' },
	{ type: 'rating', value: '4' },
	{ type: 'bulleted_list', value: 'Gum rubber outsole\nReinforced toe\nRemovable insole' },
	{ type: 'numbered_list', value: 'Lace up firmly\nWarm up\nPlay' },
	{ type: 'copy_clipboard', value: 'VBVAULT10' },
];
/** Lasso's own Pros and Cons fields (ids 2 and 3), filled on the `fields` product. */
export const BUILT_IN_FIELDS: { id: number; value: string }[] = [
	{ id: 2, value: 'Light and quick off the floor.\nA wide, stable base for lateral moves.' },
	{ id: 3, value: 'Runs half a size small.' },
];

const ELEMENT_KEYS = [ 'badge', 'image', 'title', 'price', 'prime', 'desc', 'proscons', 'primary', 'secondary', 'timestamp' ] as const;
const ALL_ON = Object.fromEntries( ELEMENT_KEYS.map( ( k ) => [ k, true ] ) ) as Record< string, boolean >;

/**
 * What a group changes in staging's Single Display settings. `elements` replaces the Single element map,
 * `layout` and `density` are merged over staging's Single bucket, `reset` is Lasso's "Reset display settings"
 * (every Display value at Lasso's defaults: classic rendering, R53, R64).
 */
export type GroupSettings = Partial< {
	elements: Record< string, boolean >;
	layout: Record< string, unknown >;
	density: { preset: string; values?: Record< string, number > };
	keep_site_stripe_ui: boolean;
	reset: true;
} >;

export type Group = { id: string; settings: GroupSettings; themes: readonly Theme[]; states: string[] };

const SEVEN = [ 'plain', 'badge', 'two-buttons', 'rating', 'pros-cons', 'all-fields', 'no-image' ];

// One key at a time, from staging's Single layout (side-by-side, image left, left aligned, buttons spread, full-width
// buttons, contain). The last is a phone override (R56), seen at 390 px.
const LAYOUTS: [ string, Record< string, unknown > ][] = [
	[ 'stacked', { orientation: 'stacked', imagePosition: 'top' } ],
	[ 'stacked-image-bottom', { orientation: 'stacked', imagePosition: 'bottom' } ],
	[ 'image-right', { imagePosition: 'right' } ],
	[ 'align-center', { align: 'center' } ],
	[ 'align-right', { align: 'right' } ],
	[ 'buttons-left', { buttonAlign: 'left' } ],
	[ 'buttons-center', { buttonAlign: 'center' } ],
	[ 'buttons-right', { buttonAlign: 'right' } ],
	[ 'buttons-hug', { ctaWidth: 'hug' } ],
	[ 'image-cover', { imageFit: 'cover' } ],
	[ 'phone-override', { mobile: { orientation: 'side-by-side', imagePosition: 'left', buttonAlign: 'right', ctaWidth: 'hug' } } ],
];

const DENSITIES: [ string, GroupSettings[ 'density' ] ][] = [
	[ 'compact', { preset: 'compact' } ],
	[ 'balanced', { preset: 'balanced' } ],
	[ 'spacious', { preset: 'spacious' } ],
	// Custom values off every preset, with both line clamps (R58, R59).
	[ 'custom', { preset: 'custom', values: { gap: 8, padding: 12, spaceBetween: 6, titleSize: 20, titleLines: 1, descSize: 14, descLines: 2, priceSize: 22, buttonSize: 18, imageHeight: 200 } } ],
];

export const GROUPS: Group[] = [
	// Staging's settings as readers see them (Lab's settings, advanced rendering), every theme and state.
	{ id: 'site', settings: {}, themes: THEMES, states: SEVEN },
	// The same with every element on, so price, timestamp, Pros & Cons and fields show; plus the Copy field.
	{ id: 'elements-on', settings: { elements: ALL_ON }, themes: THEMES, states: [ ...SEVEN, 'copy-field' ] },
	// Lasso's defaults: classic rendering (no v2 wrapper). image_url="hide" only hides in advanced rendering.
	{ id: 'classic', settings: { reset: true }, themes: THEMES, states: SEVEN.filter( ( s ) => s !== 'no-image' ) },
	...LAYOUTS.map( ( [ id, layout ] ): Group => ( { id: `layout-${ id }`, settings: { elements: ALL_ON, layout }, themes: FAMILIES, states: [ 'full' ] } ) ),
	...DENSITIES.map( ( [ id, density ] ): Group => ( { id: `density-${ id }`, settings: { elements: ALL_ON, density }, themes: FAMILIES, states: [ 'full' ] } ) ),
	// Each element off alone (R60-R63): the badge attribute beats a hidden Badge (full), the product's own badge does
	// not (full-own-badge); the rating stays with Pros & cons off.
	...ELEMENT_KEYS.map( ( key ): Group => ( {
		id: `element-${ key }-off`,
		settings: { elements: { ...ALL_ON, [ key ]: false } },
		themes: FAMILIES,
		states: key === 'badge' ? [ 'full', 'full-own-badge' ] : [ 'full' ],
	} ) ),
	// "Keep Site Stripe UI" on (R13); the SiteStripe card has no theme.
	{ id: 'sitestripe', settings: { keep_site_stripe_ui: true }, themes: [ 'Lab' ], states: [ 'sitestripe' ] },
];

export type Cell = { group: string; theme: Theme; state: string; id: string; base: string };

export function cells( group: Group ): Cell[] {
	return group.themes.flatMap( ( theme ) =>
		group.states.map( ( state ) => ( {
			group: group.id,
			theme,
			state,
			id: `cell-${ theme }-${ state }`,
			base: `themes/${ group.id }/${ theme }-${ state }`,
		} ) )
	);
}

/** Every file the capture writes, relative to tests/fixtures/baselines/: a desktop and a phone PNG and the HTML. */
export function matrixFiles(): string[] {
	return GROUPS.flatMap( cells ).flatMap( ( c ) => [ `${ c.base }-desktop.png`, `${ c.base }-mobile.png`, `${ c.base }.html` ] );
}
