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
 | REVISION: 2014-05-29 01:48:51                                       |
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
if (strpos($_SERVER['SCRIPT_NAME'], '/plugins/ninjafirewall/') !== FALSE) { die('Forbidden !'); }
if (defined('NFW_STATUS')) { return; }

// Used for benchmarks purpose :
$nfw_['fw_starttime'] = microtime(true);

// Optional NinjaFirewall configuration file
// ( see http://nintechnet.com/nfwp/1.1.3/ ) :
if ( @file_exists( $nfw_['file'] = dirname(getenv('DOCUMENT_ROOT') ) . '/.htninja') ) {
	$nfw_['res'] = @include($nfw_['file']);
	// Allow and stop filtering :
	if ( $nfw_['res'] == 'ALLOW' ) {
		define( 'NFW_STATUS', 20 );
		unset($nfw_);
		return;
	}
	// Reject immediately :
	if ( $nfw_['res'] == 'BLOCK' ) {
		header('HTTP/1.1 403 Forbidden');
		header('Status: 403 Forbidden');
		die('403 Forbidden');
	}
}

// Brute-force attack detection :
if ( strpos($_SERVER['SCRIPT_NAME'], 'wp-login.php' ) !== FALSE ) {
	nfw_bfd();
}

// We need to get access to the database but we cannot include/require()
// either wp-load.php or wp-config.php, because that would load the core
// part of WordPress. Remember, we are supposed to act like a real and
// stand-alone firewall, not like a lame security plugin: we must hook
// every single PHP request **before** WordPress. Therefore, we must find,
// open and parse the wp-config.php file.
if (empty ($wp_config)) {
	$wp_config = dirname( strstr(__FILE__, '/plugins/ninjafirewall/lib', true) ) . '/wp-config.php';
}
if (! file_exists($wp_config) ) {
	// set the error flag and return :
	define( 'NFW_STATUS', 1 );
	unset($nfw_);
	unset($wp_config);
	return;
}
if (! $nfw_['fh'] = fopen($wp_config, 'r') ) {
	define( 'NFW_STATUS', 2 );
	unset($nfw_);
	unset($wp_config);
	return;
}

// Fetch WP configuration:
while (! feof($nfw_['fh'])) {
	$nfw_['line'] = fgets($nfw_['fh']);
	if ( preg_match('/^\s*define\s*\(\s*\'DB_NAME\'\s*,\s*\'(.+?)\'/', $nfw_['line'], $nfw_['match']) ) {
		$nfw_['DB_NAME'] = $nfw_['match'][1];
	} elseif ( preg_match('/^\s*define\s*\(\s*\'DB_USER\'\s*,\s*\'(.+?)\'/', $nfw_['line'], $nfw_['match']) ) {
		$nfw_['DB_USER'] = $nfw_['match'][1];
	} elseif ( preg_match('/^\s*define\s*\(\s*\'DB_PASSWORD\'\s*,\s*\'(.+?)\'/', $nfw_['line'], $nfw_['match']) ) {
		$nfw_['DB_PASSWORD'] = $nfw_['match'][1];
	} elseif ( preg_match('/^\s*define\s*\(\s*\'DB_HOST\'\s*,\s*\'(.+?)\'/', $nfw_['line'], $nfw_['match']) ) {
		$nfw_['DB_HOST'] = $nfw_['match'][1];
	} elseif ( preg_match('/^\s*\$table_prefix\s*=\s*\'(.+?)\'/', $nfw_['line'], $nfw_['match']) ) {
		$nfw_['table_prefix'] = $nfw_['match'][1];
	}
}
fclose($nfw_['fh']);
unset($wp_config);
if ( (! isset($nfw_['DB_NAME'])) || (! isset($nfw_['DB_USER'])) || (! isset($nfw_['DB_PASSWORD'])) ||	(! isset($nfw_['DB_HOST'])) || (! isset($nfw_['table_prefix'])) ) {
	define( 'NFW_STATUS', 3 );
	unset($nfw_);
	return;
}

// So far, so good. Connect to the DB:
@$nfw_['mysqli'] = new mysqli($nfw_['DB_HOST'], $nfw_['DB_USER'], $nfw_['DB_PASSWORD'], $nfw_['DB_NAME']);

if ( mysqli_connect_error() ) {
	define( 'NFW_STATUS', 4 );
	unset($nfw_);
	return;
}
$nfw_['table_prefix'] = @$nfw_['mysqli']->real_escape_string($nfw_['table_prefix']);

// Fetch our user options table:
if (! $nfw_['result'] = @$nfw_['mysqli']->query('SELECT * FROM `' . $nfw_['table_prefix'] . "options` WHERE `option_name` = 'nfw_options'")) {
	define( 'NFW_STATUS', 5 );
	$nfw_['mysqli']->close();
	unset($nfw_);
	return;
}
if (! $nfw_['options'] = @$nfw_['result']->fetch_object() ) {
	define( 'NFW_STATUS', 6 );
	$nfw_['mysqli']->close();
	unset($nfw_);
	return;
}
$nfw_['result']->close();

$nfw_['nfw_options'] = unserialize($nfw_['options']->option_value);

