=== NinjaFirewall (WP edition) ===
Contributors: nintechnet
Tags: attack, backdoor, botnet, brute force, brute force attack, brute force protection, denial, firewall, hack, infection, injection, login, malware, nintechnet, ninja, phishing, prevention, protection, security, trojan, user enumeration, virus, WAF, Web application firewall, wp-login, XML-RPC, XSS
Requires at least: 3.3.0
Tested up to: 3.9.0
Stable tag: 1.1.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A true Web Application Firewall.

== Description ==

NinjaFirewall (WP edition) is a true Web Application Firewall. Although it can be installed and configured just like a plugin, it is a stand-alone firewall that sits in front of WordPress.

It will hook, scan, sanitise or reject any HTTP / HTTPS request sent to a PHP script before it reaches WordPress or any of its plugins. All scripts located inside the blog installation directories and sub-directories will be protected, including those that aren't part of the WordPress package. Even encoded PHP scripts, hackers shell scripts and backdoors will be filtered by NinjaFirewall.

= Web Application Firewall =

* Full standalone web application firewall
* Multi-site support
* IPv6 compatible
* Protects against RFI, LFI, XSS, code execution, SQL injections, brute-force scanners, shell scripts, backdoors and many other threats
* Scans and/or sanitises GET / POST requests, HTTP / HTTPS traffic, cookies, server variables (HTTP_USER_AGENT, HTTP_REFERER, PHP_SELF, PATH_TRANSLATED, PATH_INFO)
* Sanitises variables names and values
* Advanced filtering options (ASCII control characters, NULL byte, PHP built-in wrappers, base64 decoder)
* Blocks username enumeration scanning attempts through the author archives and the login page
* Blocks/allows uploads, sanitises uploaded file names
* Blocks suspicious bots and scanners
* Hides PHP error and notice messages
* Blocks direct access to PHP scripts located inside specific directories
* Blocks access to WordPress XML-RPC API
* Whitelist option for WordPress administrator(s), localhost and private IP address spaces
* Configurable HTTP return code and message
* Rules editor to enable/disable built-in security rules
* Activity log and statistics
* Debugging mode

= Brute-Force Attack Protection =

By processing incoming HTTP requests before your blog and any of its plugins, NinjaFirewall is the **only plugin** for WordPress able to protect it against very large brute-force attacks, including distributed attacks coming from several thousands of different IPs.

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

