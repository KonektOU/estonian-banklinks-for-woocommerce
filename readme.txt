=== Estonian Banklinks for WooCommerce ===
Contributors: konektou, ristoniinemets, mstannu
Tags: woocommerce, estonia, banklink, pangalink, payment gateway
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.7
WC requires at least: 7.0
WC tested up to: 11.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Extends WooCommerce with most commonly used Estonian banklinks. All in one.

== Description ==

This plugin consists of several Estonian banklinks:

*   Danske, Coop, LHV, SEB, Swedbank, Luminor, Liisi ID (iPizza protocol)
*   Luminor (Solo protocol)
*   Maksekeskus (Redirect), Maksekeskus Billing API (BETA)
*   Estcard (E-Commerce Payment Gateway)

Code is maintained and developed at Github. Contributions and discussions are very welcome at [Github](https://github.com/KonektOU/estonian-banklinks-for-woocommerce)

The gateways work on both the shortcode checkout and the checkout block. The one exception is Maksekeskus Billing API, which needs the customer to pick a bank before the order is placed; that chooser is not built as a block field yet, so that gateway is offered on the shortcode checkout only.


== Installation ==

1. Upload `estonian-banklinks-for-woocommerce` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the `Plugins` menu in WordPress
3. Go to WooCommerce - Settings
4. Payment gateways will be available to be configured in "Checkout" settings

== Screenshots ==

1. All Payment Gateways
2. Example of LHV banklink gateway
3. WooCommerce Checkout page

== Changelog ==

= 1.7 =
* Fix: Luminor (iPizza) requests were signed with SHA-512 while telling the bank they were SHA-1. The method that was to return the algorithm was misspelled when it was added in 1.6, so it was never called
* Fix: Estcard looked its transaction up with a hand-written query against wp_postmeta, while the plugin declares itself compatible with high performance order storage - where order meta is not in wp_postmeta. On a store that had switched, no Estcard payment could be matched to its order
* Feature: the gateways are registered with the checkout block, so they appear on a store using it. They were invisible there before
* Security: MAC comparisons use hash_equals() instead of ==
* Security: a verified response is checked against the order's amount and currency before the order is completed. Filterable per gateway with woocommerce_{gateway_id}_response_matches_order
* Fix: a response naming an order that does not exist no longer takes the site down with a fatal
* Fix: a missing or unreadable bank public key fails the payment instead of throwing a TypeError on PHP 8
* Fix: Maksekeskus responses are verified against the JSON string as received, which is what Maksekeskus signs. The re-encoded form is still accepted
* Fix: iPizza responses are unslashed before the MAC is built, so a payer name with an apostrophe in it verifies
* Fix: Maksekeskus Billing API showed a blank page when the API call failed or no bank had been picked; it now says so and offers the payment page again
* Fix: debug logging honoured WP_DEBUG_LOG only when it was exactly true, so nothing was written on a site logging to its own file
* Tested against PHP 8.4 and WooCommerce 11

= 1.6 =
* Add SHA512 encryption support for IPIZZA protocols
* Fix translations loading in WP 6.7

= 1.5 =
* Run code through PHPCS
* Add and declare HPOS compatibility

= 1.4 =
* Added Maksekeskus Billing API gateway (beta) for better and simpler checkout with Maksekeskus
* Fix: Incorrectly formatted additionalinfo field for Estcard caused issues and was not used in MAC data
* Cleaned up code

= 1.3.4 =
* Fix: PHP 7.1 rounding issue, which caused Maksekeskus transactions to fail
* Forced Maksekeskus' transaction URL
* Added filter `(woocommerce_{$gateway_id}_gateway_transaction_fields)` to hook into transaction data

= 1.3.3 =
* Renamed Krediidipank to Coop
* Renamed Nordea to Luminor
* Fix: WooCommerce 3 minor compatibility fix with Maksekeskus

= 1.3.2 =
* Fix: Order ID and such should not be accessed directly with WooCommerce 3.0.x. Compatibility fix.
* Save transaction ID with iPizza protocol

= 1.3.1 =
* Compability with WooCommerce 3.0.x

= 1.3 =
* Liisi ID via IPIZZA protocol.

= 1.2 =
* Nordea payments via IPIZZA protocol. Older SOLO protocol remains available.

= 1.1 =
* "Maksekeskus Redirect" gateway compability with newest system

= 1.0.2 =
* Fix: Estcard ecuno has to be unique

= 1.0.1 =
* Compressed bank logos (approx. 75KB total)

= 1.0 =
* Release


== Upgrade Notice ==

= 1.7 =
Luminor iPizza signing is fixed, Estcard works on high performance order storage, and the gateways now show up in the checkout block.

= 1.3.3 =
Krediidipank has been renamed to Coop and Nordea to Luminor. New logo for Coop is provided in the update, but you will have to change logo file URL from settings manually (krediidipank.png -> coop.png in the end of the logo URL).