// Are we supposed to do anything ?
if ( empty($nfw_['nfw_options']['enabled']) ) {
	$nfw_['mysqli']->close();
	define( 'NFW_STATUS', 20 );
	unset($nfw_);
	return;
}

// Force SSL for admin and logins ?
if (! empty($nfw_['nfw_options']['force_ssl']) ) {
	define('FORCE_SSL_ADMIN', true);
}
// Disable the plugin and theme editor ?
if (! empty($nfw_['nfw_options']['disallow_edit']) ) {
	define('DISALLOW_FILE_EDIT', true);
}
// Disable plugin and theme update/installation ?
if (! empty($nfw_['nfw_options']['disallow_mods']) ) {
	define('DISALLOW_FILE_MODS', true);
}

// Event notifications :
$nfw_['a_msg'] = '';
// plugins.php
if ( strpos($_SERVER['SCRIPT_NAME'], '/plugins.php' ) !== FALSE ) {
	if ( isset( $_REQUEST['action2'] )) {
		if ( (! isset( $_REQUEST['action'] )) || ( $_REQUEST['action'] == '-1') ) {
			$_REQUEST['action'] = $_REQUEST['action2'];
		}
		$_REQUEST['action2'] = '-1';
	}
	if ( isset( $_REQUEST['action'] )  ) {
		if ( $_REQUEST['action'] == 'update-selected' ) {
			if (! empty( $_POST['checked'] ) ) {
				$nfw_['a_msg'] = '1:4:' . @implode(", ", $_POST['checked']);
			}
		} elseif ( $_REQUEST['action'] == 'activate' ) {
			$nfw_['a_msg'] = '1:3:' . @$_REQUEST['plugin'];
		} elseif ( $_REQUEST['action'] == 'activate-selected' ) {
			if (! empty( $_POST['checked'] ) ) {
				$nfw_['a_msg'] = '1:3:' . @implode(", ", $_POST['checked']);
			}
		} elseif ( $_REQUEST['action'] == 'deactivate' ) {
			$nfw_['a_msg'] = '1:5:' . @$_REQUEST['plugin'];
		} elseif ( ( $_REQUEST['action'] == 'deactivate-selected' ) ){
			if (! empty( $_POST['checked'] ) ) {
				$nfw_['a_msg'] = '1:5:' . @implode(", ", $_POST['checked']);
			}
		} elseif ( ( $_REQUEST['action'] == 'delete-selected' ) &&
			( isset($_REQUEST['verify-delete'])) ) {
			if (! empty( $_POST['checked'] ) ) {
				$nfw_['a_msg'] = '1:6:' . @implode(", ", $_POST['checked']);
			}
		}
	}
// themes.php
} elseif ( strpos($_SERVER['SCRIPT_NAME'], '/themes.php' ) !== FALSE ) {
	if ( isset( $_GET['action'] )  ) {
		if ( $_GET['action'] == 'activate' ) {
			$nfw_['a_msg'] = '2:3:' . @$_GET['stylesheet'];
		} elseif ( $_GET['action'] == 'delete' ) {
			$nfw_['a_msg'] = '2:4:' . @$_GET['stylesheet'];
		}
	}
// update.php
} elseif ( strpos($_SERVER['SCRIPT_NAME'], '/update.php' ) !== FALSE ) {
	if ( isset( $_GET['action'] )  ) {
		if ( $_REQUEST['action'] == 'update-selected' ) {
			if (! empty( $_POST['checked'] ) ) {
				$nfw_['a_msg'] = '1:4:' . @implode(", ", $_POST['checked']);
			}
		} elseif ( $_GET['action'] == 'upgrade-plugin' ) {
			$nfw_['a_msg'] = '1:4:' . @$_REQUEST['plugin'];
		} elseif ( $_GET['action'] == 'activate-plugin' ) {
			$nfw_['a_msg'] = '1:3:' . @$_GET['plugins'];
		} elseif ( $_GET['action'] == 'install-plugin' ) {
			$nfw_['a_msg'] = '1:2:' . @$_REQUEST['plugin'];
		} elseif ( $_GET['action'] == 'upload-plugin' ) {
			$nfw_['a_msg'] = '1:1:' . @$_FILES['pluginzip']['name'];
		} elseif ( $_GET['action'] == 'install-theme' ) {
			$nfw_['a_msg'] = '2:2:' . @$_REQUEST['theme'];
		} elseif ( $_GET['action'] == 'upload-theme' ) {
			$nfw_['a_msg'] = '2:1:' . @$_FILES['themezip']['name'];
		}
	}
// update-core.php
} elseif ( strpos($_SERVER['SCRIPT_NAME'], '/update-core.php' ) !== FALSE ) {
	if ( isset( $_GET['action'] )  ) {
		if ( $_GET['action'] == 'do-plugin-upgrade' ) {
			if (! empty( $_POST['checked'] ) ) {
				$nfw_['a_msg'] = '1:4:' . @implode(", ", $_POST['checked']);
			}
		} elseif ( $_GET['action'] == 'do-core-upgrade' ) {
			$nfw_['a_msg'] = '3:1:' . @$_POST['version'];
		}
	}
}
if ( $nfw_['a_msg'] ) {
	// Enable alerts flag :
	define('NFW_ALERT', $nfw_['a_msg']);
}

