=== Affibloom ===
Contributors: devrupash
Tags: affiliate, amazon, product box, comparison table, affiliate links
Requires at least: 7.1.4
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 1.0.0-rc.2
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

No account is needed and nothing is sent to an outside service, except Amazon and Google Analytics when you set them up.

== Installation ==

1. Upload the plugin through Plugins → Add New → Upload Plugin, or copy the `affibloom` folder into `wp-content/plugins`.
2. Activate it. The setup wizard opens on the first Affibloom page: pick a theme, your colours, your Amazon tracking ID and your Google Analytics ID.
3. Add your first product with **Add New Link** on the Dashboard, then place it in a post with the Product box block.

== Frequently Asked Questions ==

= Does it need Amazon API credentials? =

No. Amazon links, your tracking ID, boxes, clicks and the link index work without them. Prices, the Prime logo, image refresh, Amazon search and stock alerts need the Amazon Creators API.

= What happens to my data when I delete the plugin? =

Nothing is deleted unless you switch on Settings → General → Uninstall → "Delete all Affibloom data when the plugin is deleted". Export your products from the Dashboard first.

== Changelog ==

= 1.0.0-rc.2 =
* Second release candidate: the build for the live cutover. Settings that only worked through Lasso's cloud, or that nothing read, are removed with their stored values; the extension API the Affibloom Lasso Migrator uses.

= 1.0.0-rc.1 =
* First release candidate.
