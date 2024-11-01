=== Zoom for WordPress ===
Contributors: dkjensen, seattlewebco
Tags: zoom,webinars,meetings,woocommerce
Requires at least: 5.4
Tested up to: 5.7.2
Requires PHP: 7.0.0
Stable tag: 1.5.4
License: GPL-3.0
License URI: https://github.com/seattlewebco/wp-zoom/blob/master/LICENSE

Sell, display, register users for webinars with Zoom for WordPress

== Description ==
Zoom for WordPress has a native WooCommerce integration, allowing you to sell webinars and register users automatically when completing checkout.

This plugin integrates with the Zoom API located at https://api.zoom.us/v2


== Installation ==

= Minimum Requirements =

* PHP 7.0 or greater is required
* MySQL 5.6 or greater is recommended

1. Visit [Zoom Marketplace](https://marketplace.zoom.us/develop/create) to create a new App
2. Click create under OAuth apps
3. User-level app 
4. Do not select publish app on Zoom App Marketplace
5. In App Credentials, set the Redirect URL for OAuth to use your WordPress site URL with the following path appended to the end: 
 **/wp-admin/options-general.php?page=wp-zoom**
6. Add the same URL to the list of whitelisted URLs
7. Use the following scopes: **user_profile**, **webinar:read**, **webinar:write**
8. Define `WP_ZOOM_CLIENT_ID` and `WP_ZOOM_CLIENT_SECRET` in your **wp-config.php**, use the values given when creating the app from the steps above
9. Login to WordPress, navigate to the admin dashboard and visit **WooCommerce > Settings > Integrations > Zoom**
10. Proceed to authorize with Zoom