// Do not scan/filter WordPress admin (if logged in) ?
if (! session_id() ) { session_start(); }
if (! empty($_SESSION['nfw_goodguy']) ) {
	$nfw_['mysqli']->close();
	// for testing purpose (used during the installation process) :
	if (! empty( $_POST['nfw_test'] ) ) {
		define( 'NFW_IT_WORKS', true );
	}
	define( 'NFW_STATUS', 20 );
	unset($nfw_);
	return;
}

// Hide PHP notice/error messages ?
if (! empty($nfw_['nfw_options']['php_errors']) ) {
	@error_reporting(0);
	@ini_set('display_errors', 0);
}

// Ignore localhost & private IP address spaces ?
if (! empty($nfw_['nfw_options']['allow_local_ip']) && ! filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) ) {
	$nfw_['mysqli']->close();
	unset($nfw_);
	define( 'NFW_STATUS', 20 );
	return;
}

// Scan HTTP traffic only... ?
if ( (@$nfw_['nfw_options']['scan_protocol'] == 1) && ($_SERVER['SERVER_PORT'] == 443) ) {
	$nfw_['mysqli']->close();
	unset($nfw_);
	define( 'NFW_STATUS', 20 );
	return;
}
// ...or HTTPS only ?
if ( (@$nfw_['nfw_options']['scan_protocol'] == 2) && ($_SERVER['SERVER_PORT'] != 443) ) {
	$nfw_['mysqli']->close();
	define( 'NFW_STATUS', 20 );
	unset($nfw_);
	return;
}

// File Guard :
if (! empty($nfw_['nfw_options']['fg_enable']) ) {
	// Stat() the requested script :
	if ( $nfw_['nfw_options']['fg_stat'] = stat( $_SERVER['SCRIPT_FILENAME'] ) ) {
		// Was is created/modified lately ?
		if ( time() - $nfw_['nfw_options']['fg_mtime'] * 3660 < $nfw_['nfw_options']['fg_stat']['ctime'] ) {
			// Did we check it already ?
			if (! file_exists( dirname(__DIR__) . '/log/cache/fg_' . $nfw_['nfw_options']['fg_stat']['ino'] . '.php' ) ) {
				// We need to alert the admin :
				if (! $nfw_['nfw_options']['tzstring'] = ini_get('date.timezone') ) {
					$nfw_['nfw_options']['tzstring'] = 'UTC';
				}
				date_default_timezone_set($nfw_['nfw_options']['tzstring']);
				$nfw_['nfw_options']['m_headers'] = 'From: "NinjaFirewall" <postmaster@'. $_SERVER['SERVER_NAME'] . ">\r\n\r\n";
				$nfw_['nfw_options']['m_subject'] = '[NinjaFirewall] Alert: File Guard detection';
				$nfw_['nfw_options']['m_msg'] = 	'Someone accessed a script that was modified or created less than ' .
					$nfw_['nfw_options']['fg_mtime'] . ' hour(s) ago:' . "\n\n".
					'Date           : ' . date('F j, Y @ H:i:s') . ' (UTC '. date('O') . ")\n" .
					'SERVER_NAME    : ' . $_SERVER['SERVER_NAME'] . "\n" .
					'SCRIPT_FILENAME: ' . $_SERVER['SCRIPT_FILENAME'] . "\n" .
					'REQUEST_URI    : ' . $_SERVER['REQUEST_URI'] . "\n" .
					'REMOTE_ADDR    : ' . $_SERVER['REMOTE_ADDR'] . "\n\n" .
					'NinjaFirewall (WP edition) - http://ninjafirewall.com/' . "\n" .
					'Support forum: http://wordpress.org/support/plugin/ninjafirewall' . "\n";
				mail( $nfw_['nfw_options']['alert_email'], $nfw_['nfw_options']['m_subject'], $nfw_['nfw_options']['m_msg'], $nfw_['nfw_options']['m_headers']);
				// Remember it so that we don't spam the admin each time the script is requested :
				touch(dirname(__DIR__) . '/log/cache/fg_' . $nfw_['nfw_options']['fg_stat']['ino'] . '.php');
				// Log it :
				nfw_log('Access to a script modified/created less than ' . $nfw_['nfw_options']['fg_mtime'] . ' hour(s) ago', $_SERVER['SCRIPT_FILENAME'], 2, 0);
			}
		}
	}
}

// HTTP_HOST is an IP ?
if (! empty($nfw_['nfw_options']['no_host_ip']) && @filter_var(parse_url('http://'.$_SERVER['HTTP_HOST'], PHP_URL_HOST), FILTER_VALIDATE_IP) ) {
	nfw_log('HTTP_HOST is an IP', $_SERVER['HTTP_HOST'], 1, 0);
   nfw_block();
}

