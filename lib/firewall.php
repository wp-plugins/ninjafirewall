<?php
/*
 +---------------------------------------------------------------------+
 | NinjaFirewall (WordPress edition)                                   |
 |                                                                     |
 | (c)2012-2013 NinTechNet                                             |
 | <wordpress@nintechnet.com>                                          |
 +---------------------------------------------------------------------+
 | http://nintechnet.com/                                              |
 +---------------------------------------------------------------------+
 | REVISION: 2013-09-28 23:39:13                                       |
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

if ( $_SERVER['SCRIPT_FILENAME'] == __FILE__ ) { die('Forbidden'); }

// Used for benchmarks purpose :
$nfw_starttime = microtime(true);

// Brute-force attacks detection :
if ( strpos($_SERVER['SCRIPT_NAME'], 'wp-login.php' ) !== FALSE ) {
	nfw_bfd();
}

// Set to '0' if you don't want NinjaFirewall to output error
// and warning messages (not recommended, though) :
$nfw_warn = 1;

// We need to get access to the database but we cannot include/require()
// either wp-load.php or wp-config.php, because that would load the core
// part of WordPress. Remember, we are supposed to act like a real and
// stand-alone firewall, not like a lame security plugin: we must hook
// every single PHP request **before** WordPress.
// Therefore, we must find, open and parse the wp-config.php file:
$wp_config = dirname( strstr(__FILE__, '/plugins/ninjafirewall/lib', true) ) . '/wp-config.php';
if (! file_exists($wp_config) ) {
	// Warn and return:
	if ( $nfw_warn ) {
		echo '<span style="background:#ffffff;border:1px dotted red;padding:2px">'.
		'<strong><font color=red>ERROR:</font> NinjaFirewall cannot find WordPress '.
		'configuration file.</strong></span>';
	}
	return;
}
if (! $fh = fopen($wp_config, 'r') ) {
	if ( $nfw_warn ) {
		echo '<span style="background:#ffffff;border:1px dotted red;padding:2px">'.
		'<strong><font color=red>ERROR:</font> NinjaFirewall cannot read WordPress '.
		'configuration file.</strong></span>';
	}
	return;
}
// Fetch WP configuration:
while (! feof($fh)) {
	$line = fgets($fh);
	if ( preg_match('/^\s*define\s*\(\s*\'DB_NAME\'\s*,\s*\'(.+?)\'/', $line, $match) ) {
		$DB_NAME = $match[1];
	} elseif ( preg_match('/^\s*define\s*\(\s*\'DB_USER\'\s*,\s*\'(.+?)\'/', $line, $match) ) {
		$DB_USER = $match[1];
	} elseif ( preg_match('/^\s*define\s*\(\s*\'DB_PASSWORD\'\s*,\s*\'(.+?)\'/', $line, $match) ) {
		$DB_PASSWORD = $match[1];
	} elseif ( preg_match('/^\s*define\s*\(\s*\'DB_HOST\'\s*,\s*\'(.+?)\'/', $line, $match) ) {
		$DB_HOST = $match[1];
	} elseif ( preg_match('/^\s*\$table_prefix\s*=\s*\'(.+?)\'/', $line, $match) ) {
		$table_prefix = $match[1];
	}
}
fclose($fh);

if ( (! isset($DB_NAME)) || (! isset($DB_USER)) || (! isset($DB_PASSWORD)) ||	(! isset($DB_HOST)) || (! isset($table_prefix)) ) {
	if ( $nfw_warn ) {
		echo '<span style="background:#ffffff;border:1px dotted red;padding:2px">'.
		'<strong><font color=red>ERROR:</font> NinjaFirewall cannot retrieve WordPress '.
		'database credentials.</strong></span>';
	}
	return;
}

// So far, so good. Connect to the DB:
@$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME);

// We don't want any PHP script (incl. potential backdoor/shell scripts
// left on the server) to inherit the DB credentials from us, do we?
$DB_HOST = $DB_USER = $DB_PASSWORD = $DB_NAME = '';

if (mysqli_connect_error() ) {
	if ( $nfw_warn ) {
		echo '<span style="background:#ffffff;border:1px dotted red;padding:2px">'.
		'<strong><font color=red>ERROR:</font> NinjaFirewall cannot connect to WordPress '.
		'database.</strong></span>';
	}
	$table_prefix = '';
	return;
}
$table_prefix = @$mysqli->real_escape_string($table_prefix);

// Fetch our user options table:
if (! $result = @$mysqli->query('SELECT * FROM `' . $table_prefix . 'options` WHERE `option_name` = \'nfw_options\'')) {
	if ( $nfw_warn ) {
		echo '<span style="background:#ffffff;border:1px dotted red;padding:2px">'.
		'<strong><font color=red>ERROR:</font> NinjaFirewall cannot retrieve user '.
		'options #1.</strong></span>';
	}
	$table_prefix = '';
	$mysqli->close();
	return;
}
if (! $options = @$result->fetch_object() ) {
	if ( $nfw_warn ) {
		echo '<span style="background:#ffffff;border:1px dotted red;padding:2px">'.
		'<strong><font color=red>ERROR:</font> NinjaFirewall cannot retrieve user '.
		'options #2.</strong></span>';
	}
	$table_prefix = '';
	$mysqli->close();
	return;
}
$result->close();

$nfw_options = unserialize($options->option_value);

// Are we supposed to do anything ?
if ( empty($nfw_options['enabled']) ) {
	$table_prefix = '';
	$mysqli->close();
	return;
}

// Force SSL for admin and logins ?
if (! empty($nfw_options['force_ssl']) ) {
	define('FORCE_SSL_ADMIN', true);
}
// Disable the plugin and theme editor ?
if (! empty($nfw_options['disallow_edit']) ) {
	define('DISALLOW_FILE_EDIT', true);
}
// Disable plugin and theme update/installation ?
if (! empty($nfw_options['disallow_mods']) ) {
	define('DISALLOW_FILE_MODS', true);
}

// E-mail alerts
$a_msg = '';
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
				$a_msg = '1:4:' . @implode(", ", $_POST['checked']);
			}
		} elseif ( $_REQUEST['action'] == 'activate' ) {
			$a_msg = '1:3:' . @$_REQUEST['plugin'];
		} elseif ( $_REQUEST['action'] == 'activate-selected' ) {
			if (! empty( $_POST['checked'] ) ) {
				$a_msg = '1:3:' . @implode(", ", $_POST['checked']);
			}
		} elseif ( $_REQUEST['action'] == 'deactivate' ) {
			$a_msg = '1:5:' . @$_REQUEST['plugin'];
		} elseif ( ( $_REQUEST['action'] == 'deactivate-selected' ) ){
			if (! empty( $_POST['checked'] ) ) {
				$a_msg = '1:5:' . @implode(", ", $_POST['checked']);
			}
		} elseif ( ( $_REQUEST['action'] == 'delete-selected' ) &&
			( isset($_REQUEST['verify-delete'])) ) {
			if (! empty( $_POST['checked'] ) ) {
				$a_msg = '1:6:' . @implode(", ", $_POST['checked']);
			}
		}
	}
// themes.php
} elseif ( strpos($_SERVER['SCRIPT_NAME'], '/themes.php' ) !== FALSE ) {
	if ( isset( $_GET['action'] )  ) {
		if ( $_GET['action'] == 'activate' ) {
			$a_msg = '2:3:' . @$_GET['stylesheet'];
		} elseif ( $_GET['action'] == 'delete' ) {
			$a_msg = '2:4:' . @$_GET['stylesheet'];
		}
	}
// update.php
} elseif ( strpos($_SERVER['SCRIPT_NAME'], '/update.php' ) !== FALSE ) {
	if ( isset( $_GET['action'] )  ) {
		if ( $_REQUEST['action'] == 'update-selected' ) {
			if (! empty( $_POST['checked'] ) ) {
				$a_msg = '1:4:' . @implode(", ", $_POST['checked']);
			}
		} elseif ( $_GET['action'] == 'upgrade-plugin' ) {
			$a_msg = '1:4:' . @$_REQUEST['plugin'];
		} elseif ( $_GET['action'] == 'activate-plugin' ) {
			$a_msg = '1:3:' . @$_GET['plugins'];
		} elseif ( $_GET['action'] == 'install-plugin' ) {
			$a_msg = '1:2:' . @$_REQUEST['plugin'];
		} elseif ( $_GET['action'] == 'upload-plugin' ) {
			$a_msg = '1:1:' . @$_FILES['pluginzip']['name'];
		} elseif ( $_GET['action'] == 'install-theme' ) {
			$a_msg = '2:2:' . @$_REQUEST['theme'];
		} elseif ( $_GET['action'] == 'upload-theme' ) {
			$a_msg = '2:1:' . @$_FILES['themezip']['name'];
		}
	}
// update-core.php
} elseif ( strpos($_SERVER['SCRIPT_NAME'], '/update-core.php' ) !== FALSE ) {
	if ( isset( $_GET['action'] )  ) {
		if ( $_GET['action'] == 'do-plugin-upgrade' ) {
			if (! empty( $_POST['checked'] ) ) {
				$a_msg = '1:4:' . @implode(", ", $_POST['checked']);
			}
		} elseif ( $_GET['action'] == 'do-core-upgrade' ) {
			$a_msg = '3:1:' . @$_POST['version'];
		}
	}
}
if ( $a_msg ) {
	// Enable alerts flag :
	define('NFW_ALERT', $a_msg);
}

// Do not scan/filter WordPress admin (if logged in) ?
if (! session_id() ) { session_start(); }
if ( (! empty($nfw_options['wl_admin']) ) && (! empty($_SESSION['nfw_goodguy']) ) ) {
	$table_prefix = '';
	$mysqli->close();
	// for testing purpose (used during the installation process) :
	if (! empty( $_POST['nfw_test'] ) ) {
		define( 'NFW_IT_WORKS', true );
	}
	return;
}

// Hide PHP notice/error messages ?
if (! empty($nfw_options['php_errors']) ) {
	@error_reporting(0);
	@ini_set('display_errors', 0);
}

// Ignore localhost & private IP address spaces ?
if ( (! empty($nfw_options['allow_local_ip']) ) && (preg_match("/^(?:::ffff:)?(?:10|172\.(?:1[6-9]|2[0-9]|3[0-1])|192\.168)\./", $_SERVER['REMOTE_ADDR'])) ) {
	$table_prefix = '';
	$mysqli->close();
	return;
}

// HTTP_HOST is an IP ?
if ( (! empty($nfw_options['no_host_ip'])) && (preg_match('/^[\d.:]+$/', $_SERVER['HTTP_HOST'])) ) {
	nfw_log('HTTP_HOST is an IP', $_SERVER['HTTP_HOST'], 1, 0);
   nfw_block();
}

// Scan HTTP traffic only... ?
if ( (@$nfw_options['scan_protocol'] == 1) && ($_SERVER['SERVER_PORT'] == 443) ) {
	$table_prefix = '';
	$mysqli->close();
	return;
}
// ...or HTTPS only ?
if ( (@$nfw_options['scan_protocol'] == 2) && ($_SERVER['SERVER_PORT'] != 443) ) {
	$table_prefix = '';
	$mysqli->close();
	return;
}

// block POST without Referer header ?
if ( (! empty($nfw_options['referer_post']) ) && ($_SERVER['REQUEST_METHOD'] == 'POST') && (! isset($_SERVER['HTTP_REFERER'])) ) {
	nfw_log('POST method without Referer header', $_SERVER['REQUEST_METHOD'], 1, 0);
   nfw_block();
}

// POST request in the themes folder ?
if ( (! empty($nfw_options['no_post_themes'])) && ($_SERVER['REQUEST_METHOD'] == 'POST') && (strpos($_SERVER['SCRIPT_NAME'], $nfw_options['no_post_themes']) !== FALSE) ) {
	nfw_log('POST request in the themes folder', $_SERVER['SCRIPT_NAME'], 2, 0);
   nfw_block();
}

// Block direct access to any PHP file located in wp_dir :
if ( (! empty($nfw_options['wp_dir'])) && (preg_match( '`' . $nfw_options['wp_dir'] . '`', $_SERVER['SCRIPT_NAME'])) ) {
	nfw_log('Forbidden direct access to PHP script', $_SERVER['SCRIPT_NAME'], 2, 0);
   nfw_block();
}

// Look for upload:
nfw_check_upload();

// Fetch our rules table :
if (! $result = @$mysqli->query('SELECT * FROM `' . $table_prefix . 'options` WHERE `option_name` = \'nfw_rules\'')) {
	if ( $nfw_warn ) {
		echo '<span style="background:#ffffff;border:1px dotted red;padding:2px">'.
		'<strong><font color=red>ERROR:</font> NinjaFirewall cannot retrieve user '.
		'rules #1.</strong></span>';
	}
	$table_prefix = '';
	$mysqli->close();
	return;
}

$table_prefix = '';

if (! $rules = @$result->fetch_object() ) {
	if ( $nfw_warn ) {
		echo '<span style="background:#ffffff;border:1px dotted red;padding:2px">'.
		'<strong><font color=red>ERROR:</font> NinjaFirewall cannot retrieve user '.
		'rules #2.</strong></span>';
	}
	$mysqli->close();
	return;
}
$result->close();

// Parse all requests and server variables :
nfw_check_request( unserialize($rules->option_value) );

// Sanitise requests/variables if needed :
if ( (! empty($nfw_options['get_sanitise']) ) && (isset($_GET)) ){
	$_GET = nfw_sanitise( $_GET, 1, 'GET');
}
if ( (! empty($nfw_options['post_sanitise']) ) && (isset($_POST)) ){
	$_POST = nfw_sanitise( $_POST, 1, 'POST');
}
if ( (! empty($nfw_options['cookies_sanitise']) ) && (isset($_COOKIE)) ) {
	$_COOKIE = nfw_sanitise( $_COOKIE, 1, 'COOKIE');
}
if ( (! empty($nfw_options['ua_sanitise']) ) && (! empty($_SERVER['HTTP_USER_AGENT'])) ) {
	$_SERVER['HTTP_USER_AGENT'] = nfw_sanitise( $_SERVER['HTTP_USER_AGENT'], 1, 'HTTP_USER_AGENT');
}
if ( (! empty($nfw_options['referer_sanitise']) ) && (! empty($_SERVER['HTTP_REFERER'])) ) {
	$_SERVER['HTTP_REFERER'] = nfw_sanitise( $_SERVER['HTTP_REFERER'], 1, 'HTTP_REFERER');
}
if ( (! empty($nfw_options['php_path_i']) ) && (! empty($_SERVER['PATH_INFO'])) ) {
	$_SERVER['PATH_INFO'] = nfw_sanitise( $_SERVER['PATH_INFO'], 2, 'PATH_INFO');
}
if ( (! empty($nfw_options['php_path_t']) ) && (! empty($_SERVER['PATH_TRANSLATED'])) ) {
	$_SERVER['PATH_TRANSLATED'] = nfw_sanitise( $_SERVER['PATH_TRANSLATED'], 2, 'PATH_TRANSLATED');
}
if ( (! empty($nfw_options['php_self']) ) && (! empty($_SERVER['PHP_SELF'])) ) {
	$_SERVER['PHP_SELF'] = nfw_sanitise( $_SERVER['PHP_SELF'], 2, 'PHP_SELF');
}

@$mysqli->close();

// That's all !
return;

/* ================================================================== */

