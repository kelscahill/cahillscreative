=== Search & Filter ===
Contributors: codeamp
Tags: search, filter, taxonomy, tag, category, product, shop, post type
Requires at least: 6.5
Tested up to: 6.9
Requires PHP: 7.2
Stable tag: 3.2.3
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

= 3.2.3 =
* Fix - issues with the styles editor not saving styles.
* Fix - PHP warnings when $post is NULL on save_post.
* Fix - missing custom class & width setting in our field editor.
* Fix - layout issues in wp-admin with Jetpack.
* Fix - issues with <fieldset> nesting in checkboxes and radios.
* Fix - an issue with our the admin UI not updating after enabling/disabling integrations.
* Fix - a JS error on the frontend causing URLs not to update correctly after searching.
* Fix - an issue with woocommerce fields not working with the selection field.

= 3.2.2 =
* New - enable Divi integration.
* Fix - an issue with select fields not displaying properly with numeric ACF data types when a site hasn't upgraded to the new styles system.
* Fix - an issue with the button field not being interactive in some circumstances.

= 3.2.1 =
* Fix - an issue with our admin JS not loading in some circumstances.

= 3.2.0 =
* Change - renamed the CSS class `search-filter-component-popup` to `search-filter-component-popover`.
* Change - updated field hook names for consistency & added deprecation warnings (for admin users only).
* New - Admin UI redesign - UX improvements, grid views, performance enhancements, better support for different screen sizes, easy access controls & much more.
* New - Admin - add tooltips for disabled settings.
* New - Blocks - reworked to a unified block system, seperate blocks for each field type, bug fixes and tons of quality of life improvements.
* New - Fields - Discover field Locations.
* New - Import & export all data - queries, fields, styles and settings - includes bulk and individual import & export options.
* New - Styles - Editor rework - create accessible styles presets quicker than ever.
* New - Styles - add placeholder color option.
* New - Styles - add input customization: border accent, divider, border styling (color, radius, width, style), shadow, and padding.
* New - Styles - add label & description border options (radius, color, style, width).
* New - Styles - add dropdown customization: border styling, shadow, item padding, attachment mode (attached/floating), gap, indent depth, and scale.
* New - Fields - Results Per Page - allow users to change the numbers of results per page.
* New - Fields - show counts for active selections in select fields.
* New - Queries - include and exclude pages & posts from queries.
* New - Queries - add support for random ordering & random ordering with a seed (for pagination support).
* New - Queries - add offset paramater.
* New - Frontend performance improvements & dynamic asset loading - only load the components and & CSS that's needed.
* New - added `filter_next_query` action to the shortcode as part of the v2 feature parity work - `[searchandfilter query="123" action="filter_next_query"]`
* New - select specific taxonomy archives or term archive for filtering.
* New - Fields - Add term ordering
* New - Accessibility improvements.
* New - Fields - Disable text search in choice select fields.
* New - Fields - Allow toggling of tri-state checkboxes or classic selection mode.
* Improvement - block editor - show the connected Search & Filter query in the query loop preview.
* Improvement - allow upto 150 results per page and usage of `-1` to show all posts.
* Improvement - dynamically calculate popup z-index values for better compatibility.
* Improvement - performance - reduced the number of frontend queries by roughly 50%.
* Improvement - allow additional strings to be customised for the select input type and screen readers.
* Improvement - set the max number of options in choice fields to 100.
* Improvement - add the Query ID to the block editor modal.
* Improvement - track WP_Query query data in the debugging tools.
* Improvement - better support WooCommerce ordering arguments via the dropdown or default option (in the customizer).
* Fix - issues with inline counts not wrapping.
* Fix - issues with background gradients in the styles editor.
* Fix - issues with generating CSS files for styles presets.
* Fix - issues with commas in field values.
* Fix - issues with archives not setting defaults in a field with post tags and categories.
* Fix - PHP errors when trying to use queries that are not initialised yet.
* Fix - issues unhooking from the WooCommerce collections block.
* Fix - an issue with the query loop where the post type was not being overriden if it was set to "post" in the query block.
* Fix - the JavaScript `foundPosts` variable was reporting 1 post found in cases when there should be none.
* Fix - issues with field input widths when margins were unset.
* Fix - issue with initialising the datepicker on mobile.
* Fix - issue with radio buttons not clearing their values correctly.
* Fix - a rendering issue when using Google translate.
* Fix - issues with default settings & features not being applied correctly on new installs.
* Fix - issues with attribute resolution in the editor.
* Fix - issues with custom CSS classnames not being applied to fields.
* Fix - an issue where order by count was not working.
* Fix - issues copying shortcodes to the clipboard on Macs.
* Fix - support for the media post type.