// block POST without Referer header ?
if ( (! empty($nfw_['nfw_options']['referer_post']) ) && ($_SERVER['REQUEST_METHOD'] == 'POST') && (! isset($_SERVER['HTTP_REFERER'])) ) {
	nfw_log('POST method without Referer header', $_SERVER['REQUEST_METHOD'], 1, 0);
   nfw_block();
}

// Block access to WordPress XML-RPC API ?
if ( (! empty($nfw_['nfw_options']['no_xmlrpc'])) && (strpos($_SERVER['SCRIPT_NAME'], $nfw_['nfw_options']['no_xmlrpc']) !== FALSE) ) {
	nfw_log('Access to WordPress XML-RPC API', $_SERVER['SCRIPT_NAME'], 2, 0);
   nfw_block();
}

// POST request in the themes folder ?
if ( (! empty($nfw_['nfw_options']['no_post_themes'])) && ($_SERVER['REQUEST_METHOD'] == 'POST') && (strpos($_SERVER['SCRIPT_NAME'], $nfw_['nfw_options']['no_post_themes']) !== FALSE) ) {
	nfw_log('POST request in the themes folder', $_SERVER['SCRIPT_NAME'], 2, 0);
   nfw_block();
}

// Block direct access to any PHP file located in wp_dir :
if ( (! empty($nfw_['nfw_options']['wp_dir'])) && (preg_match( '`' . $nfw_['nfw_options']['wp_dir'] . '`', $_SERVER['SCRIPT_NAME'])) ) {
	nfw_log('Forbidden direct access to PHP script', $_SERVER['SCRIPT_NAME'], 2, 0);
   nfw_block();
}

// Look for upload:
nfw_check_upload();

// Fetch our rules table :
if (! $nfw_['result'] = @$nfw_['mysqli']->query('SELECT * FROM `' . $nfw_['table_prefix'] . "options` WHERE `option_name` = 'nfw_rules'")) {
	define( 'NFW_STATUS', 7 );
	$nfw_['mysqli']->close();
	unset($nfw_);
	return;
}

if (! $nfw_['rules'] = @$nfw_['result']->fetch_object() ) {
	define( 'NFW_STATUS', 8 );
	$nfw_['mysqli']->close();
	unset($nfw_);
	return;
}
$nfw_['result']->close();

// Parse all requests and server variables :
nfw_check_request( unserialize($nfw_['rules']->option_value), $nfw_['nfw_options'] );

// Sanitise requests/variables if needed :
if (! empty($nfw_['nfw_options']['get_sanitise']) && ! empty($_GET) ){
	$_GET = nfw_sanitise( $_GET, 1, 'GET');
}
if (! empty($nfw_['nfw_options']['post_sanitise']) && ! empty($_POST) ){
	$_POST = nfw_sanitise( $_POST, 1, 'POST');
}
if (! empty($nfw_['nfw_options']['request_sanitise']) && ! empty($_REQUEST) ){
	$_REQUEST = nfw_sanitise( $_REQUEST, 1, 'REQUEST');
}
if (! empty($nfw_['nfw_options']['cookies_sanitise']) && ! empty($_COOKIE) ) {
	$_COOKIE = nfw_sanitise( $_COOKIE, 1, 'COOKIE');
}
if (! empty($nfw_['nfw_options']['ua_sanitise']) && ! empty($_SERVER['HTTP_USER_AGENT']) ) {
	$_SERVER['HTTP_USER_AGENT'] = nfw_sanitise( $_SERVER['HTTP_USER_AGENT'], 1, 'HTTP_USER_AGENT');
}
if (! empty($nfw_['nfw_options']['referer_sanitise']) && ! empty($_SERVER['HTTP_REFERER']) ) {
	$_SERVER['HTTP_REFERER'] = nfw_sanitise( $_SERVER['HTTP_REFERER'], 1, 'HTTP_REFERER');
}
if (! empty($nfw_['nfw_options']['php_path_i']) && ! empty($_SERVER['PATH_INFO']) ) {
	$_SERVER['PATH_INFO'] = nfw_sanitise( $_SERVER['PATH_INFO'], 2, 'PATH_INFO');
}
if (! empty($nfw_['nfw_options']['php_path_t']) && ! empty($_SERVER['PATH_TRANSLATED']) ) {
	$_SERVER['PATH_TRANSLATED'] = nfw_sanitise( $_SERVER['PATH_TRANSLATED'], 2, 'PATH_TRANSLATED');
}
if (! empty($nfw_['nfw_options']['php_self']) && ! empty($_SERVER['PHP_SELF']) ) {
	$_SERVER['PHP_SELF'] = nfw_sanitise( $_SERVER['PHP_SELF'], 2, 'PHP_SELF');
}

@$nfw_['mysqli']->close();
unset($nfw_);
define( 'NFW_STATUS', 20 );
// That's all !
return;

/* ================================================================== */

