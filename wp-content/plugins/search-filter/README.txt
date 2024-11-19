=== Search & Filter ===
Contributors: codeamp
Tags: search, filter, taxonomy, tag, category, product, shop, post type
Requires at least: 6.2
Tested up to: 6.7
Requires PHP: 7.2
Stable tag: 3.0.7
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

= 3.0.7 =
* New - add notices to suggest enabling integrations when they are detected.
* Change - remove beta feedback form.
* Fix - select input types were not showing their placeholders on mobile and multiselect were not showing selections properly.
* Fix - issues when using CSS variable colors from block editor themes.
* Fix - an issue with the new query modal throwing an error in the block editor.
* Fix - issues with the Main Query option not being available for archives.
* Fix - stop enqueuing uncessary JS in admin screens.

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
