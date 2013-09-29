=== NinjaFirewall (WP edition) ===
Contributors: nintechnet
Tags: attack, backdoor, botnet, brute-force, denial, firewall, hack, infection, injection, login, malware, nintechnet, ninja, phishing, prevention, protection, security, trojan, virus, WAF, wp-login, XSS
Requires at least: 3.3.0
Tested up to: 3.6.1
Stable tag: 1.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A true web application firewall for WordPress.

== Description ==

NinjaFirewall (WP edition) is a true web application firewall. Although it can be installed and configured just like a plugin, it is a stand-alone firewall that sits in front of WordPress.

It will hook, scan, sanitise or reject any HTTP / HTTPS request sent to a PHP script before it reaches WordPress. All scripts located inside the blog installation directories and sub-directories will be protected, including those that aren't part of the WordPress package. Even encoded PHP scripts, hackers shell scripts and backdoors will be filtered by NinjaFirewall.

= Web Application Firewall =

* Full standalone web application firewall
* Multi-site support
* Protects against RFI, LFI, XSS, code execution, SQL injections, brute-force scanners, shell scripts, backdoors and many other threats
* Scans and/or sanitises GET / POST requests, HTTP / HTTPS traffic, cookies, server variables (HTTP_USER_AGENT, HTTP_REFERER, PHP_SELF, PATH_TRANSLATED, PATH_INFO)
* Sanitises variables names and values
* Advanced filtering options (ASCII control characters, NULL byte, PHP built-in wrappers, base64 decoder)
* Blocks/allows uploads, sanitises uploaded file names
* Blocks suspicious bots and scanners
* Hides PHP error and notice messages
* Blocks direct access to PHP scripts located inside specific directories
* Whitelist option for WordPress administrator(s), localhost and private IP address spaces
* Configurable HTTP return code and message
* Rules editor to enable/disable built-in security rules
* Activity log and statistics
* Debugging mode

= Brute-Force Attack Protection =

By processing incoming HTTP requests before your blog and any of its plugins, NinjaFirewall is the **only** plugin for WordPress able to protect it against very large brute-force attacks, including distributed attacks coming from thousands of different IPs.

See our benchmark and stress-test: [WordPress brute-force detection plugins comparison](http://nintechnet.com/1.1.1/ "").

= Events Notification =

NinjaFirewall can alert you by email on specific events triggered within your blog. Some of those alerts are enabled by default and it is highly recommended to keep them enabled. It is not unusual for a hacker, after breaking into your WordPress admin console, to install or just to upload a backdoored plugin or theme in order to take full control of your website.

Monitored events:

* Administrator login
* Plugins upload, installation, (de)activation, update, deletion
* Themes upload, installation, activation, deletion
* WordPress update

= Low Footprint Firewall =

NinjaFirewall is very fast, optimised, compact, and requires very low system resource.
Don't believe us? See for yourself: download and install [GoDaddy's P3 Plugin Performance Profiler](http://wordpress.org/plugins/p3-profiler/ "") and compare NinjaFirewall performances with other security plugins.

= Contextual Help =

Each NinjaFirewall menu page has a contextual help screen with useful information about how to use and configure it.
If you need help, click on the *Help* menu tab located in the upper right corner of each page in your admin panel.

= Strong Privacy Policy =

[NinTechNet](http://nintechnet.com/ "NinTechNet") strictly follows the [WordPress Plugin Developer guidelines](http://wordpress.org/plugins/about/guidelines/ ""): our software, [NinjaFirewall (WP edition)](http://ninjafirewall.com/ninjafirewall_wp.html "NinjaFirewall"), is 100% free, 100% open source and 100% fully functional, no "trialware", no "obfuscated code", no "crippleware", no "phoning home".
It does not require a registration process or an activation key to be used or installed.
Because we do not collect any user data, we do not even know that you are using (and hopefully enjoying!) our product.

= Requirements =

* WordPress 3.3 or higher
* PHP 5.3 or higher
* Apache / Nginx / LiteSpeed
* Unix-like OS (Linux, BSD) only


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

= 1.1.1 =
* Added protection against very large brute-force attacks, including distributed attacks coming from thousands of different IPs (see new `Login Protection` menu).
* Fixed firewall initialisation error due to user defined WP_CONTENT_DIR.
* Fixed a bug where an extended ASCII code could make the log unreadable from WP admin console.

= 1.1.0 =
* Added multi-site network support.
* Added an option to decode and scan base64 encoded values in POST requests (Firewall Policies page).

= 1.0.4 =
* Added an `E-mail Alerts` configuration page to send alerts on specific events (users login, themes/plugins installation, activation, deletion etc).
* Added `Privacy Policy` to the About page and to the installer.

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

= 1.1.1 =
* Added protection against very large brute-force attacks, including distributed attacks coming from thousands of different IPs (see new `Login Protection` menu).
* Fixed firewall initialisation error due to user defined WP_CONTENT_DIR.
* Fixed a bug where an extended ASCII code could make the log unreadable from WP admin console.

= 1.1.0 =
* Added multi-site network support.
* Added an option to decode and scan base64 encoded values in POST requests (Firewall Policies page).

= 1.0.4 =
* Added an `E-mail Alerts` configuration page to send alerts on specific events (users login, themes/plugins installation, activation, deletion etc).
* Added `Privacy Policy` to the About page and to the installer.

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
