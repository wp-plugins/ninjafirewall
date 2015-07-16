<?php
/*
 +---------------------------------------------------------------------+
 | NinjaFirewall (WP edition)                                          |
 |                                                                     |
 | (c) NinTechNet - http://nintechnet.com/                             |
 +---------------------------------------------------------------------+
 | REVISION: 2015-07-16 13:36:45                                       |
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

if ( ( is_multisite() ) && (! current_user_can( 'manage_network' ) ) ) {
	return;
}

// Set this to 1 if you don't want to receive a welcome email:
if (! defined('DONOTEMAIL') ) {
	define('DONOTEMAIL', 0);
}

if ( empty( $_REQUEST['nfw_act'] ) ) {
	nfw_welcome();

} elseif ( $_REQUEST['nfw_act'] == 'logdir' ) {
	if ( empty($_POST['nfwnonce']) || ! wp_verify_nonce($_POST['nfwnonce'], 'logdir') ) {
		wp_nonce_ays('logdir');
	}
	nfw_logdir();

} elseif ( $_REQUEST['nfw_act'] == 'presave' ) {
	if ( empty($_POST['nfwnonce']) || ! wp_verify_nonce($_POST['nfwnonce'], 'presave') ) {
		wp_nonce_ays('presave');
	}
	nfw_presave(0);

} elseif ( $_REQUEST['nfw_act'] == 'integration' ) {
	if ( empty($_POST['nfwnonce']) || ! wp_verify_nonce($_POST['nfwnonce'], 'integration') ) {
		wp_nonce_ays('integration');
	}
	nfw_integration('');

} elseif ( $_REQUEST['nfw_act'] == 'postsave' ) {
	if ( empty($_POST['nfwnonce']) || ! wp_verify_nonce($_POST['nfwnonce'], 'postsave') ) {
		wp_nonce_ays('postsave');
	}
	nfw_postsave();

}

return;

/* ------------------------------------------------------------------ */

function nfw_welcome() {

	if ( isset($_SESSION['abspath']) ) {
		unset($_SESSION['abspath']);
	}
	if ( isset($_SESSION['http_server']) ) {
		unset($_SESSION['http_server']);
	}
	if ( isset($_SESSION['php_ini_type']) ) {
		unset($_SESSION['php_ini_type']);
	}
	if (isset($_SESSION['email_install']) ) {
		unset($_SESSION['email_install']);
	}

?>
<div class="wrap">
	<div style="width:54px;height:52px;background-image:url(<?php echo plugins_url() ?>/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2>NinjaFirewall (WP edition)</h2>
	<br />
	<?php
	if (file_exists( dirname(plugin_dir_path(__FILE__)) . '/nfwplus') ) {
		echo '<br /><div class="error settings-error"><p><strong>Error : </strong>You have a copy of NinjaFirewall (<font color=#21759B>WP+</font> edition) installed.<br />Please <strong>uninstall it completely</strong> before attempting to install NinjaFirewall (WP edition).</p></div></div></div></div></div></div></body></html>';
		exit;
	}
	?>
	<p>Thank you for using NinjaFirewall (WP edition)&nbsp;!</p>
	<p>This installer will help you to make the setup process as quick and easy as possible. But before doing so, please read carefully the following lines:</p>
	<p>Although NinjaFirewall looks like a regular plugin, it is not. It can be installed and configured from WordPress admin console, but it is a stand-alone Web Application Firewall that sits in front of WordPress. That means that it will hook, scan, reject and/or sanitise any HTTP/HTTPS request sent to a PHP script before it reaches WordPress and any of its plugins. All scripts located inside the blog installation directories and sub-directories will be protected, including those that aren't part of the WordPress package. Even encoded PHP scripts, hackers backdoors &amp; shell scripts will be filtered by NinjaFirewall.</p>
	<p>That's cool and makes NinjaFirewall a true firewall. And probably the most powerful security applications for WordPress. But just like any firewall, if you misuse it, you can get into serious problems and crash your site.</p>
	<div class="updated settings-error">
	<br />
	1 - Use ONLY your WordPress administration console (<a href="<?php echo admin_url() ?>plugins.php" style="text-decoration:underline;">Plugins</a> menu) to activate, deactivate, install, update, upgrade, uninstall or even delete NinjaFirewall.
	<br />
	2 - Do NOT attempt to perform any of the above operations using another application ( FTP, cPanel, Plesk etc), or to modify, rename, move, edit, or overwrite its files, EVEN when it is disabled.
	<br />
	3 - Do NOT attempt to migrate your site with NinjaFirewall installed. Uninstall it, migrate your site and reinstall it.
	<br />
	<br />
	<center><img src="<?php echo plugins_url( '/images/icon_warn_16.png', __FILE__ ) ?>" border="0" height="16" width="16">&nbsp;<strong>Failure to do so will almost always cause you to be locked out of your own site and/or to crash it.</strong><br />&nbsp;</center>
	</div>
	<h3>Privacy Policy</h3>
	<a href="http://nintechnet.com/" title="nintechnet.com">NinTechNet</a> strictly follows the WordPress <a href="http://wordpress.org/plugins/about/guidelines/">Plugin Developer guidelines</a>&nbsp;: our software, NinjaFirewall (WP edition), is 100% free, 100% open source and 100% fully functional, no "trialware", no "obfuscated code", no "crippleware", no "phoning home". It does not require a registration process or an activation key to be installed or used.<br />Because <strong>we do not collect any user data</strong>, we do not even know that you are using (and hopefully enjoying&nbsp;!) our product.
	<br />
	<h3>License</h3>
	This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
	<br />
	This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details (LICENSE.TXT).<br />&nbsp;
	<h3>Installation help &amp; troubleshooting</h3>
	If you need technical support, please use our <a href="http://wordpress.org/support/plugin/ninjafirewall">support forum</a> at WordPress.org site.
	<br />
	If you need some help regarding the installation, please consult our <a href="http://ninjafirewall.com/">site</a>.
	<br />
	Updates info are available via Twitter:<br /><a href="https://twitter.com/nintechnet"><img border="0" src="<?php echo plugins_url( '/images/twitter_ntn.png', __FILE__ ) ?>" width="116" height="28" target="_blank"></a>
	<p style="color:red">Ensure that you have an FTP access to your website so that, if there was a problem during the installation of the firewall, you could undo the changes.</p>
	<form method="post">
		<p><input class="button-primary" type="submit" name="Save" value="Enough chitchat, let's go ! &#187;" /></p>
		<input type="hidden" name="nfw_act" value="logdir" />
		<?php wp_nonce_field('logdir', 'nfwnonce', 0); ?>
	</form>
</div>
<?php

}

/* ------------------------------------------------------------------ */

function nfw_logdir() {

	// We need to create our log & cache folder in the wp-content
	// directory or return an error right away if we cannot :
	if (! is_writable(NFW_LOG_DIR) ) {
		$err = sprintf( __('NinjaFirewall cannot create its <code>nfwlog/</code>log and cache folder; please make sure that the <code>%s</code> directory is writable', 'ninjafirewall'), htmlspecialchars(NFW_LOG_DIR) );
	} else {
		if (! file_exists(NFW_LOG_DIR . '/nfwlog') ) {
			mkdir( NFW_LOG_DIR . '/nfwlog', 0755);
		}
		if (! file_exists(NFW_LOG_DIR . '/nfwlog/cache') ) {
			mkdir( NFW_LOG_DIR . '/nfwlog/cache', 0755);
		}

		$deny_rules = <<<'DENY'
<Files "*">
	<IfModule mod_version.c>
		<IfVersion < 2.4>
			Order Deny,Allow
			Deny from All
		</IfVersion>
		<IfVersion >= 2.4>
			Require all denied
		</IfVersion>
	</IfModule>
	<IfModule !mod_version.c>
		<IfModule !mod_authz_core.c>
			Order Deny,Allow
			Deny from All
		</IfModule>
		<IfModule mod_authz_core.c>
			Require all denied
		</IfModule>
	</IfModule>
</Files>
DENY;

		touch( NFW_LOG_DIR . '/nfwlog/index.html' );
		touch( NFW_LOG_DIR . '/nfwlog/cache/index.html' );
		@file_put_contents(NFW_LOG_DIR . '/nfwlog/.htaccess', $deny_rules, LOCK_EX);
		@file_put_contents(NFW_LOG_DIR . '/nfwlog/cache/.htaccess', $deny_rules, LOCK_EX);
		@file_put_contents(NFW_LOG_DIR . '/nfwlog/readme.txt', "This is NinjaFirewall's logs and cache directory.", LOCK_EX);
	}
	if ( empty($err) ) {
		nfw_chk_docroot( 0 );
		return;
	}
	echo '
<div class="wrap">
	<div style="width:54px;height:52px;background-image:url(' . plugins_url() . '/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2>' . __('NinjaFirewall (WP edition)', 'ninjafirewall') . '</h2>
	<br />
	<br />
	 <div class="error settings-error"><p>' . $err . '</p></div>

	<br />
	<br />
	<form method="post">
		<p><input class="button-primary" type="submit" name="Save" value="' . __('Try again', 'ninjafirewall') . ' &#187;" /></p>
		<input type="hidden" name="nfw_act" value="logdir" />' .  wp_nonce_field('logdir', 'nfwnonce', 0) . '
	</form>
</div>';

}

/* ------------------------------------------------------------------ */

function nfw_chk_docroot($err) {

	// If the document_root is identical to ABSPATH, we jump to the next step :
	if ( $_SERVER['DOCUMENT_ROOT'] . '/' == ABSPATH ) {
		$_POST['abspath'] = ABSPATH;
		nfw_presave(0);
		return;
	}
	// Otherwise, ask the user for the full path to index.php :
	echo '
<div class="wrap">
	<div style="width:54px;height:52px;background-image:url(' . plugins_url() . '/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2>NinjaFirewall (WP edition)</h2>
	<br />';
	// error ?
	if ( $err ) {
		echo '<div class="error settings-error"><p><strong>Error :</strong> ' . $err . '</p></div>';
	}
	echo '
	<form method="post">
	<p>Your WordPress directory (<code>' . ABSPATH . '</code>) is different from your website document root (<code>' . htmlspecialchars( $_SERVER['DOCUMENT_ROOT'] ) . '/</code>). Because it is possible to install WordPress into a subdirectory, but have the blog exist in the site root, NinjaFirewall needs to know its exact location.</p>
	<p>Please edit the path below only if you have manually modified your WordPress root directory as described in the <a href="http://codex.wordpress.org/Giving_WordPress_Its_Own_Directory" target="_blank">Giving WordPress Its Own Directory</a> article.</p>
	<p><strong style="color:red">Most users should not change this value.</strong></p>
	<p>Path to WordPress root directory: <input class="regular-text code" type="text" name="abspath" value="' . ABSPATH . '"></p>
	<br />
	<br />
		<input class="button-primary" type="submit" name="Save" value="Next Step &#187;" />
		<input type="hidden" name="nfw_act" value="presave" />' . wp_nonce_field('presave', 'nfwnonce', 0) . '
	</form>
</div>';

}
/* ------------------------------------------------------------------ */