= 3.1.6 =
* Improvement - platform updates to support extensions.
* Improvement - update integrations list.
* Improvement - show ID column by default in admin.

= 3.1.5 =
* Fix - an issue with rendering field previews in admin screens.

= 3.1.4 =
* Fix - issue with the taxonomies tab not displaying correctly in the query editor.
* Fix - issue when using past end of life database servers (MySQL version < 5.7 or MariaDB version < 10.2) when creating the options table.
* Fix - error when queries are loaded in admin screens and trying to access `is_archive()`.
* Fix - issue showing incorrect field counts in the query editor.

= 3.1.3 =
* New - added hooks to support new pro features.
* New - reworked WPML integration.
* Improvement - logging and debugging tweaks.
* Improvement - support publicly queryable post types in fields and queries.
* Improvement - better performance in admins settings screens.
* Improvement - better compatibility with the WooCommerce collections block.
* Improvement - update license server URL.
* Change - renamed the query and fields `remove()` function to `unload()`.
* Fix - JavaScript issues in the block editor when using WooCommerce.
* Fix - an issue detecting default post types to display.
* Fix - an issue setting post types when using the Search location.

= 3.1.2 =
* Improvement - stop using `getmypid()` when its not available (some hosting companies like Kinsta disable this function).
* Fix - an issue with setting the correct post type for archives & WooCommerce shop.
* Fix - issue with WooCommerce attributes that were not used for variations.
* Fix - issue with the datepicker not clearing correctly after using a reset button.
* Fix - number formatting issues when using the range slider.
* Fix - show the "all options" default option as selected when no other options are selected.
* Fix - an issue with the admin fields not rendering on a clean install.

= 3.1.1 =
* New - added hooks to our rest api requests to prevent caching.
* Fix - hotfix to remove the HPOS warning when using WooCommerce.
* Fix - issues with field previews on new sites, when no queries have been created.

= 3.1.0 =
* New - add support for WooCommerce Product Brands.
* New - enable filtering on WooCommerce product archives option when using the shop integration.
* New - `has_active_fields()` PHP method for queries.
* New - added debugging options and logging levels.
* Improvement - add plugin action link to the settings page.
* Improvement - better detection of current page URL.
* Improvement - add option values as data attributes for easier targetting with CSS.
* Improvement - reliability with some hosts when generating our CSS file on the server.
* Improvement - batch api requests in the block editor and admin screens.
* Fix - a fatal error caused when using certain themes.
* Fix - issues with WP 6.7 and loading translations too early.
* Fix - an issue in the query editor when choosing taxonomy archives, causing the query tab to throw an error.
* Fix - issues generating hierarchical taxonomy term URLs.
* Fix - admin JS issues when navigating between templates in FSE.
* Fix - styling issues with the sort fields label.
* Fix - an issue with hierarchical taxonomies not showing posts only assigned to parents.

= 3.0.7 =
* New - add notices to suggest enabling integrations when they are detected.
* Change - remove beta feedback form.
* Fix - select input types were not showing their placeholders on mobile and multiselect were not showing selections properly.
* Fix - issues when using CSS variable colors from block editor themes.
* Fix - an issue with the new query modal throwing an error in the block editor.
* Fix - issues with the Main Query option not being available for archives.
* Fix - stop enqueuing unnecessary JS in admin screens.

= 3.0.6 =
* New - New `dynamic` query integration location, replaces the dynamic toggle.
* New - improvements to the integrations screen - install extensions with a single click!
* New - Duplicate fields, queries & styles from the admin UI
* Improvement - JavaScript APIs have been restructured and renamed.
* Improvement - change the JS initialisation to improve compatibility.
* Improvement - added ID column to admin tables - check the column view dropdown menu to enable it.
* Improvement - disable text input on select fields on mobile devices.
* Change - rename the query "integration" tab to to query "location"
* Updated - renamed hooks in field render function to match naming conventions.
* Fix - an issue with the count containers being added the DOM unnecessarily.
* Fix - an admin error when shortcodes are disabled when using the fields dropdown.
* Fix - issues with our fields not inheriting the block gap setting in the block editor.
* Fix - order options in choice fields without case sensitivity.

= 3.0.5 =
* Hotfix for fatal error thrown in Search & Filter Pro

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
* Release version 3.0.0.
* Fix - issue with placeholders sometimes not being the correct scale.
* Fix - issue with buttons not applying the correct width.
* Fix - issue with select dropdown positioning.
* Fix - regression with archive queries not being properly attached.
* Fix - issue with the button field not showing the selected option.
* Fix - issues when migrating fields from older versions.
* Fix - an JS issue being thrown with the ResizeObserver inside FSE iframes.
* Fix - various issues when using taxonomy archive query integration.
* Fix - issue with count brackets not showing.
