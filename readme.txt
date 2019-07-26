=== WooCommerce Visibility by State using Geolocation ===
Contributors: rezendemarcos
Donate link: http://bit.ly/30uLsfs
Tags: woocommerce
Requires at least: 4.0
Tested up to: 5.1.1
Stable tag: 5.1.1
License: GPLv2 
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Restrinja a venda dos produtos cadastrados no WooCommerce por estados brasileiros!

== Description ==

This plugin allows you to restrict products on WooCommerce shop to sell (or not) on specific states, using WooCommerce Geolocation.

== How it works ==

* Go to plugin settings and setup the general visibility options
* For each product in your catalog you can set a list of states to allow or disallow for sale.
* WooCommerce shipping state is used to determine what state the visitor is from a state which is restricted for a product, if a shipping state is not set, WooCommerce Geolocation is used.

You will need WooCommerce 3.0 or newer.

== 3rd Party Service: ipinfo.io ==

We are using https://ipinfo.io/ services in order to obtain  identification of an IP address' geographic location in the real world. This service provides a response that includes every IP’s latitude and longitude coordinates, region, country, postal/ZIP code, and city. So, basically we are getting the IP from visitors calling WooCommerce Geolocation Service and making a json request to ipinfo.io free interface located at http://ipinfo.io/json

Please see the ToS of them: https://ipinfo.io/terms-of-service