function nfw_check_upload() {

	global $nfw_options;
	$tmp = '';

	// Uploads are disallowed :
	if ( empty($nfw_options['uploads']) ) {
		foreach ($_FILES as $file) {
			// Empty field ?
			if (! $file['name']) { continue; }
         $tmp .= $file['name'] . ', ' . number_format($file['size']) . ' bytes ';
      }
      if ( $tmp ) {
			// Log and block :
			nfw_log('File upload attempt', rtrim($tmp, ' '), 2, 0);
			nfw_block();
		}
	// Uploads are allowed :
	} else {
		foreach ($_FILES as $nm => $file) {
			if(! $file['tmp_name']) { continue; }
			// Sanitise filename ?
			if (! empty($nfw_options['sanitise_fn']) ) {
				$file['name'] = preg_replace('/[^\w\.\-]/i', 'X', $file['name'], -1, $count);
				if ($count) {
					$tmp = ' (sanitising '. $count . ' char. from filename)';
				}
				if ( $tmp ) {
					$_FILES[$nm]['name'] = $file['name'];
				}
			}
			// Log and let it go :
			nfw_log('Uploading file' . $tmp , $file['name'] . ', ' . number_format($file['size']) . ' bytes', 5, 0);
		}
	}
}

/* ================================================================== */

