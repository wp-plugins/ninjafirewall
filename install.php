<?php
/*
 +---------------------------------------------------------------------+
 | NinjaFirewall (WP edition)                                          |
 |                                                                     |
 | (c) NinTechNet                                                      |
 | <wordpress@nintechnet.com>                                          |
 +---------------------------------------------------------------------+
 | http://nintechnet.com/                                              |
 +---------------------------------------------------------------------+
 | REVISION: 2014-07-11 14:21:25                                       |
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
 +---------------------------------------------------------------------+
*/

if (! defined( 'NFW_ENGINE_VERSION' ) ) { die( 'Forbidden' ); }

if ( ( is_multisite() ) && (! current_user_can( 'manage_network' ) ) ) {
	return;
}

if ( empty( $_POST['nfw_act'] ) ) {
	nfw_install_1();

} elseif ( $_POST['nfw_act'] == 11 ) {
	nfw_install_2a( 0 );

} elseif ( $_POST['nfw_act'] == 1 ) {
	nfw_install_2( 0 );

} elseif ( $_POST['nfw_act'] == 2 ) {
	nfw_install_3();
}
return;

/* ================================================================== */

function nfw_install_1() {

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
	<div class="updated settings-error"><br />Use ONLY your WordPress administration console (<a href="<?php echo admin_url() ?>plugins.php" style="text-decoration:underline;">Plugins</a> menu) to activate, deactivate, install, update, upgrade, uninstall or even delete NinjaFirewall.
	<br />
	Do NOT attempt to perform any of the above operations using another application ( FTP, cPanel, Plesk etc), or to modify, rename, move, edit, or overwrite its files, EVEN when it is disabled.
	<br />
	Do NOT attempt to migrate your site with NinjaFirewall installed. Uninstall it, migrate your site and reinstall it.
	<br />
	<br />
	<center><img src="<?php echo plugins_url( '/images/icon_warn_16.png', __FILE__ ) ?>" border="0" height="16" width="16">&nbsp;<strong>Failure to do so will almost always cause you to be locked out of your own site and/or to crash it.</strong><br />&nbsp;</center>
	</div>
	<br />
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
	If you need some help regarding the installation, please consult our <a href="http://ninjafirewall.com/wordpress/help.php">site</a>.
	<br />
	<br />
	<form method="post">
		<input class="button-primary" type="submit" name="Save" value="Enough chitchat, let's go ! &gt;&gt;" />
		<input type="hidden" name="nfw_act" value="11" />
	</form>
</div>
<?php

}

/* ================================================================== */

function nfw_install_2a( $err ) {

	// if the document_root is identical to ABSPATH, we jump
	// to the next step :
	if ( (getenv( 'DOCUMENT_ROOT' ) . '/') == ABSPATH ) {
		$_POST['abspath'] = ABSPATH;
		nfw_install_2( 0 );
		return;
	}
	// otherwise, ask the user for the full path to index.php :
	echo '
<div class="wrap">
	<div style="width:54px;height:52px;background-image:url(' . plugins_url() . '/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2>NinjaFirewall (WP edition)</h2>
	<br />
	<br />';
	// error ?
	if ( $err ) {
		echo '<div class="error settings-error"><p><strong>Error :</strong> ' . $err . '</p></div>';
	}
	echo '<br />
	<form method="post">
	<p>Your WordPress directory (<code>' . ABSPATH . '</code>) is different from your website document root (<code>' . getenv('DOCUMENT_ROOT') . '/</code>). Because it is possible to install WordPress into a subdirectory, but have the blog exist in the site root, NinjaFirewall needs to know its exact location.</p>
	<p>Please edit the path below only if you have manually modified your WordPress root directory as described in the <a href="http://codex.wordpress.org/Giving_WordPress_Its_Own_Directory" target="_blank">Giving WordPress Its Own Directory</a> article.</p>
	<p><strong style="color:red">Most users should not change this value.</strong></p>
	<p>Path to WordPress root directory: <input class="regular-text code" type="text" name="abspath" value="' . ABSPATH . '"></p>
	<br />
	<br />
		<input class="button-primary" type="submit" name="Save" value="Next Step &gt;&gt;" />
		<input type="hidden" name="nfw_act" value="1" />
	</form>
</div>';

}
/* ================================================================== */

