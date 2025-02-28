=== Newsletter, SMTP, Email marketing and Subscribe forms by Brevo (formely Sendinblue) ===
 Contributors: neeraj_slit
Tags: smtp, email, form, newsletter, subscribe
Requires at least: 4.4
Tested up to: 6.7.1
Requires PHP: 5.6
Stable tag: 3.1.93
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Manage your contact lists, subscription forms and all email and marketing-related topics from your wp panel, within one single plugin

== Description ==

<a href="https://www.brevo.com/?utm_source=wordpress_plugin&utm_medium=plugin&utm_campaign=addons_page" target="_blank" rel="noopener">Brevo</a>’s official plugin for WordPress is a powerful all-in-one email marketing plugin. At a glance:
<ul>
<li><b>Subscription forms</b> - Create custom subscription forms and easily integrate them into your posts, pages or sidebars</li>
<li><b>Contact lists</b> - Manage your contact lists and take advantage of advanced segmentation to improve your campaign performance</li>
<li><b>Marketing campaigns</b> - Easily create and send beautiful newsletters using our mobile-friendly, drag and drop builder to create custom emails or select from our template library</li>
<li><b>Transactional emails</b> - The wp_mail() function automatically uses Brevo’s SMTP for enhanced deliverability and tracking</li>
<li><b>Statistics</b> - Real-time report dashboard gives you advanced insights into deliverability and performance: opens, clicks, bounce reports, etc.</li>
<li><b>Marketing automation</b> - Enable Automation on WordPress to auto-install the script on your website and the identify tag on the Brevo forms and Wordpress Login </li>
</ul>

= Subscription Forms =
<ul>
<li>Form designer with WYSIWYG and direct HTML / CSS editing (if desired)</li>
<li>Integration as widget or shortcode [sibwp_form]</li>
<li>Send a confirmation email - you choose the template and the sender</li>
<li>Use a double opt-in confirmation - you choose the template and the sender</li>
<li>URL redirection</li>
<li>Confirmation / error message customization</li>
</ul>

= Contact Lists =
<ul>
<li>Folder and lists management</li>
<li>CSV and TXT file import</li>
<li>Unlimited custom fields</li>
<li>Advanced segmentation. Example: Search for contacts who are less than 45 years who clicked links in my last 3 campaigns.</li>
</ul>

= Marketing Campaigns =
<ul>
<li>Responsive, drag and drop email design tools</li>
<li>Library of mobile-friendly, creative email design templates</li>
<li>Subject and content personalization. Example: Hello {NAME},</li>
<li>Inbox and design rendering tests for multiple devices and email clients</li>
<li>Schedule campaigns in advance</li>
</ul>

= Transactional Emails & Statistics =
<ul>
<li>Automatic replacement of default SMTP when you use wp_mail function</li>
<li>Create transactional email templates that are easy to reuse via the API</li>
<li>Real-time and exhaustive statistics: delivered, opened, clicked, etc.</li>
</ul>

= Marketing Automation =
<ul>
<li>Auto-installation of the automation script on your website</li>
<li>Auto-deployment of the identify tag for Brevo’s forms and Wordpress plugin</li>
<li>Access to the Brevo online dashboard for workflow creation and management</li>
</ul>

= Plugin Support =
To get support, please send an email to <a href="mailto:contact@brevo.com">contact@brevo.com</a>, we will be happy to help you!

The plugin is available in English and French.

== Installation ==

1.	In your WordPress admin panel, go to Plugins > New Plugin, search for "Brevo for WP" and click "Install now". Alternatively, download the plugin and upload the contents of mailin.zip to your plugins directory, which may be  /wp-content/plugins/.
2.	Activate the Brevo plugin through the 'Plugins' menu in WordPress.
3.	The "Brevo" tab must appear in your WordPress side navigation panel, then set your <a href="https://app.brevo.com/settings/keys/api?utm_source=wordpress_plugin&utm_medium=plugin&utm_campaign=addons_page" target="_blank" rel="noopener">Brevo API key</a> in the plugin homepage.

To get a Brevo API key, you have to <a href="https://onboarding.brevo.com/account/register/?utm_source=wordpress_plugin&utm_medium=plugin&utm_campaign=addons_page" target="_blank" rel="noopener">create an account</a>. It's free and takes less than 2 minutes!

