=== Estonian Banklinks for WooCommerce ===
Contributors: konektou, ristoniinemets, mstannu
Tags: woocommerce, estonia, banklink, pangalink, payment gateway
Requires at least: 4.1
Tested up to: 6.7.1
Stable tag: 1.6
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

= 1.3.3 =
Krediidipank has been renamed to Coop and Nordea to Luminor. New logo for Coop is provided in the update, but you will have to change logo file URL from settings manually (krediidipank.png -> coop.png in the end of the logo URL).
