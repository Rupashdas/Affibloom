=== Affibloom ===
Contributors: rupash
Tags: affiliate, amazon, product box, comparison table, affiliate links
Requires at least: 7.1
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

An affiliate product library: product boxes, buttons, grids, lists and comparison tables, with link checks and click counts.

== Description ==

Affibloom keeps your affiliate products in one library and draws them in your posts.

* **Products:** add a link from any merchant, several at once, or search Amazon. Give each product buttons, a picture, a badge, a description, features, a rating and your own custom fields.
* **Displays:** a product box in eight themes, a button, an image, a text link, a field's value, a group as a grid, list or gallery, and comparison tables. Place them with the Product box block, the `[affibloom]` shortcode, the classic editor's button or the Elementor widget.
* **Link health:** a daily check of every link; broken and out-of-stock alerts on the Dashboard.
* **Clicks:** counted on your own site, per product, post and button, without personal data; optional Google Analytics events.
* **Where used:** a link index of every post that uses a product, and the opportunities to monetize links you already have.
* **Amazon:** your tracking ID in every Amazon link, automatic products for Amazon links in your content, and product data, prices and stock through the Amazon Creators API (credentials from your Associates account).
* **Cloaked links** on your own domain, groups, URL variants, saved Dashboard views and a Ctrl+Shift+L product search.

No account is needed. Affibloom itself has no cloud: nothing is sent to its author, and the services below are only the ones you ask for.

== External services ==

Affibloom talks to a service only after you have set it up, and only for what that feature needs.

**Amazon Product Advertising API (Creators API)** — used for Amazon product data: the title, picture, price, Prime status, availability and search results. When you add or refresh an Amazon product, run a bulk sync, or search Amazon from the Dashboard, Affibloom sends the product's ASIN (or your search words), the store's country and your tracking ID, signed with the Associates API credentials you entered under Settings → Amazon. Nothing is sent until you enter those credentials. Service terms: https://affiliate-program.amazon.com/help/operating/agreement ; API licence: https://webservices.amazon.com/paapi5/documentation/license-agreement.html ; privacy: https://www.amazon.com/gp/help/customer/display.html?nodeId=468496

**Google Analytics (gtag.js)** — used only when you enter a Measurement ID under Settings → Analytics. The page then loads Google's gtag.js from googletagmanager.com and sends an event when a reader clicks one of your affiliate links (the product's name and the link's place). With no Measurement ID, nothing is loaded and nothing is sent. Terms: https://marketingplatform.google.com/about/analytics/terms/us/ ; privacy: https://policies.google.com/privacy

**The sites you link to** — the daily link check, and the check when you save a product, request your own affiliate links from the merchants you chose (for example amazon.com or an advertiser's site), to see whether the page still answers. Only the address you stored is requested; no data about your readers is sent. Amazon short links (amzn.to) are resolved the same way, once each.

== Installation ==

1. Upload the plugin through Plugins → Add New → Upload Plugin, or copy the `affibloom` folder into `wp-content/plugins`.
2. Activate it. The setup wizard opens on the first Affibloom page: pick a theme, your colours, your Amazon tracking ID and your Google Analytics ID.
3. Add your first product with **Add New Link** on the Dashboard, then place it in a post with the Product box block.

== Source code ==

The editor's block and the Elementor script are written as ES modules under `blocks/` and `assets/editor/`, and built
into `build/` with `@wordpress/scripts` (`npm install && npm run build`; the build is configured in `webpack.config.js`).
Both the sources and the built files are in this plugin, so nothing here is minified without its source beside it.

== Frequently Asked Questions ==

= Does it need Amazon API credentials? =

No. Amazon links, your tracking ID, boxes, clicks and the link index work without them. Prices, the Prime logo, image refresh, Amazon search and stock alerts need the Amazon Creators API.

= What happens to my data when I delete the plugin? =

Nothing is deleted unless you switch on Settings → General → Uninstall → "Delete all Affibloom data when the plugin is deleted". Export your products from the Dashboard first.

== Screenshots ==

1. The Dashboard: every affiliate product, where it is used, how it is doing, and the links that need attention.
2. A product's page: its links and buttons, its picture, badge, price and fields, with the box drawn beside the form as you edit it.
3. Settings → Display: the theme, spacing, elements and colours of the box, with the live preview beside them.
4. The Affibloom block in the editor: pick a display, and customise it from the block's own panel.
5. What the reader sees: the product box in a post, with its badge, price, description and buttons.

== Changelog ==

= 1.0.3 =
* Ready for the WordPress.org directory: the external services are named, the block's source ships beside its build, and what Plugin Check found is fixed. The product page's url fields ask for an address, a custom field's switches say what they do, and the admin menu carries Affibloom's flower.

= 1.0.2 =
* The editor's panel gives each setting the control it deserves (Theme, Title tag, Columns, Button type and Bullet are dropdowns, Limit takes a number, Compact is a toggle); the box preview has room for its badge; the Quick Detail is wider; the Dashboard's Filters panel stays on screen on a phone.

= 1.0.1 =
* The editor's Quick Detail saves again where WordPress isolates the editor's page, its preview is the wider half and its title and image follow the form; the block is called Affibloom; fields you cannot edit look it.

= 1.0.0 =
* First stable release: rc.2 as tested on the live site's copy, with the Locations tab's post links large enough to tap.

= 1.0.0-rc.2 =
* Second release candidate: the build for the live cutover. Settings that only worked through Lasso's cloud, or that nothing read, are removed with their stored values; the extension API the Affibloom Lasso Migrator uses.

= 1.0.0-rc.1 =
* First release candidate.