function nfw_install_2( $err ) {


	if (empty ($_POST['abspath']) ) {
		nfw_install_2a( 'please enter the full path to WordPress index.php file' );
		return;
	}
	$abspath = rtrim( $_POST['abspath'], '/' );
	if (! file_exists( $abspath . '/index.php' ) ) {
		nfw_install_2a( 'cannot find <code>' . $abspath . '/index.php</code> ! Please correct the full path to WordPress root directory.' );
		return;
	}

	$_SESSION['abspath'] = $abspath . '/';

	// Save the configuration to the DB :
	nfw_default_conf();

	$abspath_writable = 0;
	$htaccess = $htaccess_path = $htaccess_writable = $htaccess_data = 0;
	$phpini = $phpini_path = $phpini_writable = $phpini_data = $phpini_user = 0;
	$diy = 0;

	nfw_ini_data();

	echo '
<div class="wrap">
	<div style="width:54px;height:52px;background-image:url(' . plugins_url() . '/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2>NinjaFirewall (WP edition)</h2>
	<br />
	<br />';

	// ensure the log directory is writable :
	if (! is_writable( plugin_dir_path(__FILE__) .  'log' ) ) {
		echo '<div class="error settings-error"><p><strong>Error :</strong> NinjaFirewall log directory is not writable (<code>' . plugin_dir_path(__FILE__) . 'log/</code>). Please chmod it to 0777 and reload this page.</p></div></div>';
		return;
	}

	// error ?
	if ( $err ) {
		echo '<div class="error settings-error"><p><strong>Error :</strong> ' . $err . '</p></div>';
	}

	echo 'In order to hook and protect all PHP files, NinjaFirewall needs to add some specific directives to system files located inside WordPress root directory. Those files will have to be created, or, if they exist, to be edited. If your WordPress root directory is writable, I will make those changes for you, otherwise, you will need to do it yourself (using your FTP client or any suitable admin panel).
	<br />
	<br />
	<strong>Checking your system configuration :</strong>
	<br />
	<br />

	<form method="post" name="nfw_install03">';

	// Multisite ?
	if ( is_multisite() ) {
		echo '<li>Multisite network detected : NinjaFirewall will protect all sites from your network but its configuration interface will be <strong>accessible only to the Super Admin</strong> from the network main site.</li>';
	}

	// If mod_php is running, we won't need any PHP INI file :
	if ( preg_match( '/apache/i', PHP_SAPI ) ) {
		echo '<li>Your server seems to be running PHP as an Apache module (<code>' . strtoupper( PHP_SAPI ) . '</code>). Therefore, we will not need any PHP INI file.</li>';

		// look for .htaccess :
		echo '<li>Looking for <code>' . $_SESSION['abspath'] . '.htaccess</code> file : ';
		if ( file_exists( $_SESSION['abspath'] . '.htaccess' ) ) {
			echo '<strong>found</strong></li>';
			$htaccess_path = $_SESSION['abspath'];
			$htaccess = '.htaccess';
			// is it writable ?
			echo '<li>Checking if <code>.htaccess</code> is writable : ';
			if ( is_writable( $_SESSION['abspath'] . '.htaccess' ) ) {
				echo '<strong>yes</strong></li>';
				$htaccess_writable = 1;
			} else {
				echo '<strong>no</strong></li>';
			}
		} else {
			echo '<strong>not found</strong></li>';
		}
		// check whether WP root dir is writable :
		echo '<li>Checking if WordPress root directory is writable : ';
		if ( is_writable( $_SESSION['abspath'] ) ) {
			echo '<strong>yes</strong></li>';
			$abspath_writable = 1;
		} else {
			echo '<strong>no</strong></li>';
		}
		echo '<br /><strong>Required changes :</strong><br />';

		// .htaccess exists :
		if ( $htaccess ) {
			// fetch its content :
			$data = file_get_contents( $htaccess_path . $htaccess );

			// make sure we don't have already some of our own instructions left :
			$pos_start = strpos( $data, HTACCESS_BEGIN );
			$pos_end   = strpos( $data, HTACCESS_END );
			if ( ( $pos_start !== FALSE ) && ( $pos_end !== FALSE ) && ( $pos_end > $pos_start ) ) {
				$data = substr( $data, $pos_end + strlen( HTACCESS_END ) );
			}

			if ( $htaccess_writable ) {
				echo '<li>I will add the following <font color="red">red lines</font> of code to your <code>' . $htaccess_path . $htaccess . '</code> file. All other lines, if any, are the actual content of the file&nbsp;:</li>';
				$button_title = 'Apply changes';
			} else {
				echo '<li>I cannot make any change to your system because it is read-only. Please download the <code>' . $htaccess_path . $htaccess . '</code> file with your FTP client, overwrite its content with the one below (the <font color="red">red lines</font> of code will be used by NinjaFirewall, and all other lines, if any, are the actual content of the file). Then re-upload it to your server and click the "Test configuration" button&nbsp;:</li>';
				$button_title = 'Test configuration';
				$diy = 1;
			}
		} else {
			// There is no .htaccess, we need to create one :
			if ( is_writable( $_SESSION['abspath'] ) ) {
				echo '<li>I will create the <code>' . $_SESSION['abspath'] . '.htaccess</code> file and will add the following <font color="red">red lines</font> of code&nbsp;:</li>';
				$button_title = 'Apply changes';
			} else {
				echo '<li>I cannot make the required changes because your system is read-only. Please create a file named <code>.htaccess</code> ( see "<a href="http://codex.wordpress.org/Using_Permalinks#Creating_and_editing_.28.htaccess.29" target="_blank">Creating and editing .htaccess</a>" ), add the following <font color="red">red lines</font> of code and, using your FTP client, upload it into your WordPress root directory (<code>' . $_SESSION['abspath'] . '.htaccess</code>). Then click the "Test configuration" button&nbsp;:</li>';
				$diy = 1;
				$button_title = 'Test configuration';
			}
		}
		$htaccess_data = HTACCESS_BEGIN . "\n" . HTACCESS_MODPHP . "\n" . HTACCESS_END . "\n\n";
		echo '<pre style="background-color:#EAEAEA;border: 1px solid #cccccc;padding:5px;overflow: auto;">' .
			'<font color="red">' . htmlentities( $htaccess_data ) . '</font>';

		if (! empty( $data ) ) {
			echo htmlentities( $data );
			$htaccess_data .= $data;
		}
		echo '</pre>';


	// ------------------------------------------------------------------
	// PHP as CGI :
	} else {

		echo '<li>Your server is running PHP as <code>' . strtoupper( PHP_SAPI ) . '</code> SAPI</li>';

		// look for PHP INI file :
		echo '<li>Looking for a PHP INI file inside <code>' . $_SESSION['abspath'] . '</code> : ';
		if ( file_exists( $_SESSION['abspath'] . 'php.ini' ) ) {
			$phpini = 'php.ini';

			echo '<strong>found</strong> <code>php.ini</code></li>';
		} elseif ( file_exists( $_SESSION['abspath'] . 'php5.ini' ) ) {
			echo '<strong>found</strong> <code>php5.ini</code></li>';
			$phpini = 'php5.ini';
		} elseif ( file_exists( $_SESSION['abspath'] . '.user.ini' ) ) {
			echo '<strong>found</strong> <code>.user.ini</code></li>';
			$phpini ='.user.ini';
		}
		if ( $phpini ) {
			$phpini_path = $_SESSION['abspath'];
			// is it writable ?
			echo '<li>Checking if PHP INI is writable : ';
			if ( is_writable( $_SESSION['abspath'] . $phpini ) ) {
				echo '<strong>yes</strong></li>';
				$phpini_writable = 1;
			} else {
				echo '<strong>no</strong></li>';
			}
		} else {
			echo '<strong>not found</strong></li>';
		}
		// Look for .htaccess :
		echo '<li>Looking for <code>' . $_SESSION['abspath'] . '.htaccess</code> file : ';
		if ( file_exists( $_SESSION['abspath'] . '.htaccess' ) ) {
			echo '<strong>found</strong></li>';
			$htaccess = '.htaccess';
			$htaccess_path = $_SESSION['abspath'];
			// is it writable ?
			echo '<li>Checking if <code>.htaccess</code> is writable : ';
			if ( is_writable( $_SESSION['abspath'] . '.htaccess' ) ) {
				echo '<strong>yes</strong></li>';
				$htaccess_writable = 1;
			} else {
				echo '<strong>no</strong></li>';
			}
		} else {
			echo '<strong>not found</strong></li>';
		}
		// check whether WP root dir is writable :
		echo '<li>Checking if WordPress root directory is writable : ';
		if ( is_writable( $_SESSION['abspath'] ) ) {
			echo '<strong>yes</strong></li>';
			$abspath_writable = 1;
		} else {
			echo '<strong>no</strong></li>';
		}

		echo '<br /><strong>Required changes :</strong><br /><br />';

		// PHP INI exists :
		$data = '';
		if ( $phpini ) {
			// fetch its content :
			$data = file_get_contents( $_SESSION['abspath'] . $phpini );

			// make sure we don't have already some of our own instructions left :
			$pos_start = strpos( $data, PHPINI_BEGIN );
			$pos_end   = strpos( $data, PHPINI_END );
			if ( ( $pos_start !== FALSE ) && ( $pos_end !== FALSE ) && ( $pos_end > $pos_start ) ) {
				$data = substr( $data, $pos_end + strlen( PHPINI_END ) );
			}
			if ( $phpini_writable ) {
				echo '<li>I will add the following <font color="red">red lines</font> of code to your <code>' . $_SESSION['abspath'] . $phpini . '</code> file. All other lines, if any, are the actual content of the file&nbsp;:</li>';
				$button_title = 'Apply changes';
			} else {
				echo '<li>I cannot make any change to your system because it is read-only. Please download the <code>' . $_SESSION['abspath'] . $phpini . '</code> file with your FTP client, overwrite its content with the one below (the <font color="red">red lines</font> of code will be used by NinjaFirewall, and all other lines, if any, are the actual content of the file). Then re-upload it to your server&nbsp;:</li>';
				$button_title = 'Test configuration';
				$diy = 1;
			}
		} else {
			// There is no PHP INI we need to create one :
			if ( is_writable( $_SESSION['abspath'] ) ) {
				echo '<li>I need to create a PHP INI file inside your WordPress main directory (<code>' . $_SESSION['abspath'] . '</code>). Usually, such a file is named <code>php.ini</code> but some hosting companies may use <code>php5.ini</code> (e.g. GoDaddy) or <code>.user.ini</code> files instead. Please select which PHP INI file you want me to create&nbsp;:</li>
				<label><input type="radio" name="phpini_user" value="php.ini" checked onclick="document.getElementById(\'phpiniuser\').innerHTML = \'php.ini\';document.nfw_install03.elements[\'nfw_conf_arr[phpini_user]\'].value=this.value">&nbsp;<code>php.ini</code> (default)</label>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<label><input type="radio" name="phpini_user" value="php5.ini" onclick="document.getElementById(\'phpiniuser\').innerHTML = \'php5.ini\';document.nfw_install03.elements[\'nfw_conf_arr[phpini_user]\'].value=this.value">&nbsp;<code>php5.ini</code></label>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<label><input type="radio" name="phpini_user" value=".user.ini" onclick="document.getElementById(\'phpiniuser\').innerHTML = \'.user.ini\';document.nfw_install03.elements[\'nfw_conf_arr[phpini_user]\'].value=this.value">&nbsp;<code>.user.ini</code></label>
				<br /><br />
				I will add the <font color="red">red lines</font> of code below to that file.';
				$button_title = 'Apply changes';
				// default name :
				$phpini_user = 'php.ini';
			} else {
				echo '<li>I cannot make the required changes because your system is read-only. Using your FTP client, you need to create a PHP INI file inside your WordPress main directory. Usually, such a file is named <code>php.ini</code> but some hosting companies may use <code>php5.ini</code> (e.g. GoDaddy) or <code>.user.ini</code> files instead.
				<br />
				Select which PHP INI file you will create so that I could give you the lines of code to use&nbsp;:</li>
				<label><input type="radio" name="phpini_user" value="php.ini" checked onclick="document.getElementById(\'phpiniuser\').innerHTML = \'php.ini\';document.getElementById(\'phpiniusertxt\').innerHTML = \'php.ini\';document.nfw_install03.elements[\'nfw_conf_arr[phpini_user]\'].value=this.value">&nbsp;<code>php.ini</code> (default)</label>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<label><input type="radio" name="phpini_user" value="php5.ini" onclick="document.getElementById(\'phpiniuser\').innerHTML = \'php5.ini\';document.getElementById(\'phpiniusertxt\').innerHTML = \'php5.ini\';document.nfw_install03.elements[\'nfw_conf_arr[phpini_user]\'].value=this.value">&nbsp;<code>php5.ini</code></label>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<label><input type="radio" name="phpini_user" value=".user.ini" onclick="document.getElementById(\'phpiniuser\').innerHTML = \'.user.ini\';document.getElementById(\'phpiniusertxt\').innerHTML = \'.user.ini\';document.nfw_install03.elements[\'nfw_conf_arr[phpini_user]\'].value=this.value">&nbsp;<code>.user.ini</code></label>
				<br /><br />
				Please add the following <font color="red">red lines</font> of code to that file and upload it into your WordPress root directory (<code>' . $_SESSION['abspath'] . '<font id="phpiniusertxt">php.ini</font></code>)&nbsp;:</li>';
				$diy = 1;
				$button_title = 'Test configuration';
				// default name :
				$phpini_user = 'php.ini';
			}
		}
		$phpini_data = PHPINI_BEGIN . "\n" . PHPINI_DATA . "\n" . PHPINI_END . "\n\n";
		echo '<pre style="background-color:#EAEAEA;border: 1px solid #cccccc;padding:5px;overflow: auto;">' .
			'<font color="red">' . htmlentities(  $phpini_data ) . '</font>';

		if (! empty( $data ) ) {
			echo htmlentities( $data );
			$phpini_data .= $data;
		}
		echo '</pre>
		Some hosting accounts may require a few minutes before a newly uploaded or modified PHP INI file is reloaded by PHP. This value is often 300 seconds/5 minutes by default ( see <a href="http://php.net/manual/en/configuration.file.per-user.php" target="_blank">user_ini.cache_ttl</a> ). If NinjaFirewall does not work after applying your changes, <strong>please wait 5 minutes and try again</strong>.
		<br />
		<br />';


		// .htaccess exists :
		$data = '';
		if ( $htaccess ) {
			// fetch its content :
			$data = file_get_contents( $_SESSION['abspath'] . $htaccess );

			// make sure we don't have already some of our own instructions left :
			$pos_start = strpos( $data, HTACCESS_BEGIN );
			$pos_end   = strpos( $data, HTACCESS_END );
			if ( ( $pos_start !== FALSE ) && ( $pos_end !== FALSE ) && ( $pos_end > $pos_start ) ) {
				$data = substr( $data, $pos_end + strlen( HTACCESS_END ) );
			}

			if ( $htaccess_writable ) {
				echo '<li>I will add the following <font color="red">red lines</font> of code to your <code>' . $_SESSION['abspath'] . $htaccess . '</code> file. All other lines, if any, are the actual content of the file&nbsp;:</li>';
				$button_title = 'Apply changes';
			} else {
				echo '<li>I cannot make any change to your system because it is read-only. Please download the <code>' . $_SESSION['abspath'] . $htaccess . '</code> file with your FTP client, overwrite its content with the one below (the <font color="red">red lines</font> of code will be used by NinjaFirewall, and all other lines, if any, are the actual content of the file). Then re-upload it to your server&nbsp;:</li>';
				$button_title = 'Test configuration';
				$diy = 1;
			}
		} else {
			// There is no .htaccess, we need to create one :
			if ( is_writable( $_SESSION['abspath'] ) ) {
				echo '<li>I will create a <code>' . $_SESSION['abspath'] . '.htaccess</code> file and will add the following <font color="red">red lines</font> of code&nbsp;:</li>';
				$button_title = 'Apply changes';
			} else {
				echo '<li>I cannot make the required changes because your system is read-only. Please create a file named <code>.htaccess</code> ( see "<a href="http://codex.wordpress.org/Using_Permalinks#Creating_and_editing_.28.htaccess.29" target="_blank">Creating and editing .htaccess</a>" ), add the following <font color="red">red lines</font> of code and, using your FTP client, upload it into your WordPress root directory (<code>' . $_SESSION['abspath'] . '.htaccess</code>)&nbsp;:</li>';
				$diy = 1;
				$button_title = 'Test configuration';
			}
		}

		$htaccess_data = HTACCESS_BEGIN . "\n" . HTACCESS_CGI_01;
		$htaccess_data .= $_SESSION['abspath'] . 'NFW_XYW';
		$htaccess_data .= HTACCESS_CGI_02 . "\n";
		// For Litespeed server, if running in mod_php-like mode:
		if ( preg_match( '/litespeed/i', PHP_SAPI ) ) {
			$htaccess_data .= HTACCESS_MODPHP . "\n";
		}
		$htaccess_data .= HTACCESS_END . "\n\n";

		echo '<pre style="background-color:#EAEAEA;border: 1px solid #cccccc;padding:5px;overflow: auto;">' .
			'<font color="red">' . HTACCESS_BEGIN . "\n" . htmlentities( HTACCESS_CGI_01 ) . $_SESSION['abspath'] .
			'<font id="phpiniuser">';
			if ( $phpini_user ) { echo $phpini_user; }
			elseif ( $phpini ) { echo $phpini; }
			else { echo 'php.ini'; }
			echo '</font>' . htmlentities( HTACCESS_CGI_02 ) . "\n";
		// For Litespeed server, if running in mod_php-like mode:
		if ( preg_match( '/litespeed/i', PHP_SAPI ) ) {
			echo htmlentities( HTACCESS_MODPHP ) . "\n";
		}
		echo HTACCESS_END . "\n\n</font>";

		if (! empty( $data ) ) {
			echo htmlentities( $data );
			$htaccess_data .= $data;
		}
		echo '</pre>';

	} // PHP as CGI

	echo '<br />If, after applying changes, there was an HTTP error and your site was not reachable, use your FTP client to download the above files, undo all changes and try again.
		<br />
		<br />
		<input type="hidden" name="nfw_conf_arr[abspath_writable]" value="' . $abspath_writable . '">
		<input type="hidden" name="nfw_conf_arr[htaccess]" value="' . $htaccess . '">
		<input type="hidden" name="nfw_conf_arr[htaccess_path]" value="' . $htaccess_path . '">
		<input type="hidden" name="nfw_conf_arr[htaccess_writable]" value="' . $htaccess_writable . '">
		<input type="hidden" name="nfw_conf_arr[htaccess_data]" value="' . base64_encode( $htaccess_data ) . '">
		<input type="hidden" name="nfw_conf_arr[phpini]" value="' . $phpini . '">
		<input type="hidden" name="nfw_conf_arr[phpini_path]" value="' . $phpini_path . '">
		<input type="hidden" name="nfw_conf_arr[phpini_writable]" value="' . $phpini_writable . '">
		<input type="hidden" name="nfw_conf_arr[phpini_data]" value="' . base64_encode( $phpini_data ) . '">
		<input type="hidden" name="nfw_conf_arr[phpini_user]" value="' . $phpini_user . '">
		<input type="hidden" name="nfw_conf_arr[diy]" value="' . $diy . '">
		<input type="hidden" name="nfw_act" value="2">
		<input type="hidden" name="nfw_test" value="1">
		<input class="button-primary" type="submit" name="config_button" value="' . $button_title . '">
	</form>
	</div>';

}

