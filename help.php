<?php
/*
 +------------------------------------------------------------------+
 | NinjaFirewall (WordPress edition)                                |
 |                                                                  |
 | (c)2012-2013 NinTechNet                                          |
 | <wordpress@nintechnet.com>                                       |
 +------------------------------------------------------------------+
 | http://nintechnet.com/                                           |
 +------------------------------------------------------------------+
 | REVISION: 2013-11-09 23:31:04                                    |
 +------------------------------------------------------------------+
 | This program is free software: you can redistribute it and/or    |
 | modify it under the terms of the GNU General Public License as   |
 | published by the Free Software Foundation, either version 3 of   |
 | the License, or (at your option) any later version.              |
 |                                                                  |
 | This program is distributed in the hope that it will be useful,  |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of   |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the    |
 | GNU General Public License for more details.                     |
 +------------------------------------------------------------------+
*/


if (! defined( 'NFW_ENGINE_VERSION' ) ) { die( 'Forbidden' ); }


// contextual help - choose Help on the top right
// of the admin panel to preview this.


/* ================================================================== */

function help_nfsubmain() {

	// Overview menu help :

	get_current_screen()->add_help_tab( array(
		'id'        => 'main01',
		'title'     => 'NinjaFirewall Help',
		'content'   => '<br />This is the contextual help&nbsp;!<br />Each NinjaFirewall menu page has such a contextual help screen with useful information about how to use and configure it.<br />&nbsp;'
	) );
}

/* ================================================================== */

function help_nfsubstat() {

	// Stats menu help :

	get_current_screen()->add_help_tab( array(
		'id'        => 'help01',
		'title'     => 'Monthly stats',
		'content'   => '<br />Statistics are taken from the current log. It is rotated on the first day of each month.<br />You can view the log by clicking on the <a href="?page=nfsublog">Firewall Log</a> menu.'
	) );
	get_current_screen()->add_help_tab( array(
		'id'        => 'help02',
		'title'     => 'Benchmarks',
		'content'   => '<br />Benchmarks show the time NinjaFirewall took, in seconds, to proceed each request it has blocked.'
	) );
}
/* ================================================================== */

function help_nfsubopt() {

	// Firewall options menu help :

	get_current_screen()->add_help_tab( array(
		'id'        => 'opt01',
		'title'     => 'Firewall protection',
		'content'   => '<br />This option allows you to disable NinjaFirewall. It has basically the same effect as deactivating it from the <a href="' . admin_url() . 'plugins.php" style="text-decoration:underline;">Plugins menu</a> page.<br />Your site will remain unprotected until you enable it again.'
	) );
	get_current_screen()->add_help_tab( array(
		'id'        => 'opt02',
		'title'     => 'Debugging mode',
		'content'   => '<br />In Debugging mode, NinjaFirewall will not block suspicious requests but will only log them (the <a href="?page=nfsublog">firewall log</a> will display <code>DEBUG_ON</code> in the LEVEL column) and sanitise them according to your <a href="?page=nfsubpolicies">Firewall Policies</a>.'
	) );
	get_current_screen()->add_help_tab( array(
		'id'        => 'opt03',
		'title'     => 'Error code and message to return',
		'content'   => '<br />Lets you customize the HTTP error code returned by NinjaFirewall when blocking a dangerous request and the message to display to the user. You can use any HTML tags and 3 built-in variables:
		<li><code>%%REM_ADDRESS%%</code> : the blocked user IP.</li><li><code>%%NUM_INCIDENT%%</code> : the unique incident number as it will appear in the <a href="?page=nfsublog">firewall log</a> INCIDENT column.</li><li><code>%%NINJA_LOGO%%</code> : NinjaFirewall logo.</li>'
	) );
}
/* ================================================================== */