[NinTechNet](http://nintechnet.com/ "NinTechNet") strictly follows the [WordPress Plugin Developer guidelines](http://wordpress.org/plugins/about/guidelines/ ""): our software, [NinjaFirewall (WP edition)](http://ninjafirewall.com/wordpress/ "NinjaFirewall"), is 100% free, 100% open source and 100% fully functional, no "trialware", no "obfuscated code", no "crippleware", no "phoning home".
It does not require a registration process or an activation key to be installed or used.
Because **we do not collect any user data**, we do not even know that you are using (and hopefully enjoying!) our product.

= Need more security ? =

Check out our new supercharged edition: [NinjaFirewall WP+](http://ninjafirewall.com/wordpress/nfwplus.php "NinjaFirewall WP+")

* Unix shared memory use for inter-process communication and blazing fast performances.
* IP-based Access Control.
* Role-based Access Control.
* Country-based Access Control via geolocation.
* URL-based Access Control.
* Bot-based Access Control.
* Antispam for comment and user regisration forms.
* Rate limiting option to block aggressive bots, crawlers, web scrapers and HTTP DoS attacks.
* Response body filter to scan the output of the HTML page right before it is sent to your visitors browser.
* Better File uploads management.
* Better logs management.
* Full IPv6 compatibility.

[NinjaFirewall WP+](http://ninjafirewall.com/wordpress/nfwplus.php "NinjaFirewall WP+"), the supercharged edition.


= Requirements =

* WordPress 3.3 or higher
* PHP 5.3 or higher
* Apache / Nginx / LiteSpeed
* Unix-like OS (Linux, BSD) only

== Frequently Asked Questions ==

= Why is NinjaFirewall different from other security plugins for WordPress ? =

NinjaFirewall sits between the attacker and WordPress. It can filter requests before they reach your blog and any of its plugins. This is how it works :

`Attacker > HTTP server > PHP > NinjaFirewall > WordPress > Plugins`

And this is how all WordPress plugins work :

`Attacker > HTTP server > PHP > WordPress > Plugins`

= Do I need root privileges to install NinjaFirewall ? =

NinjaFirewall does not require any root privileges and is fully compatible with shared hosting accounts. You can install it from your WordPress admin console, just like a regular plugin.


= Does it work with Nginx ? =

NinjaFirewall works with any Unix-based HTTP server (Apache, Nginx, LiteSpeed etc). Please [follow these steps](http://wordpress.org/support/topic/nginx-instructions "").

= Do I need to alter my PHP scripts ? =

You do not need to make any modifications to your scripts. NinjaFirewall hooks all requests before they reach your scripts. It will even work with encoded scripts (ionCube, ZendGuard, SourceGuardian etc).

= I moved my wp-config.php file to another directory. Will it work with NinjaFirewall ? =

Since version 1.1.3, you can use an optional configuration file to tell NinjaFirewall where is located your WordPress configuration file, wp-config.php, if you moved it to another directory. Please [follow these steps](http://nintechnet.com/nfwp/1.1.3/ "").

= Will NinjaFirewall detect the correct IP of my visitors if I am behind a CDN service like Cloudflare ? =

You can use an optional configuration file to tell NinjaFirewall which IP to use. Please [follow these steps](http://nintechnet.com/nfwp/1.1.3/ "").

= Will it slow down my site ? =

Your visitors will not notice any difference with or without NinjaFirewall. From WordPress administration console, you can click "NinjaFirewall > Status" menu to see the benchmarks and statistics (the fastest, slowest and average time per request). NinjaFirewall is very fast, optimised, compact, requires very low system resources and [outperforms all other security plugins](http://nintechnet.com/wordpress-brute-force-detection-plugins-benchmarks.html "").
By blocking dangerous requests and bots before WordPress is loaded, it will save bandwidth and reduce server load.

= Is there any Windows version ? =

NinjaFirewall works on Unix-like servers only. There is no Windows version and we do not expect to release any.


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

= 1.1.9 =
This update installs a new set of security rules and fixes a few issues. See Changelog for more details.

== Changelog ==

= 1.1.9 =
* NinjaFirewall is now fully compatible with IPv6.
* All logs will have a .php extension in order to be protected by NinjaFirewall if the HTTP server does not support .htaccess (Nginx, Lighttpd, Cherokee, OpenLiteSpeed etc).
* Fixed a small JS issue in the "Login Protection" page (the 'onChange' event wasn't working well with IE browsers).
* The firewall blocked message will now return by default around 700 bytes only, instead of 8Kb.
* Introducing a new supercharded edition of NinjaFirewall (see "WP+ Edition" page).

= 1.1.8 =
* Updated firewall rules.
* Fixed a bug where notifications were not sent to the contact email address given by the user ("Event Notifications" page).
* The "Protect against username enumeration" option ("Firewall Policies" page) will not be enabled by default, to prevent Google bot from being blocked.
* Modified the handling of session_start.
* Added a stats file to summarize the firewall log statistics in order to speed up the display of the dashboard widget when the log is huge.
* Added new features to the `.htninja` file to quickly allow or block visitors. See `http://nintechnet.com/nfwp/1.1.3/` for full details.

= 1.1.7 =
* Updated firewall rules.
* Tweaked security rules ID 100 and 300 to reduce false positives.
* Fixed some code and minor errors.

= 1.1.6 =
* Updated firewall rules.
* Added an option to sanitise HTTP REQUEST variables ("Firewall Policies" page).
* Added NinjaFirewall Statistics widget to WP dashboard.
* Fixed multiple file upload error.
* Fixed a bug where login alerts were sent even when NinjaFirewall was disabled from the "Firewall Options" menu.
* NinjaFirewall status icon in the admin bar (multi-site installation) will always be visible to the Super Admin, even when it is disabled.
* Log file and stats will be saved and restored after upgrading NinjaFirewall.

= 1.1.5 =
* Updated firewall rules.
* Improved admin UI to offer better smartphones compatibility.
* Fixed a bug where the localhost IP was not blacklisted.
* Fixed a bug where some disabled Firewall Policies options were wrongly accessible from the Rules Editor.
* Renamed `E-mail Alerts` menu to `Event Notifications`.

= 1.1.4 =
* Updated firewall rules.
* Fixed potential session timeout for the logged-in admin.
* Fixed dead links in doc.
* Improved installer/uninstaller.
* Added a warning to the firewall status page if the `log` directory is not writable.
* Fixed an undefined `NFW_DOC_ROOT` constant warning.

= 1.1.3 =
* Added an option to block username enumeration scanning attempts through the author archives and the login page (Firewall Policies page).
* Added an option to always enforce HTTP Basic authentication to protect the login page and the possibility to set a custom 'realm' message (Login Protection page).
* Added an optional configuration file that can be used to tell NinjaFirewall where is located the `wp-config.php` file, in the case it was moved to another directory (see `http://nintechnet.com/nfwp/1.1.3/` for full details).
* Added a warning about blocking direct access to PHP scripts located in the `/wp-includes/` directory because it could prevent non-admin users from using the TinyMCE WYSIWYG editor.

= 1.1.2 =
* Updated firewall rules.
* Added an option to block access to WordPress XML-RPC API (Firewall Policies page).
* Better error handling (critical errors will be displayed in the admin console only).
* Fixed a bug where NinjaFirewall brute-force protection was always triggered by the login modals introduced in WordPress 3.6.
* Firewall rules and options are now using `WP_CONTENT_DIR` constant.
* The installer will attempt to detect if WordPress files were installed into a subdirectory different from the root directory.

= 1.1.1 =
* Added protection against very large brute-force attacks, including distributed attacks coming from several thousands of different IPs (see new `Login Protection` menu).
* Fixed firewall initialisation error due to user defined `WP_CONTENT_DIR`.
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

== Upgrade Notice ==

= 1.1.5 =
This update fixes a few bugs, improves some features and installs a new set of security rules. See Changelog for more details.