function nfw_check_request( $nfw_rules ) {

	global $nfw_options;
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
							check_b64($reqkey, $reqvalue);
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

function check_b64( $reqkey, $string ) {

	// clean-up the string before testing it :
	$string = preg_replace( '`[^A-Za-z0-9+/=]`', '', $string);
	if ( (! $string) || (strlen($string) % 4 != 0) ) { return; }

	if ( base64_encode( $decoded = base64_decode($string) ) === $string ) {
		if ( preg_match( '`\b(?:\$?_(COOKIE|ENV|FILES|(?:GE|POS|REQUES)T|SE(RVER|SSION))|HTTP_(?:(?:POST|GET)_VARS|RAW_POST_DATA)|GLOBALS)\s*[=\[)]|\b(?i:array_map|assert|base64_(?:de|en)code|chmod|curl_exec|(?:ex|im)plode|error_reporting|eval|file(?:_get_contents)?|f(?:open|write|close)|fsockopen|function_exists|gzinflate|md5|move_uploaded_file|ob_start|passthru|preg_replace|phpinfo|stripslashes|strrev|(?:shell_)?exec|system|unlink)\s*\(|\becho\s*[\'"]|<\s*(?i:applet|div|embed|i?frame(?:set)?|img|meta|marquee|object|script|textarea)\b|\b(?i:(?:ht|f)tps?|php)://`', $decoded) ) {
			nfw_log('base64-encoded injection', 'POST:' . $reqkey . ' = ' . $string, '3', 0);
			nfw_block();
		}
	}
}

/* ================================================================== */

function nfw_sanitise( $str, $how, $msg ) {

	global $mysqli;

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
			$str2 = $mysqli->real_escape_string($str);
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

	global $nfw_options;
	global $num_incident;
	global $mysqli;
	global $table_prefix;

	// We don't block anyone if we are running in debugging mode :
	if (! empty($nfw_options['debug']) ) {
		return;
	}

	$table_prefix = '';
	@$mysqli->close();

	$http_codes = array(
      400 => '400 Bad Request', 403 => '403 Forbidden',
      404 => '404 Not Found', 406 => '406 Not Acceptable',
      500 => '500 Internal Server Error', 503 => '503 Service Unavailable',
   );

	// Prepare the page to display to the blocked user :
	$tmp = str_replace( '%%NUM_INCIDENT%%', $num_incident,  $nfw_options['blocked_msg'] );
	$ninja_logo = substr( __FILE__, 0, -16) . 'images/ninjafirewall_75.png';
	if (file_exists( $ninja_logo ) ) {
		$tmp = @str_replace( '%%NINJA_LOGO%%', '<img title="NinjaFirewall" src="data:image/png;base64,' .
		base64_encode( file_get_contents( $ninja_logo ) ) . '" width="75" height="75">', $tmp );
	} else {
		$tmp = @str_replace( '%%NINJA_LOGO%%', '', $tmp );
	}
	$tmp = str_replace( '%%REM_ADDRESS%%', $_SERVER['REMOTE_ADDR'], $tmp );

	if (! headers_sent() ) {
		header('HTTP/1.0 ' . $http_codes[$nfw_options['ret_code']] );
		header('Status: ' .  $http_codes[$nfw_options['ret_code']] );
	}

	echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">' . "\n" .
		'<html><head><title>NinjaFirewall: ' . $http_codes[$nfw_options['ret_code']] .
		'</title></head><body>' . $tmp . '</body></html>';

	exit;

}

/* ================================================================== */

function nfw_log($loginfo, $logdata, $loglevel, $ruleid) {

	global $nfw_options;
	global $num_incident;
	global $nfw_warn;
	global $nfw_starttime;

	// Info/sanitise ? Don't block and do not issue any incident number :
	if ( $loglevel == 6) {
		$num_incident = '0000000';
		$http_ret_code = '200 OK';
	} else {
		// Debugging ? Don't block and do not issue any incident number
		// but set loglevel to 7 (will display 'DEBUG_ON' in log) :
		if (! empty($nfw_options['debug']) ) {
			$num_incident = '0000000';
			$loglevel = 7;
			$http_ret_code = '200 OK';
		// Create a random incident number :
		} else {
			$num_incident = mt_rand(1000000, 9000000);
			$http_ret_code = $nfw_options['ret_code'];
		}
	}

	// Prepare the line to log :
   if (strlen($logdata) > 100) { $logdata = substr($logdata, 0, 100) . '...'; }
	$res = '';
	$string = str_split($logdata);
	foreach ( $string as $char ) {
		// Allow only ASCII printable characters :
		if ( ( ord($char) < 32 ) || ( ord($char) > 127 ) ) {
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

	$log_file = substr(__FILE__, 0, -16) . 'log/firewall_' . date('Y-m') . '.log';
	if (! $fh = fopen($log_file, 'a') ) {
		if ( $nfw_warn ) {
			echo '<span style="background:#ffffff;border:1px dotted red;padding:2px"><strong>' .
			'<font color=red>ERROR:</font> NinjaFirewall cannot write to its logfile</strong></span>';
		}
		return;
	}

   fwrite( $fh,
      '[' . time() . '] ' . '[' . round( (microtime(true) - $nfw_starttime), 5) . '] ' .
      '[' . $_SERVER['SERVER_NAME'] . '] ' . '[#' . $num_incident . '] ' .
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

	$bf_conf_dir = substr( __FILE__, 0, -16) . 'log';

	// Is brute-force protection enabled ?
	if (! file_exists($bf_conf_dir . '/nfwbfd.php') ) {
		return;
	}

	$now = time();
	// Get config :
	require_once($bf_conf_dir . '/nfwbfd.php');

	// Has protection already been triggered ?
	if ( file_exists($bf_conf_dir . '/nfwblocked' . $_SERVER['SERVER_NAME'] . $bf_rand) ) {
		// Ensure the banning period is not over :
		$fstat = stat( $bf_conf_dir . '/nfwblocked' . $_SERVER['SERVER_NAME'] . $bf_rand );
		if ( ($now - $fstat['mtime']) < $bf_bantime * 60 ) {
			// User authentication required :
			nfw_check_auth($auth_name, $auth_pass);
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

	// If this is an attempt to log in, ensure all variables, values and
	// cookies are okay, otherwise we don't even forward it to WordPress
	// and reject it right away :
	if ( ($_SERVER['REQUEST_METHOD'] == 'POST') && (! isset($_REQUEST['action'])) ) {
		if ( (empty($_POST['log'])) || (empty($_POST['pwd'])) || (empty($_POST['wp-submit'])) ||
			(empty($_POST['redirect_to'])) || (empty($_POST['testcookie'])) ||
			(empty($_COOKIE['wordpress_test_cookie'])) ) {
			// Record it :
			@file_put_contents($bf_conf_dir . '/nfwlog' . $_SERVER['SERVER_NAME'] . $bf_rand, $now . "\n", FILE_APPEND);
			// Force HTTP authentication :
			nfw_check_auth($auth_name, $auth_pass);
			return;

		}
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
				nfw_log('Brute-force attack detected', 'enabling HTTP authentication for ' . $bf_bantime . 'mn', 6, 0);
				// Force HTTP authentication :
				nfw_check_auth($auth_name, $auth_pass);
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

function nfw_check_auth($auth_name, $auth_pass) {

	if ( (! empty($_SERVER['PHP_AUTH_USER'])) && (! empty($_SERVER['PHP_AUTH_PW'])) ) {
		// Allow authenticated users only :
		if ( ($_SERVER['PHP_AUTH_USER'] == $auth_name) && (sha1($_SERVER['PHP_AUTH_PW']) == $auth_pass) ) {
			return;
		}
	}
	header('WWW-Authenticate: Basic realm="Access temporarily restricted"');
	header('HTTP/1.0 401 Unauthorized');
	echo '401 Access temporarily restricted';
	exit;
}

/* ================================================================== */
// EOF
?>