function nfw_check_upload() {

	if ( defined('NFW_STATUS') ) { return; }

	global $nfw_;

	// Fetch uploaded files, if any :
	$f_uploaded = nfw_fetch_uploads();

	// Uploads are disallowed :
	if ( empty($nfw_['nfw_options']['uploads']) ) {
		$tmp = '';
		foreach ($f_uploaded as $key => $value) {
			// Empty field ?
			if (! $f_uploaded[$key]['name']) { continue; }
         $tmp .= $f_uploaded[$key]['name'] . ', ' . number_format($f_uploaded[$key]['size']) . ' bytes ';
      }
      if ( $tmp ) {
			// Log and block :
			nfw_log('File upload attempt', rtrim($tmp, ' '), 3, 0);
			nfw_block();
		}
	// Uploads are allowed :
	} else {
		foreach ($f_uploaded as $key => $value) {
			if (! $f_uploaded[$key]['name']) { continue; }
			// Sanitise filename ?
			if (! empty($nfw_['nfw_options']['sanitise_fn']) ) {
				$tmp = '';
				$f_uploaded[$key]['name'] = preg_replace('/[^\w\.\-]/i', 'X', $f_uploaded[$key]['name'], -1, $count);
				if ($count) {
					$tmp = ' (sanitising '. $count . ' char. from filename)';
				}
				if ( $tmp ) {
					list ($kn, $is_arr, $kv) = explode('::', $f_uploaded[$key]['where']);
					if ( $is_arr ) {
						$_FILES[$kn]['name'][$kv] = $f_uploaded[$key]['name'];
					} else {
						$_FILES[$kn]['name'] = $f_uploaded[$key]['name'];
					}
				}
			}
			// Log and let it go :
			nfw_log('Uploading file' . $tmp , $f_uploaded[$key]['name'] . ', ' . number_format($f_uploaded[$key]['size']) . ' bytes', 5, 0);
		}
	}
}

/* ================================================================== */

function nfw_fetch_uploads() {

	$f_uploaded = array();
	$count = 0;
	foreach ($_FILES as $nm => $file) {
		if ( is_array($file['name']) ) {
			foreach($file['name'] as $key => $value) {
				$f_uploaded[$count]['name'] = $file['name'][$key];
				$f_uploaded[$count]['size'] = $file['size'][$key];
				$f_uploaded[$count]['tmp_name'] = $file['tmp_name'][$key];
				$f_uploaded[$count]['where'] = $nm . '::1::' . $key;
				$count++;
			}
		} else {
			$f_uploaded[$count]['name'] = $file['name'];
			$f_uploaded[$count]['size'] = $file['size'];
			$f_uploaded[$count]['tmp_name'] = $file['tmp_name'];
			$f_uploaded[$count]['where'] = $nm . '::0::0' ;
			$count++;
		}
	}
	return $f_uploaded;
}

/* ================================================================== */

function nfw_check_request( $nfw_rules, $nfw_options ) {

	if ( defined('NFW_STATUS') ) { return; }

	$b64_post = array();

	foreach ($nfw_rules as $rules_id => $rules_values) {
		// Ignored disabled rules :
		if ( empty( $rules_values['on']) ) { continue; }
		$wherelist = explode('|', $rules_values['where']);
		foreach ($wherelist as $where) {

			// Global GET/POST/COOKIE/REQUEST requests :
			if ( (($where == 'POST') && (! empty($nfw_options['post_scan']))) || (($where == 'GET') && (! empty($nfw_options['get_scan']))) || (($where == 'COOKIE') && (! empty($nfw_options['cookies_scan']))) || ($where == 'REQUEST') ) {
				foreach ($GLOBALS['_' . $where] as $reqkey => $reqvalue) {
					// Look for an array() :
					if ( is_array($reqvalue) ) {
						$res = nfw_flatten( "\n", $reqvalue );
						$reqvalue = $res;
						$rules_values['what'] = '(?m:'. $rules_values['what'] .')';
					} else {
						if ( (! empty($nfw_options['post_b64'])) && ($where == 'POST') && ($reqvalue) && (! isset( $b64_post[$reqkey])) ) {
							$b64_post[$reqkey] = 1;
							nfw_check_b64($reqkey, $reqvalue);
						}
					}
					if (! $reqvalue) { continue; }
					if ( preg_match('`'. $rules_values['what'] .'`', $reqvalue) ) {
						nfw_log($rules_values['why'], $where .':' . $reqkey . ' = ' . $reqvalue, $rules_values['level'], $rules_id);
						nfw_block();
               }
				}
				continue;
			}

			// Specific POST:xx, GET:xx, COOKIE:xxx requests :
			$sub_value = explode(':', $where);
			if ( (($sub_value[0] == 'POST') && ( empty($nfw_options['post_scan']))) || (($sub_value[0] == 'GET' ) && ( empty($nfw_options['get_scan']))) || (($sub_value[0] == 'COOKIE' ) && ( empty($nfw_options['cookies_scan']))) ) { continue; }
			if ( (! empty($sub_value[1]) ) && ( @isset($GLOBALS['_' . $sub_value[0]] [$sub_value[1]]) ) ) {
				if ( is_array($GLOBALS['_' . $sub_value[0]] [$sub_value[1]]) ) {
					$res = nfw_flatten( "\n", $GLOBALS['_' . $sub_value[0]] [$sub_value[1]] );
					$GLOBALS['_' . $sub_value[0]] [$sub_value[1]] = $res;
					$rules_values['what'] = '(?m:'. $rules_values['what'] .')';
				}
				if (! $GLOBALS['_' . $sub_value[0]][$sub_value[1]] ) { continue; }
				if ( preg_match('`'. $rules_values['what'] .'`', $GLOBALS['_' . $sub_value[0]][$sub_value[1]]) ) {
					nfw_log($rules_values['why'], $sub_value[0]. ':' .$sub_value[1]. ' = ' .$GLOBALS['_' . $sub_value[0]][$sub_value[1]], $rules_values['level'], $rules_id);
					nfw_block();
				}
				continue;
			}

			// Other requests & server variables (HTTP_REFERER, etc) :
			if ( isset($_SERVER[$where]) ) {
				if ( ( ($where == 'HTTP_USER_AGENT') && (empty($nfw_options['ua_scan'])) ) || ( ($where == 'HTTP_REFERER') && (empty($nfw_options['referer_scan'])) ) ) { continue; }
				if ( preg_match('`'. $rules_values['what'] .'`', $_SERVER[$where]) ) {
					nfw_log($rules_values['why'], $where. ' = ' .$_SERVER[$where], $rules_values['level'], $rules_id);
					nfw_block();
            }
			}
		}
	}
}