/* ================================================================== */

function nfw_install_3() {

	if (! isset( $_POST['nfw_conf_arr'] ) ) {
		nfw_install_2( 0 );
		return;
	}

	if (empty ($_SESSION['abspath']) ) {
		nfw_install_2a( 'please enter the full path to WordPress index.php file' );
		return;
	}
	if (! file_exists( $_SESSION['abspath'] . 'index.php' ) ) {
		nfw_install_2a( 'cannot find <code>' . $abspath . '/index.php</code> ! Please correct the full path to WordPress root directory.' );
		return;
	}

	$nfw_conf_arr = $_POST['nfw_conf_arr'];

	// User made unsuccessful changes ?
	if (! empty( $nfw_conf_arr['diy'] ) ) {
		nfw_install_2( 'NinjaFirewall is not setup properly. Please try again.' );
		return;
	}

	// We must have some data to write... :
	if ( empty( $nfw_conf_arr['htaccess_data'] ) && empty( $nfw_conf_arr['phpin_data'] ) ) {
		// ...obviously, there is a problem here :
		nfw_install_2( 'I do not know what to do !' );
		return;
	}

	$err = '';

	// Modify the current .htaccess ?
	if (! empty( $nfw_conf_arr['htaccess'] ) ) {
		// We must have something to write to it :
		if ( empty( $nfw_conf_arr['htaccess_data'] ) ) {
			nfw_install_2( 'I do not know what to write to the .htaccess file. Please do the changes manually.' );
			return;
		}

		// Ensure the file is still there :
		if (! file_exists( $nfw_conf_arr['htaccess_path'] . $nfw_conf_arr['htaccess'] ) ) {
			nfw_install_2( 'I cannot find the .htaccess file. Please do the changes manually.' );
			return;
		}
		// Ensure it is writable :
		if (! is_writable( $nfw_conf_arr['htaccess_path'] . $nfw_conf_arr['htaccess'] ) ) {
			nfw_install_2( 'the .htaccess file is not writable. Please do the changes manually.' );
			return;
		}
		// backup the current .htaccess (if WP ASBPATH is writable) :
		if ( is_writable( $_SESSION['abspath'] ) ) {
			$copy = time();
			copy( $nfw_conf_arr['htaccess_path'] . $nfw_conf_arr['htaccess'] ,
					$nfw_conf_arr['htaccess_path'] . $nfw_conf_arr['htaccess'] . '.' . $copy );
			@chmod( $nfw_conf_arr['htaccess_path'] . $nfw_conf_arr['htaccess'] . '.' . $copy, 0644 );
		}
	// We need to create a .htaccess :
	} else {
		$nfw_conf_arr['htaccess'] = '.htaccess';
		$nfw_conf_arr['htaccess_path'] = $_SESSION['abspath'];
	}

	$nfw_conf_arr['htaccess_data'] = base64_decode( $nfw_conf_arr['htaccess_data'] );

	// replace our placeholder ('NFW_XYW'), if any, with the correct PHP INI file name :
	if (! empty( $_POST['phpini_user'] ) ) {
		$nfw_conf_arr['htaccess_data'] = str_replace('NFW_XYW', $_POST['phpini_user'], $nfw_conf_arr['htaccess_data']);
	} elseif (! empty( $nfw_conf_arr['phpini'] ) ) {
		$nfw_conf_arr['htaccess_data'] = str_replace('NFW_XYW', $nfw_conf_arr['phpini'], $nfw_conf_arr['htaccess_data']);
	}

	// write our instructions :
	if ( file_put_contents( $nfw_conf_arr['htaccess_path'] . $nfw_conf_arr['htaccess'],
			$nfw_conf_arr['htaccess_data'] ) === FALSE ) {
		$err .= 'Unable to write to ' . $nfw_conf_arr['htaccess_path'] . $nfw_conf_arr['htaccess']  . '<br />';
	} else {
		@chmod( $nfw_conf_arr['htaccess_path'] . $nfw_conf_arr['htaccess'], 0644 );
	}

	// Modify the current PHP INI ?
	if (! empty( $nfw_conf_arr['phpini'] ) ) {
		// We must have something to write to it :
		if ( empty( $nfw_conf_arr['phpini_data'] ) ) {
			nfw_install_2( 'I do not know what to write to the PHP INI file. Please do the changes manually.' );
			return;
		}
		// Ensure the file is still there :
		if (! file_exists( $nfw_conf_arr['phpini_path'] . $nfw_conf_arr['phpini'] ) ) {
			nfw_install_2( 'I cannot find the PHP INI file. Please do the changes manually.' );
			return;
		}
		// Ensure it is writable :
		if (! is_writable( $nfw_conf_arr['phpini_path'] . $nfw_conf_arr['phpini'] ) ) {
			nfw_install_2( 'the PHP INI file is not writable. Please do the changes manually.' );
			return;
		}
		// backup the current PHP INI (if WP ASBPATH is writable) :
		if ( is_writable( $_SESSION['abspath'] ) ) {
			$copy = time();
			copy( $nfw_conf_arr['phpini_path'] . $nfw_conf_arr['phpini'] ,
					$nfw_conf_arr['phpini_path'] . $nfw_conf_arr['phpini'] . '.' . $copy );
			@chmod( $nfw_conf_arr['phpini_path'] . $nfw_conf_arr['phpini'] . '.' . $copy, 0644 );
		}

	// User defined PHP INI ?
	} elseif (! empty( $_POST['phpini_user'] ) ) {
		$nfw_conf_arr['phpini'] = $_POST['phpini_user'];
		$nfw_conf_arr['phpini_path'] = $_SESSION['abspath'];
	}

	// Are we supposed to write to a PHP INI file ?
	if ( $nfw_conf_arr['phpini'] ) {

		$nfw_conf_arr['phpini_data'] = base64_decode( $nfw_conf_arr['phpini_data'] );

		// write our instructions :
		if ( file_put_contents( $nfw_conf_arr['phpini_path']. $nfw_conf_arr['phpini'],
				$nfw_conf_arr['phpini_data'] ) === FALSE ) {
			$err .= 'Unable to write to ' . $nfw_conf_arr['phpini_path'] . $nfw_conf_arr['phpini'];
		} else {
			@chmod( $_SESSION['abspath'] . $nfw_conf_arr['phpini'], 0644 );
		}
	}

	// Save files path :
	$nfw_install['htaccess'] = $nfw_conf_arr['htaccess_path'] . $nfw_conf_arr['htaccess'];
	$nfw_install['phpini']   = $nfw_conf_arr['phpini_path'] . $nfw_conf_arr['phpini'];
	update_option( 'nfw_install', $nfw_install);

	echo '
<div class="wrap">
	<div style="width:54px;height:52px;background-image:url(' . plugins_url() . '/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2>NinjaFirewall (WP edition)</h2>
	<br />
	<br />';

	if ( $err ) {
		echo '<div class="error settings-error"><p><strong>Error : </strong>' . $err . '</p></div>';
	} else {
		echo '<div class="updated settings-error"><p><strong>Your changes have been saved.</strong></p></div>';
	}
	echo '<br />
	<br />
	<form method="get" action="admin.php">
		<input type="hidden" name="page" value="NinjaFirewall">
		<input type="hidden" name="nfw_firstrun" value="1">
		<input class="button-primary" type="submit" value="Test NinjaFirewall configuration">
	</form>
	</div>';
}