== Screenshots ==
1. First, your are invited to create an account on Brevo then enter your API keys
2. The Homepage gives you an overall view over your campaigns and allows you to activate Brevo SMTP for all transactional emails and Marketing Automation to better target your customers
3. The Forms page gives you access to your forms list
4. When you click on a specific form, you can configure your sign up process and customize it
5. The Statistics page gives you a global view over your performance: delivered, opened, clicked, etc.

== Frequently Asked Questions ==

= What is Brevo? =
Brevo is a powerful all-in-one marketing platform. Over 165 000 companies around the world trust Brevo to deliver their emails and SMS messages.
Brevo combines competitive pricing and excellent deliverability & powerful features such as Email, SMS, Facebook, Chat, CRM, and marketing automation.
Brevo is available and supported in 6 languages: English, French, Spanish, German, Italian, and Portuguese.

= Why use Brevo as an SMTP relay for my website? =
By using Brevo’s SMTP, you will avoid the risk of having your legitimate emails ending up in the spam folder and you will have statistics on emails sent: deliverability, opens, clicks, etc. Brevo’s proprietary infrastructure optimizes your deliverability, enabling you to focus on your content.

= Why do I need a Brevo account? =
The Brevo for WordPress plugin uses Brevo’s API to synchronize contacts, send emails and get statistics. Creating an account on Brevo is free and takes less than 2 minutes. Once logged into your account, you can get the API key, and you can send up to 300 emails / day on the free (forever) plan.

= Do I have to pay to use the plugin and send emails? =
No, the plugin is totally free and Brevo offers a free forever plan with 9,000 emails per month. Additionally, Brevo comes with unlimited contacts (including on the free plan), so there is no hidden cost.
If you need to send more than 300 emails / day, check out our pricing. Paid plans start at $25 / month to send up to 10 000 emails / month with no daily sending limit.

= How do I get my get synchronize my lists? =
You have nothing to do – synchronization is automatic! It doesn’t matter whether your lists were uploaded on your WordPress interface or on your Brevo account: they will always remain up-to-date on both sides.
Brevo also integrates with most lead capture and advanced form builder plugins.

= How can I get support? =
If you need some assistance, you can post an issue in the Support tab, or send us an email at contact@brevo.com.

= How do I create a signup form? =
In order to create a signup form, you need to:
1. Go to Wp admin > Brevo > Settings in order to define your form’s fields and settings
2. Integrate the form in a sidebar using a widget from WP panel > Appearance > Widgets. The Brevo widget form should appear in your widgets list, you just to have to drag and drop the widget into the sidebar of your choice.

== Changelog ==
= 3.1.93 =
* Removed workflow tab 
* Improvements related to enabling marketing automation
* Added support for Brevo WebPush

= 3.1.92 =
* Plugin code enhancements to extend the success message display duration after form submission.

= 3.1.91 =
* Plugin code enhancements to handle logout flows

= 3.1.90 =
* Plugin made compatible with Wordpress v6.7.1

= 3.1.89 =
* Enhancements to Brevo tracking script for automation events and syncing

= 3.1.88 =
* Fix vulnerability related to logout

= 3.1.87 =
* Enhancement in template language

= 3.1.86 =
* Plugin made compatible with Wordpress v6.6.2

= 3.1.85 =
* Plugin enhancements to improve plugin registration flow on Brevo with connection ID instead of name

= 3.1.84 =
* Fix to resolve issue around DOI confirmation email being sent twice

= 3.1.83 =
* Fix vulnerability related to forms

= 3.1.82 =
* Plugin is now compatible with Wordpress v6.5.4
* Improvement in query for table update

= 3.1.81 =
* Included ability to set light/dark theme for Cloudflare Capthca

= 3.1.80 =
* Plugin made compatible with Wordpress v6.5.2

= 3.1.79 =
* Enhancements in Brevo Forms

= 3.1.78 =
* Plugin enhancements related to security

= 3.1.77 =
* Rebranded plugin from old Sendinblue brand to new Brevo brand

= 3.1.76 =
* Added support for Cloudflare Turnstile Captcha to be used with Brevo Forms in the plugin

= 3.1.75 =
* Updated default sender for test emails to use customer’s domain instead of Brevo domain

= 3.1.74 =
* Plugin made compatible with Wordpress v6.4

= 3.1.73 =
* Plugin security enhancements

= 3.1.72 =
* Plugin Enhancements related to Multi Language support

= 3.1.71 =
* Fixed issue in "Required fields" error message in forms

= 3.1.70 =
* Enabled edit access to the plugin for Editor roles in Wordpress