/* ================================================================== */

function nfw_flatten( $glue, $pieces ) {

	if ( defined('NFW_STATUS') ) { return; }

   foreach ($pieces as $r_pieces) {
      if ( is_array($r_pieces)) {
         $ret[] = nfw_flatten($glue, $r_pieces);
      } else {
         $ret[] = $r_pieces;
      }
   }
   return implode($glue, $ret);
}

/* ================================================================== */

function nfw_check_b64( $reqkey, $string ) {

	if ( defined('NFW_STATUS') ) { return; }

	// clean-up the string before testing it :
	$string = preg_replace( '`[^A-Za-z0-9+/=]`', '', $string);
	if ( (! $string) || (strlen($string) % 4 != 0) ) { return; }

	if ( base64_encode( $decoded = base64_decode($string) ) === $string ) {
		if ( preg_match( '`\b(?:\$?_(COOKIE|ENV|FILES|(?:GE|POS|REQUES)T|SE(RVER|SSION))|HTTP_(?:(?:POST|GET)_VARS|RAW_POST_DATA)|GLOBALS)\s*[=\[)]|\b(?i:array_map|assert|base64_(?:de|en)code|chmod|curl_exec|(?:ex|im)plode|error_reporting|eval|file(?:_get_contents)?|f(?:open|write|close)|fsockopen|function_exists|gzinflate|md5|move_uploaded_file|ob_start|passthru|preg_replace|phpinfo|stripslashes|strrev|(?:shell_)?exec|system|unlink)\s*\(|\becho\s*[\'"]|<\s*(?i:applet|div|embed|i?frame(?:set)?|img|meta|marquee|object|script|textarea)\b|\b(?i:(?:ht|f)tps?|php)://|\W\$\{\s*[\'"]\w+[\'"]|<\?(?i:php)`', $decoded) ) {
			nfw_log('base64-encoded injection', 'POST:' . $reqkey . ' = ' . $string, '3', 0);
			nfw_block();
		}
	}
}

/* ================================================================== */

function nfw_sanitise( $str, $how, $msg ) {

	if ( defined('NFW_STATUS') ) { return; }

	global $nfw_;

	if (! isset($str) ) {
		return null;

	// String :
	} else if (is_string($str) ) {
		if (get_magic_quotes_gpc() ) { $str = stripslashes($str); }
		// We sanitise variables **value** either with :
		// -mysql_real_escape_string to escape [\x00], [\n], [\r], [\],
		//	 ['], ["] and [\x1a]
		//	-str_replace to escape backtick [`]
		//	Applies to $_GET, $_POST, $_COOKIE, $_SERVER['HTTP_USER_AGENT']
		//	and $_SERVER['HTTP_REFERER']
		//
		// Or:
		//
		// -str_replace to escape [<], [>], ["], ['], [`] and , [\]
		//	-str_replace to replace [\n], [\r], [\x1a] and [\x00] with [X]
		//	Applies to $_SERVER['PATH_INFO'], $_SERVER['PATH_TRANSLATED']
		//	and $_SERVER['PHP_SELF']
		if ($how == 1) {
			$str2 = $nfw_['mysqli']->real_escape_string($str);
			$str2 = str_replace('`', '\`', $str2);
		} else {
			$str2 = str_replace(	array('\\', "'", '"', "\x0d", "\x0a", "\x00", "\x1a", '`', '<', '>'),
				array('\\\\', "\\'", '\\"', 'X', 'X', 'X', 'X', '\\`', '\\<', '\\>'),	$str);
		}
		if ($str2 != $str) {
			nfw_log('Sanitising user input', $msg . ': ' . $str, 6, 0);
		}
		return $str2;

	// Array :
	} else if (is_array($str) ) {
		foreach($str as $key => $value) {
			if (get_magic_quotes_gpc() ) {$key = stripslashes($key);}
			// We sanitise variables **name** using :
			// -str_replace to escape [\], ['] and ["]
			// -str_replace to replace [\n], [\r], [\x1a] and [\x00] with [X]
			//	-str_replace to replace [`], [<] and [>] with their HTML entities (&#96; &lt; &gt;)
			$key2 = str_replace(	array('\\', "'", '"', "\x0d", "\x0a", "\x00", "\x1a", '`', '<', '>'),
				array('\\\\', "\\'", '\\"', 'X', 'X', 'X', 'X', '&#96;', '&lt;', '&gt;'),	$key, $occ);
			if ($occ) {
				unset($str[$key]);
				nfw_log('Sanitising user input', $msg . ': ' . $key, 6, 0);
			}
			// Sanitise the value :
			$str[$key2] = nfw_sanitise($value, $how, $msg);
		}
		return $str;
	}
}

