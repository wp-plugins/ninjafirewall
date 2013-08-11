=== NinjaFirewall (WP edition) ===
Contributors: nintechnet
Tags: firewall, security, protection, malware, virus, hacking, attack, admin
Requires at least: 3.3.0
Tested up to: 3.6
Stable tag: 1.0.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A true web application firewall for WordPress.

== Description ==

NinjaFirewall (WP edition) is a true web application firewall. Although it can be installed and configured just like a plugin, it is a stand-alone firewall that sits in front of WordPress.

It will hook, scan, sanitise or reject any HTTP / HTTPS request sent to a PHP script before it reaches WordPress. All scripts located inside the blog installation directories and sub-directories will be protected, including those that aren't part of the WordPress package. Even encoded PHP scripts, hackers shell scripts and backdoors will be filtered by NinjaFirewall.

* Full standalone web application firewall
* Protects against RFI, LFI, XSS, code execution, SQL injections, brute-force scanners, shell scripts, backdoors and many other threats
* Scans and/or sanitises GET / POST requests, HTTP / HTTPS traffic, cookies, server variables (HTTP_USER_AGENT, HTTP_REFERER, PHP_SELF, PATH_TRANSLATED, PATH_INFO)
* Sanitises variables names and values
* Blocks/allows uploads, sanitises uploaded file names
* Blocks suspicious bots and scanners
* Hides PHP error and notice messages
* Advanced filtering options (ASCII control characters, NULL byte, PHP built-in wrappers)
* Blocks direct access to PHP scripts located inside specific directories
* Whitelist option for WordPress administrator(s), localhost and private IP address spaces
* Configurable HTTP return code and message
* Activity log and statistics
* Debugging mode

[NinjaFirewall (WP edition)](http://ninjafirewall.com/ninjafirewall_wp.html "NinjaFirewall") is 100% free, 100% open source and 100% fully functional: no trialware, no registration or activation key needed :)


= Requirements =

* WordPress 3.3 or higher (single-user version only)
* PHP 5.3 or higher
* Apache / Nginx / LiteSpeed
* Unix-like OS (Linux, *BSD) only

== Installation ==

1. Upload `ninjafirewall` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Plugin settings are located in 'NinjaFirewall' menu.

== Screenshots ==

1. NinjaFirewall stats page.
2. NinjaFirewall options page.
3. NinjaFirewall Policies 1/4
4. NinjaFirewall Policies 2/4
5. NinjaFirewall Policies 3/4
6. NinjaFirewall Policies 4/4
7. NinjaFirewall log

== Upgrade Notice ==

= 1.0.3 =
* Added a `Rules Editor` menu to enable/disable built-in rules individually.
* Fixed installation issue with Listespeed HTTP server when using Apache-style configuration directives (`php_value`).
* Added a call to `stripslashes()` to prevent WordPress from escaping quotes in the "Blocked user message" textarea.

= 1.0.2 =
* Updated firewall rules.
* Added extensive contextual help to the Firewall Policies page.
* Fixed some code, minor errors and typos.

= 1.0.1 =
* Fixed a `Call to undefined function flatten()` error message.
* NinjaFirewall will warn and refuse to install if `SAFE_MODE` is enabled with PHP 5.3+.

= 1.0.0 =
* Initial release.

== Changelog ==

= 1.0.3 =
* Added a `Rules Editor` menu to enable/disable built-in rules individually.
* Fixed installation issue with Listespeed HTTP server when using Apache-style configuration directives (`php_value`).
* Added a call to `stripslashes()` to prevent WordPress from escaping quotes in the "Blocked user message" textarea.

= 1.0.2 =
* Updated firewall rules.
* Added extensive contextual help to the Firewall Policies page.
* Fixed some code, minor errors and typos.

= 1.0.1 =
* Fixed a `Call to undefined function flatten()` error message.
* NinjaFirewall will warn and refuse to install if `SAFE_MODE` is enabled with PHP 5.3+.

= 1.0.0 =
* Initial release.
