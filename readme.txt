=== NinjaFirewall (WP edition) ===
Contributors: nintechnet, bruandet
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=paypal%40ninjafirewall%2ecom&lc=US&item_name=Donation %20to%20NinjaFirewall%20WP%20Edition%20Plugin&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: attack, backdoor, botnet, brute force, brute force attack, brute force protection, denial, firewall, hack, hhvm, infection, injection, login, malware, nginx, nintechnet, ninja, phishing, prevention, protection, security, shellshock, soaksoak, trojan, user enumeration, virus, WAF, Web application firewall, wp-login, XML-RPC, xmlrpc, XSS
Requires at least: 3.3.0
Tested up to: 4.1.1
Stable tag: 1.3.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A true Web Application Firewall.

== Description ==

NinjaFirewall (WP edition) is a true Web Application Firewall. Although it can be installed and configured just like a plugin, it is a stand-alone firewall that sits in front of WordPress.

It will hook, scan, sanitise or reject any HTTP / HTTPS request sent to a PHP script before it reaches WordPress or any of its plugins. All scripts located inside the blog installation directories and sub-directories will be protected, including those that aren't part of the WordPress package. Even encoded PHP scripts, hackers shell scripts and backdoors will be filtered by NinjaFirewall.

= Web Application Firewall =