/* ================================================================== */

function nfw_block() {

	if ( defined('NFW_STATUS') ) { return; }

	global $nfw_;

	// We don't block anyone if we are running in debugging mode :
	if (! empty($nfw_['nfw_options']['debug']) ) {
		return;
	}

	@$nfw_['mysqli']->close();

	$http_codes = array(
      400 => '400 Bad Request', 403 => '403 Forbidden',
      404 => '404 Not Found', 406 => '406 Not Acceptable',
      500 => '500 Internal Server Error', 503 => '503 Service Unavailable',
   );

	// Prepare the page to display to the blocked user :
	if (empty($nfw_['num_incident']) ) { $nfw_['num_incident'] = '000000'; }
	$tmp = str_replace( '%%NUM_INCIDENT%%', $nfw_['num_incident'],  $nfw_['nfw_options']['blocked_msg'] );
	$tmp = @str_replace( '%%NINJA_LOGO%%', '<img title="NinjaFirewall" src="' . $nfw_['nfw_options']['logo'] . '" width="75" height="75">', $tmp );
	$tmp = str_replace( '%%REM_ADDRESS%%', $_SERVER['REMOTE_ADDR'], $tmp );

	if (! headers_sent() ) {
		header('HTTP/1.0 ' . $http_codes[$nfw_['nfw_options']['ret_code']] );
		header('Status: ' .  $http_codes[$nfw_['nfw_options']['ret_code']] );
	}

	echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">' . "\n" .
		'<html><head><title>NinjaFirewall: ' . $http_codes[$nfw_['nfw_options']['ret_code']] .
		'</title><style>body{font-family:sans-serif;font-size:13px;color:#000000;}</style></head><body bgcolor="white">' . $tmp . '</body></html>';
	exit;
}

/* ================================================================== */

