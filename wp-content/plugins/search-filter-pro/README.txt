=== Search & Filter Pro ===
Contributors: codeamp
Tags: search, filter, taxonomy, tag, category, product, shop, post type
Requires at least: 6.5
Tested up to: 6.9
Requires PHP: 7.2
Stable tag: 3.2.3

Create powerful search and filtering experiences for your users and customers.

== Description ==


== Installation ==


== Frequently Asked Questions ==


== Screenshots ==


== Changelog ==

= 3.2.3 =
* New - Divi Integration added to the integrations screen.
* Improvement - disable indexer syncing on frontend updates by default.
* Improvement - faster indexing and more reliable error handling.
* Fix - an issue with tables not upgrading in some circumstances.
* Fix - add polyfills for `mb_..`  functions to support the new search indexer.
* Fix - a php error with range fields and `number_format()` in certain environments.
* Fix - missing css class & width options for fields.
* Fix - an issue where post meta conditions were not saving properly.
* Fix - a regression with the "hide empty" setting in fields that don't use the indexer.
* Fix - issues with sites using litespeed and slow/failing indexing processes.

= 3.2.2 =
* Fix - an issue with inconsistent deselection when using the selection field.

= 3.2.1 =
* Fix - an issue using and undefined function when the base plugin is not loaded.
* Fix - add hardening to prevent using base plugin classes unless fully loaded.

= 3.2.0 =
* New - Reworked indexer - specialised indexing tables for different types of data with 10x improvements to frontend query speeds.
* New - Enhanced Search - enable via beta features - create search indexes for lightning fast searches at volume.
* New - Faster indexing - enable via beta feaures - rebuild the indexer upto 10x faster.
* New - Large Query Optimizer - enable via beta feaures - works around issues with some hosts where using a large `post__in` causes significant slow downs.
* New - accessibility improvements - announce result counts and no results scenarios after live search has completed for all integration types.
* New - support searching SKUs for WooCommerce products.
* New - add support for ACF range fields.
* New - Customize url prefixes - its no longer required to use underscores before your field names `_`.
* New - dynamic update of fields & options on user interaction (before a submit).
* New - default option "inherit search value from the current query" for search fields.
* New - indexed range fields set their min/max values according to the current search criteria.
* New - add support for the "hide field when empty" setting to selection fields and range fields.
* New - option "Show Field Label" for selection fields.
* New - added WooCommerce "Custom Attribute" data type.
* New - added WooCommerce Weight and Dimensions data types for choice & range fields.
* Change - disable Relevanssi for autocomplete fields - we are unable to provide relevant suggestions based on indexer configuration.
* Improvement - Autocomplete fields submit on selection, have more reliable submission and don't submit the results unless "Auto submit on type" is enabled.
* Improvement - Fields - fix delays when choosing auto submit suggestions and add an option to control auto submit when typing.
* Improvement - better reliability and compatibility with the "load more" button.
* Improvement - autocomplete fields don't use nonces by default to better support sites with cache - enable via Advanced Settings.
* Fix - Fields - issues with the datepicker field when using the indexer.
* Fix - indexing issues with large WooCommerce imports (enable batch indexing)
* Fix - various issues detecting taxonomy archives (include WooCommerce archives).
* Fix - issues with the Load More pagination not calculating the correct number of pages.
* Fix - a JS issue when a field could not be found in the html markup.
* Fix - issues with JS errors in admin when adjusting range field settings.
* Fix - upper range limit was not preselected in the field if it used decimals.
* Fix - issues displaying range fields in the styles editor.
* Fix - show prefix and suffix for range values in the selections field.
* Fix - issue when clearing a range selection field not unsetting.
* Fix - various issues with default value detection.
* Fix - toggled fields were not keeping their state after a search was submitted.
* Fix - issue with custom fields only showing upto 60 options after applying the `get_attributes` filter.
* Fix - issue with child pages not showing in the results when using the indexer.
* Fix - issues with `post__not_in` not working when using the indexer.
* Fix - products brands were not indexing correctly for variations.
* Fix - issues with range sliders not working correctly with product variations when using the indexer.
* Fix - issues ordering custom fields options and ACF fields options.
* Fix - allow for `.` to be used as the thousands seperator when decimals are disabled in range input types.
* Fix - issues with displaying and filtering custom field options with a value of `0`.