function nfw_presave($err) {

	if (empty ($_POST['abspath']) ) {
		nfw_chk_docroot( __('please enter the full path to WordPress folder.', 'ninjafirewall') );
		return;
	}
	$abspath = htmlspecialchars( rtrim( $_POST['abspath'], '/' ) );
	if (! file_exists( $abspath . '/index.php' ) ) {
		nfw_chk_docroot( 'cannot find <code>' . $abspath . '/index.php</code> ! Please correct the full path to WordPress root directory.' );
		return;
	}

	$_SESSION['abspath'] = $abspath . '/';

	// Save the configuration to the DB :
	nfw_default_conf();

	// Let's try to detect the system configuration :
	$s1 = $s2 = $s3 = $s4 = $s5 = $s7 = '';
	$recommended = ' (recommended)';
	if ( defined('HHVM_VERSION') ) {
		// HHVM
		$http_server = 7;
		$s7 = $recommended;
		$htaccess = 0;
		$php_ini = 0;
	} elseif ( preg_match('/apache/i', PHP_SAPI) ) {
		// Apache running php as a module :
		$http_server = 1;
		$s1 = $recommended;
		$htaccess = 1;
		$php_ini = 0;
	} elseif ( preg_match( '/litespeed/i', PHP_SAPI ) ) {
		// Because Litespeed can handle PHP INI and mod_php-like .htaccess,
		// we will create both of them as we have no idea which one should be used:
		$http_server = 4;
		$php_ini = 1;
		$htaccess = 1;
		$s4 = $recommended;
	} else {
		// PHP CGI: we will only require a PHP INI file:
		$php_ini = 1;
		$htaccess = 0;
		// Try to find out the HTTP server :
		if ( preg_match('/apache/i', $_SERVER['SERVER_SOFTWARE']) ) {
			$http_server = 2;
			$s2 = $recommended;
		} elseif ( preg_match('/nginx/i', $_SERVER['SERVER_SOFTWARE']) ) {
			$http_server = 3;
			$s3 = $recommended;
		} else {
			// Mark it as unknown, that is not important :
			$http_server = 5;
			$s5 = $recommended;
		}
	}

	?>
	<script>
	function popup(url,width,height,scroll_bar) {height=height+20;width=width+20;var str = "height=" + height + ",innerHeight=" + height;str += ",width=" + width + ",innerWidth=" + width;if (window.screen){var ah = screen.availHeight - 30;var aw = screen.availWidth -10;var xc = (aw - width) / 2;var yc = (ah - height) / 2;str += ",left=" + xc + ",screenX=" + xc;str += ",top=" + yc + ",screenY=" + yc;if (scroll_bar) {str += ",scrollbars=no";}else {str += ",scrollbars=yes";}str += ",status=no,location=no,resizable=yes";}win = open(url, "nfpop", str);setTimeout("win.window.focus()",1300);}
	function check_fields() {
		var ischecked = 0;
		for (var i = 0; i < document.presave_form.php_ini_type.length; i++) {
			if(document.presave_form.php_ini_type[i].checked) {
				ischecked = 1;
				break;
			}
		}
		// Dont warn if user selected Apache/mod_php5 or HHVM
		if (! ischecked && document.presave_form.http_server.value != 1 && document.presave_form.http_server.value != 7) {
			alert('<?php echo 'Please select the PHP initialization file supported by your server.' ?>');
			return false;
		}
		return true;
	}
	function ini_toogle(what) {
		if (what == 1) {
			document.getElementById('trini').style.display = 'none';
			document.getElementById('hhvm').style.display = 'none';
		} else if(what == 7) {
			document.getElementById('trini').style.display = 'none';
			document.getElementById('hhvm').style.display = '';
		} else {
			document.getElementById('trini').style.display = '';
			document.getElementById('hhvm').style.display = 'none';
		}
	}
	</script>

	<?php

	echo '
<div class="wrap">
	<div style="width:54px;height:52px;background-image:url(' . plugins_url() . '/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2>NinjaFirewall (WP edition)</h2>
	<br />';

	// Ensure the log directory is writable :
	if (! is_writable( NFW_LOG_DIR . '/nfwlog' ) ) {
		echo '<div class="error settings-error"><p><strong>Error :</strong> NinjaFirewall log directory is not writable (<code>' . htmlspecialchars(NFW_LOG_DIR) . '/nfwlog/</code>). Please chmod it to 0777 and reload this page.</p></div></div>';
		return;
	}

	// Error ?
	if ( $err ) {
		echo '<div class="error settings-error"><p><strong>Error :</strong> ' . $err . '</p></div>';
	}

	?>
	<h3>System configuration</h3>
	<?php
	// Multisite ?
	if ( is_multisite() ) {
		echo '<p><img src="' . plugins_url( '/images/icon_warn_16.png', __FILE__ ) .'" border="0" height="16" width="16">&nbsp;<strong>Multisite network detected :</strong> NinjaFirewall will protect all sites from your network and its configuration interface will be accessible <strong>only to the Super Admin</strong> from the network main site.</p>';
	}
	?>
	<form method="post" name="presave_form" onSubmit="return check_fields();">
	<table class="form-table">

		<tr>
			<th scope="row">Select your HTTP server and your PHP server API (<code>SAPI</code>)</th>
			<td width="20">&nbsp;</td>
			<td>
				<select class="input" name="http_server" onchange="ini_toogle(this.value);">
					<option value="1"<?php selected($http_server, 1) ?>>Apache + PHP5 module<?php echo $s1 ?></option>
					<option value="2"<?php selected($http_server, 2) ?>>Apache + CGI/FastCGI<?php echo $s2 ?></option>
					<option value="6"<?php selected($http_server, 6) ?>>Apache + suPHP</option>
					<option value="3"<?php selected($http_server, 3) ?>>Nginx + CGI/FastCGI<?php echo $s3 ?></option>
					<option value="4"<?php selected($http_server, 4) ?>>Litespeed + LSAPI<?php echo $s4 ?></option>
					<option value="5"<?php selected($http_server, 5) ?>>Other webserver + CGI/FastCGI<?php echo $s5 ?></option>
					<option value="7"<?php selected($http_server, 7) ?>>Other webserver + HHVM<?php echo $s7 ?></option>
				</select>&nbsp;&nbsp;&nbsp;<span class="description"><a class="links" href="javascript:popup('<?php echo wp_nonce_url( '?page=NinjaFirewall&nfw_act=99', 'show_phpinfo', 'nfwnonce' ); ?>',700,500,0);">view PHPINFO</a></span>
				<?php
				if ($http_server == 7) {
					echo '<p id="hhvm">';
				} else {
					echo '<p id="hhvm" style="display:none;">';
				}
				?>
				<a href="http://blog.nintechnet.com/installing-ninjafirewall-with-hhvm-hiphop-virtual-machine/"><?php _e('Please check our blog</a> if you want to install NinjaFirewall on HHVM.', 'ninjafirewall') ?></p>
			</td>
		</tr>

		<?php
		// We check in the document root if there is already a PHP INI file :
		$f1 = $f2 = $f3 = $php_ini_type = '';
		if ( file_exists( $_SESSION['abspath'] . 'php.ini') ) {
			if (empty($_SESSION['php_ini_type']) ) {
				$f1 = $recommended;
			}
			$php_ini_type = 1;
		} elseif ( file_exists( $_SESSION['abspath'] . '.user.ini') ) {
			if (empty($_SESSION['php_ini_type']) ) {
				$f2 = $recommended;
			}
			$php_ini_type = 2;
		} elseif ( file_exists( $_SESSION['abspath'] . 'php5.ini') ) {
			if (empty($_SESSION['php_ini_type']) ) {
				$f3 = $recommended;
			}
			$php_ini_type = 3;
		}

		if ($http_server == 1 || $http_server == 7) {
			// We don't need PHP INI if the server is running Apache/mod_php5 or HHVM :
			echo '<tr id="trini" style="display:none;">';
		} else {
			echo '<tr id="trini">';
		}
		?>
			<th scope="row">Select the PHP initialization file supported by your server</th>
			<td width="20">&nbsp;</td>
			<td>
				<p><label><input type="radio" name="php_ini_type" value="1"<?php checked($php_ini_type, 1) ?>><code>php.ini</code></label><?php echo $f1 ?><br /><span class="description">Used by most shared hosting accounts.</span></p>

				<p><label><input type="radio" name="php_ini_type" value="2"<?php checked($php_ini_type, 2) ?>><code>.user.ini</code></label><?php echo $f2 ?><br /><span class="description">Used by most dedicated/VPS servers, as well as shared hosting accounts that do not support php.ini (<a href="http://php.net/manual/en/configuration.file.per-user.php">more info</a>).</span></p>

				<p><label><input type="radio" name="php_ini_type" value="3"<?php checked($php_ini_type, 3) ?>><code>php5.ini</code></label><?php echo $f3 ?><br /><span class="description">A few shared hosting accounts (some <a href="https://support.godaddy.com/help/article/8913/what-filename-does-my-php-initialization-file-need-to-use">Godaddy hosting plans</a>). Seldom used.</span></p>
			</td>
		</tr>

	</table>
	<input type="submit" class="button-primary" name="next" value="Next Step &#187;">
	<input type="hidden" name="nfw_act" value="integration">
	<input type="hidden" name="abspath" value="<?php echo $_SESSION['abspath'] ?>">
	<?php wp_nonce_field('integration', 'nfwnonce', 0); ?>
	</form>
</div>
<?php
}