function nfw_log($loginfo, $logdata, $loglevel, $ruleid) {

	if ( defined('NFW_STATUS') ) { return; }

	global $nfw_;

	// Info/sanitise ? Don't block and do not issue any incident number :
	if ( $loglevel == 6) {
		$nfw_['num_incident'] = '0000000';
		$http_ret_code = '200 OK';
	} else {
		// Debugging ? Don't block and do not issue any incident number
		// but set loglevel to 7 (will display 'DEBUG_ON' in log) :
		if (! empty($nfw_['nfw_options']['debug']) ) {
			$nfw_['num_incident'] = '0000000';
			$loglevel = 7;
			$http_ret_code = '200 OK';
		// Create a random incident number :
		} else {
			$nfw_['num_incident'] = mt_rand(1000000, 9000000);
			$http_ret_code = $nfw_['nfw_options']['ret_code'];
		}
	}

	// Prepare the line to log :
   if (strlen($logdata) > 100) { $logdata = substr($logdata, 0, 100) . '...'; }
	$res = '';
	$string = str_split($logdata);
	foreach ( $string as $char ) {
		// Allow only ASCII printable characters :
		if ( ( ord($char) < 32 ) || ( ord($char) > 126 ) ) {
			$res .= '%' . bin2hex($char);
		} else {
			$res .= $char;
		}
	}

	// Set the date timezone (used for log name only) :
	if (! $tzstring = ini_get('date.timezone') ) {
		$tzstring = 'UTC';
	}
	date_default_timezone_set($tzstring);
	$cur_month = date('Y-m');

	$log_dir = substr(__FILE__, 0, -16) . 'log/';
	$stat_file = $log_dir. 'stats_' . $cur_month . '.php';
	$log_file = $log_dir. 'firewall_' . $cur_month . '.php';

	// Update stats :
	if ( file_exists( $stat_file ) ) {
		$nfw_stat = file_get_contents( $stat_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
	} else {
		$nfw_stat = '0:0:0:0:0:0:0:0:0:0';
	}
	$nfw_stat_arr = explode(':', $nfw_stat . ':');
	$nfw_stat_arr[$loglevel]++;

	@file_put_contents( $stat_file, $nfw_stat_arr[0] . ':' . $nfw_stat_arr[1] . ':' .
		$nfw_stat_arr[2] . ':' . $nfw_stat_arr[3] . ':' . $nfw_stat_arr[4] . ':' .
		$nfw_stat_arr[5] . ':' . $nfw_stat_arr[6] . ':' . $nfw_stat_arr[7] . ':' .
		$nfw_stat_arr[8] . ':' . $nfw_stat_arr[9] );

	if (! $fh = fopen($log_file, 'a') ) {
		return;
	}

   fwrite( $fh,
      '[' . time() . '] ' . '[' . round( (microtime(true) - $nfw_['fw_starttime']), 5) . '] ' .
      '[' . $_SERVER['SERVER_NAME'] . '] ' . '[#' . $nfw_['num_incident'] . '] ' .
      '[' . $ruleid . '] ' .
      '[' . $loglevel . '] ' . '[' . $_SERVER['REMOTE_ADDR'] . '] ' .
      '[' . $http_ret_code . '] ' . '[' . $_SERVER['REQUEST_METHOD'] . '] ' .
      '[' . $_SERVER['SCRIPT_NAME'] . '] ' . '[' . $loginfo . '] ' .
      '[' . $res . ']' . "\n"
   );
   fclose($fh);
}

/* ================================================================== */

function nfw_bfd() {

	if ( defined('NFW_STATUS') ) { return; }

	$bf_conf_dir = substr( __FILE__, 0, -16) . 'log';
	// Is brute-force protection enabled ?
	if (! file_exists($bf_conf_dir . '/nfwbfd.php') ) {
		return;
	}

	global $nfw_;

	$now = time();
	// Get config :
	require($bf_conf_dir . '/nfwbfd.php');

	// Shall we always force HTTP authentication ?
	if ( $bf_enable == 2 ) {
		nfw_check_auth($auth_name, $auth_pass, $auth_msg);
		return;
	}

	// Has protection already been triggered ?
	if ( file_exists($bf_conf_dir . '/nfwblocked' . $_SERVER['SERVER_NAME'] . $bf_rand) ) {
		// Ensure the banning period is not over :
		$fstat = stat( $bf_conf_dir . '/nfwblocked' . $_SERVER['SERVER_NAME'] . $bf_rand );
		if ( ($now - $fstat['mtime']) < $bf_bantime * 60 ) {
			// User authentication required :
			nfw_check_auth($auth_name, $auth_pass, $auth_msg);
			return;
		} else {
			// Reset counter :
			unlink($bf_conf_dir . '/nfwblocked' . $_SERVER['SERVER_NAME'] . $bf_rand);
		}
	}

	// Are we supposed to handle that HTTP request (GET or POST or both) ?
	if ( strpos($bf_request, $_SERVER['REQUEST_METHOD']) === false ) {
		return;
	}

	// Read our log, if any :
	if ( file_exists($bf_conf_dir . '/nfwlog' . $_SERVER['SERVER_NAME'] . $bf_rand ) ) {
		$tmp_log = file( $bf_conf_dir . '/nfwlog' . $_SERVER['SERVER_NAME'] . $bf_rand, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		if ( count( $tmp_log) >= $bf_attempt ) {
			if ( ($tmp_log[count($tmp_log) - 1] - $tmp_log[count($tmp_log) - $bf_attempt]) <= $bf_maxtime ) {
				// Threshold has been reached, lock down access to the page :
				$bfdh = fopen( $bf_conf_dir . '/nfwblocked' . $_SERVER['SERVER_NAME'] . $bf_rand, 'w');
				fclose( $bfdh );
				// Clear the log :
				unlink( $bf_conf_dir . '/nfwlog' . $_SERVER['SERVER_NAME'] . $bf_rand );
				// Setup HTTP ret code here, because we do not have access
				// to the DB yet :
				$nfw_['nfw_options']['ret_code'] = '401';
				nfw_log('Brute-force attack detected', 'enabling HTTP authentication for ' . $bf_bantime . 'mn', 3, 0);
				// Force HTTP authentication :
				nfw_check_auth($auth_name, $auth_pass, $auth_msg);
				return;

			}
		}
		// If the logfile is too old, flush it :
		$fstat = stat( $bf_conf_dir . '/nfwlog' . $_SERVER['SERVER_NAME'] . $bf_rand );
		if ( ($now - $fstat['mtime']) > $bf_bantime * 60 ) {
			unlink( $bf_conf_dir . '/nfwlog' . $_SERVER['SERVER_NAME'] . $bf_rand );
		}
	}

	// Let it go, but record the request :
	file_put_contents($bf_conf_dir . '/nfwlog' . $_SERVER['SERVER_NAME'] . $bf_rand, $now . "\n", FILE_APPEND );

}
/* ================================================================== */

function nfw_check_auth($auth_name, $auth_pass, $auth_msg) {

	if ( defined('NFW_STATUS') ) { return; }

	if ( (! empty($_SERVER['PHP_AUTH_USER'])) && (! empty($_SERVER['PHP_AUTH_PW'])) ) {
		// Allow authenticated users only :
		if ( ($_SERVER['PHP_AUTH_USER'] == $auth_name) && (sha1($_SERVER['PHP_AUTH_PW']) == $auth_pass) ) {
			return;
		}
	}
	header('WWW-Authenticate: Basic realm="' . $auth_msg . '"');
	header('HTTP/1.0 401 Unauthorized');
	echo $auth_msg;
	exit;
}

/* ================================================================== */
// EOF
