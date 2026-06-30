=== Woo Customer Group Discount ===
Contributors: sourov
Tags: woocommerce, discount, customer groups, wholesale, pricing
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Define customer groups, give each a percentage discount, and assign customers from one admin panel.

== Description ==

Manage discounts centrally instead of juggling per-customer coupons. Create customer
groups, set a percentage discount for each, and assign customers to a group from a single
panel under **WooCommerce → Customer Groups**.

**Features**

* Unlimited customer groups, each with its own percentage discount.
* Assign customers to a group right inside the panel (reuses WooCommerce's customer search).
* Two discount modes (global setting):
  * **Cart discount line** (default) — the saving shows as a discount line at cart/checkout.
  * **Adjusted prices** — product prices are reduced everywhere, wholesale/B2B style.
* No custom database tables; built on WordPress options and user meta.
* Compatible with WooCommerce High-Performance Order Storage (HPOS).
* Translation-ready; ships with German (de_DE).

One customer belongs to one group. The discount applies store-wide to the cart subtotal.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install the zip via
   **Plugins → Add New → Upload Plugin**.
2. Activate the plugin (WooCommerce must be active).
3. Go to **WooCommerce → Customer Groups**.
4. Choose a discount mode, create a group with a percentage, and add customers to it.

== Frequently Asked Questions ==

= Can a customer be in more than one group? =
No. Each customer belongs to a single group; adding them to a group removes them from any other.

= Does the discount stack with coupons? =
The group discount is applied independently. In cart mode it appears as its own discount line
alongside any coupons.

= Where do I assign a customer to a group? =
Inside the group's edit screen in the Customer Groups panel — search for the customer and add them.

== Changelog ==

= 1.0.3 =
* Add German (de_DE) translation and load the plugin text domain.

= 1.0.2 =
* Add a configurable per-group label for the cart/checkout discount line (supports a {percent} token).

= 1.0.1 =
* Add GitHub release automation on version change.

= 1.0.0 =
* Initial release: customer groups, per-group percentage discount, cart-line and adjusted-price modes.