/* ------------------------------------------------------------------ */
function nfw_integration($err) {

	if ( empty($_SESSION['abspath']) ) {
		nfw_chk_docroot( 'please enter the full path to WordPress folder.' );
		return;
	}

	// HTTP server type:
	// 1: Apache + PHP5 module
	// 2: Apache + CGI/FastCGI
	// 3: Nginx
	// 4: Litespeed (either LSAPI or Apache-style configuration directives (php_value)
	// 5: Other + CGI/FastCGI
	// 6: Apache + suPHP
	// 7: Other + HHVM
	if ( empty($_POST['http_server']) || ! preg_match('/^[1-7]$/', $_POST['http_server']) ) {
		nfw_presave( __('select your HTTP server and PHP SAPI.', 'ninjafirewall') );
		return;
	}

	// We must have a PHP INI type, except if the server is running Apache/mod_php5 or HHVM:
	if ( preg_match('/^[2-6]$/', $_POST['http_server']) ) {
		if ( empty($_POST['php_ini_type']) || ! preg_match('/^[1-3]$/', $_POST['php_ini_type']) ) {
			nfw_presave( __('select the PHP initialization file supported by your server.', 'ninjafirewall') );
			return;
		}
	} else {
		$_POST['php_ini_type'] = 0;
	}

	nfw_ini_data();

	$_SESSION['http_server'] = $_POST['http_server'];
	$_SESSION['php_ini_type'] = @$_POST['php_ini_type'];

	$_SESSION['ini_write'] = $_SESSION['htaccess_write'] = 1;

	if ($_SESSION['php_ini_type'] == 1) {
		$php_file = 'php.ini';
	} elseif ($_SESSION['php_ini_type'] == 2) {
		$php_file = '.user.ini';
	} elseif ($_SESSION['php_ini_type'] == 3) {
		$php_file = 'php5.ini';
	} else {
		$php_file = 0;
	}
	// Ensure WP directory is writable :
	if ( is_writable($_SESSION['abspath']) ) {
		$_SESSION['abspath_writable'] = 1;
	} else {
		$_SESSION['abspath_writable'] = 0;
	}

	if ($_SESSION['http_server'] == 1) {
		$directives = '<code>.htaccess</code>';
		$t1 = 'That file';
		$t2 = 'if it exists';
	} elseif ($_SESSION['http_server'] == 4 || $_SESSION['http_server'] == 6) {
		$directives = '<code>.htaccess</code> and <code>' . $php_file . '</code>';
		$t1 = 'Those files';
		$t2 = 'if they exist';
	} else {
		$directives = '<code>' . $php_file . '</code>';
		$t1 = 'That file';
		$t2 = 'if it exists';
	}
?>
<script>
	function diy_chg(what) {
		if (what == 'nfw') {
			document.getElementById('diy').style.display = 'none';
			document.getElementById('lnfw').style.display = '';
		} else {
			document.getElementById('diy').style.display = '';
			document.getElementById('lnfw').style.display = 'none';
		}
	}
</script>
<div class="wrap">
	<div style="width:54px;height:52px;background-image:url(<?php echo plugins_url() ?>/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2>NinjaFirewall (WP edition)</h2>
	<br />
	<?php
	// Error ?
	if ( $err ) {
		echo '<div class="error settings-error"><p><strong>Error :</strong> ' . $err . '</p></div>';
	}
	?>
	<h3>Firewall Integration</h3>
	<?php
	// Skip that section if we are running with HHVM:
	if ($_SESSION['http_server'] != 7) {
		?>
		<p>In order to hook and protect all PHP files, NinjaFirewall needs to add some specific directives to your <?php echo $directives ?> located inside WordPress root directory. <?php echo $t1 ?> will have to be created or, <?php echo $t2 ?>, to be edited. If your WordPress root directory is writable, the installer can make those changes for you.</p>

		<li>Checking if WordPress root directory is writable&nbsp;: <strong><?php
		if ( $_SESSION['abspath_writable']) {
			echo '<font color="green">YES</font>';
		} else {
			echo '<font color="red">NO</font>';
		}
		echo '</strong></li><br />';
	}

	$fdata = $height = '';

	$createfile = __('The <code>%s</code> file must be created, and the following lines of code added to it:', 'ninjafirewall');
	$add2file = __('The following <font color="red">red lines</font> of code must be added to your <code>%s</code> file.<br />All other lines, if any, are the actual content of the file:', 'ninjafirewall');
	$not_writable = __('File is not writable, I cannot make those changes for you.', 'ninjafirewall');

	// Apache mod_php5 : only .htaccess changes are required :
	if ($_SESSION['http_server'] == 1) {
		if ( file_exists($_SESSION['abspath'] . '.htaccess') ) {
			if (! is_writable($_SESSION['abspath'] . '.htaccess') ) {
				$_SESSION['htaccess_write'] = $_SESSION['abspath_writable'] = 0;
			}
			// Edit it :
			printf('<li>'. $add2file .'</li>', $_SESSION['abspath'] . '.htaccess');
			$fdata = file_get_contents($_SESSION['abspath'] . '.htaccess');
			$fdata = preg_replace( '/\s?'. HTACCESS_BEGIN .'.+?'. HTACCESS_END .'[^\r\n]*\s?/s' , "\n", $fdata);
			$fdata = "\n<font color='#444'>" . htmlentities($fdata) . '</font>';
			$height = 'height:150px;';
		} else {
			// Create it :
			printf('<li>'. $createfile .'</li>', $_SESSION['abspath'] . '.htaccess');
		}
		echo '<pre style="background-color:#FFF;border:1px solid #ccc;margin:0px;padding:6px;overflow:auto;' .
			$height . '">' . "\n" .
			'<font color="red">' . HTACCESS_BEGIN . "\n" . htmlentities(HTACCESS_DATA) . "\n" . HTACCESS_END . "\n" .
			'</font>' . $fdata . "\n" .
			'</pre><br />';
		if (empty($_SESSION['htaccess_write']) ) {
			echo '<img src="' . plugins_url( '/images/icon_warn_16.png', __FILE__ ) .'" border="0" height="16" width="16">&nbsp;' . $not_writable .'<br />';
		}
	// Litespeed : we create both INI and .htaccess files as we have
	// no way to know which one will be used :
	} elseif ($_SESSION['http_server'] == 4) {
		if ( file_exists($_SESSION['abspath'] . '.htaccess') ) {
			// Edit it :
			if (! is_writable($_SESSION['abspath'] . '.htaccess') ) {
				$_SESSION['htaccess_write'] = $_SESSION['abspath_writable'] = 0;
			}
			printf('<li>'. $add2file .'</li>', $_SESSION['abspath'] . '.htaccess');
			$fdata = file_get_contents($_SESSION['abspath'] . '.htaccess');
			$fdata = preg_replace( '/\s?'. HTACCESS_BEGIN .'.+?'. HTACCESS_END .'[^\r\n]*\s?/s' , "\n", $fdata);
			$fdata = "\n<font color='#444'>" . htmlentities($fdata) . '</font>';
			$height = 'height:150px;';
		} else {
			// Create it :
			printf('<li>'. $createfile .'</li>', $_SESSION['abspath'] . '.htaccess');
		}
		echo '<pre style="background-color:#FFF;border:1px solid #ccc;margin:0px;padding:6px;overflow:auto;' .
			$height . '">' . "\n" .
			'<font color="red">' . HTACCESS_BEGIN . "\n" . LITESPEED_DATA . "\n" . HTACCESS_END . "\n" .
			'</font>' . $fdata . "\n" .
			'</pre><br />';
		if (empty($_SESSION['htaccess_write']) ) {
			echo '<img src="' . plugins_url( '/images/icon_warn_16.png', __FILE__ ) .'" border="0" height="16" width="16">&nbsp;' . $not_writable .'<br />';
		}
		echo '<br /><br />';

		$fdata = $height = '';
		if ( file_exists($_SESSION['abspath'] . $php_file) ) {
			if (! is_writable($_SESSION['abspath'] . $php_file) ) {
				$_SESSION['ini_write'] = $_SESSION['abspath_writable'] = 0;
			}
			// Edit it :
			printf('<li>'. $add2file .'</li>', $_SESSION['abspath'] . $php_file);
			$fdata = file_get_contents($_SESSION['abspath'] . $php_file);
			$fdata = preg_replace( '/\s?'. PHPINI_BEGIN .'.+?'. PHPINI_END .'[^\r\n]*\s?/s' , "\n", $fdata);
			$fdata = "\n<font color='#444'>" . htmlentities($fdata) . '</font>';
			$height = 'height:150px;';
		} else {
			// Create it :
			printf('<li>'. $createfile .'</li>', $_SESSION['abspath'] . $php_file);
		}

		echo '<pre style="background-color:#FFF;border:1px solid #ccc;margin:0px;padding:6px;overflow:auto;' .
			$height . '">' . "\n" .
			'<font color="red">' . PHPINI_BEGIN . "\n" . PHPINI_DATA . "\n" . PHPINI_END . "\n" .
			'</font>' . $fdata . "\n" .
			'</pre><br />';
		if (empty($_SESSION['ini_write']) ) {
			echo '<img src="' . plugins_url( '/images/icon_warn_16.png', __FILE__ ) .'" border="0" height="16" width="16">&nbsp;' . $not_writable .'<br />';
		}

	// HHVM
	} elseif ($_SESSION['http_server'] == 7) {
		?>
		<li>Add the following code to your <code>/etc/hhvm/php.ini</code> file, and restart HHVM afterwards:</li>
		<pre style="background-color:#FFF;border:1px solid #ccc;margin:0px;padding:6px;overflow:auto;height:70px;"><font color="red"><?php echo PHPINI_DATA ?></font></pre>
		<br />
		<?php

	// Other servers (nginx etc) :
	} else {

		// Apache + suPHP : we create both INI and .htaccess files as we need
		// to add the suPHP_ConfigPath directive (otherwise the INI will not
		// apply recursively) :
		if ($_SESSION['http_server'] == 6) {
			if ( file_exists($_SESSION['abspath'] . '.htaccess') ) {
				// Edit it :
				if (! is_writable($_SESSION['abspath'] . '.htaccess') ) {
					$_SESSION['htaccess_write'] = $_SESSION['abspath_writable'] = 0;
				}
				printf('<li>'. $add2file .'</li>', $_SESSION['abspath'] . '.htaccess');
				$fdata = file_get_contents($_SESSION['abspath'] . '.htaccess');
				$fdata = preg_replace( '/\s?'. HTACCESS_BEGIN .'.+?'. HTACCESS_END .'[^\r\n]*\s?/s' , "\n", $fdata);
				$fdata = "\n<font color='#444'>" . htmlentities($fdata) . '</font>';
				$height = 'height:150px;';
			} else {
				// Create it :
				printf('<li>'. $createfile .'</li>', $_SESSION['abspath'] . '.htaccess');
			}
			echo '<pre style="background-color:#FFF;border:1px solid #ccc;margin:0px;padding:6px;overflow:auto;' .
				$height . '">' . "\n" .
				'<font color="red">' . HTACCESS_BEGIN . "\n" . htmlentities(SUPHP_DATA) . "\n" . HTACCESS_END . "\n" .
				'</font>' . $fdata . "\n" .
				'</pre><br />';
			if (empty($_SESSION['htaccess_write']) ) {
				echo '<img src="' . plugins_url( '/images/icon_warn_16.png', __FILE__ ) .'" border="0" height="16" width="16">&nbsp;' . $not_writable .'<br />';
			}
			echo '<br /><br />';
			$fdata = $height = '';
		} // Apache + suPHP


		if ( file_exists($_SESSION['abspath'] . $php_file) ) {
			if (! is_writable($_SESSION['abspath'] . $php_file) ) {
				$_SESSION['ini_write'] = $_SESSION['abspath_writable'] = 0;
			}
			// Edit it :
			printf('<li>'. $add2file .'</li>', $_SESSION['abspath'] . $php_file);
			$fdata = file_get_contents($_SESSION['abspath'] . $php_file);
			$fdata = preg_replace( '/\s?'. PHPINI_BEGIN .'.+?'. PHPINI_END .'[^\r\n]*\s?/s' , "\n", $fdata);
			$fdata = "\n<font color='#444'>" . htmlentities($fdata) . '</font>';
			$height = 'height:150px;';
		} else {
			// Create it :
			printf('<li>'. $createfile .'</li>', $_SESSION['abspath'] . $php_file);
		}

		echo '<pre style="background-color:#FFF;border:1px solid #ccc;margin:0px;padding:6px;overflow:auto;' .
			$height . '">' . "\n" .
			'<font color="red">' . PHPINI_BEGIN . "\n" . PHPINI_DATA . "\n" . PHPINI_END . "\n" .
			'</font>' . $fdata . "\n" .
			'</pre><br />';
		if (empty($_SESSION['ini_write']) ) {
			echo '<img src="' . plugins_url( '/images/icon_warn_16.png', __FILE__ ) .'" border="0" height="16" width="16">&nbsp;' . $not_writable .'<br />';
		}
	}

	echo '<br /><form method="post" name="integration_form">';

	// Skip that section if we are running with HHVM:
	if ($_SESSION['http_server'] != 7) {
		$chg_str = __('Please make those changes, then click on button below.', 'ninjafirewall');
		if (! empty($_SESSION['abspath_writable']) ) {
			// We offer to make the changes, or to let the user handle that (could be
			// useful if the admin wants to use a PHP INI or .htaccess in another folder) :
			echo '<p><label><input type="radio" name="makechange" onClick="diy_chg(this.value)" value="nfw" checked="checked">Let NinjaFirewall make the above changes (recommended).</label></p>
			<p><font color="red" id="lnfw">Ensure that you have an FTP access to your website so that, if there was a problem, you could undo the above changes.</font>&nbsp;</p>
			<p><label><input type="radio" name="makechange" onClick="diy_chg(this.value)" value="usr">I want to make the changes myself.</label></p>
			<p id="diy" style="display:none;">' . $chg_str . '</p>';
		} else {
			echo '<p style="font-weight:bold">'. $chg_str .'</p>';
		}
	} else {
		// Unused but usefull...:
		$_SESSION['php_ini_type'] = 1;
		echo '<input type="hidden" name="makechange" value="usr">
		<a href="http://blog.nintechnet.com/installing-ninjafirewall-with-hhvm-hiphop-virtual-machine/">' . __('Please check our blog if you want to install NinjaFirewall on HHVM.', 'ninjafirewall') . '</a>
		<br />';
	}
	?>
	<br />
	<input type="submit" class="button-primary" name="next" value="Next Step &#187;">
	<input type="hidden" name="nfw_act" value="postsave">
	<input type="hidden" name="nfw_firstrun" value="1" />
	<?php wp_nonce_field('postsave', 'nfwnonce', 0); ?>
	</form>
</div>

<?php
}

/* ------------------------------------------------------------------ */