/* ================================================================== */

function nfw_ini_data() {

	define( 'HTACCESS_BEGIN', '# BEGIN NinjaFirewall' );
	define( 'HTACCESS_END', '# END NinjaFirewall' );
	define( 'HTACCESS_MODPHP', '<IfModule mod_php5.c>' .
		"\nphp_value auto_prepend_file " . 	plugin_dir_path(__FILE__) .
		"lib/firewall.php\n</IfModule>" );
	define( 'HTACCESS_CGI_01', "<IfModule !mod_php5.c>\nSetEnv PHPRC " );
	define( 'HTACCESS_CGI_02', "\n</IfModule>" );
	define( 'PHPINI_BEGIN', '; BEGIN NinjaFirewall' );
	define( 'PHPINI_DATA', 'auto_prepend_file = ' .
		plugin_dir_path(__FILE__) . 'lib/firewall.php' );
	define( 'PHPINI_END', '; END NinjaFirewall' );

	// set the admin goodguy flag :
	$_SESSION['nfw_goodguy'] = true;

}

/* ================================================================== */

function nfw_default_conf() {

	// Get user options if this is an update :
	$nfw_options = get_option( 'nfw_options' );

	// New ones :
	$nfw_options_new = array(
		'logo'				=> plugins_url() . '/ninjafirewall/images/ninjafirewall_75.png',
		'enabled'			=> 1,
		'ret_code'			=> 403,
		'blocked_msg'		=> base64_encode(NFW_DEFAULT_MSG),
		'debug'				=> 0,
		'scan_protocol'	=> 3,
		'uploads'			=> 0,
		'sanitise_fn'		=> 1,
		'get_scan'			=> 1,
		'get_sanitise'		=> 1,
		'post_scan'			=> 1,
		'post_sanitise'	=> 0,
		'cookies_scan'		=> 1,
		'cookies_sanitise'=> 1,
		'ua_scan'			=> 1,
		'ua_sanitise'		=> 1,
		'referer_scan'		=> 1,
		'referer_sanitise'=> 1,
		'referer_post'		=> 0,
		'no_host_ip'		=> 0,
		'allow_local_ip'	=> 0,
		'php_errors'		=> 1,
		'php_self'			=> 1,
		'php_path_t'		=> 1,
		'php_path_i'		=> 1,
		'wp_dir'				=> '/wp-admin/(?:css|images|includes|js)/|' .
									'/wp-includes/(?:(?:css|images|js|theme-compat)/|[^/]+\.php)|' .
									'/'. basename(WP_CONTENT_DIR) .'/uploads/|/cache/',
		'no_post_themes'	=> 0,
		'force_ssl'			=> 0,
		'disallow_edit'	=> 1,
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
		'alert_email'	 	=> get_option('admin_email'),
		// v1.1.0 :
		'alert_sa_only'	=> 2,
		'nt_show_status'	=> 1,
		'post_b64'			=> 1,
		// v1.1.2 :
		'no_xmlrpc'			=> 0,
		// v1.1.3 :
		'enum_archives'	=> 0,
		'enum_login'		=> 1,
		// v1.1.6 :
		'request_sanitise'=> 0,
		// v1.2.1 :
		'fg_enable'			=>	0,
		'fg_mtime'			=>	10,
	);

	// save new options but do not overwrite existing ones :
	foreach ( $nfw_options_new as $new_key => $new_value ) {
		if (! isset( $nfw_options[$new_key] ) ) {
			$nfw_options[$new_key] = $new_value;
		}
	}
	// Update engine and rules versions :
	$nfw_options['engine_version'] = NFW_ENGINE_VERSION;
	$nfw_options['rules_version']  = NFW_RULES_VERSION;

	// Get current rules if this is an update :
	$nfw_rules = get_option( 'nfw_rules' );

	// Get the new ones :
	$nfw_rules_new = unserialize( nfw_default_rules() );

	foreach ( $nfw_rules_new as $new_key => $new_value ) {
		foreach ( $new_value as $key => $value ) {
			// if that rule exists already, we don't change the 'on' flag :
			if ( ( isset( $nfw_rules[$new_key]['on'] ) ) && ( $key == 'on' ) ) {
				continue;
			} else {
				$nfw_rules[$new_key][$key] = $value;
			}
		}
	}
	// Always ensure the document root is correct :
	if ( strlen( getenv( 'DOCUMENT_ROOT' ) ) > 5 ) {
		$nfw_rules[NFW_DOC_ROOT]['what'] = getenv( 'DOCUMENT_ROOT' );
		if (! isset( $nfw_rules[NFW_DOC_ROOT]['on']  ) ) {
			$nfw_rules[NFW_DOC_ROOT]['on'] = 1;
		}
	} elseif ( strlen( $_SERVER['DOCUMENT_ROOT'] ) > 5 ) {
		$nfw_rules[NFW_DOC_ROOT]['what'] = $_SERVER['DOCUMENT_ROOT'];
		if (! isset( $nfw_rules[NFW_DOC_ROOT]['on']  ) ) {
			$nfw_rules[NFW_DOC_ROOT]['on'] = 1;
		}
	} else {
		$nfw_rules[NFW_DOC_ROOT]['on']  = 0;
	}


	// Save to the DB :
	update_option( 'nfw_options', $nfw_options);
	update_option( 'nfw_rules', $nfw_rules);

}

