=== Search & Filter Pro ===
Contributors: codeamp
Tags: search, filter, taxonomy, tag, category, product, shop, post type
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 5.3
Stable tag: 3.0.6

Create powerful search and filtering experiences for your users and customers.

== Description ==


== Installation ==


== Frequently Asked Questions ==


== Screenshots ==


== Changelog ==

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