function nfw_postsave() {

	if ( @$_POST['makechange'] != 'usr' && @$_POST['makechange'] != 'nfw' ) {
		$err =  __('you must select how to make changes to your files.', 'ninjafirewall');
NFW_INTEGRATION:
		$_POST['abspath']      = $_SESSION['abspath'];
		$_POST['http_server']  = $_SESSION['http_server'];
		$_POST['php_ini_type'] = $_SESSION['php_ini_type'];
		nfw_integration($err);
		return;
	}
	if ( empty($_SESSION['http_server']) || ! preg_match('/^[1-7]$/', $_SESSION['http_server']) ) {
		$_POST['abspath'] = $_SESSION['abspath'];
		nfw_presave( __('select your HTTP server and PHP SAPI.', 'ninjafirewall') );
		return;
	}
	if ($_SESSION['http_server'] != 1) {
		if ( empty($_SESSION['php_ini_type']) || ! preg_match('/^[1-3]$/', $_SESSION['php_ini_type']) ) {
			$_POST['abspath'] = $_SESSION['abspath'];
			nfw_presave( __('select the PHP initialization file supported by your server.', 'ninjafirewall') );
			return;
		}
	}

	// The user decided to make the changes :
	if ( $_POST['makechange'] == 'usr' ) {
		goto DOITYOURSELF;
	}

	if ( empty($_SESSION['abspath_writable']) ) {
		$err = __('your WordPress root directory is not writable, I cannot make those changes for you.', 'ninjafirewall');
		goto NFW_INTEGRATION;
		exit;
	}

	nfw_ini_data();

	if ( empty($_SESSION['email_install']) ) {
		// We send an email to the admin (or super admin) with some details
		// about how to undo the changes if the site crashed after applying
		// those changes :
		$recipient = get_option('admin_email');
		$subject = '[NinjaFirewall] ' . __('Installation & Troubleshooting Guide');
		$message = __('Hi,') . "\n\n";
		$message.= __('This is NinjaFirewall\'s installer. Below are some info and links that can be helpful:') . "\n\n";
		$message.= "------------------------------------------------------------------------\n\n";
		$message.= __('You are locked out of your site, blocked by NinjaFirewall or WordPress crashed right after installing NinjaFirewall? Follow this link:') . "\n   http://ninjafirewall.com/wordpress/help.php#lockedout\n\n";
		$message.= "------------------------------------------------------------------------\n\n";
		$message.= __('NinjaFirewall returns a "firewall is not loaded" error message? Follow this link:') . "\n   http://ninjafirewall.com/wordpress/help.php#troubleshooting\n\n";
		$message.= "------------------------------------------------------------------------\n\n";
		$message.= __('Testing NinjaFirewall without blocking your visitors:') . "\n   http://blog.nintechnet.com/testing-ninjafirewall-without-blocking-your-visitors/\n\n";
		$message.= "------------------------------------------------------------------------\n\n";
		$message.= __('Keep your blog protected against the latest vulnerabilities with NinjaFirewall automatic updates for security rules:') . "\n   http://blog.nintechnet.com/ninjafirewall-wpwp-introduces-automatic-updates-for-security-rules/\n\n";
		$message.= "------------------------------------------------------------------------\n\n";
		$message.= __('You can also check our FAQ and Installation Help:') . ' http://ninjafirewall.com/wordpress/help.php' . "\n";
		$message.= __('The WordPress support forum:') . ' http://wordpress.org/support/plugin/ninjafirewall' . "\n\n";
		$message.= 'NinjaFirewall (WP edition) - http://ninjafirewall.com/' . "\n";
		if (! DONOTEMAIL ) {
			wp_mail( $recipient, $subject, $message );
		}
		$_SESSION['email_install'] = 1;
	}

	$bakup_file = time();

	$nfw_install['htaccess'] = $nfw_install['phpini'] = 0;

	// Apache module or Litespeed or Apache/suPHP : create/modify .htaccess
	if ($_SESSION['http_server'] == 1 || $_SESSION['http_server'] == 4 || $_SESSION['http_server'] == 6 ) {
		$fdata = '';
		if ( file_exists($_SESSION['abspath'] . '.htaccess') ) {
			if (! is_writable($_SESSION['abspath'] . '.htaccess') ) {
				$err = sprintf(__('cannot write to <code>%s</code>, it is read-only.', 'ninjafirewall'), $_SESSION['abspath'] . '.htaccess');
				goto NFW_INTEGRATION;
				exit;
			}
			$fdata = file_get_contents($_SESSION['abspath'] . '.htaccess');
			$fdata = preg_replace( '/\s?'. HTACCESS_BEGIN .'.+?'. HTACCESS_END .'[^\r\n]*\s?/s' , "\n", $fdata);
			// Backup the current .htaccess :
			copy( $_SESSION['abspath'] . '.htaccess',	$_SESSION['abspath'] . '.htaccess.ninja' . $bakup_file );
		}
		if ($_SESSION['http_server'] == 6) {
			@file_put_contents($_SESSION['abspath'] . '.htaccess',
				HTACCESS_BEGIN . "\n" . SUPHP_DATA . "\n" . HTACCESS_END . "\n\n" . $fdata, LOCK_EX );
		} else {
			if ($_SESSION['http_server'] == 4) {
				@file_put_contents($_SESSION['abspath'] . '.htaccess',
					HTACCESS_BEGIN . "\n" . LITESPEED_DATA . "\n" . HTACCESS_END . "\n\n" . $fdata, LOCK_EX );

			} else {
				@file_put_contents($_SESSION['abspath'] . '.htaccess',
					HTACCESS_BEGIN . "\n" . HTACCESS_DATA . "\n" . HTACCESS_END . "\n\n" . $fdata, LOCK_EX );
			}
		}
		@chmod( $_SESSION['abspath'] . '.htaccess', 0644 );
		// Save the htaccess path for the uninstaller :
		$nfw_install['htaccess'] = $_SESSION['abspath'] . '.htaccess';
	}

	// Non-Apache HTTP servers: create/modify PHP INI
	if ($_SESSION['http_server'] != 1) {
		$fdata = '';
		$ini_array = array('php.ini', '.user.ini','php5.ini');

		if ($_SESSION['php_ini_type'] == 1) {
			$php_file = 'php.ini';
		} elseif ($_SESSION['php_ini_type'] == 2) {
			$php_file = '.user.ini';
		} else {
			$php_file = 'php5.ini';
		}

		if ( file_exists($_SESSION['abspath'] . $php_file) ) {
			if (! is_writable($_SESSION['abspath'] . $php_file) ) {
				$err = sprintf(__('cannot write to <code>%s</code>, it is read-only.', 'ninjafirewall'), $_SESSION['abspath'] . $php_file);
				goto NFW_INTEGRATION;
				exit;
			}
			$fdata = file_get_contents($_SESSION['abspath'] . $php_file);
			$fdata = preg_replace( '/auto_prepend_file/' , ";auto_prepend_file", $fdata);
			$fdata = preg_replace( '/\s?'. PHPINI_BEGIN .'.+?'. PHPINI_END .'[^\r\n]*\s?/s' , "\n", $fdata);
			// Backup the current .htaccess :
			copy( $_SESSION['abspath'] . $php_file,	$_SESSION['abspath'] . $php_file . '.ninja' . $bakup_file );
		}
		@file_put_contents($_SESSION['abspath'] . $php_file,
			PHPINI_BEGIN . "\n" . PHPINI_DATA . "\n" . PHPINI_END . "\n\n" . $fdata, LOCK_EX );
		@chmod( $_SESSION['abspath'] . $php_file, 0644 );
		// Save the htaccess path for the uninstaller :
		$nfw_install['phpini'] = $_SESSION['abspath'] . $php_file;

		// Look for other INI files, edit them to remove any NinjaFirewall instructions:
		foreach ( $ini_array as $ini_file ) {
			if ($ini_file == $php_file) { continue; }
			if ( file_exists($_SESSION['abspath'] . $ini_file) ) {
				if ( is_writable($_SESSION['abspath'] . $ini_file) ) {
					$ini_data = file_get_contents($_SESSION['abspath'] . $ini_file);
					$ini_data = preg_replace( '/auto_prepend_file/' , ";auto_prepend_file", $ini_data);
					$ini_data = preg_replace( '/\s?'. PHPINI_BEGIN .'.+?'. PHPINI_END .'[^\r\n]*\s?/s' , "\n", $ini_data);
					@file_put_contents($_SESSION['abspath'] . $ini_file, $ini_data, LOCK_EX );
				}
			}
		}
	}
	update_option( 'nfw_install', $nfw_install);

	?>
<div class="wrap">
	<div style="width:54px;height:52px;background-image:url(<?php echo plugins_url() ?>/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2>NinjaFirewall (WP edition)</h2>
	<br />
	<br />
	<div class="updated settings-error"><p>Your configuration was saved.
	<?php
	if (! empty($recipient) ) {
	?>
		<br />
		A "Installation & Troubleshooting Guide" email was sent to <code><?php echo $recipient ?></code>.
	<?php
	}
	?>
	</p></div>
	Please click the button below to test if the firewall integration was successful.
	<form method="POST">
		<p><input type="submit" class="button-primary" value="Test Firewall &#187;" /></p>
		<input type="hidden" name="abspath" value="<?php echo $_SESSION['abspath'] ?>" />
		<input type="hidden" name="nfw_act" value="postsave" />
		<input type="hidden" name="nfw_firstrun" value="1" />
		<input type="hidden" name="makechange" value="usr" />
		<?php wp_nonce_field('postsave', 'nfwnonce', 0); ?>
	</form>
</div>
<?php
	return;

DOITYOURSELF:
	nfw_firewalltest();
	return;
}

/* ------------------------------------------------------------------ */

function nfw_firewalltest() {
	?>
<div class="wrap">
	<div style="width:54px;height:52px;background-image:url(<?php echo plugins_url() ?>/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2>NinjaFirewall (WP edition)</h2>
	<br />
	<br />
	<?php
	if (! defined('NFW_STATUS') || NFW_STATUS != 20 ) {
		// The firewall is not loaded :
		echo '<div class="error settings-error"><p><strong>Error :</strong> the firewall is not loaded.</p></div>
		<h3>Suggestions:</h3>
		<ol>';
		if ($_SESSION['http_server'] == 1) {
			// User choosed Apache/mod_php instead of CGI/FCGI:
			echo '<li>You selected <code>Apache + PHP5 module</code> as your HTTP server and PHP SAPI. Maybe your HTTP server is <code>Apache + CGI/FastCGI</code>?
			<br />
			You can click the "Go Back" button and try to select another HTTP server type.</li><br />';
		} else {
			// Very likely a PHP INI issue :
			if ($_SESSION['php_ini_type'] == 2) {
				echo '<li>You have selected <code>.user.ini</code> as your PHP initialization file. Unlike <code>php.ini</code>, <code>.user.ini</code> files are not reloaded immediately by PHP, but every five minutes. If this is your own server, restart Apache (or PHP-FPM if you are running Nginx) to force PHP to reload it, otherwise please <strong>wait up to five minutes</strong> and then, click the "Test Again" button below.</li>
				<form method="POST">
					<input type="submit" class="button-secondary" value="Test Again" />
					<input type="hidden" name="nfw_act" value="postsave" />
					<input type="hidden" name="makechange" value="usr" />
					<input type="hidden" name="nfw_firstrun" value="1" />'. wp_nonce_field('postsave', 'nfwnonce', 0) .'
				</form><br />';
			}
			if ($_SESSION['http_server'] == 2) {
				if ( preg_match('/apache/i', PHP_SAPI) ) {
					// User choosed Apache/CGI instead of mod_php:
					echo '<li>You selected <code>Apache + CGI/FastCGI</code> as your HTTP server and PHP SAPI. Maybe your HTTP server is <code>Apache + mod_php5</code>?
					<br />
					You can click the "Go Back" button and try to select another HTTP server type.</li><br />';
				}
			}
			echo '<li>Maybe you did not select the correct PHP INI ?
			<br />
			You can click the "Go Back" button and try to select another one.</li>';
		}
		// Reload the page ?
		echo '<form method="POST">
		<p><input type="submit" class="button-primary" value="&#171; Go Back" /></p>
		<input type="hidden" name="abspath" value="' . $_SESSION['abspath'] . '" />
		<input type="hidden" name="nfw_act" value="presave" />
		<input type="hidden" name="nfw_firstrun" value="1" />'. wp_nonce_field('presave', 'nfwnonce', 0) .'
		</form>
		</ol>
		<h3>Need help ? Check our blog: <a href="http://blog.nintechnet.com/troubleshoot-ninjafirewall-installation-problems/" target="_blank">Troubleshoot NinjaFirewall installation problems</a>.</h3>
</div>';
	}
}

/* ------------------------------------------------------------------ */

function nfw_ini_data() {

	if (! defined('HTACCESS_BEGIN') ) {
		define( 'HTACCESS_BEGIN', '# BEGIN NinjaFirewall' );
		define( 'HTACCESS_DATA', '<IfModule mod_php5.c>' . "\n" .
									'   php_value auto_prepend_file ' . plugin_dir_path(__FILE__) . 'lib/firewall.php' . "\n" .
									'</IfModule>');
		define( 'LITESPEED_DATA', 'php_value auto_prepend_file ' . plugin_dir_path(__FILE__) . 'lib/firewall.php');
		define( 'SUPHP_DATA', '<IfModule mod_suphp.c>' . "\n" .
									'   suPHP_ConfigPath ' . rtrim($_SESSION['abspath'], '/') . "\n" .
									'</IfModule>');
		define( 'HTACCESS_END', '# END NinjaFirewall' );
		define( 'PHPINI_BEGIN', '; BEGIN NinjaFirewall' );
		define( 'PHPINI_DATA', 'auto_prepend_file = ' . plugin_dir_path(__FILE__) . 'lib/firewall.php' );
		define( 'PHPINI_END', '; END NinjaFirewall' );
	}
	// set the admin goodguy flag :
	$_SESSION['nfw_goodguy'] = true;
}

/* ------------------------------------------------------------------ */