= 3.1.69 =
* Plugin made compatible with Wordpress v6.3

= 3.1.68 =
* Enhanced user experience of plugin.

= 3.1.67 =
* Rebranded plugin from old Sendinblue brand to new Brevo brand

= 3.1.66 =
* Enhanced user experience of plugin.

= 3.1.65 =
* Plugin code enhancements to correctly handle the plugin activation and deactivation events on Brevo.

= 3.1.64 =
* Enhanced user experience of plugin.

= 3.1.63 =
* Added fix for correctly displaying custom error message while using Wordpress form.

= 3.1.62 =
* Improvements have been made to the plugin code to enhance performance and security.

= 3.1.61 =
* Fixed the vulnerability issues for WPML.

= 3.1.60 =
* Enhancement in the backend links of the plugin for migration.

= 3.1.59 =
* Enhanced the plugin code to be compatible with WordPress version 6.2.

= 3.1.58 =
* Fixed bug related to multiple attachments not being sent when sending emails via Sendinblue SMTP.

= 3.1.57 =
* Enhanced invisible reCAPTCHA to allow for additional class modification.

= 3.1.56 =
* Enhanced Web Push notifications.

= 3.1.55 =
* New fix to support multiple invisible reCAPTCHA installations on the same page.

= 3.1.54 =
* Account and onboarding URLs updated in plugin.
* Enhanced the plugin with compatibility checks for WordPress version 6.1.1.

= 3.1.53 =
* Enhanced the plugin with compatibility checks for WordPress version 6.1.

= 3.1.52 =
* Improvement in the form preview for a better user experience.
* Handling exception on blank attribute

= 3.1.51 =
* We have fixed the syntax error at different form files.

= 3.1.50 =
* We’ve fixed the form previewer for the country flag attached to the SMS number field.

= 3.1.49 =
* We’ve fixed the behaviour of the country flag attached to the SMS number field.

= 3.1.48 =
* An additional header is added for the technical improvement in the log tracking.
* Enhanced the plugin to be compatible with WordPress version 6.0.2.
* Technical improvement in the contact create.

= 3.1.47 =
* Fixed the issue related to backward compatibility.

= 3.1.46 =
* Enhanced the plugin to be compatible with WordPress version 6.0.1.
* Fixed issue related to multiple Google reCaptcha on the same page.

= 3.1.45 =
* Resolved conflict of the sender when both WooCommerce SMTP and Sendinblue SMTP are enabled.

= 3.1.44 =
* Updated text message for marketing automation activation for new users.

= 3.1.43 =
* Added required javascript file for push notification support.

= 3.1.42 =
* Enhanced the plugin to be compatible upto WordPress version 6.0

= 3.1.41 =
* UI fixes for the form building page and user synchronization popup.
* HereDoc string literals have been removed.

= 3.1.40 =
* Security fixes related to data sanitization and output escaping.
* Fixed the issue, where the “Sync My Users” button stays active after synching all users.
* Removed type hinting to keep supporting PHP 5.6

= 3.1.39 =
* Added some missing CSS and JS files.

= 3.1.38 =
* Bootstrap library updated to version 5.1.3.
* Added support for the email address fields to comply with <a href="http://www.faqs.org/rfcs/rfc2822.html" target="_blank">RFC 2822</a>.

= 3.1.37 =
* Security fix include user input data sanitization.

= 3.1.36 =
* Security fixes include cookies handling, user input data sanitization, and escaping HTML entities from the output.
* Added missing font file for bootstrap 3.4.1

= 3.1.35 =
* Security fixes, Input data sanitization during user sync, and cookies creation.

= 3.1.34 =
* Sanitization of form HTML to fix vulnerability issues.
* Fixed the issue of the user sync button still enabled even after all users are synched.

= 3.1.33 =
* Enhanced the plugin to be compatible upto Wordpress version 5.9

= 3.1.32 =
* Fixed issue related to data sanitization.

= 3.1.31 =
* Fixed issue related to data sanitization.

= 3.1.30 =
* Fixed issue related to data sanitization.

= 3.1.29 =
* Fixed issue related to data sanitization.

= 3.1.28 =
* Updated bootstrap version from 3.2 to 3.4.1.

= 3.1.27 =
* Fixed issue related to XSS vulnerability and security checks.

= 3.1.26 =
* Fixed an issue related to XSS reflected Vulnerability.

