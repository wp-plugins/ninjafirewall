<?php
/*
 +---------------------------------------------------------------------+
 | NinjaFirewall (WP edition)                                          |
 |                                                                     |
 | (c) NinTechNet - http://nintechnet.com/                             |
 +---------------------------------------------------------------------+
 | REVISION: 2015-03-02 19:17:04                                       |
 +---------------------------------------------------------------------+
 | This program is free software: you can redistribute it and/or       |
 | modify it under the terms of the GNU General Public License as      |
 | published by the Free Software Foundation, either version 3 of      |
 | the License, or (at your option) any later version.                 |
 |                                                                     |
 | This program is distributed in the hope that it will be useful,     |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of      |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the       |
 | GNU General Public License for more details.                        |
 +---------------------------------------------------------------------+ sa
*/

if (! defined( 'NFW_ENGINE_VERSION' ) ) { die( 'Forbidden' ); }

// Block immediately if user is not allowed :
nf_not_allowed( 'block', __LINE__ );

?>
<div class="wrap">
	<div style="width:54px;height:52px;background-image:url(<?php echo plugins_url() ?>/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;" title="NinTechNet"></div>
	<h2><font color="#21759B">WP+</font> Edition</h2>
	<br />
	<br />
	<table border="0" cellspacing="2" cellpadding="5" width="100%">
		<tr>
			<td>
				<h2>
				<b>Need more security ? Check out NinjaFirewall (<font color="#21759B">WP+</font> edition).</b>
				</h2>
				<b>NinjaFirewall (<font color="#21759B">WP+</font> Edition)</b> is a supercharged edition of our Web Application Firewall. It adds many new exciting features and blazing fast performances to make it the fastest and most advanced security plugin for WordPress.

			</td>
		</tr>
		<tr>
			<td>
				<h3>Access Control</h3>
				<p><b>Access Control</b> is a powerful set of directives that can be used to allow or restrict access to your blog, depending on the <strong>User Role</strong>, <strong>IP</strong>, <strong>Geolocation</strong>, <strong>Requested URL</strong>, <strong>User-agent</strong> and visitors behavior (<strong>Rate Limiting</strong>). Those directives will be processed before the Firewall Policies and NinjaFirewall's built-in security rules.
				<p>
				Its main configuration allows you to whitelist WordPress users depending on their roles, to select the source IP (useful if your site is using a CDN or behind a reverse-proxy/load balancer), and the HTTP methods all directives should apply to:</p>
				<center><img src="<?php echo plugins_url() ?>/ninjafirewall/images/screenshots/01_ac_main.png" width="490" height="504" style="border: 1px solid #999;-moz-box-shadow:-3px 5px 5px #999;-webkit-box-shadow:-3px 5px 5px #999;box-shadow:-3px 5px 5px #999;"></center>

				<br />

				<p><b>Access Control</b> can use geolocation to block visitors from specific countries. If you have a theme or a plugin that needs to know your visitors location, you can even ask NinjaFirewall to append the country code to the PHP headers:</p>
				<center><img src="<?php echo plugins_url() ?>/ninjafirewall/images/screenshots/02_ac_geoip.png" width="471" height="428" style="border: 1px solid #999;-moz-box-shadow:-3px 5px 5px #999;-webkit-box-shadow:-3px 5px 5px #999;box-shadow:-3px 5px 5px #999;"></center>

				<br />

				<p><b>Access Control</b> can be used to whitelist/blacklist an IP or any part of it. NinjaFirewall natively supports IPv4 and IPv6 protocols, for both public and private addresses:</p>
				<center><img src="<?php echo plugins_url() ?>/ninjafirewall/images/screenshots/03_ac_ip.png" width="471" height="374" style="border: 1px solid #999;-moz-box-shadow:-3px 5px 5px #999;-webkit-box-shadow:-3px 5px 5px #999;box-shadow:-3px 5px 5px #999;"></center>

				<br />

				<p><b>Access Control</b> can slow down aggressive bots, crawlers, web scrapers or even small HTTP DoS attacks with its <strong>Rate-Limiting</strong> feature.
				<br />
				Because it can block attackers <strong>before WordPress and all its plugins are loaded</strong> and can handle thousands of HTTP requests per second, NinjaFirewall will save precious bandwidth and reduce your server load.</p>
				<center><img src="<?php echo plugins_url() ?>/ninjafirewall/images/screenshots/04_ac_limit.png" width="471" height="122" style="border: 1px solid #999;-moz-box-shadow:-3px 5px 5px #999;-webkit-box-shadow:-3px 5px 5px #999;box-shadow:-3px 5px 5px #999;"></center>

				<br />

				<p><b>URL Access Control</b> lets you permanently allow/block any access to one or more PHP scripts based on their path or name:</p>
				<center><img src="<?php echo plugins_url() ?>/ninjafirewall/images/screenshots/05_ac_url.png" width="467" height="367" style="border: 1px solid #999;-moz-box-shadow:-3px 5px 5px #999;-webkit-box-shadow:-3px 5px 5px #999;box-shadow:-3px 5px 5px #999;"></center>

				<br />

				<p><b>Bots Access Control</b> allows you block bots, scanners and various annoying crawlers:</p>
				<center><img src="<?php echo plugins_url() ?>/ninjafirewall/images/screenshots/06_ac_bots.png" width="471" height="263" style="border: 1px solid #999;-moz-box-shadow:-3px 5px 5px #999;-webkit-box-shadow:-3px 5px 5px #999;box-shadow:-3px 5px 5px #999;"></center>

				<br />

				<h3>Web Filter</h3>
				<p>If <b>NinjaFirewall</b> can hook and scan incoming requests, the <b><font color="#21759B">WP+</font> Edition</b> can also hook the response body (i.e., the output of the HTML page right before it is sent to your visitors browser) and search it for some specific keywords. Such a filter can be useful to detect hacking or malware patterns injected into your HTML page (text strings, spam links, malicious JavaScript code), hackers shell script, redirections and even errors (PHP/MySQL errors). Some suggested keywords as well as a default list are included.
				<br />
				In the case of a positive detection, NinjaFirewall will not block the response body but will send you an alert by email. It can even attach the whole HTML source of the page for your review:</p>
				<center><img src="<?php echo plugins_url() ?>/ninjafirewall/images/screenshots/07_webfilter.png" width="461" height="445" style="border: 1px solid #999;-moz-box-shadow:-3px 5px 5px #999;-webkit-box-shadow:-3px 5px 5px #999;box-shadow:-3px 5px 5px #999;"></center>

				<br />

				<h3>Antispam</h3>
				<p><b>NinjaFirewall (<font color="#21759B">WP+</font> Edition)</b> can protect your blog comment and registration forms against spam. The protection is totally transparent to your visitors and does not require any interaction: no CAPTCHA, no math puzzles or trivia questions. Extremely easy to activate, but powerful enough to make spam bots life as miserable as possible:</p>
				<center><img src="<?php echo plugins_url() ?>/ninjafirewall/images/screenshots/08_antispam.png" width="490" height="323" style="border: 1px solid #999;-moz-box-shadow:-3px 5px 5px #999;-webkit-box-shadow:-3px 5px 5px #999;box-shadow:-3px 5px 5px #999;">
				<br />
				<p class="description"> NinjaFirewall antispam feature works only with WordPress built-in comment and registration forms.</p></center>

				<br />

				<h3>Improved features</h3>
				<strong>File uploads:</strong>
				<p><b>NinjaFirewall (<font color="#21759B">WP+</font> Edition)</b> makes it possible to allow uploads while rejecting potentially dangerous files: system files (.htaccess, .htpasswd. PHP INI), scripts (bash/shell, PHP, Ruby, Perl/CGI, Python), C/C++ source code and Unix/Linux binary files (ELF). You can easily limit the size of each uploaded file too, without having to modify your PHP configuration:</p>
				<center><img src="<?php echo plugins_url() ?>/ninjafirewall/images/screenshots/09_uploads.png" width="410" height="363" style="border: 1px solid #999;-moz-box-shadow:-3px 5px 5px #999;-webkit-box-shadow:-3px 5px 5px #999;box-shadow:-3px 5px 5px #999;"></center>

				<br />

				<p><strong>Firewall Log:</strong>
				<br />
				The log menu has been revamped too. You can disable the firewall log, delete the current one, enable its rotation based on the size of the file and, if any, view each rotated log separately. Quick filtering options are easily accessible from checkboxes:</p>
				<center><img src="<?php echo plugins_url() ?>/ninjafirewall/images/screenshots/10_log.png" width="489" height="465" style="border: 1px solid #999;-moz-box-shadow:-3px 5px 5px #999;-webkit-box-shadow:-3px 5px 5px #999;box-shadow:-3px 5px 5px #999;"></center>

				<br />

				<p><strong>Shared Memory use:</strong>
				<br />
				Although <b>NinjaFirewall</b> is already <a href="http://blog.nintechnet.com/wordpress-brute-force-attack-detection-plugins-comparison/">much faster than other WordPress plugins</a>, the <b><font color="#21759B">WP+</font> Edition</b> brings its performance to a whole new level by using Unix shared memory in order to speed things up even more. This allows easier and faster inter-process communication between the firewall and the plugin part of NinjaFirewall and, because its data and configuration are stored in shared memory segments, the firewall does not need to connect to the database any more. This dramatically increases the processing speed (there is nothing faster than RAM), prevents blocking I/O and MySQL slow queries. On a very busy server like a multi-site network, the firewall processing speed will increase from 25% to 30%. It can be enabled from the "Firewall Options" menu:</p>

				<center><img src="<?php echo plugins_url() ?>/ninjafirewall/images/screenshots/11_shmop.png" width="490" height="306" style="border: 1px solid #999;-moz-box-shadow:-3px 5px 5px #999;-webkit-box-shadow:-3px 5px 5px #999;box-shadow:-3px 5px 5px #999;">
				<br />
				<span class="description">This feature requires that PHP was compiled with the <code>--enable-shmop</code> parameter.</span>
				</center>

			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr style="background-color:#F9F9F9;border: solid 1px #DFDFDF;">
			<td style="text-align:center">
				<h2><b><a href="http://ninjafirewall.com/wordpress/nfwplus.php">Learn more</a> about the <font color="#21759B">WP+</font> edition unique features.</b></h2>
				<h2><b><a href="http://ninjafirewall.com/wordpress/overview.php">Compare</a> the WP and <font color="#21759B">WP+</font> editions.</b></h2>
			</td>
		</tr>
	</table>
</div>
<?php
/* ------------------------------------------------------------------ */
// EOF