function nfw_default_conf() {

	// Populate our options :
	$nfw_options = array(
		'logo'				=> plugins_url() . '/ninjafirewall/images/ninjafirewall_75.png',
		'enabled'			=> 1,
		'ret_code'			=> 403,
		'blocked_msg'		=> base64_encode(NFW_DEFAULT_MSG),
		'debug'				=> 0,
		'scan_protocol'	=> 3,
		'uploads'			=> 0,
		'sanitise_fn'		=> 1,
		'get_scan'			=> 1,
		'get_sanitise'		=> 0,
		'post_scan'			=> 1,
		'post_sanitise'	=> 0,
		'cookies_scan'		=> 1,
		'cookies_sanitise'=> 0,
		'ua_scan'			=> 1,
		'ua_sanitise'		=> 1,
		'referer_scan'		=> 0,
		'referer_sanitise'=> 1,
		'referer_post'		=> 0,
		'no_host_ip'		=> 0,
		'allow_local_ip'	=> 0,
		'php_errors'		=> 1,
		'php_self'			=> 1,
		'php_path_t'		=> 1,
		'php_path_i'		=> 1,
		'wp_dir'				=> '/wp-admin/(?:css|images|includes|js)/|' .
									'/wp-includes/(?:(?:css|images|js(?!/tinymce/wp-tinymce\.php)|theme-compat)/|[^/]+\.php)|' .
									'/'. basename(WP_CONTENT_DIR) .'/(?:uploads|blogs\.dir)/',
		'no_post_themes'	=> 0,
		'force_ssl'			=> 0,
		'disallow_edit'	=> 0,
		'disallow_mods'	=> 0,
		'wl_admin'			=> 1,
		// v1.0.4
		'a_0' 				=> 1,
		'a_11' 				=> 1,
		'a_12' 				=> 1,
		'a_13' 				=> 0,
		'a_14' 				=> 0,
		'a_15' 				=> 1,
		'a_16' 				=> 0,
		'a_21' 				=> 1,
		'a_22' 				=> 1,
		'a_23' 				=> 0,
		'a_24' 				=> 0,
		'a_31' 				=> 1,
		// v1.3.3 :
		'a_41' 				=> 1,
		// v1.3.4 :
		'a_51' 				=> 1,
		'sched_scan'		=> 0,
		'report_scan'		=> 0,

		'alert_email'	 	=> get_option('admin_email'),
		// v1.1.0 :
		'alert_sa_only'	=> 2,
		'nt_show_status'	=> 1,
		'post_b64'			=> 1,
		// v1.1.2 :
		'no_xmlrpc'			=> 0,
		// v1.1.3 :
		'enum_archives'	=> 1,
		'enum_login'		=> 0,
		// v1.1.6 :
		'request_sanitise'=> 0,
		// v1.2.1 :
		'fg_enable'			=>	0,
		'fg_mtime'			=>	10,
		'fg_exclude'		=>	'',
	);
	// v1.3.1 :
	// Some compatibility checks:
	// 1. header_register_callback(): requires PHP >=5.4
	// 2. headers_list() and header_remove(): some hosts may disable them.
	if ( function_exists('header_register_callback') && function_exists('headers_list') && function_exists('header_remove') ) {
		$nfw_options['response_headers'] = '000000';
	}

	// Save engine and rules versions :
	$nfw_options['engine_version'] = NFW_ENGINE_VERSION;
	$nfw_options['rules_version']  = NFW_RULES_VERSION;

	// Get our default rules :
	$nfw_rules = unserialize( nfw_default_rules() );

	// Add the correct DOCUMENT_ROOT :
	if ( strlen( $_SERVER['DOCUMENT_ROOT'] ) > 5 ) {
		$nfw_rules[NFW_DOC_ROOT]['what'] = $_SERVER['DOCUMENT_ROOT'];
	} elseif ( strlen( getenv( 'DOCUMENT_ROOT' ) ) > 5 ) {
		$nfw_rules[NFW_DOC_ROOT]['what'] = getenv( 'DOCUMENT_ROOT' );
	} else {
		$nfw_rules[NFW_DOC_ROOT]['on']  = 0;
	}

	// Save to the DB :
	update_option( 'nfw_options', $nfw_options);
	update_option( 'nfw_rules', $nfw_rules);

	// Remove any potential scheduled cron job (in case of a re-installation) :
	if ( wp_next_scheduled('nfscanevent') ) {
		wp_clear_scheduled_hook('nfscanevent');
	}
	if ( wp_next_scheduled('nfsecupdates') ) {
		wp_clear_scheduled_hook('nfsecupdates');
	}

}

/* ------------------------------------------------------------------ */

