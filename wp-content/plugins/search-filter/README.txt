=== Search & Filter ===
Contributors: codeamp
Tags: search, filter, taxonomy, tag, category, product, shop, post type
Requires at least: 6.2
Tested up to: 6.7
Requires PHP: 7.2
Stable tag: 3.0.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create powerful search and filtering experiences for your users and customers.

== Description ==


== Installation ==


== Frequently Asked Questions ==


= A question that someone might have =


= What about foo bar? =


== Screenshots ==


== Changelog ==

= 3.0.5 =
* Hotfix for fatal error thrown in Search & Filter pro

= 3.0.4 =
* New - added an "all items" option for radios, selects and buttons input type.
* New - add limit depth, hide empty, show count, order by, include & exclude terms to WooCommerce data types.
* Improvement - various admin UI improvements related to dynamically showing settings.
* Improvement - set the default sticky posts option to ignore.
* Fix - multiple issues with the UI showing out of date messaging in the block editor and admin screens.
* Fix - limit number of options shown in fields wasn't working on the frontend in some scenarios.
* Fix - issue with fields when restricting taxonomy terms and post authors in the block editor.
* Fix - indentation issues with hierarchical taxonomy checkboxes & radios.
* Fix - order option in fields was not working when using post attributes as the data source.
* Fix - counts were not showing in button fields.
* Fix - issues with checkbox selection when pressing forwards/backwards in the browser history.

= 3.0.3 =
* Fix - issues detecting the current page in a query.
* Fix - properly respect relevance ordering if set in the query when a search term has been entered.
* Fix - issues with Cron schedules.
* Fix - issues with broken links.

= 3.0.2 =
* New - add support to include/exclude taxonomy terms from fields.
* New - added custom fields to query sort order.
* New - added support for multiple sort orders to a query.
* New - added sort order field type (control field).
* Improvement - UI improvements to make working with shortcodes easier when the feature is enabled.
* Fix - issues when integrations & features are updated and it not reflecting throughout the admin UI.
* Fix - issues showing the correct styles in admin / block editor.
* Fix - issue with connected queries and fields not displaying correctly in the admin list screens.

= 3.0.1 =
* New - add sorting options for data types: post type, post status.
* Improvement - show the ID of fields, queries & styles next to the screen title.
* Fix - issues with fields not loading when block editor features are disabled.
* Fix - an issue when changing field types and settings were persisting.
* Fix - an issue with sort order not working correctly.

= 3.0.0 =
* Fix - issue with placeholders sometimes not being the correct scale.
* Fix - issue with buttons not applying the correct width.
* Fix - issue with select dropdown positioning.
* Fix - regression with archive queries not being properly attached.
* Fix - issue with the button field not showing the selected option.
* Fix - issues when migrating fields from older versions.
* Fix - an JS issue being thrown with the ResizeObserver inside FSE iframes.
* Fix - various issues when using taxonomy archive query integration.
* Fix - issue with count brackets not showing.

= 3.0.0-beta-15 =
* Platform upgrades to support the pro extension.
* Minor bug fixes.

= 3.0.0-beta-14 =
* Notice - updated the `search-filter/queries/query/apply_wp_query_args` hook to pass in the query object instead of the attributes.
* Minor bug fixes.
* Platform upgrades to support the pro extension.
placeholder

= 3.0.0-beta-13 =
* Fix - an issue with fields not submitting.

= 3.0.0-beta-12 =
* New - revamped styles editor and field styles tab.
* New - added query option `Exclude Current Post`.
* Changed - class names for fields, queries & styles
* Tweak - added copy shortcode button for fields.
* Fix - the wrong fields were showing when creating a new query.
* Fix - various issues with taxonomy archives.
* Fix - issues with pagination and the updated query block (also affects products block).
* Fix - an issue where the plugin data was not removed, even though the setting to remove data on uninstall was enabled.
* Notice - if you are using filters for post types and post stati via the block editor, these will need to be re-saved.

= 3.0.0-beta-11 =
* New - accessibility improvements.
* New - enable post type archive queries to also filter taxonomy archives.
* New - debugging tools
* New - support for the site editor / FSE
* Fix - an issue with checkbox filters throwing a PHP error
* Fix - issues with taxonomy archive search not working correctly.
* Fix - various JS issues with the site editor.

= 3.0.0-beta-10 =
* New - add integrations & settings pages
* New - feedback widget for beta testers
* New - add fallback support for legacy shortcodes
* Improvement - rework the query editor in the block editor
* Improvement - update the help screen
* Fix - various admin layout issues due to an update in Gutenberg's SlotFills system.

= 3.0.0-beta-9 =
* Fix - issues with WP 6.4 and lodash no longer being loaded

= 3.0.0-beta-8 =
* New - support hierarchical taxonomies in checkboxes, radios and select dropdowns
* New - restrict hierarchical taxonomy depth
* New - import/export styles
* New - padding controls for fields
* New - Scale and spacing controls for labels
* Improvement - various admin screen improvements including speed and caching
* Fix - various issues with field previews in the styles editor
* Fix - issues with the post type filter not working correctly
* Bump PHP Version to 7.2
* Bump WP version to 6.2

= 3.0.0-beta-7 =
* Improvement - PHP 8.1 and 8.2 compatiblity
* Fix - an issue with the hide empty setting not working for taxonomies
* Fix - an issue with our admin screens not loading when the WP install was in a subdirectory

= 3.0.0-beta-6 =
* New - added query filter to fields admin screen
* New - added breacrumbs to admin screens
* New - Query editor sidebar for the block + site editor
* New - reworked query editor for the Query Loop Block (you will need to reconnect your fields to query loops)
* Updated - renamed our taxnomy comparison mode in filters
* Updated - renamed woocommerce shop integration
* Fix - issue with some rest api requests preloading on the frontend
* Fix - styling issues with WP 6.1
* Fix - issue with query ID not working with query blocks
* Improved - support for the site editor
* Notice - please check the upgrade notes as you will need to reconfigure your saved queries with this update

= 3.0.0-beta-5 =
* New - a button to "go pro".
* Fix - pressing enter on the search input now submits the form.
* Fix - an issue where entities were being double encoded.
* Fix - an issue where the select dropdown auto-closed on mobile (when the on screen keyboard opens).

= 3.0.0-beta-4 =
* Fix - issue with shortcode implemenation where fields would not show their options.

= 3.0.0-beta-3 =
* Update - docs links
* Update - dashboard buttons

= 3.0.0-beta-2 =
* New - add a context (reusable field / block editor fields) to fields admin page.
* Update - rename Template Field block to Reusable Field block - **breaking change**.

== Upgrade Notice ==

= 3.0.0-beta-12 = 
There may be some issues with block attributes being renamed, these changes won't break the frontend, but please verify any Search & Filter blocks created when editing your posts and pages.