= 3.1.25 =
* Fixed an issue related to XSS reflected Vulnerability.
* Add more default attributes to map with Sendinblue account.
* Add UTM tracking links to some hyperlinks.
* Fixed an issue related to attributes in French not showing correctly.

= 3.1.24 =
* Fixed an issue related to contact attributes not updating via DOI confirmation.
* Fixed an issue related to an error message which not translated into French.

= 3.1.23 =
* Enhanced the security of website tracking via automation by skipping the password field from the forms.

= 3.1.22 =
* Improved the content of test email.
* Fixed an issue related to slowness of plugin because of a mysql query.

= 3.1.21 =
* Fixed an issue with all templates on Sendinblue account not showing on plugin configuration.

= 3.1.20 =
* Fixing issue with plugin deactivation on update.

= 3.1.19 =
* Fixing issue with plugin deactivation on update.

= 3.1.18 =
* Modified permissions of mailin.php.

= 3.1.17 =
* Fixed some reported bugs related to internationalization (i18n)
* Fixed an issue with DOI email and confirmation email sent at the same time.

= 3.1.16 =
* Enhanced the plugins to be compatible upto PHP version 8
* Enhanced the plugin to be compatible upto Wordpress version 5.8

= 3.1.15 =
**Improvements**

* Fixed some minor issues related to double optin subscription
* Removed legacy code corresponding double optin subscription

= 3.1.14 =
**Improvements**

* Improve technical performance of the plugin

= 3.1.13 =
**Improvements**

* The subscribed contact from a double optin form would be created or updated on Sendinblue platform only on clicking the double optin link in the confirmation email.
* Removed the default message for Existing subscribers

= 3.1.12 =
**Improvements**

* Improved technical performance of the plugin

= 3.1.11 =
**Improvements**

* Improved technical performance of the plugin

= 3.1.10 =
**Improvements**

* Fixed few minor bugs and notices

= 3.1.9 =
**Improvements**

* Fixed an issue related to transactional email getting distorted.

= 3.1.8 =
**Improvements**

* Fixed the issue of email not being sent to recipient in cc or bcc.

= 3.1.7 =
**Improvements**

* Improved technical performance of the plugin

= 3.1.6 =
**Bug fixes**

* Fixed the unwanted backslash issue when adding apostrophe in text field at forms or at forms settings.

= 3.1.5 =
**Bug fixes**

* Fixed the unwanted backslash issue when adding apostrophe in text field at forms or at forms settings.
* Fixed the Missing/Invalid Parameter errors in response from API when sending transactional.

= 3.1.4 =
**Bug fixes**

* Fixed an issue related to auto-creation of DOI list in FORM folder.

= 3.1.3 =
**Bug fixes**

* Fixed an issue related to empty attachment while sending transactional mailing

= 3.1.2 =
**Bug fixes**

* Improved plugin stability and performance.

= 3.1.1 =
**Improvements**

* Removed data validation so that DD-MM-YYY format is accepted as well
* Improved monitoring of plugin technical performance
* Updated WordPress Repository content by adding FAQ section

= 3.1.0 =
**Bug fixes**

* Fixed the blank/suspicious mail content with PDF attachments.
* Headers are visible on statistics page even if data is not available.

= 3.0.9 =
**Improvements**

* Fixed critical errors for PHP Mailer triggered for Wordpress version 5.5 and beyond

* Transactional mails with attachment issue has been fixed


= 3.0.8 =
**Improvements:**

* Mapping contact attribute

* Added partner via API V3 to users collection

* Re-subscriptions with DOI flow


= 3.0.7 =
**Improvements:**

* Updated Translations

**Bug fixes:**

* Custom redirection URL for DOI mailings

= 3.0.6 =
**Bug fixes:**

* Compatibility issue with Gravity Forms which was affecting transactional mailing

= 3.0.5 =
**Bug fixes:**

* Multi-list selection
* Recipients boolean attributes

= 3.0.4 =
* Some Improvements

= 3.0.3 =
* Bug fixes & improvements

= 3.0.2 =
* Bug fixes & improvements

= 3.0.1 =
* Fix login process with api v3 key

= 3.0.0 =
* [Improvement] Migrated the plugin from API v2 to API v3
* [Improvement] Removed iframe (due to security reasons)
* [More details] No changes in features (Contact sync, transactional mailing, marketing automation, forms)
* [More details] New version has only 3 tabs: Home, Forms, Statistics
* [More details] Tabs with iframe (lists, campaigns) were removed