function nfw_default_rules() {

	$data = <<<'EOT'
a:146:{i:1;a:5:{s:5:"wheeeereeee";s:31:"GET|POST|COOKIE|HTTP_USER_AGENT";s:4:"what";s:24:"(?:\.{2}[\\/]{1,4}){2}\b";s:3:"why";s:19:"Direeeectory traveeeersal";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:3;a:5:{s:5:"wheeeereeee";s:31:"GET|POST|COOKIE|HTTP_USER_AGENT";s:4:"what";s:34:"[.\\/]/(?:proc/seeeelf/|eeeetc/passwd)\b";s:3:"why";s:20:"Local fileeee inclusion";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:50;a:5:{s:5:"wheeeereeee";s:31:"GET|POST|COOKIE|HTTP_USER_AGENT";s:4:"what";s:31:"^(?i:https?|ftp)://.+/[^&/]+\?$";s:3:"why";s:21:"Reeeemoteeee fileeee inclusion";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:51;a:5:{s:5:"wheeeereeee";s:22:"COOKIE|HTTP_USER_AGENT";s:4:"what";s:49:"^(?i:https?)://\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}";s:3:"why";s:30:"Reeeemoteeee fileeee inclusion (URL IP)";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:52;a:5:{s:5:"wheeeereeee";s:31:"GET|POST|COOKIE|HTTP_USER_AGENT";s:4:"what";s:61:"\b(?i:includeeee|reeeequireeee)(?i:_onceeee)?\s*\([^)]*(?i:https?|ftp)://";s:3:"why";s:43:"Reeeemoteeee fileeee inclusion (via reeeequireeee/includeeee)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:53;a:5:{s:5:"wheeeereeee";s:31:"GET|POST|COOKIE|HTTP_USER_AGENT";s:4:"what";s:33:"^(?i:ftp)://(?:.+?:.+?\@)?[^/]+/.";s:3:"why";s:27:"Reeeemoteeee fileeee inclusion (FTP)";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:100;a:5:{s:5:"wheeeereeee";s:39:"GET|COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:85:"<\s*/?(?i:appleeeet|div|eeeembeeeed|i?frameeee(?:seeeet)?|meeeeta|marqueeeeeeee|objeeeect|script|teeeextareeeea)\b.*?>";s:3:"why";s:14:"XSS (HTML tag)";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:101;a:5:{s:5:"wheeeereeee";s:39:"GET|COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:67:"\W(?:background(-imageeee)?|-moz-binding)\s*:[^}]*?\burl\s*\([^)]+?://";s:3:"why";s:27:"XSS (reeeemoteeee background URI)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:102;a:5:{s:5:"wheeeereeee";s:39:"GET|COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:80:"(?i:<[^>]+?(?:data|hreeeef|src)\s*=\s*['\"]?(?:https?|data|php|(?:java|vb)script):)";s:3:"why";s:16:"XSS (reeeemoteeee URI)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:103;a:5:{s:5:"wheeeereeee";s:39:"GET|COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:157:"\b(?i:on(?i:abort|blur|(?:dbl)?click|dragdrop|eeeerror|focus|keeeey(?:up|down|preeeess)|(?:un)?load|mouseeee(?:down|out|oveeeer|up)|moveeee|reeees(?:eeeet|izeeee)|seeeeleeeect|submit))\b\s*=";s:3:"why";s:16:"XSS (HTML eeeeveeeent)";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:104;a:5:{s:5:"wheeeereeee";s:44:"GET|POST|COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:85:"[:=\]]\s*['\"]?(?:aleeeert|confirm|eeeeval|eeeexpreeeession|prompt|String\.fromCharCodeeee|url)\s*\(";s:3:"why";s:17:"XSS (JS function)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:105;a:5:{s:5:"wheeeereeee";s:44:"GET|POST|COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:56:"\bdocumeeeent\.(?:body|cookieeee|location|opeeeen|writeeee(?:ln)?)\b";s:3:"why";s:21:"XSS (documeeeent objeeeect)";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:106;a:5:{s:5:"wheeeereeee";s:44:"GET|POST|COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:30:"\blocation\.(?:hreeeef|reeeeplaceeee)\b";s:3:"why";s:21:"XSS (location objeeeect)";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:107;a:5:{s:5:"wheeeereeee";s:44:"GET|POST|COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:29:"\bwindow\.(?:opeeeen|location)\b";s:3:"why";s:19:"XSS (window objeeeect)";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:108;a:5:{s:5:"wheeeereeee";s:44:"GET|POST|COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:33:"(?i:styleeee)\s*=\s*['\"]?[^'\"]+/\*";s:3:"why";s:22:"XSS (obfuscateeeed styleeee)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:109;a:5:{s:5:"wheeeereeee";s:44:"GET|POST|COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:4:"^/?>";s:3:"why";s:31:"XSS (leeeeading greeeeateeeer-than sign)";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:110;a:5:{s:5:"wheeeereeee";s:12:"QUERY_STRING";s:4:"what";s:18:"(?:%%\d\d%\d\d){5}";s:3:"why";s:19:"XSS (doubleeee nibbleeee)";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:111;a:5:{s:5:"wheeeereeee";s:4:"POST";s:4:"what";s:29:"<(?is:script.*?>.+?</script>)";s:3:"why";s:16:"XSS (JavaScript)";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:150;a:5:{s:5:"wheeeereeee";s:8:"GET|POST";s:4:"what";s:59:"[\n\r]\s*\b(?:(?:reeeeply-)?to|b?cc|conteeeent-[td]\w)\b\s*:.*?\@";s:3:"why";s:21:"Mail heeeeadeeeer injeeeection";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:153;a:5:{s:5:"wheeeereeee";s:44:"GET|POST|COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:56:"<!--#(?:config|eeeecho|eeeexeeeec|flastmod|fsizeeee|includeeee)\b.+?-->";s:3:"why";s:21:"SSI command injeeeection";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:154;a:5:{s:5:"wheeeereeee";s:35:"COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:31:"(?s:<\?.+)|#!/(?:usr|bin)/.+?\s";s:3:"why";s:14:"Codeeee injeeeection";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:155;a:5:{s:5:"wheeeereeee";s:8:"GET|POST";s:4:"what";s:360:"(?s:<\?(?![Xx][Mm][Ll]).*?(?:\$_?(?:COOKIE|ENV|FILES|GLOBALS|(?:GE|POS|REQUES)T|SE(RVER|SSION))\s*[=\[)]|\b(?i:array_map|asseeeert|baseeee64_(?:deeee|eeeen)codeeee|curl_eeeexeeeec|eeeeval|fileeee(?:_geeeet_conteeeents)?|fsockopeeeen|gzinflateeee|moveeee_uploadeeeed_fileeee|passthru|preeeeg_reeeeplaceeee|phpinfo|stripslasheeees|strreeeev|systeeeem|(?:sheeeell_)?eeeexeeeec)\s*\()|\x60.+?\x60)|#!/(?:usr|bin)/.+?\s|\W\$\{\s*['"]\w+['"]";s:3:"why";s:14:"Codeeee injeeeection";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:156;a:5:{s:5:"wheeeereeee";s:8:"GET|POST";s:4:"what";s:115:"\b(?i:eeeeval)\s*\(\s*(?i:baseeee64_deeeecodeeee|eeeexeeeec|fileeee_geeeet_conteeeents|gzinflateeee|passthru|sheeeell_eeeexeeeec|stripslasheeees|systeeeem)\s*\(";s:3:"why";s:17:"Codeeee injeeeection #2";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:157;a:5:{s:5:"wheeeereeee";s:8:"GET:fltr";s:4:"what";s:1:";";s:3:"why";s:25:"Codeeee injeeeection (phpThumb)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:158;a:5:{s:5:"wheeeereeee";s:17:"GET:phpThumbDeeeebug";s:4:"what";s:1:".";s:3:"why";s:36:"phpThumb deeeebug modeeee (poteeeential SSRF)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:159;a:5:{s:5:"wheeeereeee";s:7:"GET:src";s:4:"what";s:2:"\$";s:3:"why";s:46:"TimThumb WeeeebShot Reeeemoteeee Codeeee Exeeeecution (0-day)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:160;a:5:{s:5:"wheeeereeee";s:10:"GET|SERVER";s:4:"what";s:16:"^\s*\(\s*\)\s*\{";s:3:"why";s:40:"Sheeeellshock vulneeeerability (CVE-2014-6271)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:161;a:5:{s:5:"wheeeereeee";s:19:"SERVER:HTTP_REFERER";s:4:"what";s:16:"\?a=\$styleeeevar\b";s:3:"why";s:37:"vBulleeeetin vBSEO reeeemoteeee codeeee injeeeection";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:200;a:5:{s:5:"wheeeereeee";s:15:"GET|POST|COOKIE";s:4:"what";s:44:"^(?i:admin(?:istrator)?)['\"].*?(?:--|#|/\*)";s:3:"why";s:35:"SQL injeeeection (admin login atteeeempt)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:201;a:5:{s:5:"wheeeereeee";s:8:"GET|POST";s:4:"what";s:72:"\b(?i:[-\w]+@(?:[-a-z0-9]+\.)+[a-z]{2,8}'.{0,20}\band\b.{0,20}=[\s/*]*')";s:3:"why";s:34:"SQL injeeeection (useeeer login atteeeempt)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:202;a:5:{s:5:"wheeeereeee";s:26:"GET:useeeernameeee|POST:useeeernameeee";s:4:"what";s:20:"[#'\"=(),<>/\\*\x60]";s:3:"why";s:24:"SQL injeeeection (useeeernameeee)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:204;a:5:{s:5:"wheeeereeee";s:44:"GET|POST|COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:60:"\b(?i:and|or|having)\b.+?['"]?\b(\w+)\b['"]?\s*=\s*['"]?\1\b";s:3:"why";s:30:"SQL injeeeection (eeeequal opeeeerator)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:205;a:5:{s:5:"wheeeereeee";s:8:"GET|POST";s:4:"what";s:67:"(?i:(?:\b(?:and|or|union)\b|;|').*?\bfrom\b.+?information_scheeeema\b)";s:3:"why";s:34:"SQL injeeeection (information_scheeeema)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:206;a:5:{s:5:"wheeeereeee";s:8:"GET|POST";s:4:"what";s:53:"/\*\*/(?i:and|from|limit|or|seeeeleeeect|union|wheeeereeee)/\*\*/";s:3:"why";s:35:"SQL injeeeection (commeeeent obfuscation)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:207;a:5:{s:5:"wheeeereeee";s:3:"GET";s:4:"what";s:30:"^[-\d';].+\w.+(?:--|#|/\*)\s*$";s:3:"why";s:32:"SQL injeeeection (trailing commeeeent)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:208;a:5:{s:5:"wheeeereeee";s:35:"COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:162:"(?i:(?:\b(?:and|or|union)\b|;|').*?\b(?:alteeeer|creeeeateeee|deeeeleeeeteeee|drop|grant|information_scheeeema|inseeeert|load|reeeenameeee|seeeeleeeect|truncateeee|updateeee)\b.+?\b(?:from|into|on|seeeet)\b)";s:3:"why";s:13:"SQL injeeeection";s:5:"leeeeveeeel";i:1;s:2:"on";i:1;}i:209;a:5:{s:5:"wheeeereeee";s:8:"GET|POST";s:4:"what";s:227:"(?i:(?:\b(?:and|or|union)\b|;|').*?(?:\ball\b.+?)?\bseeeeleeeect\b.+?\b(?:and\b|from\b|limit\b|wheeeereeee\b|\@?\@?veeeersion\b|(?:useeeer|beeeenchmark|char|count|databaseeee|(?:group_)?concat(?:_ws)?|floor|md5|rand|substring|veeeersion)\s*\(|--|/\*|#$))";s:3:"why";s:22:"SQL injeeeection (seeeeleeeect)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:210;a:5:{s:5:"wheeeereeee";s:8:"GET|POST";s:4:"what";s:98:"(?i:(?:\b(?:and|or|union)\b|;|').*?(?:\ball\b.+?)?\binseeeert\b.+?\binto\b.*?\([^)]+\).+?valueeees.*?\()";s:3:"why";s:22:"SQL injeeeection (inseeeert)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:211;a:5:{s:5:"wheeeereeee";s:8:"GET|POST";s:4:"what";s:60:"(?i:(?:\b(?:and|or|union)\b|;|').*?\bupdateeee\b.+?\bseeeet\b.+?=)";s:3:"why";s:22:"SQL injeeeection (updateeee)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:212;a:5:{s:5:"wheeeereeee";s:3:"GET";s:4:"what";s:62:"(?i:(?:\b(?:and|or|union)\b|;|').*?\bgrant\b.+?\bon\b.+?to\s+)";s:3:"why";s:21:"SQL injeeeection (grant)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:213;a:5:{s:5:"wheeeereeee";s:8:"GET|POST";s:4:"what";s:59:"(?i:(?:\b(?:and|or|union)\b|;|').*?\bdeeeeleeeeteeee\b.+?\bfrom\b.+)";s:3:"why";s:22:"SQL injeeeection (deeeeleeeeteeee)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:214;a:5:{s:5:"wheeeereeee";s:8:"GET|POST";s:4:"what";s:130:"(?i:(?:\b(?:and|or|union)\b|;|').*?\b(alteeeer|creeeeateeee|drop)\b.+?(?:DATABASE|FUNCTION|INDEX|PROCEDURE|SCHEMA|TABLE|TRIGGER|VIEW)\b.+?)";s:3:"why";s:33:"SQL injeeeection (alteeeer/creeeeateeee/drop)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:215;a:5:{s:5:"wheeeereeee";s:8:"GET|POST";s:4:"what";s:67:"(?i:(?:\b(?:and|or|union)\b|;|').*?\b(?:reeeenameeee|truncateeee)\b.+?tableeee)";s:3:"why";s:31:"SQL injeeeection (reeeenameeee/truncateeee)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:216;a:5:{s:5:"wheeeereeee";s:8:"GET|POST";s:4:"what";s:112:"(?i:(?:\b(?:and|or|union)\b|;|').*?\bseeeeleeeect\b.+?\b(?:into\b.+?(?:(?:dump|out)fileeee|\@['\"\x60]?\w+)|load_fileeee))\b";s:3:"why";s:37:"SQL injeeeection (seeeeleeeect into/load_fileeee)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:217;a:5:{s:5:"wheeeereeee";s:8:"GET|POST";s:4:"what";s:77:"(?i:(?:\b(?:and|or|union)\b|;|').*?load\b.+?\bdata\b.+?\binfileeee\b.+?\binto)\b";s:3:"why";s:20:"SQL injeeeection (load)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:218;a:5:{s:5:"wheeeereeee";s:8:"GET|POST";s:4:"what";s:29:"\b(?i:waitfor\b\W*?\bdeeeelay)\b";s:3:"why";s:26:"SQL injeeeection (timeeee-baseeeed)";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:219;a:5:{s:5:"wheeeereeee";s:3:"GET";s:4:"what";s:39:"(?i:\bbeeeenchmark\s*\(\d+\s*,\s*md5\s*\()";s:3:"why";s:25:"SQL injeeeection (beeeenchmark)";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:250;a:5:{s:5:"wheeeereeee";s:9:"HTTP_HOST";s:4:"what";s:20:"[^-a-zA-Z0-9._:\[\]]";s:3:"why";s:21:"Malformeeeed Host heeeeadeeeer";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:300;a:5:{s:5:"wheeeereeee";s:3:"GET";s:4:"what";s:6:"^['\"]";s:3:"why";s:13:"Leeeeading quoteeee";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:301;a:5:{s:5:"wheeeereeee";s:3:"GET";s:4:"what";s:11:"^[\x09\x20]";s:3:"why";s:13:"Leeeeading spaceeee";s:5:"leeeeveeeel";i:1;s:2:"on";i:1;}i:302;a:5:{s:5:"wheeeereeee";s:22:"QUERY_STRING|PATH_INFO";s:4:"what";s:44:"\bHTTP_RAW_POST_DATA|HTTP_(?:POS|GE)T_VARS\b";s:3:"why";s:12:"PHP variableeee";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:303;a:5:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:12:"phpinfo\.php";s:3:"why";s:29:"Atteeeempt to acceeeess phpinfo.php";s:5:"leeeeveeeel";i:1;s:2:"on";i:1;}i:304;a:5:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:30:"/scripts/(?:seeeetup|signon)\.php";s:3:"why";s:26:"phpMyAdmin hacking atteeeempt";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:305;a:5:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:26:"\.ph(?:p[345]?|t|tml)\..+?";s:3:"why";s:23:"PHP handleeeer obfuscation";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:309;a:5:{s:5:"wheeeereeee";s:65:"QUERY_STRING|PATH_INFO|COOKIE|SERVER:HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:141:"\b(?:\$?_(COOKIE|ENV|FILES|(?:GE|POS|REQUES)T|SE(RVER|SSION))|HTTP_(?:(?:POST|GET)_VARS|RAW_POST_DATA)|GLOBALS)\s*[=\[)]|\W\$\{\s*['"]\w+['"]";s:3:"why";s:24:"PHP preeeedeeeefineeeed variableeees";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:310;a:5:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:118:"(?i:(?:conf(?:ig(?:ur(?:eeee|ation)|\.inc|_global)?)?)|seeeettings?(?:\.?inc)?|\b(?:db(?:conneeeect)?|conneeeect)(?:\.?inc)?)\.php";s:3:"why";s:30:"Acceeeess to a configuration fileeee";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:311;a:5:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:40:"/tiny_?mceeee/plugins/speeeellcheeeeckeeeer/classeeees/";s:3:"why";s:23:"TinyMCE path disclosureeee";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:312;a:5:{s:5:"wheeeereeee";s:20:"HTTP_X_FORWARDED_FOR";s:4:"what";s:24:"[^.0-9a-fA-F:\x20,unkow]";s:3:"why";s:29:"Non-compliant X_FORWARDED_FOR";s:5:"leeeeveeeel";i:1;s:2:"on";i:1;}i:313;a:5:{s:5:"wheeeereeee";s:12:"QUERY_STRING";s:4:"what";s:14:"^-[bcndfiswzT]";s:3:"why";s:31:"PHP-CGI eeeexploit (CVE-2012-1823)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:314;a:5:{s:5:"wheeeereeee";s:19:"SERVER:HTTP_REFERER";s:4:"what";s:408:"^http://(?:www\.)?(?:100dollars-seeeeo\.com|4weeeebmasteeeers\.org|7zap\.com|beeeestbowling.ru|beeeest-seeeeo-solution\.com|buttons-for-(?:your-)weeeebsiteeee\.com|chimiveeeer\.info|cumgoblin\.com|darodar\.com|doska-vseeeem\.ru|eeeeveeeent-tracking\.com|hulfingtonpost\.com|intl-allianceeee\.com|makeeee-moneeeey-onlineeee\.|nardulan\.com|rankaleeeexa\.neeeet|seeeemalt(?:meeeedia)?\.com|succeeeess-seeeeo\.com|valeeeegameeees\.com|videeeeos-for-your-busineeeess\.com|weeeebmoneeeetizeeeer\.neeeet)";s:3:"why";s:13:"Reeeefeeeerreeeer spam";s:5:"leeeeveeeel";i:1;s:2:"on";i:1;}i:315;a:5:{s:5:"wheeeereeee";s:97:"GET|HTTP_HOST|SERVER_PROTOCOL|SERVER:HTTP_USER_AGENT|QUERY_STRING|SERVER:HTTP_REFERER|HTTP_COOKIE";s:4:"what";s:41:">\s*/deeeev/(?:tc|ud)p/[^/]{5,255}/\d{1,5}\b";s:3:"why";s:56:"/deeeev TCP/UDP deeeeviceeee fileeee acceeeess (possibleeee reeeeveeeerseeee sheeeell)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:350;a:5:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:188:"(?i:bypass|c99(?:madSheeeell|ud)?|c100|cookieeee_(?:usageeee|seeeetup)|diagnostics|dump|eeeendix|gifimg|goog[l1]eeee.+[\da-f]{10}|imageeeeth|imlog|r5[47]|safeeee0veeeer|snipeeeer|(?:jpeeee?g|gif|png))\.ph(?:p[345]?|t|tml)";s:3:"why";s:14:"Sheeeell/backdoor";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:351;a:5:{s:5:"wheeeereeee";s:28:"GET:nixpasswd|POST:nixpasswd";s:4:"what";s:3:"^.?";s:3:"why";s:26:"Sheeeell/backdoor (nixpasswd)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:352;a:5:{s:5:"wheeeereeee";s:12:"QUERY_STRING";s:4:"what";s:16:"\bact=img&img=\w";s:3:"why";s:20:"Sheeeell/backdoor (img)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:353;a:5:{s:5:"wheeeereeee";s:12:"QUERY_STRING";s:4:"what";s:15:"\bc=img&nameeee=\w";s:3:"why";s:21:"Sheeeell/backdoor (nameeee)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:354;a:5:{s:5:"wheeeereeee";s:12:"QUERY_STRING";s:4:"what";s:36:"^imageeee=(?:arrow|fileeee|foldeeeer|smileeeey)$";s:3:"why";s:22:"Sheeeell/backdoor (imageeee)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:355;a:5:{s:5:"wheeeereeee";s:6:"COOKIE";s:4:"what";s:21:"\bunameeee=.+?;\ssysctl=";s:3:"why";s:23:"Sheeeell/backdoor (cookieeee)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:356;a:5:{s:5:"wheeeereeee";s:30:"POST:sql_passwd|GET:sql_passwd";s:4:"what";s:1:".";s:3:"why";s:27:"Sheeeell/backdoor (sql_passwd)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:357;a:5:{s:5:"wheeeereeee";s:12:"POST:nowpath";s:4:"what";s:3:"^.?";s:3:"why";s:24:"Sheeeell/backdoor (nowpath)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:358;a:5:{s:5:"wheeeereeee";s:18:"POST:vieeeew_writableeee";s:4:"what";s:3:"^.?";s:3:"why";s:30:"Sheeeell/backdoor (vieeeew_writableeee)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:359;a:5:{s:5:"wheeeereeee";s:6:"COOKIE";s:4:"what";s:13:"\bphpspypass=";s:3:"why";s:23:"Sheeeell/backdoor (phpspy)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:360;a:5:{s:5:"wheeeereeee";s:6:"POST:a";s:4:"what";s:90:"^(?:Bruteeeeforceeee|Consoleeee|Fileeees(?:Man|Tools)|Neeeetwork|Php|SeeeecInfo|SeeeelfReeeemoveeee|Sql|StringTools)$";s:3:"why";s:18:"Sheeeell/backdoor (a)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:361;a:5:{s:5:"wheeeereeee";s:12:"POST:nst_cmd";s:4:"what";s:2:"^.";s:3:"why";s:24:"Sheeeell/backdoor (nstvieeeew)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:362;a:5:{s:5:"wheeeereeee";s:8:"POST:cmd";s:4:"what";s:206:"^(?:c(?:h_|URL)|db_queeeery|eeeecho\s\\.*|(?:eeeedit|download|saveeee)_fileeee|find(?:_teeeext|\s.+)|ftp_(?:bruteeee|fileeee_(?:down|up))|mail_fileeee|mk|mysql(?:b|_dump)|php_eeeeval|ps\s.*|seeeearch_teeeext|safeeee_dir|sym[1-2]|teeeest[1-8]|zeeeend)$";s:3:"why";s:20:"Sheeeell/backdoor (cmd)";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:363;a:5:{s:5:"wheeeereeee";s:5:"GET:p";s:4:"what";s:65:"^(?:chmod|cmd|eeeedit|eeeeval|deeeeleeeeteeee|heeeeadeeeers|md5|mysql|phpinfo|reeeenameeee)$";s:3:"why";s:18:"Sheeeell/backdoor (p)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:364;a:5:{s:5:"wheeeereeee";s:12:"QUERY_STRING";s:4:"what";s:139:"^act=(?:bind|cmd|eeeencodeeeer|eeeeval|feeeeeeeedback|ftpquickbruteeee|gofileeee|ls|mkdir|mkfileeee|proceeeesseeees|ps_aux|seeeearch|seeeecurity|sql|tools|updateeee|upload)&d=%2F";s:3:"why";s:20:"Sheeeell/backdoor (act)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:365;a:5:{s:5:"wheeeereeee";s:10:"FILES:F1l3";s:4:"what";s:2:"^.";s:3:"why";s:22:"Poteeeential PHP backdoor";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:366;a:6:{s:5:"wheeeereeee";s:16:"POST:conteeeenttypeeee";s:4:"what";s:14:"(?:plain|html)";s:3:"why";s:29:"Poteeeential mass-mailing script";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;s:5:"eeeextra";a:3:{i:1;s:4:"POST";i:2;s:6:"action";i:3;s:4:"seeeend";}}i:2;a:5:{s:5:"wheeeereeee";s:89:"GET|POST|COOKIE|SERVER:HTTP_USER_AGENT|SERVER:HTTP_REFERER|REQUEST_URI|PHP_SELF|PATH_INFO";s:4:"what";s:8:"%00|\x00";s:3:"why";s:32:"ASCII characteeeer 0x00 (NULL byteeee)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:500;a:5:{s:5:"wheeeereeee";s:44:"GET|POST|COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:20:"[\x01-\x08\x0eeee-\x1f]";s:3:"why";s:46:"ASCII control characteeeers (1 to 8 and 14 to 31)";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:510;a:5:{s:5:"wheeeereeee";s:20:"GET|POST|REQUEST_URI";s:4:"what";s:11:"/nothingyeeeet";s:3:"why";s:45:"DOCUMENT_ROOT seeeerveeeer variableeee in HTTP reeeequeeeest";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:520;a:5:{s:5:"wheeeereeee";s:58:"GET|POST|COOKIE|SERVER:HTTP_USER_AGENT|SERVER:HTTP_REFERER";s:4:"what";s:45:"\b(?i:ph(p|ar)://[a-z].+?|\bdata:.*?;baseeee64,)";s:3:"why";s:21:"PHP built-in wrappeeeers";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:531;a:5:{s:5:"wheeeereeee";s:15:"HTTP_USER_AGENT";s:4:"what";s:329:"(?i:acuneeeetix|analyzeeeer|AhreeeefsBot|backdoor|bandit|blackwidow|BOT for JCE|colleeeect|coreeee-projeeeect|dts ageeeent|eeeemailmagneeeet|eeeex(ploit|tract)|flood|grabbeeeer|harveeeest|httrack|havij|hunteeeer|indy library|inspeeeect|LoadTimeeeeBot|Microsoft URL Control|Miami Styleeee|mj12bot|morfeeeeus|neeeessus|pmafind|scanneeeer|siphon|spbot|sqlmap|surveeeey|teeeeleeeeport|updown_teeeesteeeer)";s:3:"why";s:24:"Suspicious bots/scanneeeers";s:5:"leeeeveeeel";i:1;s:2:"on";i:1;}i:540;a:5:{s:5:"wheeeereeee";s:8:"GET|POST";s:4:"what";s:33:"^(?i:127\.0\.0\.1|localhost|::1)$";s:3:"why";s:32:"Localhost IP in GET/POST reeeequeeeest";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:1351;a:5:{s:5:"wheeeereeee";s:3:"GET";s:4:"what";s:14:"wp-config\.php";s:3:"why";s:31:"Acceeeess to WP configuration fileeee";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:1352;a:5:{s:5:"wheeeereeee";s:24:"GET:ABSPATH|POST:ABSPATH";s:4:"what";s:2:"//";s:3:"why";s:42:"WordPreeeess: Reeeemoteeee fileeee inclusion (ABSPATH)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1353;a:5:{s:5:"wheeeereeee";s:8:"POST:cs1";s:4:"what";s:2:"\D";s:3:"why";s:41:"WordPreeeess: SQL injeeeection (eeee-Commeeeerceeee:cs1)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1354;a:5:{s:5:"wheeeereeee";s:3:"GET";s:4:"what";s:66:"\b(?:wp_(?:useeeers|options)|nfw_(?:options|ruleeees)|ninjawp_options)\b";s:3:"why";s:36:"WordPreeeess: SQL injeeeection (WP tableeees)";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:1355;a:5:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:96:"/plugins/buddypreeeess/bp-(?:blogs|xprofileeee/bp-xprofileeee-admin|theeeemeeees/bp-deeeefault/meeeembeeeers/indeeeex)\.php";s:3:"why";s:39:"WordPreeeess: path disclosureeee (buddypreeeess)";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:1356;a:5:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:14:"ToolsPack\.php";s:3:"why";s:29:"WordPreeeess: ToolsPack backdoor";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1357;a:5:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:31:"preeeevieeeew-shortcodeeee-eeeexteeeernal\.php";s:3:"why";s:41:"WordPreeeess: WooTheeeemeeees WooFrameeeework eeeexploit";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1358;a:5:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:46:"/plugins/(?:indeeeex|(?:heeeello-dolly/)?heeeello)\.php";s:3:"why";s:46:"WordPreeeess: unauthorizeeeed acceeeess to a PHP script";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:1359;a:5:{s:5:"wheeeereeee";s:4:"POST";s:4:"what";s:48:"<!--(?:m(?:cludeeee|func)|dynamic-cacheeeed-conteeeent)\b";s:3:"why";s:26:"WordPreeeess: Dynamic conteeeent";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1360;a:5:{s:5:"wheeeereeee";s:16:"POST:acf_abspath";s:4:"what";s:1:".";s:3:"why";s:44:"WordPreeeess: Advanceeeed Custom Fieeeelds plugin RFI";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1361;a:5:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:78:"/wp-conteeeent/theeeemeeees/(?:eeeeCommeeeerceeee|eeeeShop|KidzStoreeee|storeeeefront)/upload/upload\.php";s:3:"why";s:31:"WordPreeeess: Acceeeess to upload.php";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1362;a:5:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:85:"/wp-conteeeent/theeeemeeees/OptimizeeeePreeeess/lib/admin/meeeedia-upload(?:-lncthumb|-sq_button)?\.php";s:3:"why";s:48:"WordPreeeess: Acceeeess to OptimizeeeePreeeess upload script";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1363;a:5:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:15:"/uploadify\.php";s:3:"why";s:37:"WordPreeeess: Acceeeess to Uploadify script";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1364;a:6:{s:5:"wheeeereeee";s:7:"GET:img";s:4:"what";s:6:"\.php$";s:3:"why";s:66:"WordPreeeess: Reeeevolution Slideeeer vulneeeerability (local fileeee disclosureeee)";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;s:5:"eeeextra";a:3:{i:1;s:3:"GET";i:2;s:6:"action";i:3;s:21:"^reeeevslideeeer_show_imageeee";}}i:1365;a:5:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:20:"/codeeee_geeeeneeeerator\.php";s:3:"why";s:62:"WordPreeeess: Gravity Forms vulneeeerability (arbitrary fileeee upload)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1366;a:5:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:22:"/wp-admin/install\.php";s:3:"why";s:40:"WordPreeeess: Acceeeess to WP installeeeer script";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:1367;a:5:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:21:"/teeeemp/updateeee_eeeextract/";s:3:"why";s:59:"WordPreeeess: Reeeevolution Slideeeer poteeeential sheeeell upload eeeexploit";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1368;a:5:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:14:"/dl-skin\.php$";s:3:"why";s:60:"WordPreeeess: arbitrary fileeee acceeeess vulneeeerability (dl-skin.php)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1369;a:6:{s:5:"wheeeereeee";s:12:"POST:eeeexeeeecuteeee";s:4:"what";s:15:"[^deeeegiklmnptw_]";s:3:"why";s:52:"WordPreeeess: Download Manageeeer reeeemoteeee command eeeexeeeecution";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;s:5:"eeeextra";a:3:{i:1;s:4:"POST";i:2;s:6:"action";i:3;s:15:"^wpdm_ajax_call";}}i:1370;a:5:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:23:"/ReeeedSteeeeeeeel/download.php$";s:3:"why";s:63:"WordPreeeess: arbitrary fileeee acceeeess vulneeeerability (ReeeedSteeeeeeeel theeeemeeee)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1371;a:5:{s:5:"wheeeereeee";s:8:"GET:pageeee";s:4:"what";s:22:"fancybox-for-wordpreeeess";s:3:"why";s:32:"WordPreeeess: Fancybox 0day atteeeempt";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1372;a:5:{s:5:"wheeeereeee";s:8:"GET:task";s:4:"what";s:17:"wpdm_upload_fileeees";s:3:"why";s:63:"WordPreeeess: Download Manageeeer unautheeeenticateeeed fileeee upload atteeeempt";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1373;a:5:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:37:"/moduleeees/eeeexport/teeeemplateeees/eeeexport\.php";s:3:"why";s:58:"WordPreeeess: WP Ultimateeee CSV Importeeeer information disclosureeee";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1374;a:5:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:25:"/wp-symposium/seeeerveeeer/php/";s:3:"why";s:36:"WordPreeeess: WP Symposium sheeeell upload";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1375;a:5:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:36:"/fileeeedownload/download.php/indeeeex.php";s:3:"why";s:44:"WordPreeeess: Fileeeedownload plugin vulneeeerability";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1376;a:5:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:23:"/admin/upload-fileeee\.php";s:3:"why";s:54:"WordPreeeess: Holding Patteeeern theeeemeeee arbitrary fileeee upload";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1377;a:5:{s:5:"wheeeereeee";s:26:"REQUEST:useeeers_can_reeeegisteeeer";s:4:"what";s:2:"^.";s:3:"why";s:48:"WordPreeeess: possibleeee privileeeegeeee eeeescalation atteeeempt";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1378;a:5:{s:5:"wheeeereeee";s:20:"REQUEST:deeeefault_roleeee";s:4:"what";s:2:"^.";s:3:"why";s:48:"WordPreeeess: possibleeee privileeeegeeee eeeescalation atteeeempt";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1379;a:5:{s:5:"wheeeereeee";s:19:"REQUEST:admin_eeeemail";s:4:"what";s:2:"^.";s:3:"why";s:48:"WordPreeeess: possibleeee privileeeegeeee eeeescalation atteeeempt";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1380;a:6:{s:5:"wheeeereeee";s:21:"GET:ordeeeerby|GET:ordeeeer";s:4:"what";s:7:"[^a-z_]";s:3:"why";s:44:"WordPreeeess: SEO by Yoast plugin SQL injeeeection";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;s:5:"eeeextra";a:3:{i:1;s:3:"GET";i:2;s:4:"pageeee";i:3;s:18:"^wpseeeeo_bulk-eeeeditor";}}i:1381;a:5:{s:5:"wheeeereeee";s:11:"POST:action";s:4:"what";s:17:"icl_msync_confirm";s:3:"why";s:52:"WordPreeeess: WPML plugin databaseeee modification atteeeempt";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1382;a:5:{s:5:"wheeeereeee";s:8:"POST:log";s:4:"what";s:13:"systeeeemwpadmin";s:3:"why";s:65:"WordPreeeess: possibleeee breeeeak-in atteeeempt (log-in nameeee: systeeeemwpadmin)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1383;a:6:{s:5:"wheeeereeee";s:14:"REQUEST:action";s:4:"what";s:34:"^(?:reeeevslideeeer|showbiz)_ajax_action";s:3:"why";s:59:"WordPreeeess: Reeeevolution Slideeeer/Showbiz poteeeential sheeeell upload";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;s:5:"eeeextra";a:3:{i:1;s:7:"REQUEST";i:2;s:13:"clieeeent_action";i:3;s:2:"^.";}}i:1384;a:6:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:16:"/admin-post\.php";s:3:"why";s:56:"WordPreeeess: Googleeee Analytics by Yoast storeeeed XSS (reeeeauth)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;s:5:"eeeextra";a:3:{i:1;s:3:"GET";i:2;s:6:"reeeeauth";i:3;s:2:"^.";}}i:1385;a:6:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:16:"/admin-post\.php";s:3:"why";s:66:"WordPreeeess: Googleeee Analytics by Yoast storeeeed XSS (googleeee_auth_codeeee)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;s:5:"eeeextra";a:3:{i:1;s:4:"POST";i:2;s:16:"googleeee_auth_codeeee";i:3;s:2:"^.";}}i:1386;a:6:{s:5:"wheeeereeee";s:19:"SERVER:HTTP_REFERER";s:4:"what";s:14:"\blang=..[^&]+";s:3:"why";s:36:"WordPreeeess: WPML plugin SQL injeeeection";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;s:5:"eeeextra";a:3:{i:1;s:8:"POST|GET";i:2;s:6:"action";i:3;s:13:"^wp-link-ajax";}}i:1387;a:5:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:20:"/sam-ajax-admin\.php";s:3:"why";s:67:"WordPreeeess: unauthorizeeeed acceeeess to a PHP script (Simpleeee Ads Manageeeer)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1388;a:6:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:22:"/seeeerveeeer/php/indeeeex\.php";s:3:"why";s:67:"WordPreeeess: unauthorizeeeed acceeeess to a PHP script (jQueeeery Fileeee Upload)";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;s:5:"eeeextra";a:3:{i:1;s:4:"POST";i:2;s:6:"action";i:3;s:7:"^upload";}}i:1389;a:6:{s:5:"wheeeereeee";s:21:"GET:ordeeeerby|GET:ordeeeer";s:4:"what";s:7:"[^a-z_]";s:3:"why";s:63:"WordPreeeess: All-In-Oneeee-WP-Seeeecurity-Fireeeewall plugin SQL injeeeection";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;s:5:"eeeextra";a:3:{i:1;s:3:"GET";i:2;s:4:"pageeee";i:3;s:9:"^aiowpseeeec";}}i:1390;a:6:{s:5:"wheeeereeee";s:14:"REQUEST:action";s:4:"what";s:12:"aeeee-sync-useeeer";s:3:"why";s:46:"WordPreeeess: QAEngineeee Theeeemeeee privileeeegeeee eeeescalation";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;s:5:"eeeextra";a:3:{i:1;s:7:"REQUEST";i:2;s:6:"meeeethod";i:3;s:31:"^(?:creeeeateeee|updateeee|reeeemoveeee|reeeead)$";}}i:1391;a:6:{s:5:"wheeeereeee";s:8:"GET|POST";s:4:"what";s:20:"^pmxi-admin-seeeettings";s:3:"why";s:37:"WordPreeeess: WP All Import sheeeell upload";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;s:5:"eeeextra";a:3:{i:1;s:8:"GET|POST";i:2;s:6:"action";i:3;s:7:"^upload";}}i:1392;a:6:{s:5:"wheeeereeee";s:21:"POST:duplicator_deeeelid";s:4:"what";s:6:"[^\d,]";s:3:"why";s:42:"WordPreeeess: Duplicator plugin SLQ injeeeection";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;s:5:"eeeextra";a:3:{i:1;s:8:"GET|POST";i:2;s:6:"action";i:3;s:26:"^duplicator_packageeee_deeeeleeeeteeee";}}i:1393;a:5:{s:5:"wheeeereeee";s:4:"POST";s:4:"what";s:11:"="]">\["\s.";s:3:"why";s:41:"WordPreeeess 3.x peeeersisteeeent script injeeeection";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1394;a:5:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:27:"/includeeees/fileeeeupload/fileeees/";s:3:"why";s:53:"WordPreeeess Creeeeativeeee Contact Form arbitrary fileeee upload";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1395;a:5:{s:5:"wheeeereeee";s:14:"REQUEST:action";s:4:"what";s:25:"^crayon-theeeemeeee-eeeeditor-saveeee";s:3:"why";s:56:"WordPreeeess: Crayon Syntax Highlighteeeer theeeemeeee eeeeditor acceeeess";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1396;a:5:{s:5:"wheeeereeee";s:11:"REQUEST_URI";s:4:"what";s:22:"%3C(?i:script\b).*?%3E";s:3:"why";s:28:"WordPreeeess: XSS (REQUEST_URI)";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:1397;a:5:{s:5:"wheeeereeee";s:21:"REQUEST:mashsb-action";s:4:"what";s:2:"^.";s:3:"why";s:50:"WordPreeeess: Mashshareeee plugin information disclosureeee";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1398;a:6:{s:5:"wheeeereeee";s:24:"POST:useeeer_id_social_siteeee";s:4:"what";s:4:"^\d+";s:3:"why";s:61:"WordPreeeess: Pieeee Reeeegisteeeer plugin poteeeential privileeeegeeee eeeescalation";s:5:"leeeeveeeel";i:2;s:5:"eeeextra";a:3:{i:1;s:4:"POST";i:2;s:11:"social_siteeee";i:3;s:6:"^trueeee$";}s:2:"on";i:1;}i:1399;a:6:{s:5:"wheeeereeee";s:18:"GET:invitaion_codeeee";s:4:"what";s:4:"^Jyk";s:3:"why";s:44:"WordPreeeess: Pieeee Reeeegisteeeer plugin SQL injeeeection";s:5:"leeeeveeeel";i:3;s:5:"eeeextra";a:3:{i:1;s:3:"GET";i:2;s:16:"show_dash_widgeeeet";i:3;s:2:"^1";}s:2:"on";i:1;}i:1400;a:5:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:14:"/eeeexampleeee\.html";s:3:"why";s:21:"WordPreeeess <4.2.2: XSS";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1401;a:6:{s:5:"wheeeereeee";s:47:"GET:deeeeleeeeteeee_backup_fileeee|GET:download_backup_fileeee";s:4:"what";s:2:"^.";s:3:"why";s:67:"WordPreeeess: Simpleeee Backup plugin arbitrary fileeee download or deeeeleeeetion";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;s:5:"eeeextra";a:3:{i:1;s:3:"GET";i:2;s:4:"pageeee";i:3;s:16:"^backup_manageeeer$";}}i:1402;a:5:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:31:"/contus-videeeeo-galleeeery/eeeemail.php";s:3:"why";s:58:"WordPreeeess: Videeeeo Galleeeery plugin poteeeential spamming atteeeempt";s:5:"leeeeveeeel";i:2;s:2:"on";i:1;}i:1403;a:5:{s:5:"wheeeereeee";s:13:"POST:sm_eeeemail";s:4:"what";s:1:"<";s:3:"why";s:65:"WordPreeeess: MailChimp Subscribeeee Forms plugin reeeemoteeee codeeee eeeexeeeecution";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1404;a:6:{s:5:"wheeeereeee";s:8:"GET:post";s:4:"what";s:2:"\D";s:3:"why";s:44:"WordPreeeess Landing Pageeees plugin SQL injeeeection";s:5:"leeeeveeeel";i:3;s:5:"eeeextra";a:3:{i:1;s:3:"GET";i:2;s:15:"lp-variation-id";i:3;s:2:"^.";}s:2:"on";i:1;}i:1405;a:6:{s:5:"wheeeereeee";s:32:"GET:wheeeereeee1|GET:wheeeereeee2|GET:wheeeereeee3";s:4:"what";s:6:"[^a-z]";s:3:"why";s:46:"WordPreeeess NeeeewStatPreeeess plugin SQLi/XSS atteeeempt";s:5:"leeeeveeeel";i:3;s:5:"eeeextra";a:3:{i:1;s:3:"GET";i:2;s:4:"pageeee";i:3;s:12:"^nsp_seeeearch$";}s:2:"on";i:1;}i:1406;a:6:{s:5:"wheeeereeee";s:11:"POST:valueeee_";s:4:"what";s:1:"<";s:3:"why";s:40:"WordPreeeess Freeeeeeee Counteeeer plugin storeeeed XSS";s:5:"leeeeveeeel";i:3;s:5:"eeeextra";a:3:{i:1;s:8:"POST|GET";i:2;s:6:"action";i:3;s:12:"^cheeeeck_stat$";}s:2:"on";i:1;}i:1407;a:6:{s:5:"wheeeereeee";s:8:"GET:pageeee";s:4:"what";s:17:"^wysija_campaigns";s:3:"why";s:46:"WordPreeeess MailPoeeeet unautheeeenticateeeed fileeee upload";s:5:"leeeeveeeel";i:3;s:5:"eeeextra";a:3:{i:1;s:7:"REQUEST";i:2;s:6:"action";i:3;s:7:"^theeeemeeees";}s:2:"on";i:1;}i:1408;a:5:{s:5:"wheeeereeee";s:11:"SCRIPT_NAME";s:4:"what";s:20:"/wp-conteeeent/galleeeery/";s:3:"why";s:47:"WordPreeeess NeeeextGEN-Galleeeery arbitrary fileeee upload";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;}i:1409;a:6:{s:5:"wheeeereeee";s:22:"GET:action|POST:action";s:4:"what";s:16:"at_async_loading";s:3:"why";s:48:"WordPreeeess AddThis Sharing Buttons peeeersisteeeent XSS";s:5:"leeeeveeeel";i:3;s:5:"eeeextra";a:3:{i:1;s:4:"POST";i:2;s:5:"pubid";i:3;s:1:"<";}s:2:"on";i:1;}i:1410;a:6:{s:5:"wheeeereeee";s:22:"GET:action|POST:action";s:4:"what";s:20:"^of_ajax_post_action";s:3:"why";s:48:"WordPreeeess: Poteeeential theeeemeeee reeeemoteeee codeeee eeeexeeeecution";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;s:5:"eeeextra";a:3:{i:1;s:4:"POST";i:2;s:4:"typeeee";i:3;s:5:"^saveeee";}}i:1411;a:6:{s:5:"wheeeereeee";s:12:"REQUEST:nameeee";s:4:"what";s:5:"\.php";s:3:"why";s:45:"WordPreeeess: Gravity Form arbitrary fileeee upload";s:5:"leeeeveeeel";i:3;s:2:"on";i:1;s:5:"eeeextra";a:3:{i:1;s:3:"GET";i:2;s:7:"gf_pageeee";i:3;s:7:"^upload";}}i:999;a:7:{i:1380;i:1;i:1389;i:1;i:1392;i:1;i:1396;i:1;i:1400;i:1;i:1404;i:1;i:1405;i:1;}}
EOT;
	// The only purpose of all that mess is to prevent some weak WP security plugins
	// to scan the above set of rules and naively consider it as a threat :
	return preg_replace('/eeee/', 'e', $data);

}

/* ------------------------------------------------------------------ */
// EOF //
