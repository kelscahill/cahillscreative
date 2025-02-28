=== WooCommerce Stripe Payment Gateway ===
Contributors: woocommerce, automattic, royho, akeda, mattyza, bor0, woothemes
Tags: credit card, stripe, apple pay, payment request, google pay, sepa, bancontact, alipay, giropay, ideal, p24, woocommerce, automattic
Requires at least: 6.5
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 9.2.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Attributions: thorsten-stripe

Take credit card payments on your store using Stripe.

== Description ==

Changing consumer behavior has resulted in an explosion of payment methods and experiences, which are great for increasing conversion and lowering costs—but can be difficult for businesses to maintain. Give customers a best-in-class checkout experience while you remain focused on your core business. This is the official plugin created by Stripe and WooCommerce.

= Drive 11.9% in revenue with an optimized checkout experience from Stripe =

The enhanced checkout experience from Stripe can help customers:

- **Boost conversion:** Provide an optimal experience across mobile, tablet, and desktop with a responsive checkout, and offer 23 payment methods, including [Link](https://stripe.com/payments/link), [Apple Pay](https://woocommerce.com/apple-pay/), and [Google Pay](https://www.google.com/payments/solutions/), out of the box.
- **Expand your customer base:** Convert customers who might otherwise abandon their cart with buy now, pay later methods like Klarna, Affirm, and Afterpay/Clearpay, wallets like Apple Pay, Google Pay, Alipay, and WeChat Pay, and local payment methods such as Bancontact in Europe and Alipay in Asia Pacific. Deliver a localized payment experience with out-of-the-box support for localized error messages, right-to-left languages, and automatic adjustment of input fields based on payment method and country.
- **Meet existing customer demand and localize the experience:** Offer [local payment methods](https://stripe.com/guides/payment-methods-guide), such as Bancontact, Boleto, Cash App Pay, EPS, giropay, iDEAL, Multibanco, OXXO, Przelewy 24, and SEPA Direct Debit.
- **Fight fraud:** Detect and prevent fraud with [Stripe Radar](https://stripe.com/radar), which offers seamlessly integrated, powerful fraud-detection tools that use machine learning to detect and flag potentially fraudulent transactions.
- **Accept in-person payments for products and services:** Use the Stripe Terminal M2 card reader or get started with no additional hardware using Tap to Pay on iPhone, or Tap to Pay on Android.
- **Support subscriptions:** Support recurring payments with various payment methods via [WooCommerce Subscriptions](https://woocommerce.com/products/woocommerce-subscriptions/).
- **Manage cash flow:** Get paid within minutes with Stripe Instant Payouts, if eligible.
- **Achieve [PCI-DSS](https://docs.stripe.com/security) compliance with [Stripe Elements](https://stripe.com/payments/elements) hosted input fields.**
- Support Strong Customer Authentication (SCA).

Stripe is available for store owners and merchants in [46 countries worldwide](https://stripe.com/global), with more to come.

== Frequently Asked Questions ==

= In which specific countries is Stripe available? =

Stripe is available in the following countries, with more to come:

- Australia
- Austria
- Belgium
- Brazil
- Bulgaria
- Canada
- Croatia
- Cyprus
- Czech Republic
- Denmark
- Estonia
- Finland
- France
- Germany
- Gibraltar
- Greece
- Hong Kong
- Hungary
- India
- Ireland
- Italy
- Japan
- Latvia
- Liechtenstein
- Lithuania
- Luxembourg
- Malaysia
- Malta
- Mexico
- Netherlands
- New Zealand
- Norway
- Poland
- Portugal
- Romania
- Singapore
- Slovakia
- Slovenia
- Spain
- Sweden
- Switzerland
- Thailand
- United Arab Emirates
- United Kingdom
- United States

= Does this require an SSL certificate? =

Yes. In Live Mode, an SSL certificate must be installed on your site to use Stripe. In addition to SSL encryption, Stripe provides an extra JavaScript method to secure card data using [Stripe Elements](https://stripe.com/elements).

= Does this support both production mode and sandbox mode for testing? =

Yes, it does. Both production and test (sandbox) modes are driven by the API keys you use with a checkbox in the admin settings to toggle between both.

= Where can I find documentation? =

Refer to the [Stripe WooCommerce Extension documentation for more information, including how to set up and configure the extension](https://woocommerce.com/document/stripe/).

= Where can I get support or talk to other users? =

If you get stuck, you can ask for help in the [Plugin Forum](https://wordpress.org/support/plugin/woocommerce-gateway-stripe/).

== Screenshots ==

1. With the enhanced checkout from Stripe, you can surface 23 payment methods including buy now, pay later methods; and Link, an accelerated checkout experience.
2. Link autofills your customers’ payment information to create an easy and secure checkout experience.
3. Convert customers who would usually abandon their cart and increase average order value with buy now, pay later options like Klarna, Afterpay, and Affirm. Accept credit and debit card payments from Visa, Mastercard, American Express, Discover, and Diners.
4. Stripe Radar offers seamlessly integrated, powerful fraud-detection tools that use machine learning to detect and flag potentially fraudulent transactions.
5. Accept in-person payments for products and services using the Stripe Terminal M2 card reader.
6. Get started with no additional hardware using Tap to Pay on iPhone, or Tap to Pay on Android.

== Changelog ==

= 9.2.0 - 2025-02-13 =
* Fix - Fix missing product_id parameter for the express checkout add-to-cart operation.
* Fix - Fix the quantity parameter for the express checkout add-to-cart API call.
* Dev - Replaces part of the StoreAPI call code for the cart endpoints to use the newly introduced filter.
* Fix - Clear cart first when using express checkout inside the product page.
* Fix - Avoid Stripe timeouts for the express checkout click event.
* Fix - Switch booking products back to using non-StoreAPI add-to-cart methods.
* Dev - Add new E2E tests for Link express checkout.
* Add - Add Amazon Pay to block cart and block checkout.
* Fix - Remove intentional delay when displaying tax-related notice for express checkout, causing click event to time out.
* Fix - Fixes an issue when saving Bancontact and iDEAL methods with SEPA Direct Debit disabled.
* Dev - Introduces new payment method constants for the express methods: Google Pay, Apple Pay, Link, and Amazon Pay.
* Fix - Prevent an express checkout element's load errors from affecting other express checkout elements.
* Tweak - Process ECE cart requests using the Blocks (Store) API.
* Add - Adds a new setting to toggle saving of Bancontact and iDEAL methods as SEPA Debit.
* Add - Wrap Amazon Pay in feature flag.
* Fix - Allow the saving of Bancontact tokens when SEPA is disabled.
* Tweak - Use WC Core's rate limiter on "Add payment method" page.
* Add - New Amazon Pay payment method in the Stripe Express Checkout Element for the classic, shortcode (classic) checkout, product, and cart pages.
* Dev - Introduces new payment intent status constants for the frontend.
* Fix - Fix Stripe customer creation when using the Blocks API for express checkout.
* Add - Add new payment processing flow using confirmation tokens.
* Dev - Adds new logs to identify why express payment methods are not being displayed.
* Fix - Fixes a fatal error when editing the shortcode checkout page with an empty cart on PHP 8.4.
* Fix - Fixes processing of orders through the Pay for Order page when using ECE with Blocks (Store) API.
* Add - Enables the use of Blocks API for Express Checkout Element orders by default.
* Add - Adds a new filter to allow changing the user attributed to an order when paying for it through the Order Pay page.
* Fix - Fixes an error with the fingerprint property setting when using the legacy checkout.
* Fix - Fixes order attribution data for the Express Checkout Element when using the Blocks API to process.
* Tweak - Process ECE orders using the Blocks API.
* Fix - Fixes incorrect error message for card failures due insufficient funds on the shortcode checkout page (legacy).
* Fix - Fixes deprecation warnings related to nullable method parameters when using PHP 8.4, and increases the minimum PHP version Code Sniffer considers to 7.4.
* Fix - Adds support for the Reunion country when checking out using the new checkout experience.
* Add - Support zero-amount refunds.
* Fix - A potential fix to prevent duplicate charges.
* Fix - Prevent empty settings screen when cancelling changes to the payment methods display order.
* Fix - Improve product page caching when Express Payment buttons are not enabled.
* Fix - Allow editing uncaptured orders but show a warning about the possible failure scenario.
* Fix - Fetch the payment intent status on order edit page only for unpaid orders if manual capture is enabled.
* Fix - Error when changing subscription payment method to a 3D Secure card while using a custom checkout endpoint.
* Fix - Fixes the webhook order retrieval by intent charges by adding an array check.
* Add - Add total tax amount to metadata.
* Update - Update the translation for payment requests settings section notice.
* Add - Add Amazon Pay to settings express checkout section.
* Add - Add Amazon Pay customize express checkout page.
* Fix - Improve the appearance of Stripe elements in checkout pages to match the store theme.
* Fix - Hide ECE button for synced subscription variations.
* Fix - Use the original shipping address for Amazon Pay pay for orders.
* Tweak - Improve slow query for legacy SEPA subscriptions on WC status tools page by caching the data.
* Tweak - Improve settings page load by delaying oauth URL generation.
* Tweak - Update the Woo logo in the Configure connection modal
* Add - Add currency restriction pill on Amazon Pay.
* Fix - Express checkout methods dependency.

[See changelog for all versions](https://raw.githubusercontent.com/woocommerce/woocommerce-gateway-stripe/trunk/changelog.txt).