= 3.1.7 =
* Improvement - update integrations list.
* Improvement - platform updates to support integrations.

= 3.1.6 =
* Improvement - clearing a field is no longer affected by the auto submit delay.
* Fix - an issue with rendering field previews in admin screens.
* Fix - an issue with counts not updating correctly when using the indexer.
* Fix - issues with incorrect position of our dropdown in some scenarios.
* Fix - an issue where a license that was already removed via your account couldn't not be disconnected in the plugin dashboard.
* Fix - issues with the the autocomplete field not showing suggestions in certain conditions in admin previews.

= 3.1.5 =
* New - support searching in radio, checkbox, select and button ACF fields when the indexer is enabled.
* New - support using the "load more" field with WooCommerce Products Collections blocks, shortcodes & the shop page.
* Fix - issues with live search and WooCommerce Products shortcodes.
* Fix - issues when using search input types with ACF fields.
* Fix - an issue when using the `post__in` filter  with the indexer causing incorrect results to be displayed.
* Fix - an issue where the indexer could stall.
* Fix - issue where sort order stopped working when using post meta queries.

= 3.1.4 =
* New - set field defaults and autodetect default values from archives or posts.
* Improvement - better compatibility with block editor layouts and query loops.
* Change - renamed the query and fields Javascript `remove()` function to `unload()`.
* Fix - regression with ACF fields not generating their options properly.
* Fix - issues when using WooCommerce Collections in the block editor.
* Fix - script errors in the block editor.
* Fix - update the indexer table to support longer values, matching the max length of taxonomy slugs.

= 3.1.3 =
* Fix - issues with indexing some ACF fields.
* Fix - error when the site can't connect to Search & Filter update servers.

= 3.1.2 =
* New - option to disable indexer sync when posts are updated on the frontend.
* New - add a hook for overriding the shortcode template path.
* Improvement - added warning about the maximum number of range options of 200 for select and radio ranges.
* Improvement - stop using `getmypid()` when its not available (some hosting companies like Kinsta disable this function).
* Fix - allow spaces as decimal or thousand seperators in range fields.
* Fix - errors when using the same character for decimals and thousands.
* Fix - issues with live search not working with the WooCommerce Shop when using non FSE themes.
* Fix - issues with shortcodes pagination when used on the (static) homepage.
* Fix - update the license check to use the new update server.

= 3.1.1 =
* Fix - hotfix to remove the HPOS warning when using WooCommerce.
* Fix - prevent litespeed cache from caching our indexer process leading to inaccurate indexer status.
* Fix - issues with current selection values not showing in the selection field.
* Fix - issues with plugin updates performed via the plugins screen.
* Fix - update to using the new license server to work around hosting issues.

= 3.1.0 =
* New - updates to support the integration with the Dynamic Content for Elementor plugin.
* New - support for custom WooCommerce stock statuses in fields.
* New - show out of stock option for WooCommerce fields.
* New - filter on product tags, categories and brand archives.
* Improvement - update the results shortcode template to automatically support the "load more" button
* Improvement - add additional CSS properties to range sliders to prevent themes from overriding styling (Astra)
* Improvement - click on range sliders to set automatically set the handle locations.
* Improvement - add plugin action link to the settings page.
* Improvement - stop disabling the checkbox on the plugins screen for the base plugin.
* Fix - issues with indexing WooCommerce product variations.
* Fix - an issue with the WooCommerce shop not completing ajax requests.
* Fix - an issue with WooCommerce category fields when displaying hierarchically.
* Fix - issues with WP 6.7 and loading translations too early.
* Fix - auto submit was not working with the date picker.
* Fix - issues when editing range fields, prompting for the field to be saved when no changes had been made.
* Fix - issues with floating point calculations in range fields.
* Fix - issues with date fields when connected to custom fields or ACF data - rebuild the indexer if you are using these.
* Fix - an issue with autodetecting min/max with range fields.
* Fix - links with URL fragments caused issues with live search.