* Full standalone web application firewall; works before WordPress is loaded
* Multi-site support
* IPv6 compatible
* Protects against RFI, LFI, XSS, code execution, SQL injections, brute-force scanners, shell scripts, backdoors and many other threats
* Scans and/or sanitises GET / POST requests, HTTP / HTTPS traffic, cookies, server variables (HTTP_USER_AGENT, HTTP_REFERER, PHP_SELF, PATH_TRANSLATED, PATH_INFO)
* Sanitises variables names and values
* Advanced filtering options (ASCII control characters, NULL byte, PHP built-in wrappers, base64 decoder)
* Real-time detection (`File Guard`)
* File integrity monitoring to scan your website hourly/twicedaily/daily (`File Check`)
* `Live Log` to watch your website traffic in real time
* Monitors the database and sends an alert if an administrator account is created, modified or deleted
* Hooks and secures HTTP reponse headers to prevent XSS, phishing and clickjacking attempts(`X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection`)
* Sets the `HttpOnly` flag on all cookies
* Blocks username enumeration scanning attempts through the author archives and the login page
* Blocks/allows uploads, sanitises uploaded file names
* Blocks suspicious bots and scanners
* Hides PHP error and notice messages
* Blocks direct access to PHP scripts located inside specific directories
* Protects WordPress XML-RPC API
* Whitelist option for WordPress administrator(s), localhost and private IP address spaces
* Configurable HTTP return code and message
* Rules editor to enable/disable built-in security rules
* Activity log and statistics
* Debugging mode
* Possibility to [prepend your own PHP code](http://ninjafirewall.com/wordpress/htninja/ "") to the firewall

= Brute-Force Attack Protection =

By processing incoming HTTP requests before your blog and any of its plugins, NinjaFirewall is the only plugin for WordPress able to protect it against very large brute-force attacks, including distributed attacks coming from several thousands of different IPs.

See our benchmark and stress-test:

* [WordPress brute-force attack detection plugins comparison](http://blog.nintechnet.com/wordpress-brute-force-attack-detection-plugins-comparison/ "")

* [WordPress brute-force attack protection in a production environment](http://blog.nintechnet.com/brute-force-attack-protection-in-a-production-environment/ "")

The protection applies to the `wp-login.php` script and can also include the `xmlrpc.php` one; the incident can be written to the server `AUTH` log.

= Events Notification =

NinjaFirewall can alert you by email on specific events triggered within your blog. Some of those alerts are enabled by default and it is highly recommended to keep them enabled. It is not unusual for a hacker, after breaking into your WordPress admin console, to install or just to upload a backdoored plugin or theme in order to take full control of your website.

Monitored events:

* Administrator login
* Modification of any administrator account in the database
* Plugins upload, installation, (de)activation, update, deletion
* Themes upload, installation, activation, deletion
* WordPress update

= Low Footprint Firewall =

NinjaFirewall is very fast, optimised, compact, and requires very low system resource.
Don't believe us? See for yourself: download and install [GoDaddy's P3 Plugin Performance Profiler](http://wordpress.org/plugins/p3-profiler/ "") and compare NinjaFirewall performances with other security plugins.

= Non-Intrusive User Interface =

NinjaFirewall looks and feels like a built-in WordPress feature. It does not contain intrusive banners, warnings or flashy colors. It uses the WordPress simple and clean interface and is also smartphone-friendly.

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

[Learn more](http://ninjafirewall.com/wordpress/nfwplus.php "") about the WP+ edition unique features. [Compare](http://ninjafirewall.com/wordpress/overview.php "") the WP and WP+ editions.


= Requirements =

* WordPress 3.3+
* PHP 5.3+ or [HHVM 3.4+](http://blog.nintechnet.com/installing-ninjafirewall-with-hhvm-hiphop-virtual-machine/ "")
* Apache / Nginx / LiteSpeed
* Unix-like OS (Linux, BSD) only

== Frequently Asked Questions ==

= Why is NinjaFirewall different from other security plugins for WordPress ? =

NinjaFirewall sits between the attacker and WordPress. It can filter requests before they reach your blog and any of its plugins. This is how it works :

`Attacker > HTTP server > PHP > NinjaFirewall > WordPress`

And this is how all WordPress plugins work :

`Attacker > HTTP server > PHP > WordPress > Plugins`

Unlike other security plugins, it will protect all PHP scripts, including those that aren't part of the WordPress package.

= Do I need root privileges to install NinjaFirewall ? =

NinjaFirewall does not require any root privilege and is fully compatible with shared hosting accounts. You can install it from your WordPress admin console, just like a regular plugin.


= Does it work with Nginx ? =

NinjaFirewall works with Nginx and others Unix-based HTTP servers (Apache, LiteSpeed etc). Its installer will detect it.

= Do I need to alter my PHP scripts ? =

You do not need to make any modifications to your scripts. NinjaFirewall hooks all requests before they reach your scripts. It will even work with encoded scripts (ionCube, ZendGuard, SourceGuardian etc).

= I moved my wp-config.php file to another directory. Will it work with NinjaFirewall ? =

Since version 1.1.3, you can use an optional configuration file to tell NinjaFirewall where is located your WordPress configuration file, wp-config.php, if you moved it to another directory. Please [follow these steps](http://ninjafirewall.com/wordpress/htninja/ "").

= Will NinjaFirewall detect the correct IP of my visitors if I am behind a CDN service like Cloudflare ? =

You can use an optional configuration file to tell NinjaFirewall which IP to use. Please [follow these steps](http://ninjafirewall.com/wordpress/htninja/ "").

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

1. NinjaFirewall Options page.
2. NinjaFirewall Policies page.
3. NinjaFirewall Statistics page.
4. NinjaFirewall Brute Force Protection page.
5. NinjaFirewall event Notifications page.
6. NinjaFirewall Log page.
7. NinjaFirewall Contextual Help.

== Changelog ==

= 1.3.7 =
* Added a new feature: "Live Log". It lets you watch your website traffic in real time.
* Fixed a bug in the "Event Notifications" email alert: after an update, the name of the (re)activated plugin was missing.
* It is now possible to create the ".htninja" optional configuration file in either the document root or its parent directory (see http://ninjafirewall.com/wordpress/htninja/).
* NinjaFirewall will not block access to the TinyMCE WYSIWYG editor even if the option to block direct access to any PHP file located in the `/wp-includes/` folder is enabled (see "Firewall Policies" page).

= 1.3.6 =
* Added protection against the FancyBox for WordPress 0-day vulnerability.

= 1.3.5 =
* Updated security rules.
* Added an option to select HHVM (HipHop Virtual Machine) during the installation process. See our blog about installing NinjaFirewall on HHVM (http://nin.link/hhvm).
* The plugin and theme editors will no longer be disabled by default.
* The maximum length of the username and password from the "Login Protection" option was increased to 32 characters.
* Added an option to exclude a folder from being monitored by File Guard (see "File Guard > Exclude the following folder" option).
* The installer will send an email to the administrator with some info and links that could be helpful if there was a problem or crash during NinajFirewall installation/activation.
* The installer will comment out any `auto_prepend_file` directive that may be found in the PHP INI file prior to insert its own one.
* The database monitoring option will save its data to a file whose name will be based on the `blog_id` and `site_id` variables to prevent potential false detection alerts.
* [WP+ edition] The priority of the antispam `add filter` and `add_action` hooks was lowered in order to execute them earlier.
* [WP+ edition] Updated IPv4/IPv6 GeoIP databases.

= 1.3.4 =
* Added a new option to monitor the database and send an alert if an administrator account is created, modified or deleted (see "Event Notifications > Database").
* Added a "Processing time" legend to File Check snapshot description to display the time it took to perform the scan.
* Updated security rules.
* On new installations, File Guard will be enabled by default.
* NinjaFirewall will refuse to install if the WordPress `/plugins/` directory was renamed.
* Fixed a bug in File Check scheduled scan: it was not disabled when deactivating NinjaFirewall.

= 1.3.3 =
* File Check can now run scheduled scans on a specific interval (hourly, twicedaily or daily) and send reports by email (see "File Check > Options" menu and its contextual help).
* Added an option to select Apache/suPHP SAPI during the installation process.
* Added an option to write all events/alerts to the firewall log (see "Event Notifications > Log").
* Loosened cookies sanitizing rules to reduce the risk of false-positives.

= 1.3.2 =
* Updated security rules to protect against new Slider Revolution/Showbiz Pro shell upload exploit (http://nin.link/fd78).

= 1.3.1 =
* Added a new set of options that can hook the HTTP response headers, including cookies, and modify them on-the-fly to help mitigate threats such as XSS, phishing and clickjacking attacks (see "Firewall Policies > HTTP response headers").
* Updated security rules.
* The function detecting if the firewall is enabled was rewritten and is more accurate and flexible.
* File Check will display date & time using the blog timezone rather than the user localtime.

= 1.3 =
* Added a new feature that can detect changes made to your files (see "File Check" menu and its contextual help).
* Updated security rules.

= 1.2.8 =
* Added a drop-down menu to the "Statistics" page to select and view stats from the previous months.
* Added a drop-down menu to the "Firewall Log" page to select and view logs from the previous months.
* New simpler and intuitive installer.
* Fixed the FORCE_SSL_ADMIN alert that was unnecessarily displayed when the site was already in HTTPS mode.
* Fixed a potential bug in the user enumeration protection that could block a legitimate user.
* Added a warning to WordPress admin console if the log directory does not exist.
* Added missing MIME and charset headers to all emails sent by the firewall.
* Updated "File Guard" contextual help.
* Updated security rules.
* Fixed various small bugs and typos.

= 1.2.7 =
* Added an option to import/export NinjaFirewall configuration (see "Firewall Options" page).
* The firewall logs will be saved to the `wp-content/nfwlog/` folder, to prevent WordPress from deleting them during an update.
* Added a warning to the "Overview" page if the administrator is not whitelisted by the firewall.
* Non-RFC compliant uppercase IPv6 addresses found in the X_FORWARDED_FOR header will no longer be blocked by the firewall (rule #312).
* Rules #151 and #152 (HTTP header injection) were removed to prevent false positives from occurring.
* The "AUTH log" option from the "Login Protection" page will be disabled if the server does not support it.
* Cookies and GET variable sanitizing, as well as HTTP_REFERER scan will be disabled by default in the Firewall Policies page.
* Added a rule to protect against the `shellshock` bash code injection vulnerability (CVE-2014-6271).

= 1.2.6 =
* Added a new option to record brute-force attacks to the server AUTH log (see Login Protection > AUTH log).
* NinjaFirewall is now able to parse the wp-config.php script if the DB_HOST constant is using a "host:port", "host:socket" or "host:port:socket" format.
* Fixed installer bug that could corrupt the .htaccess.
* Fixed Cloudflare and Incapsula detection warning in the "Overview" page. It will not be displayed when the correct IP is used.
* We opened a Twitter account for all updates and upgrades: @nintechnet.

= 1.2.5 =
* Fixed IE browsers italic text bug in the File Guard page.
* Updated security rules.
* Cleaned-up installer and removed useless lines of code.
* Added rules description to the enabled and disabled rules drop-down lists (see Rules Editor).
* Fixed "Invalid argument supplied for foreach" PHP notice.
* Fixed "Undefined variable: auth_pass" (potential) PHP notice.
* Fixed the XML-RPC checkbox in the "Login Protection" page. It is now visible when the protection is set to "Always ON".
* Added reverse proxy/load balancer detection. A message in the "Overview" page will warn the admin about setting up the server or NinjaFirewall in order to use the correct IP.

= 1.2.4 =
* Fixed login protection rejecting username/password on some servers running Apache PHP-CGI with suExec. NinjaFirewall will now use its own very fast authentication scheme rather than relying on the server HTTP Basic authentication.
* The length of the firewall log lines was increased from 100 to 200 characters.
* Fixed potential 500 Internal Server error during installation on Apache servers that do not have the mod_env module loaded.
* Added Cloudflare and Incapsula detection. A message in the "Overview" page will warn the admin about setting up the server or NinjaFirewall in order to use the correct IP.
* Updated security rules.

= 1.2.3 =
* The brute-force attack protection was extended to the XML-RPC API script (xmlrpc.php). See the "Login Protection" page and its contextual help.
* Fixed error when multibyte characters were used in the firewall "Blocked user message".
* Updated security rules.
* Fixed a couple of bugs in the UI (smartphone users).

= 1.2.2 =
* Security update: added protection against the new ThimThumb vulnerability (WebShot Remote Code Execution).

= 1.2.1 =
* Added a new feature that can detect, in real-time, any access to a PHP file that was recently modified/created, and can alert the administrator (see new "File Guard" menu and its contextual help).
* Added a call to `stripslashes()` to prevent WordPress from escaping quotes in the "Login Protection" password.
* The length of the "Login Protection" message (realm) was increased from 100 to 150 characters.
* Removed a small piece of code from the "Login Protection" that could block some browsers.

= 1.2.0 =
* Fixed a bug introduced in v1.1.9 : login alerts were not sent. Sorry for the inconvenience.

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
* Added new features to the `.htninja` file to quickly allow or block visitors. See `http://ninjafirewall.com/wordpress/htninja/` for full details.

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
* Added an optional configuration file that can be used to tell NinjaFirewall where is located the `wp-config.php` file, in the case it was moved to another directory (see `http://ninjafirewall.com/wordpress/htninja/` for full details).
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