function help_nfsubpolicies() {

	// Firewall policies menu help :

	get_current_screen()->add_help_tab( array(
		'id'        => 'policies01',
		'title'     => 'Policies overview',
		'content'   => '<br />Because NinjaFirewall sits in front of WordPress, it can hook, scan and sanitise all PHP requests, HTTP variables, headers and IPs before they reach your blog: <code><a href="http://www.php.net/manual/en/reserved.variables.get.php" target="_blank">$_GET</a></code>, <code><a href="http://www.php.net/manual/en/reserved.variables.post.php" target="_blank">$_POST</a></code>, <code><a href="http://www.php.net/manual/en/reserved.variables.cookies.php" target="_blank">$_COOKIES</a></code>, <code><a href="http://www.php.net/manual/en/reserved.variables.request.php" target="_blank">$_REQUEST</a></code>, <code><a href="http://www.php.net/manual/en/reserved.variables.files.php" target="_blank">$_FILES</a></code>, <code><a href="http://php.net/manual/en/reserved.variables.server.php" target="_blank">$_SERVER</a></code> in either or both HTTP &amp; HTTPS mode.<br />Use the options below to enable, disable or to tweak these rules according to your needs.<br />Keep in mind, however, that the Firewall Policies apply to <strong>any PHP scripts</strong> located inside the <code>' . ABSPATH . '</code> directory and its sub-directories, and not only to your WordPress index page.<br />'
	) );
	get_current_screen()->add_help_tab( array(
		'id'        => 'policies02',
		'title'     => 'Scan &amp; Sanitise',
		'content'   => '<br />You can choose to scan and reject dangerous content but also to sanitise requests and variables. Those 2 actions are different and can be combined together for better security.
		<li>Scan : if anything suspicious is detected, NinjaFirewall will block the request and return an <a href="?page=nfsubopt">HTTP error code and message</a>. The user request will fail and the connection will be closed immediately.</li>
		<li>Sanitise : this option will not block but sanitise the user request by escaping characters that can be used to perform code or SQL injections (<code>\'</code>, <code>"</code>, <code>\\</code>, <code>\n</code>, <code>\r</code>, <code>`</code>, <code>\x1a</code>, <code>\x00</code>) and various exploits (XSS etc). If it is a variable, i.e. <code>?name=value</code>, both its name and value will be sanitised.<br />This action will be performed when the filtering process is over, right before NinjaFirewall forwards the request to your PHP script.<br /><br /><img src="' . plugins_url( '/images/icon_warn_16.png', __FILE__ ) . '" border="0" height="16" width="16">&nbsp;<span class="description">If you enabled </span><code>POST</code><span class="description"> requests sanitising, articles and messages posted by your visitors could be corrupted with excessive backslashes or substitute characters.</span></li>'
	) );
	get_current_screen()->add_help_tab( array(
		'id'			=> 'policies04',
		'title'		=> 'Firewall Policies',
		'content'	=> '<br />
		<div style="height:250px;">

		<strong>HTTP / HTTPS</strong>
		<li>Whether to filter HTTP and/or HTTPS traffic.</li>

		<strong>Uploads</strong>
		<li>File Uploads:<span class="description"> whether to allow/disallow file uploads.</span></li>
		<li>Sanitise filenames:<span class="description"> any character that is not a letter <code>a-zA-Z</code>, a digit <code>0-9</code>, a dot <code>.</code>, a hyphen <code>-</code> or an underscore <code>_</code> will be removed from the filename and replaced with the <code>X</code> character.</span></li>

		<strong>GET requests</strong>
		<li>Whether to scan and/or sanitise <code>GET</code> requests.</li>

		<strong>POST requests</strong>
		<li>Whether to scan and/or sanitise <code>POST</code> requests.</li>
		<li>Decode base64-encoded <code>POST</code> requests:<span class="description"> NinjaFirewall will decode and scan base64 encoded values in order to detect obfuscated malicious code. This option is only available for <code>POST</code> requests.</span></li>

		<strong>Cookies</strong>
		<li>Whether to scan and/or sanitise <code>Cookies</code> requests.</li>

		<strong>HTTP_USER_AGENT server variable</strong>
		<li>Whether to scan and/or sanitise <code>HTTP_USER_AGENT</code> requests.</li>
		<li>Block suspicious bots/scanners:<span class="description"> rejects some known bots, scanners and various malicious scripts attempting to access your blog.</span></li>

		<strong>HTTP_REFERER server variable</strong>
		<li>Whether to scan and/or sanitise <code>HTTP_REFERER</code> requests.</li>
		<li>Block POST requests that do not have an <code>HTTP_REFERER</code> header:<span class="description"> this option will block any <code>POST</code> request that does not have a Referrer header (<code>HTTP_REFERER</code> variable). If you need external applications to post to your scripts (ie: Paypal IPN), you are advised to keep this option disabled otherwise they will likely be blocked. Note that <code>POST</code> requests are not required to have a Referrer header and, for that reason, this option is disabled by default.</span></li>

		<strong>IPs</strong>
		<li>Block localhost IP in <code>GET/POST</code> requests:<span class="description"> this option will block any <code>GET</code> or <code>POST</code> request containing the localhost IP (127.0.0.1). It can be useful to block SQL dumpers and various hacker\'s shell scripts.</span></li>
		<li>Block HTTP requests with an IP in the <code>Host</code> header:<span class="description"> this option will reject any request using an IP instead of a domain name in the <code>Host</code> header of the HTTP request. Unless you need to connect to your site using its IP address, (e.g. http://' . $_SERVER['SERVER_ADDR'] . '/index.php), enabling this option will block a lot of hackers scanners because such applications scan IPs rather than domain names.</span></li>
		<li>Do not scan traffic coming from localhost (127.0.0.1) and private IP address spaces:<span class="description"> this option will ignore traffic from all non-routable private IPs (10.0.0.0 to 10.255.255.255, 172.16.0.0 to 172.31.255.255 and 192.168.0.0 to 192.168.255.255) as well as the localhost IP.</span></li>

		<strong>PHP</strong>
		<li>Block PHP built-in wrappers:<span class="description"> PHP has several wrappers for use with the filesystem functions. It is possible for an attacker to use them to bypass firewalls and various IDS to exploit remote and local file inclusions. This option lets you block any script attempting to pass a <code>php://</code>or a <code>data://</code> stream inside a <code>GET</code> or <code>POST</code> request, cookies, user agent and referrer variables.</span></li>
		<li>Hide PHP notice &amp; error messages:<span class="description"> this option lets you hide errors returned by your scripts. Such errors can leak sensitive informations which can be exploited by hackers.</span></li>
		<li>Sanitise <code>PHP_SELF</code>, <code>PATH_TRANSLATED</code>, <code>PATH_INFO</code>:<span class="description"> this option can sanitise any dangerous characters found in those 3 server variables to prevent various XSS and database injection attempts.</span></li>

		<strong>Various</strong>
		<li>Block the <code>DOCUMENT_ROOT</code> server variable <code>' . getenv( 'DOCUMENT_ROOT' ) . '</code> in HTTP requests:<span class="description"> this option will block scripts attempting to pass the <code>DOCUMENT_ROOT</code> server variable in a <code>GET</code> or <code>POST</code> request. Hackers use shell scripts that often need to pass this value, but most legitimate programs do not.</span></li>
		<li>Block ASCII character 0x00 (NULL byte):<span class="description"> this option will reject any <code>GET</code> or <code>POST</code> request, <code>COOKIE</code>, <code>HTTP_USER_AGENT</code>, <code>REQUEST_URI</code>, <code>PHP_SELF</code>, <code>PATH_INFO</code> variables containing the ASCII character 0x00 (NULL byte). Such a character is dangerous and should always be rejected.</span></li>
		<li>Block ASCII control characters 1 to 8 and 14 to 31:<span class="description"> in most cases, those control characters are not needed and should be rejected as well.</span></li>

		<strong>WordPress</strong>
		<li>Whether to block direct access to PHP files located in specific WordPress directories.</li>
		<li>Protect against username enumeration:<span class="description"> it is possible to enumerate usernames either through the WordPress author archives or the login page. Although this is not a vulnerability but a WordPress feature, some hackers use it to retrieve usernames in order to launched more accurate brute-force attacks. NinjaFirewall will not block the request but, if it is a failed login attempt, it will sanitise the error message returned by WordPress and, if it is an author archives scan, it will invalidate it and redirect the user to the blog index page.</span></li>
		<li>Block access to WordPress XML-RPC API (<code>xmlrpc.php</code>):<span class="description"> XML-RPC is a remote procedure call (RPC) protocol which uses XML to encode its calls and HTTP as a transport mechanism. WordPress has an XMLRPC API that can be accessed through the <code>xmlrpc.php</code> file. Since WordPress version 3.5, it is always activated and cannot be turned off. NinjaFirewall allows you to block any access to that file. This option is not enabled by default.</span></li>
		<li>Block <code>POST</code> requests in the themes folder <code>/wp-content/themes</code>:<span class="description"> this option can be useful to block hackers from installing backdoor in the PHP theme files. However, because some custom themes may include an HTML form (contact, search form etc), this option is not enabled by default.</span></li>
		<li>Force SSL for admin and logins <code>FORCE_SSL_ADMIN</code>:<span class="description"> enable this option when you want to secure logins and the admin area so that both passwords and cookies are never sent in the clear. <font color="red"><strong>Warning:</strong></font> ensure that you can access your admin console from HTTPS (<a href="' . admin_url('/','https') . '" target="_blank">' . admin_url('/','https') . '</a>) <strong>before</strong> enabling this option, otherwise you will lock yourself out of your site&nbsp;!</span></li>
		<li>Disable the plugin and theme editor <code>DISALLOW_FILE_EDIT</code>:<span class="description"> disabling the plugin and theme editor provides an additional layer of security if a hacker gains access to a well-privileged user account.</span></li>
		<li>Disable plugin and theme update/installation <code>DISALLOW_FILE_MODS</code>:<span class="description"> this option will block users being able to use the plugin and theme installation/update functionality from the WordPress admin area. Setting this constant also disables the Plugin and Theme editor.</span></li>

		</div><br />'
	) );
	get_current_screen()->add_help_tab( array(
		'id'        => 'policies03',
		'title'     => 'Administrator',
		'content'   => '<br />By default, any logged in WordPress administrator will not be blocked by NinjaFirewall. This applies to all Firewall Policies listed below, except <code>FORCE_SSL_ADMIN</code>, <code>DISALLOW_FILE_EDIT</code> and <code>DISALLOW_FILE_MODS</code> options which, if enabled, are always enforced.<br />'
	) );

}
/* ================================================================== */

function help_nfsubnetwork() {

	// Network (multisite version only) :
	get_current_screen()->add_help_tab( array(
		'id'        => 'network01',
		'title'     => 'Network',
		'content'   => '<br />Even if NinjaFirewall administration menu is only available to the Super Admin (from the main site), you can still display its status to all sites in the network by adding a small NinjaFirewall icon to their admin bar. It will be visible only to the administrators of those sites.<br />It is recommended to enable this feature as it is the only way to know whether the sites in your network are protected and if NinjaFirewall installation was successful.'
	) );
}
/* ================================================================== */

function help_nfsubalerts() {

	// E-mail alerts menu help :

	get_current_screen()->add_help_tab( array(
		'id'        => 'log01',
		'title'     => 'E-mail alerts',
		'content'   => '<br />NinjaFirewall can alert you by email on specific events triggered within your blog. They include installations, updates, activations etc, as well as users login. Some of those alerts are enabled by default and it is highly recommended to keep them enabled. It is not unusual for a hacker, after breaking into your WordPress admin console, to install or just to upload a backdoored plugin or theme in order to take full control of your website.'
	) );
}
/* ================================================================== */

function help_nfsublogin() {

	// Login protection menu help :

	get_current_screen()->add_help_tab( array(
		'id'        => 'login01',
		'title'     => 'Login protection',
		'content'   => '
		<div style="height:250px;">
		<p><img src="http://www.testsite.com/sites/wordpress/wp-content/plugins/ninjafirewall/images/icon_warn_16.png" height="16" border="0" width="16">&nbsp;<span class="description">Disabling NinjaFirewall does not disable the brute-force protection&nbsp;! If you want to completely disable NinjaFirewall, you <strong>must</strong> disable the login protection from this page too.</span></p>

		<p>By processing incoming HTTP requests before your blog and any of its plugins, NinjaFirewall is the only plugin for WordPress able to protect it against very large brute-force attacks, including distributed attacks coming from several thousands of different IPs.</p>
		<p>You can select to enable the protection only if an attack is detected or to keep it always activated:</p>

		<strong>Yes, if under attack :</strong>
		<br />When too many login attempts are detected, it password-protects the login page (wp-login.php) immediately, regardless of the offending IP. It blocks the attack instantly and prevents it from reaching WordPress, but still allows you to access your administration console using a predefined username/password combination. NinjaFirewall uses a simple but fast <a href="http://en.wikipedia.org/wiki/Basic_access_authentication" target="_blank">HTTP Basic authentication implementation</a> and it is compatible with any HTTP server (Apache, Nginx, Lighttpd etc).<br />
		<ul>
		<li>Protect the login page against:<span class="description"> select the type of requests (<code>GET</code> and/or <code>POST</code>) to monitor.</span></li>
		<li>Password-protect the login page:<span class="description"> enter the suitable threshold that will trigger the protection.</span></li>
		<li>HTTP authentication:<span class="description"> enter the user name and password that you want to use to bypass the protection during an attack.</span></li>
		<li>Message:<span class="description"> enter the message to display during the authentication process.</span></li>
		</ul>

		<strong>Always ON :</strong>
		<br />NinjaFirewall will always enforce HTTP Basic authentication implementation and you will be prompted to enter your choosen username/password each time you will access the login page.<br />
		<ul>
		<li>HTTP authentication:<span class="description"> enter the user name and password that you want to use to access the login page.</span></li>
		<li>Message:<span class="description"> enter the message to display during the authentication process.</span></li>
		</ul>

		<br />&nbsp;
		</div>'
	) );
}
/* ================================================================== */

function help_nfsublog() {

	// Firewall log menu help :

	get_current_screen()->add_help_tab( array(
		'id'        => 'log01',
		'title'     => 'Firewall Log',
		'content'   => '<br />The firewall log displays blocked and sanitised requests as well as some useful information. It has 6  columns:<li>DATE : date and time of the incident.</li><li>INCIDENT : unique incident number/ID as it was displayed to the <a href="?page=nfsubopt">blocked user.</a></li><li>LEVEL : level of severity (<code>critical</code>, <code>high</code> or <code>medium</code>), information (<code>info</code>, <code>error</code>, <code>upload</code>) and debugging mode (<code>DEBUG_ON</code>).</li><li>RULE : reference of the NinjaFirewall built-in security rule that triggered the action. A hyphen (<code>-</code>) instead of a number means it was a rule from your own <a href="?page=nfsubpolicies">Firewall Policies</a>.</li><li>IP : the blocked user remote address.</li><li>REQUEST : the HTTP request including offending variables &amp; values as well as the reason the action was logged.</li>'
	) );
}
/* ================================================================== */

function help_nfsubedit() {

	// Firewall log menu help :

	get_current_screen()->add_help_tab( array(
		'id'        => 'log01',
		'title'     => 'Rules Editor',
		'content'   => '<br />Besides the <a href="?page=nfsubpolicies">Firewall Policies</a>, NinjaFirewall includes also a large set of built-in rules used to protect your blog against the most common vulnerabilities and hacking attempts. They are always enabled and you cannot edit them, but if you notice that your visitors are wrongly blocked by some of those rules, you can use the Rules Editor below to disable them individually:<br />
		<li>Check your <a href="?page=nfsublog">firewall log</a> and find the rule ID you want to disable (it is displayed in the <code>RULE</code> column).</li>
		<li>Select its ID from the enabled rules list below and click the "Disable it" button.</li>
		<br />
		<span class="description">Note: if the <code>RULE</code> column from your log shows a hyphen <code>-</code> instead of a number, that means that the rule can be changed in your <a href="?page=nfsubpolicies">Firewall Policies</a> page.</span>
		'
	) );
}

/* ================================================================== */

function help_nfsubabout() {

	// Firewall about menu help :

	get_current_screen()->add_help_tab( array(
		'id'        => 'about01',
		'title'     => 'NinTechNet',
		'content'   => '<br />
		<strong>NinTechNet</strong> offers a variety of security related products and services aimed to help you to protect and keep your website up and running. It addresses both professional business sites and personal blogs.
		<br /><br />
		The Ninja Technologies Network includes :
		<br />
		<li><strong>NinjaMonitoring :</strong> Reviews and monitor suspicious activity, preventing all sorts of damage to your website.</li>
		<li><strong>NinjaFirewall :</strong> Provides advanced firewall software for all PHP applications.</li>
		<li><strong>NinjaRacovery :</strong> Deals with incident response, malware removal & post-hacking recovery.</li>
		<li><strong>NinjaWPass for WordPress :</strong> Secure WordPress log-in form against keyloggers, stolen passwords and brute-force attacks.</li>
		<br />
		<center><a href="http://nintechnet.com/" title="The Ninja Technologies Network" target="_blank"><b>www.NinTechNet.com</b></a></center>
		'
	) );
	get_current_screen()->add_help_tab( array(
		'id'        => 'about02',
		'title'     => 'NinjaMonitoring',
		'content'   => '<br /><strong>NinjaMonitoring :</strong> Monitor your website for suspicious activities
		<br /><br />
		Our service can review and monitor your website for any suspicious activities, from hacking attempts to malware infection.
		<br /><br/>Service features&nbsp;:
		<br/>
		<li>Server-side scan&nbsp;: we will detect any modification of your files.</li>
		<li>Adjustable scanning interval, from 15 to 180 minutes.</li>
		<li>Unlimited number of files to monitor&nbsp;: 500, 5000 or even 50000+</li>
		<li>Fully configurable options&nbsp;: file extensions, exclusions, detection types etc.</li>
		<li>Compatible with any shared hosting account offering either PHP or Perl/CGI.</li>
		<li>Free trial.</li>
		<br />
		<center><a href="http://ninjamonitoring.com/" title="NinjaMonitoring.com" target="_blank"><b>www.NinjaMonitoring.com</b></a></center><br />'
	) );
	get_current_screen()->add_help_tab( array(
		'id'        => 'about03',
		'title'     => 'NinjaFirewall',
		'content'   => '<br /><strong>NinjaFirewall :</strong> Advanced firewall software for all your PHP applications
		<br /><br />
		NinjaFirewall is a powerful firewall software designed to protect all PHP applications from custom scripts to shopping cart softwares (osCommerce, Magento, VirtueMart etc) and also CMS applications (Joomla, WordPress etc).
		<br /><br />
		Product features :<br />
		<li>Compatible with shared hosting accounts.</li>
		<li>Protects against remote & local file inclusions, code execution, uploads, MySQL injections, brute-force scanners, XSS and many other threats.</li>
		<li>Stand-alone firewall; will not stop working when you upgrade your shopping cart or CMS software.</li>
		<li>Hooks and sanitises all requests before they reach your scripts.</li>
		<li>Management administration console.</li>
		<li>Free Opensource and Commercial versions available for download</li>
		<br />
		<center><a href="http://ninjafirewall.com/" title="NinjaFirewall.com" target="_blank"><b>www.NinjaFirewall.com</b></a></center><br />'
	) );
	get_current_screen()->add_help_tab( array(
		'id'        => 'about04',
		'title'     => 'NinjaRecovery',
		'content'   => '<br /><strong>NinjaRecovery :</strong> Incident response, malware removal & hacking recovery
		<br /><br />
		If your online business is hacked or compromised by a virus or malware, the consequences will be not only that your site will be included in Google and the other search engines blacklist, but it could damage your reputation and consequently, might determine significant financial loss.
		<br /><br />
		Our services include :<br />
		<li>Virus &amp; Malware removal</li>
		<li>Hacking recovery</li>
		<br />
		Whether it is a simple website defacement or a completely compromised server, we will remove any harmful application (code injection, backdoor etc), as we will find and patch the vulnerability exploited by the hackers. We will secure your site and server in order to avoid any similar problem in the future.<br />
		All our offers include 1-month warranty and monitoring of your site.
		<br/><br/>
		<center><a href="http://ninjarecovery.com/" title="NinjaRecovery.com" target="_blank"><b>www.NinjaRecovery.com</b></a></center><br />'
	) );
	get_current_screen()->add_help_tab( array(
		'id'        => 'about05',
		'title'     => 'NinjaWPass',
		'content'   => '<br /><strong>NinjaWPass for WordPress :</strong> Secure WordPress log-in form against keyloggers, stolen passwords and brute-force attacks.
		<br /><br />
		NinjaWPass is a WordPress plugin used to protect your blog administration console. It makes it basically impossible for a hacker who stole your password to log in to your console.
		<br/><br/>
		<center><a href="http://wordpress.org/plugins/ninjawpass/" title="NinjaWPass" target="_blank"><b>NinjaWPass</b></a></center><br />'
	) );
}

/* ================================================================== */
// EOF
?>