= 3.0.6 =
* Improvement - allow option for the indexer to build when loopback requests fail - enable via -> `settings` -> `indexer` -> disable `use background process`
* Fix - an issue with some optimization plugins stripping out our initialisation JS.
* Fix - the sort order field was not working when the indexer was enabled.
* Fix - an issue when showing available meta keys.
* Fix - prevent activating the Search & Filter base plugin if its a really old version (1.x).
* Fix - some tables were not being uninstalled when the option to remove all data was enabled.

= 3.0.5 =
* New - add option to hide fields when they don't have any choices available.
* New - improve text search for ACF fields and add support for searching inside ACF repeater fields.
* New - add support for ACF taxonomy fields.
* New - allow integration plugins to be downloaded directly from the integrations screen.
* New - add ordering parameters to autocomplete fields (order suggestions alphabetically, ascending and descending)
* Improvement - when checking for updates ensure all related plugin updates are also available.
* Improvement - increase specificity of our range slider CSS classes for better consistency.
* Change - JavaScript APIs have been restructured and renamed.
* Fix - remove debugging tools warnings when using auto submit on fields.
* Fix - load more button was not being affected by the width settings.
* Fix - fields are now properly restored when they are inside a dynamic update section.
* Fix - auto detect min/max for ranges was causing an error in admin screens.
* Fix - indexer status was not correctly updated.
* Fix - the search field would cause a double submit when the auto submit setting was enabled.


= 3.0.4 =
* New - Fields - added "show count numbers" and "hide options with no results" to fields for post types, post statuses and post authors.
* New - Fields - order field options by "count" when the indexer is enabled.
* New - expose mount script to re-init our frontend JS when needed.
* Fix - disable Query Monitor plugin output in our JSON api requests which were preventing ajax requests from being completed.
* Fix - Fields - an issue where a field option would dissapear when using the indexer.
* Fix - Fields - issues with hierarchical taxonomies showing the incorrect counts.
* Fix - Queries - various errors thrown in the indexer when a field has certain conditions.
* Fix - Queries - dynamic update settings weren't showing when using archive display method
* Fix - an issue with some of the dynamic update settings not appearing under certain conditions.
* Fix - prevent adding to the browser history state if the search/filters didn't change.

= 3.0.3 =
* New - added Relevanssi integration.
* Improvement - stability, speed and accuracy improvements for the indexer.
* Fix - issues with the load more control not working properly in some circumstances.
* Fix - issues with pagination not working in results shortcodes.
* Fix - issue in our php results template file causing issues in some setups.
* Fix - an issue with the pagination parameters appearing multiple times in a URL.
* Fix - issues with Cron schedules.

= 3.0.2 =
* Improvement - add message when a license has no activations left.
* Improvement - show the results shortcode and allow for copy and paste in the query editor.
* Fix - issue with the toggle icon not appearing in labels.
* Fix - fatal error when using `post__in` in queries connected to S&F.

= 3.0.1 =
* New - allow disabling the "scroll to" option in the query settings.
* New - add support for ACF date picker and date time picker fields.
* New - add sorting options for custom fields + ACF fields.
* Improvement - prevent disabling the base plugin unless the pro plugin is disabled.
* Fix - various issues with the indexer and showing the indexer status.
* Fix - an issue where custom fields were showing a limited number of options.

= 3.0.0 =
* Release version 3.0.0.
* Fix - issue  with the loading icon position.
* Fix - issue with count formatting.

= Upgrade Notice =

= 3.1.1 =
If you see the update error message "plugin is already at the latest version", please update via the updates screen (rather than the plugins screen).