/* ================================================================== */

function nfw_default_rules() {

	return $data = <<<'EOT'
a:91:{i:1;a:5:{s:5:"where";s:31:"GET|POST|COOKIE|HTTP_USER_AGENT";s:4:"what";s:24:"(?:\.{2}[\\/]{1,4}){2}\b";s:3:"why";s:19:"Directory traversal";s:5:"level";i:3;s:2:"on";i:1;}i:3;a:5:{s:5:"where";s:31:"GET|POST|COOKIE|HTTP_USER_AGENT";s:4:"what";s:34:"[.\\/]/(?:proc/self/|etc/passwd)\b";s:3:"why";s:20:"Local file inclusion";s:5:"level";i:2;s:2:"on";i:1;}i:50;a:5:{s:5:"where";s:31:"GET|POST|COOKIE|HTTP_USER_AGENT";s:4:"what";s:31:"^(?i:https?|ftp)://.+/[^&/]+\?$";s:3:"why";s:21:"Remote file inclusion";s:5:"level";i:3;s:2:"on";i:1;}i:51;a:5:{s:5:"where";s:31:"GET|POST|COOKIE|HTTP_USER_AGENT";s:4:"what";s:49:"^(?i:https?)://\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}";s:3:"why";s:30:"Remote file inclusion (URL IP)";s:5:"level";i:2;s:2:"on";i:1;}i:52;a:5:{s:5:"where";s:31:"GET|POST|COOKIE|HTTP_USER_AGENT";s:4:"what";s:61:"\b(?i:include|require)(?i:_once)?\s*\([^)]*(?i:https?|ftp)://";s:3:"why";s:43:"Remote file inclusion (via require/include)";s:5:"level";i:3;s:2:"on";i:1;}i:53;a:5:{s:5:"where";s:31:"GET|POST|COOKIE|HTTP_USER_AGENT";s:4:"what";s:33:"^(?i:ftp)://(?:.+?:.+?\@)?[^/]+/.";s:3:"why";s:27:"Remote file inclusion (FTP)";s:5:"level";i:2;s:2:"on";i:1;}i:100;a:5:{s:5:"where";s:56:"GET|POST|REQUEST_URI|COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:85:"<\s*/?(?i:applet|div|embed|i?frame(?:set)?|meta|marquee|object|script|textarea)\b.*?>";s:3:"why";s:14:"XSS (HTML tag)";s:5:"level";i:2;s:2:"on";i:1;}i:101;a:5:{s:5:"where";s:39:"GET|COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:67:"\W(?:background(-image)?|-moz-binding)\s*:[^}]*?\burl\s*\([^)]+?://";s:3:"why";s:27:"XSS (remote background URI)";s:5:"level";i:3;s:2:"on";i:1;}i:102;a:5:{s:5:"where";s:39:"GET|COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:80:"(?i:<[^>]+?(?:data|href|src)\s*=\s*['\"]?(?:https?|data|php|(?:java|vb)script):)";s:3:"why";s:16:"XSS (remote URI)";s:5:"level";i:3;s:2:"on";i:1;}i:103;a:5:{s:5:"where";s:39:"GET|COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:157:"\b(?i:on(?i:abort|blur|(?:dbl)?click|dragdrop|error|focus|key(?:up|down|press)|(?:un)?load|mouse(?:down|out|over|up)|move|res(?:et|ize)|select|submit))\b\s*=";s:3:"why";s:16:"XSS (HTML event)";s:5:"level";i:2;s:2:"on";i:1;}i:104;a:5:{s:5:"where";s:44:"GET|POST|COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:85:"[:=\]]\s*['\"]?(?:alert|confirm|eval|expression|prompt|String\.fromCharCode|url)\s*\(";s:3:"why";s:17:"XSS (JS function)";s:5:"level";i:3;s:2:"on";i:1;}i:105;a:5:{s:5:"where";s:44:"GET|POST|COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:56:"\bdocument\.(?:body|cookie|location|open|write(?:ln)?)\b";s:3:"why";s:21:"XSS (document object)";s:5:"level";i:2;s:2:"on";i:1;}i:106;a:5:{s:5:"where";s:44:"GET|POST|COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:30:"\blocation\.(?:href|replace)\b";s:3:"why";s:21:"XSS (location object)";s:5:"level";i:2;s:2:"on";i:1;}i:107;a:5:{s:5:"where";s:44:"GET|POST|COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:29:"\bwindow\.(?:open|location)\b";s:3:"why";s:19:"XSS (window object)";s:5:"level";i:2;s:2:"on";i:1;}i:108;a:5:{s:5:"where";s:44:"GET|POST|COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:33:"(?i:style)\s*=\s*['\"]?[^'\"]+/\*";s:3:"why";s:22:"XSS (obfuscated style)";s:5:"level";i:3;s:2:"on";i:1;}i:109;a:5:{s:5:"where";s:44:"GET|POST|COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:4:"^/?>";s:3:"why";s:31:"XSS (leading greater-than sign)";s:5:"level";i:2;s:2:"on";i:1;}i:110;a:5:{s:5:"where";s:12:"QUERY_STRING";s:4:"what";s:18:"(?:%%\d\d%\d\d){5}";s:3:"why";s:19:"XSS (double nibble)";s:5:"level";i:2;s:2:"on";i:1;}i:111;a:5:{s:5:"where";s:56:"GET|POST|REQUEST_URI|COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:48:"(\+|\%2B)A(Dw|ACIAPgA8)-.+?(\+|\%2B)AD4(APAAi)?-";s:3:"why";s:11:"XSS (UTF-7)";s:5:"level";i:2;s:2:"on";i:1;}i:150;a:5:{s:5:"where";s:8:"GET|POST";s:4:"what";s:59:"[\n\r]\s*\b(?:(?:reply-)?to|b?cc|content-[td]\w)\b\s*:.*?\@";s:3:"why";s:21:"Mail header injection";s:5:"level";i:2;s:2:"on";i:1;}i:151;a:5:{s:5:"where";s:8:"GET|POST";s:4:"what";s:36:"^[\x0d\x0a]{1,2}[-a-zA-Z0-9]+:\s*\w+";s:3:"why";s:21:"HTTP header injection";s:5:"level";i:2;s:2:"on";i:1;}i:152;a:5:{s:5:"where";s:35:"COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:10:"[\x0d\x0a]";s:3:"why";s:29:"HTTP header injection (CR/LF)";s:5:"level";i:2;s:2:"on";i:1;}i:153;a:5:{s:5:"where";s:44:"GET|POST|COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:56:"<!--#(?:config|echo|exec|flastmod|fsize|include)\b.+?-->";s:3:"why";s:21:"SSI command injection";s:5:"level";i:2;s:2:"on";i:1;}i:154;a:5:{s:5:"where";s:35:"COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:31:"(?s:<\?.+)|#!/(?:usr|bin)/.+?\s";s:3:"why";s:14:"Code Injection";s:5:"level";i:3;s:2:"on";i:1;}i:155;a:5:{s:5:"where";s:8:"GET|POST";s:4:"what";s:360:"(?s:<\?(?![Xx][Mm][Ll]).*?(?:\$_?(?:COOKIE|ENV|FILES|GLOBALS|(?:GE|POS|REQUES)T|SE(RVER|SSION))\s*[=\[)]|\b(?i:array_map|assert|base64_(?:de|en)code|curl_exec|eval|file(?:_get_contents)?|fsockopen|gzinflate|move_uploaded_file|passthru|preg_replace|phpinfo|stripslashes|strrev|system|(?:shell_)?exec)\s*\()|\x60.+?\x60)|#!/(?:usr|bin)/.+?\s|\W\$\{\s*['"]\w+['"]";s:3:"why";s:14:"Code Injection";s:5:"level";i:3;s:2:"on";i:1;}i:156;a:5:{s:5:"where";s:8:"GET|POST";s:4:"what";s:115:"\b(?i:eval)\s*\(\s*(?i:base64_decode|exec|file_get_contents|gzinflate|passthru|shell_exec|stripslashes|system)\s*\(";s:3:"why";s:17:"Code Injection #2";s:5:"level";i:2;s:2:"on";i:1;}i:157;a:5:{s:5:"where";s:8:"GET:fltr";s:4:"what";s:1:";";s:3:"why";s:25:"Code injection (phpThumb)";s:5:"level";i:3;s:2:"on";i:1;}i:158;a:5:{s:5:"where";s:17:"GET:phpThumbDebug";s:4:"what";s:1:".";s:3:"why";s:36:"phpThumb debug mode (potential SSRF)";s:5:"level";i:3;s:2:"on";i:1;}i:159;a:5:{s:5:"where";s:7:"GET:src";s:4:"what";s:2:"\$";s:3:"why";s:46:"TimThumb WebShot Remote Code Execution (0-day)";s:5:"level";i:3;s:2:"on";i:1;}i:200;a:5:{s:5:"where";s:15:"GET|POST|COOKIE";s:4:"what";s:44:"^(?i:admin(?:istrator)?)['\"].*?(?:--|#|/\*)";s:3:"why";s:35:"SQL injection (admin login attempt)";s:5:"level";i:3;s:2:"on";i:1;}i:201;a:5:{s:5:"where";s:8:"GET|POST";s:4:"what";s:72:"\b(?i:[-\w]+@(?:[-a-z0-9]+\.)+[a-z]{2,8}'.{0,20}\band\b.{0,20}=[\s/*]*')";s:3:"why";s:34:"SQL injection (user login attempt)";s:5:"level";i:3;s:2:"on";i:1;}i:202;a:5:{s:5:"where";s:26:"GET:username|POST:username";s:4:"what";s:20:"[#'\"=(),<>/\\*\x60]";s:3:"why";s:24:"SQL injection (username)";s:5:"level";i:3;s:2:"on";i:1;}i:204;a:5:{s:5:"where";s:44:"GET|POST|COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:57:"\b(?i:and|or|having)\b.+?['\"]?(\w+)['\"]?\s*=\s*['\"]?\1";s:3:"why";s:30:"SQL injection (equal operator)";s:5:"level";i:3;s:2:"on";i:1;}i:205;a:5:{s:5:"where";s:8:"GET|POST";s:4:"what";s:67:"(?i:(?:\b(?:and|or|union)\b|;|').*?\bfrom\b.+?information_schema\b)";s:3:"why";s:34:"SQL injection (information_schema)";s:5:"level";i:3;s:2:"on";i:1;}i:206;a:5:{s:5:"where";s:8:"GET|POST";s:4:"what";s:53:"/\*\*/(?i:and|from|limit|or|select|union|where)/\*\*/";s:3:"why";s:35:"SQL injection (comment obfuscation)";s:5:"level";i:3;s:2:"on";i:1;}i:207;a:5:{s:5:"where";s:3:"GET";s:4:"what";s:30:"^[-\d';].+\w.+(?:--|#|/\*)\s*$";s:3:"why";s:32:"SQL injection (trailing comment)";s:5:"level";i:3;s:2:"on";i:1;}i:208;a:5:{s:5:"where";s:35:"COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:162:"(?i:(?:\b(?:and|or|union)\b|;|').*?\b(?:alter|create|delete|drop|grant|information_schema|insert|load|rename|select|truncate|update)\b.+?\b(?:from|into|on|set)\b)";s:3:"why";s:13:"SQL injection";s:5:"level";i:1;s:2:"on";i:1;}i:209;a:5:{s:5:"where";s:8:"GET|POST";s:4:"what";s:227:"(?i:(?:\b(?:and|or|union)\b|;|').*?(?:\ball\b.+?)?\bselect\b.+?\b(?:and\b|from\b|limit\b|where\b|\@?\@?version\b|(?:user|benchmark|char|count|database|(?:group_)?concat(?:_ws)?|floor|md5|rand|substring|version)\s*\(|--|/\*|#$))";s:3:"why";s:22:"SQL injection (select)";s:5:"level";i:3;s:2:"on";i:1;}i:210;a:5:{s:5:"where";s:8:"GET|POST";s:4:"what";s:98:"(?i:(?:\b(?:and|or|union)\b|;|').*?(?:\ball\b.+?)?\binsert\b.+?\binto\b.*?\([^)]+\).+?values.*?\()";s:3:"why";s:22:"SQL injection (insert)";s:5:"level";i:3;s:2:"on";i:1;}i:211;a:5:{s:5:"where";s:8:"GET|POST";s:4:"what";s:60:"(?i:(?:\b(?:and|or|union)\b|;|').*?\bupdate\b.+?\bset\b.+?=)";s:3:"why";s:22:"SQL injection (update)";s:5:"level";i:3;s:2:"on";i:1;}i:212;a:5:{s:5:"where";s:8:"GET|POST";s:4:"what";s:62:"(?i:(?:\b(?:and|or|union)\b|;|').*?\bgrant\b.+?\bon\b.+?to\s+)";s:3:"why";s:21:"SQL injection (grant)";s:5:"level";i:3;s:2:"on";i:1;}i:213;a:5:{s:5:"where";s:8:"GET|POST";s:4:"what";s:59:"(?i:(?:\b(?:and|or|union)\b|;|').*?\bdelete\b.+?\bfrom\b.+)";s:3:"why";s:22:"SQL injection (delete)";s:5:"level";i:3;s:2:"on";i:1;}i:214;a:5:{s:5:"where";s:8:"GET|POST";s:4:"what";s:130:"(?i:(?:\b(?:and|or|union)\b|;|').*?\b(alter|create|drop)\b.+?(?:DATABASE|FUNCTION|INDEX|PROCEDURE|SCHEMA|TABLE|TRIGGER|VIEW)\b.+?)";s:3:"why";s:33:"SQL injection (alter/create/drop)";s:5:"level";i:3;s:2:"on";i:1;}i:215;a:5:{s:5:"where";s:8:"GET|POST";s:4:"what";s:67:"(?i:(?:\b(?:and|or|union)\b|;|').*?\b(?:rename|truncate)\b.+?table)";s:3:"why";s:31:"SQL injection (rename/truncate)";s:5:"level";i:3;s:2:"on";i:1;}i:216;a:5:{s:5:"where";s:8:"GET|POST";s:4:"what";s:112:"(?i:(?:\b(?:and|or|union)\b|;|').*?\bselect\b.+?\b(?:into\b.+?(?:(?:dump|out)file|\@['\"\x60]?\w+)|load_file))\b";s:3:"why";s:37:"SQL injection (select into/load_file)";s:5:"level";i:3;s:2:"on";i:1;}i:217;a:5:{s:5:"where";s:8:"GET|POST";s:4:"what";s:77:"(?i:(?:\b(?:and|or|union)\b|;|').*?load\b.+?\bdata\b.+?\binfile\b.+?\binto)\b";s:3:"why";s:20:"SQL injection (load)";s:5:"level";i:3;s:2:"on";i:1;}i:250;a:5:{s:5:"where";s:9:"HTTP_HOST";s:4:"what";s:20:"[^-a-zA-Z0-9._:\[\]]";s:3:"why";s:21:"Malformed Host header";s:5:"level";i:2;s:2:"on";i:1;}i:300;a:5:{s:5:"where";s:3:"GET";s:4:"what";s:6:"^['\"]";s:3:"why";s:13:"Leading quote";s:5:"level";i:2;s:2:"on";i:1;}i:301;a:5:{s:5:"where";s:3:"GET";s:4:"what";s:11:"^[\x09\x20]";s:3:"why";s:13:"Leading space";s:5:"level";i:1;s:2:"on";i:1;}i:302;a:5:{s:5:"where";s:22:"QUERY_STRING|PATH_INFO";s:4:"what";s:44:"\bHTTP_RAW_POST_DATA|HTTP_(?:POS|GE)T_VARS\b";s:3:"why";s:12:"PHP variable";s:5:"level";i:2;s:2:"on";i:1;}i:303;a:5:{s:5:"where";s:11:"SCRIPT_NAME";s:4:"what";s:12:"phpinfo\.php";s:3:"why";s:29:"Attempt to access phpinfo.php";s:5:"level";i:1;s:2:"on";i:1;}i:304;a:5:{s:5:"where";s:11:"SCRIPT_NAME";s:4:"what";s:30:"/scripts/(?:setup|signon)\.php";s:3:"why";s:26:"phpMyAdmin hacking attempt";s:5:"level";i:2;s:2:"on";i:1;}i:305;a:5:{s:5:"where";s:11:"SCRIPT_NAME";s:4:"what";s:24:"\.ph(?:p[2-6]?|tml)\..+?";s:3:"why";s:23:"PHP handler obfuscation";s:5:"level";i:3;s:2:"on";i:1;}i:309;a:5:{s:5:"where";s:58:"QUERY_STRING|PATH_INFO|COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:141:"\b(?:\$?_(COOKIE|ENV|FILES|(?:GE|POS|REQUES)T|SE(RVER|SSION))|HTTP_(?:(?:POST|GET)_VARS|RAW_POST_DATA)|GLOBALS)\s*[=\[)]|\W\$\{\s*['"]\w+['"]";s:3:"why";s:24:"PHP predefined variables";s:5:"level";i:2;s:2:"on";i:1;}i:310;a:5:{s:5:"where";s:11:"SCRIPT_NAME";s:4:"what";s:118:"(?i:(?:conf(?:ig(?:ur(?:e|ation)|\.inc|_global)?)?)|settings?(?:\.?inc)?|\b(?:db(?:connect)?|connect)(?:\.?inc)?)\.php";s:3:"why";s:30:"Access to a configuration file";s:5:"level";i:2;s:2:"on";i:1;}i:311;a:5:{s:5:"where";s:11:"SCRIPT_NAME";s:4:"what";s:40:"/tiny_?mce/plugins/spellchecker/classes/";s:3:"why";s:23:"TinyMCE path disclosure";s:5:"level";i:2;s:2:"on";i:1;}i:312;a:5:{s:5:"where";s:20:"HTTP_X_FORWARDED_FOR";s:4:"what";s:21:"[^.0-9a-f:\x20,unkow]";s:3:"why";s:29:"Non-compliant X_FORWARDED_FOR";s:5:"level";i:1;s:2:"on";i:1;}i:313;a:5:{s:5:"where";s:12:"QUERY_STRING";s:4:"what";s:14:"^-[bcndfiswzT]";s:3:"why";s:31:"PHP-CGI exploit (CVE-2012-1823)";s:5:"level";i:3;s:2:"on";i:1;}i:350;a:5:{s:5:"where";s:11:"SCRIPT_NAME";s:4:"what";s:186:"(?i:bypass|c99(?:madShell|ud)?|c100|cookie_(?:usage|setup)|diagnostics|dump|endix|gifimg|goog[l1]e.+[\da-f]{10}|imageth|imlog|r5[47]|safe0ver|sniper|(?:jpe?g|gif|png))\.ph(?:p[2-6]?|tml)";s:3:"why";s:14:"Shell/backdoor";s:5:"level";i:3;s:2:"on";i:1;}i:351;a:5:{s:5:"where";s:28:"GET:nixpasswd|POST:nixpasswd";s:4:"what";s:3:"^.?";s:3:"why";s:26:"Shell/backdoor (nixpasswd)";s:5:"level";i:3;s:2:"on";i:1;}i:352;a:5:{s:5:"where";s:12:"QUERY_STRING";s:4:"what";s:16:"\bact=img&img=\w";s:3:"why";s:20:"Shell/backdoor (img)";s:5:"level";i:3;s:2:"on";i:1;}i:353;a:5:{s:5:"where";s:12:"QUERY_STRING";s:4:"what";s:15:"\bc=img&name=\w";s:3:"why";s:21:"Shell/backdoor (name)";s:5:"level";i:3;s:2:"on";i:1;}i:354;a:5:{s:5:"where";s:12:"QUERY_STRING";s:4:"what";s:36:"^image=(?:arrow|file|folder|smiley)$";s:3:"why";s:22:"Shell/backdoor (image)";s:5:"level";i:3;s:2:"on";i:1;}i:355;a:5:{s:5:"where";s:6:"COOKIE";s:4:"what";s:21:"\buname=.+?;\ssysctl=";s:3:"why";s:23:"Shell/backdoor (cookie)";s:5:"level";i:3;s:2:"on";i:1;}i:356;a:5:{s:5:"where";s:30:"POST:sql_passwd|GET:sql_passwd";s:4:"what";s:1:".";s:3:"why";s:27:"Shell/backdoor (sql_passwd)";s:5:"level";i:3;s:2:"on";i:1;}i:357;a:5:{s:5:"where";s:12:"POST:nowpath";s:4:"what";s:3:"^.?";s:3:"why";s:24:"Shell/backdoor (nowpath)";s:5:"level";i:3;s:2:"on";i:1;}i:358;a:5:{s:5:"where";s:18:"POST:view_writable";s:4:"what";s:3:"^.?";s:3:"why";s:30:"Shell/backdoor (view_writable)";s:5:"level";i:3;s:2:"on";i:1;}i:359;a:5:{s:5:"where";s:6:"COOKIE";s:4:"what";s:13:"\bphpspypass=";s:3:"why";s:23:"Shell/backdoor (phpspy)";s:5:"level";i:3;s:2:"on";i:1;}i:360;a:5:{s:5:"where";s:6:"POST:a";s:4:"what";s:90:"^(?:Bruteforce|Console|Files(?:Man|Tools)|Network|Php|SecInfo|SelfRemove|Sql|StringTools)$";s:3:"why";s:18:"Shell/backdoor (a)";s:5:"level";i:3;s:2:"on";i:1;}i:361;a:5:{s:5:"where";s:12:"POST:nst_cmd";s:4:"what";s:2:"^.";s:3:"why";s:24:"Shell/backdoor (nstview)";s:5:"level";i:3;s:2:"on";i:1;}i:362;a:5:{s:5:"where";s:8:"POST:cmd";s:4:"what";s:206:"^(?:c(?:h_|URL)|db_query|echo\s\\.*|(?:edit|download|save)_file|find(?:_text|\s.+)|ftp_(?:brute|file_(?:down|up))|mail_file|mk|mysql(?:b|_dump)|php_eval|ps\s.*|search_text|safe_dir|sym[1-2]|test[1-8]|zend)$";s:3:"why";s:20:"Shell/backdoor (cmd)";s:5:"level";i:2;s:2:"on";i:1;}i:363;a:5:{s:5:"where";s:5:"GET:p";s:4:"what";s:65:"^(?:chmod|cmd|edit|eval|delete|headers|md5|mysql|phpinfo|rename)$";s:3:"why";s:18:"Shell/backdoor (p)";s:5:"level";i:3;s:2:"on";i:1;}i:364;a:5:{s:5:"where";s:12:"QUERY_STRING";s:4:"what";s:139:"^act=(?:bind|cmd|encoder|eval|feedback|ftpquickbrute|gofile|ls|mkdir|mkfile|processes|ps_aux|search|security|sql|tools|update|upload)&d=%2F";s:3:"why";s:20:"Shell/backdoor (act)";s:5:"level";i:3;s:2:"on";i:1;}i:2;a:5:{s:5:"where";s:62:"GET|POST|COOKIE|HTTP_USER_AGENT|REQUEST_URI|PHP_SELF|PATH_INFO";s:4:"what";s:8:"%00|\x00";s:3:"why";s:19:"NULL byte character";s:5:"level";i:3;s:2:"on";i:1;}i:500;a:5:{s:5:"where";s:44:"GET|POST|COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:20:"[\x01-\x08\x0e-\x1f]";s:3:"why";s:35:"Disallowed ASCII control characters";s:5:"level";i:2;s:2:"on";i:1;}i:510;a:4:{s:5:"where";s:20:"GET|POST|REQUEST_URI";s:4:"what";s:11:"/nothingyet";s:3:"why";s:24:"Document root in request";s:5:"level";i:2;}i:520;a:5:{s:5:"where";s:44:"GET|POST|COOKIE|HTTP_USER_AGENT|HTTP_REFERER";s:4:"what";s:40:"\b(?i:php://[a-z].+?|\bdata:.*?;base64,)";s:3:"why";s:12:"PHP wrappers";s:5:"level";i:3;s:2:"on";i:1;}i:531;a:5:{s:5:"where";s:15:"HTTP_USER_AGENT";s:4:"what";s:303:"(?i:acunetix|analyzer|AhrefsBot|backdoor|bandit|blackwidow|BOT for JCE|collect|core-project|dts agent|emailmagnet|ex(ploit|tract)|flood|grabber|harvest|httrack|havij|hunter|indy library|inspect|LoadTimeBot|Microsoft URL Control|mj12bot|morfeus|nessus|pmafind|scanner|siphon|spbot|sqlmap|survey|teleport)";s:3:"why";s:14:"Bad User-agent";s:5:"level";i:1;s:2:"on";i:1;}i:540;a:5:{s:5:"where";s:8:"GET|POST";s:4:"what";s:29:"^(?i:127\.0\.0\.1|localhost)$";s:3:"why";s:31:"Connection attempt to localhost";s:5:"level";i:2;s:2:"on";i:1;}i:1351;a:5:{s:5:"where";s:3:"GET";s:4:"what";s:14:"wp-config\.php";s:3:"why";s:31:"Access to WP configuration file";s:5:"level";i:2;s:2:"on";i:1;}i:1352;a:5:{s:5:"where";s:24:"GET:ABSPATH|POST:ABSPATH";s:4:"what";s:2:"//";s:3:"why";s:42:"WordPress: Remote file inclusion (ABSPATH)";s:5:"level";i:3;s:2:"on";i:1;}i:1353;a:5:{s:5:"where";s:8:"POST:cs1";s:4:"what";s:2:"\D";s:3:"why";s:41:"WordPress: SQL injection (e-Commerce:cs1)";s:5:"level";i:3;s:2:"on";i:1;}i:1354;a:5:{s:5:"where";s:3:"GET";s:4:"what";s:66:"\b(?:wp_(?:users|options)|nfw_(?:options|rules)|ninjawp_options)\b";s:3:"why";s:36:"WordPress: SQL injection (WP tables)";s:5:"level";i:2;s:2:"on";i:1;}i:1355;a:5:{s:5:"where";s:11:"SCRIPT_NAME";s:4:"what";s:96:"/plugins/buddypress/bp-(?:blogs|xprofile/bp-xprofile-admin|themes/bp-default/members/index)\.php";s:3:"why";s:39:"WordPress: path disclosure (buddypress)";s:5:"level";i:2;s:2:"on";i:1;}i:1356;a:5:{s:5:"where";s:11:"SCRIPT_NAME";s:4:"what";s:14:"ToolsPack\.php";s:3:"why";s:29:"WordPress: ToolsPack backdoor";s:5:"level";i:3;s:2:"on";i:1;}i:1357;a:5:{s:5:"where";s:11:"SCRIPT_NAME";s:4:"what";s:31:"preview-shortcode-external\.php";s:3:"why";s:41:"WordPress: WooThemes WooFramework exploit";s:5:"level";i:3;s:2:"on";i:1;}i:1358;a:5:{s:5:"where";s:11:"SCRIPT_NAME";s:4:"what";s:29:"/plugins/(?:index|hello)\.php";s:3:"why";s:46:"WordPress: unauthorised access to a PHP script";s:5:"level";i:2;s:2:"on";i:1;}i:1359;a:5:{s:5:"where";s:4:"POST";s:4:"what";s:48:"<!--(?:m(?:clude|func)|dynamic-cached-content)\b";s:3:"why";s:26:"WordPress: Dynamic content";s:5:"level";i:3;s:2:"on";i:1;}i:1360;a:5:{s:5:"where";s:16:"POST:acf_abspath";s:4:"what";s:1:".";s:3:"why";s:44:"WordPress: Advanced Custom Fields plugin RFI";s:5:"level";i:3;s:2:"on";i:1;}i:1361;a:5:{s:5:"where";s:11:"SCRIPT_NAME";s:4:"what";s:78:"/wp-content/themes/(?:eCommerce|eShop|KidzStore|storefront)/upload/upload\.php";s:3:"why";s:31:"WordPress: Access to upload.php";s:5:"level";i:3;s:2:"on";i:1;}i:1362;a:5:{s:5:"where";s:11:"SCRIPT_NAME";s:4:"what";s:85:"/wp-content/themes/OptimizePress/lib/admin/media-upload(?:-lncthumb|-sq_button)?\.php";s:3:"why";s:48:"WordPress: Access to OptimizePress upload script";s:5:"level";i:3;s:2:"on";i:1;}i:1363;a:5:{s:5:"where";s:11:"SCRIPT_NAME";s:4:"what";s:15:"/uploadify\.php";s:3:"why";s:37:"WordPress: Access to Uploadify script";s:5:"level";i:3;s:2:"on";i:1;}}
EOT;

}

/* ================================================================== */

// EOF //
?>