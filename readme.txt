=== Propoza for WooCommerce ===
Contributors: info.propoza
Donate link: https://propoza.com/
Tags: request, quote, form, rfq, commerce, ecommerce, e-commerce, conversion, store, shop, sales, pricing, woocommerce,
Requires at least: 4.0.1
Tested up to: 4.9.6
Stable tag: 2.1.2
WC requires at least: 2.2
WC tested up to: 3.4.4
Module dependency: WooCommerce
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Propoza enables your customers to submit quote requests from the WooCommerce front-end with a customizable and user-friendly Request for Quote (RFQ) form.

On submitting the request for quote, the customers receive an email confirmation for the quote requested. The store owner is then enabled to add a custom price for each requested item and send a proposal back to the customer. The customer will be able to accept or decline the offer via a client dashboard or straight from the email and check out with the custom prices.

__Features Include:__

* Add an online quotation form to your Woo-Commerce store that links to your product catalog.
* Receive instant updates when customers submit requests.
* Create price proposals in mere seconds.
* Manage all your proposals in one place.
* Propoza merchant dashboard is fully integrated into your WordPress backend.
* Fully translatable.
* Customers check out with custom prices through your WooCommerce checkout.
* A performance dashboard for all your proposals.
* Let your customers quote individual items on the spot. Through the Request quote button next to the add to cart.
* Limited WooCommerce Bundle support.
* Limited backend proposal creation. (not all product types are supported).
* Adding products to user requested quotes (not all product types are supported).

__The Benefits:__

* Close the Deal: We have built Propoza with you in mind. We know you want to close the deal and win customers.
* All in one place: Never lose track again and keep a close eye on your customer requests.
* Save Time: Manually creating quotes takes time. Expect to save hours of time with Propoza.
* All integrated into your WordPress and WooCommerce backend
* No additional checkout for your customers required, all is managed within WooCommerce
* Increase conversion rates, by adding an extra CTA

For more information visit our [Installation Guide](http://manuals.propoza.com/woocommerce-installation-manual/) or the [Propoza website](https://propoza.com).

== Installation ==

For our installation documentation, please visit the [WooCommerce Integration Page](http://manuals.propoza.com/woocommerce-installation-manual/)

= Installation via The WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'Propoza'
3. Click 'Install Now'
4. Activate the plugin on the Plugin dashboard

= Uploading in WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `propoza-woocommerce.zip.zip` from your computer
4. Click 'Install Now'
5. Activate the plugin in the Plugin dashboard

= Installation Using FTP =

1. Download `propoza-woocommerce.zip`
2. Extract the `propoza` directory to your computer
3. Upload the `propoza` directory to the `/wp-content/plugins/` directory
4. Activate the plugin in the Plugin dashboard

= After installation =

1. Configure the plugin by navigating to the settings page on the plugin page or the Propoza tab under WooCommerce settings.
2. If you do not have an account, please press Setup your account and request a subdomain and API key.
3. Insert your subdomain and API key. Found in the registration e-mail or your Propoza dashboard.
4. Press Save changes
5. Press Test connection to test the connection from WordPress to your propoza account.
6. Create a test quote in the front-end.

== Frequently Asked Questions ==

= How do I get an API key? =

You will receive an API key by e-mail after you have submitted an account on propoza.com.

= How do I get a Web address? =

You can submit a subdomain when creating an account at propoza.com.

= What does it cost? =

All of our plans begin with a free 15 day trial with no credit card required. The Standard Plan is €9,99 per month and the Business Plan €19,99 per month.
We know that some companies receive only the occasional quote request, while some will use Propoza as the backbone of their ordering process. That is why we have made our pricing match your usage - so we grow with you as Propoza will add more features and versions.

= What's in the Standard Plan? =

The Standard Plan adds quotation functionality to the WooCommerce front-end. Store owners reply with a custom price proposal. Propoza sends quotes to customers by email including PDF attachment. Price proposals can be either rejected with a comment or accepted. The Standard Plan is €9,99 p/m.

= What's in the Business Plan? =

Besides all the essential features of the Standard Plan, the Business plan enables front-end users to convert their quote requests to orders via the WooCommerce checkout. The quotation process is fully automated. The Business Plan is €19 p/m.

== Screenshots ==

1. The customer requests his or her quote on your Woocommerce store
2. Here you can find all of the quote requests by the customer ordered by status.
3. On this quote, you can make the customer the offer he can’t refuse.
4. Add a personal message to your price Proposal.
5. Once the Proposal price is up to date, the customer can either accept or decline your offer.
6. Personalize your Proposals by uploading your company logo or by adding additional information.
7. Check out with your discounted proposal price.

== Upgrade Notice ==
= 2.0 =
Will immediately add a button to request a quote for individual products.

== Changelog ==

= 1.0.0 =
* First Release

= 1.0.1 =
* Minor textual changes

= 1.0.2 =
* Minor textual and layout changes

= 1.0.3 =
* Changed code to comply with WordPress coding conventions

= 1.0.4 =
* Improved: Security
* Improved: Propoza Dashboard
* Fixed: IE 8 & 9 Bugs
* Fixed: Many small issues

= 1.0.5 =
* Fixed: Quote request showing 0 when not logged in

= 1.0.6 =
* Added: Checkout functionality on accepted quote

= 1.0.7 =
* Added: Quote request form builder

= 1.0.8 =
* Improved: Style Quote request form
* Improved: Translations Quote request form
* Fixed: Minor issues

= 1.0.9 =
* Added embedded dashboard

= 1.0.10 =
* Fixed: quote request form not working
* Fixed: Settings link on plugin page not showing
* Fixed: Form tag is stripped from html quote request dialog form
* Fixed: Javascript function ajaxSubmit possible duplicate
* Added: Id to the quote request dialog button

= 1.0.11 =
* Fixed: Fatal error: Call to undefined method WC_Cart::wc_get_cart_url()

= 1.0.12 =
* Improved: Subdomain validation on configuration page
* Small bug fixes

= 1.0.13 =
* Removed: unused files
* Fixed: Call to a member function add_to_cart() on a non-object

= 1.0.15 =
* Fixed: Quote could not be added to the checkout.
* Fixed: WooCommerce Product variations not appearing correctly in Propoza.
* Added: Compatibility with WooCommerce Bundles.
* Small bug fixes

= 1.0.16 =
* Fixed: Quote checkout not working when coupons are disabled

= 1.0.17 =
* Fixed: Request quote button is now again translatable.
* Improved: Compatibility with Woocommerce 3.3.4.

= 1.0.18 =
* Fixed: A bug with multiple variable products + bundles being quoted.
* Fixed: A rare bug with bundles that would make the coupon break on applying it after the coupon was already applied.

= 2.0 =
* Added: Quote individual products right from the product page.
* Added: Keep in tabs with your proposal performance with Propoza's new dashboard. (This feature will also work without upgrading to 2.0)

= 2.1 =
* Added: Option in the settings to enable/disable the request a quote button in the minicart.
* Fixed: Translations for the new quick quote were sometimes not translating correctly.
* Added: Quotations from the backend. See the Propoza settings on how to enable this functionality.
* Added: Adding products to quotes requested by users

= 2.1.1 =
* Fixed: Empty cart on checkout an accepted quote
* Fixed: The minimum spend for this coupon is X

= 2.1.2 =
* Improved: Readability for quick quoting a single item
