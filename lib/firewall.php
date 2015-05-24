<?php
// +---------------------------------------------------------------------+
// | NinjaFirewall (WP edition)                                          |
// |                                                                     |
// | (c) NinTechNet - http://nintechnet.com/                             |
// +---------------------------------------------------------------------+
// | REVISION: 2015-05-04 17:53:28                                       |
// +---------------------------------------------------------------------+
// | This program is free software: you can redistribute it and/or       |
// | modify it under the terms of the GNU General Public License as      |
// | published by the Free Software Foundation, either version 3 of      |
// | the License, or (at your option) any later version.                 |
// |                                                                     |
// | This program is distributed in the hope that it will be useful,     |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of      |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the       |
// | GNU General Public License for more details.                        |
// +---------------------------------------------------------------------+
if ( strpos($_SERVER['SCRIPT_NAME'], '/nfwlog/') !== FALSE ||
	strpos($_SERVER['SCRIPT_NAME'], '/ninjafirewall/') !== FALSE ) { die('Forbidden'); }
if (defined('NFW_STATUS')) { return; }

// Used for benchmarks purpose :
$nfw_['fw_starttime'] = microtime(true);

// Optional NinjaFirewall configuration file
// ( see http://ninjafirewall.com/wordpress/htninja/ ) :
if ( @file_exists($nfw_['file'] = dirname(getenv('DOCUMENT_ROOT')) .'/.htninja') ||
	@file_exists($nfw_['file'] = getenv('DOCUMENT_ROOT') .'/.htninja') ) {
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

$nfw_['wp_content'] = dirname(dirname(dirname( __DIR__ )));
// Check if we have a user-defined log directory
// (see http://ninjafirewall.com/wordpress/htninja/#nfwlogdir ) :
if ( defined('NFW_LOG_DIR') ) {
	$nfw_['log_dir'] = NFW_LOG_DIR . '/nfwlog';
} else {
	$nfw_['log_dir'] = $nfw_['wp_content'] . '/nfwlog';
}
// We ensure the log dir exists otherwise we try to create it.
// We quit and warn immediately if we fail :
if (! is_dir($nfw_['log_dir']) ) {
	if (! mkdir( $nfw_['log_dir'] . '/cache', 0755, true) ) {
		define( 'NFW_STATUS', 13 );
		return;
	}
}

// Brute-force attack detection on login page and/or XMLRPC :
if ( strpos($_SERVER['SCRIPT_NAME'], 'wp-login.php' ) !== FALSE ) {
	nfw_bfd(1);
} elseif ( strpos($_SERVER['SCRIPT_NAME'], 'xmlrpc.php' ) !== FALSE ) {
	nfw_bfd(2);
}

// We need to get access to the database but we cannot include/require()
// either wp-load.php or wp-config.php, because that would load the core
// part of WordPress. Remember, we are supposed to act like a real and
// stand-alone firewall, not like a lame security plugin: we must hook
// every single PHP request **before** WordPress. Therefore, we must find,
// open and parse the wp-config.php file.
if (empty ($wp_config)) {
	$wp_config = dirname($nfw_['wp_content']) . '/wp-config.php';
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
	if ( preg_match('/^\s*define\s*\(\s*[\'"]DB_NAME[\'"]\s*,\s*[\'"](.+?)[\'"]/', $nfw_['line'], $nfw_['match']) ) {
		$nfw_['DB_NAME'] = $nfw_['match'][1];
	} elseif ( preg_match('/^\s*define\s*\(\s*[\'"]DB_USER[\'"]\s*,\s*[\'"](.+?)[\'"]/', $nfw_['line'], $nfw_['match']) ) {
		$nfw_['DB_USER'] = $nfw_['match'][1];
	} elseif ( preg_match('/^\s*define\s*\(\s*[\'"]DB_PASSWORD[\'"]\s*,\s*[\'"](.+?)[\'"]/', $nfw_['line'], $nfw_['match']) ) {
		$nfw_['DB_PASSWORD'] = $nfw_['match'][1];
	} elseif ( preg_match('/^\s*define\s*\(\s*[\'"]DB_HOST[\'"]\s*,\s*[\'"](.+?)[\'"]/', $nfw_['line'], $nfw_['match']) ) {
		$nfw_['DB_HOST'] = $nfw_['match'][1];
	} elseif ( preg_match('/^\s*\$table_prefix\s*=\s*[\'"](.+?)[\'"]/', $nfw_['line'], $nfw_['match']) ) {
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

// So far, so good.
// Check whether we have a host, host:ip, host:socket or host:port:socket :
nfw_check_dbhost();
// Connect to the DB:
@$nfw_['mysqli'] = new mysqli($nfw_['DB_HOST'], $nfw_['DB_USER'], $nfw_['DB_PASSWORD'], $nfw_['DB_NAME'], $nfw_['port'], $nfw_['socket']);
if ($nfw_['mysqli']->connect_error) {
	define( 'NFW_STATUS', 4 );
	unset($nfw_);
	return;
}

// Fetch our user options table:
if (! $nfw_['result'] = @$nfw_['mysqli']->query('SELECT * FROM `' . $nfw_['mysqli']->real_escape_string($nfw_['table_prefix']) . "options` WHERE `option_name` = 'nfw_options'")) {
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

if (! $nfw_['nfw_options'] = @unserialize($nfw_['options']->option_value) ) {
	$nfw_['mysqli']->close();
	define( 'NFW_STATUS', 11 );
	unset($nfw_);
	return;
}

// Are we supposed to do anything ?
if ( empty($nfw_['nfw_options']['enabled']) ) {
	$nfw_['mysqli']->close();
	define( 'NFW_STATUS', 20 );
	unset($nfw_);
	return;
}

// Response headers hook :
if (! empty($nfw_['nfw_options']['response_headers']) && function_exists('header_register_callback')) {
	define('NFW_RESHEADERS', $nfw_['nfw_options']['response_headers']);
	header_register_callback('nfw_response_headers');
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
			$nfw_['a_msg'] = '1:3:' . @$_GET['plugin'];
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
// plugin updates via admin-ajax.php (since WP 4.2):
} elseif ( strpos($_SERVER['SCRIPT_NAME'], '/admin-ajax.php' ) !== FALSE ) {
	if ( isset( $_REQUEST['action']) && $_REQUEST['action'] == 'update-plugin' ) {
		if (! empty($_REQUEST['plugin']) ) {
			$nfw_['a_msg'] = '1:4:' . @$_REQUEST['plugin'];
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

// Ensure we have a proper and single IP (a user may use the .htninja file
// to redirect HTTP_X_FORWARDED_FOR, which may contain more than one IP,
// to REMOTE_ADDR):
if (strpos($_SERVER['REMOTE_ADDR'], ',') !== false) {
	$nfw_['match'] = array_map('trim', @explode(',', $_SERVER['REMOTE_ADDR']));
	foreach($nfw_['match'] as $nfw_['m']) {
		if ( filter_var($nfw_['m'], FILTER_VALIDATE_IP) )  {
			// Fix it, so that WP and other plugins can use it:
			$_SERVER['REMOTE_ADDR'] = $nfw_['m'];
			break;
		}
	}
}

nfw_check_session();
// Do not scan/filter WordPress admin (if logged in) ?
if (! empty($_SESSION['nfw_goodguy']) ) {

	// Look for Live Log AJAX request...
	if (! empty($_SESSION['nfw_livelog']) &&  isset($_POST['livecls']) && isset($_POST['lines'])) {
		$nfw_['livelog'] = $nfw_['log_dir'] . '/cache/livelog.php';
		if ( file_exists($nfw_['livelog']) ) {
			// Check if we need to flush it :
			if ($_POST['livecls'] > 0) {
				$fh = fopen($nfw_['livelog'],'w');
				fclose($fh);
			}
			$count = 0;
			$buffer = '';
			if ( $fh = fopen($nfw_['livelog'], 'r' ) ) {
				while (! feof($fh) ) {
					if ( $count >= $_POST['lines'] ) {
						$buffer .= fgets($fh);
					} else {
						fgets($fh);
					}
					$count++;
				}
				fclose($fh);
			}

			// Return the log content :
			header('HTTP/1.0 200 OK');
			if ( $buffer ) {
				echo '^'.$buffer;
			} else {
				echo '*';
			}
			touch($nfw_['log_dir'] .'/cache/livelogrun.php');
		} else {
			// Something went wrong :
			header('HTTP/1.0 503 Service Unavailable');
		}
		$nfw_['mysqli']->close();
		exit;
	}
	// ...or go ahead :

	// Check for specific rules that should apply to everyone, including whitelisted admin(s) :
	if (! $nfw_['result'] = @$nfw_['mysqli']->query('SELECT * FROM `' . $nfw_['mysqli']->real_escape_string($nfw_['table_prefix']) . "options` WHERE `option_name` = 'nfw_rules'")) {
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
	// Fetch those rules only :
	if (! $nfw_['nfw_rules'] = @unserialize($nfw_['rules']->option_value) ) {
		$nfw_['mysqli']->close();
		define( 'NFW_STATUS', 12 );
		unset($nfw_);
		return;
	}

	if (isset($nfw_['nfw_rules']['999']) ) {
		$nfw_['adm_rules'] = array();
		foreach ($nfw_['nfw_rules']['999'] as $key => $value) {
			if ($key == 'on') { continue; }
			$nfw_['adm_rules'][$key] = $nfw_['nfw_rules'][$key];
		}
		// Parse them :
		if (! empty($nfw_['adm_rules'])) {
			nfw_check_request( $nfw_['adm_rules'], $nfw_['nfw_options'] );
		}
	}
	$nfw_['mysqli']->close();
	define( 'NFW_STATUS', 20 );
	unset($nfw_);
	return;
}
define('NFW_SWL', 1);

// Live Log : record the request if needed
if ( file_exists($nfw_['log_dir'] .'/cache/livelogrun.php')) {
	$nfw_['stats'] = stat($nfw_['log_dir'] .'/cache/livelogrun.php');

	// If the file was not accessed for more than 100s, we assume
	// the admin has stopped watching the live log from WordPress
	// dashboard (max refresh rate is 45s) :
	if ( $nfw_['fw_starttime'] - $nfw_['stats']['mtime'] > 100 ) {
		unlink($nfw_['log_dir'] .'/cache/livelogrun.php');
		// If the log was not modified for the past 10mn, we delete it as well :
		$nfw_['livelog'] = $nfw_['log_dir'] . '/cache/livelog.php';
		if ( file_exists($nfw_['livelog']) ) {
			$nfw_['stats'] = stat($nfw_['livelog']);
			if ( $nfw_['fw_starttime'] - $nfw_['stats']['mtime'] > 600 ) {
				unlink( $nfw_['livelog'] );
			}
		}
	} else {
		// Check if we are supposed to log the request (http/https) :
		if ( empty($nfw_['nfw_options']['liveport']) ||
			($nfw_['nfw_options']['liveport'] == 1 && $_SERVER['SERVER_PORT'] != 443) ||
			($nfw_['nfw_options']['liveport'] == 2 && $_SERVER['SERVER_PORT'] == 443) ) {

			if ( empty($_SERVER['PHP_AUTH_USER']) ) { $PHP_AUTH_USER = '-'; }
			else { $PHP_AUTH_USER = $_SERVER['PHP_AUTH_USER']; }
			if ( empty($_SERVER['HTTP_REFERER']) ) { $HTTP_REFERER = '-'; }
			else { $HTTP_REFERER = $_SERVER['HTTP_REFERER']; }
			if ( empty($_SERVER['HTTP_USER_AGENT']) ) {	$HTTP_USER_AGENT = '-'; }
			else { $HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT']; }
			if ( empty($_SERVER['HTTP_X_FORWARDED_FOR']) ) { $HTTP_X_FORWARDED_FOR = '-'; }
			else { $HTTP_X_FORWARDED_FOR = $_SERVER['HTTP_X_FORWARDED_FOR']; }
			if ( empty($_SERVER['HTTP_HOST']) ) { $HTTP_HOST = '-'; }
			else { $HTTP_HOST = $_SERVER['HTTP_HOST']; }

			// Set the timezone :
			if (! empty($nfw_['nfw_options']['livetz']) ) {
				@date_default_timezone_set($nfw_['nfw_options']['livetz']);
			} else {
				if (! $nfw_['nfw_options']['tzstring'] = ini_get('date.timezone') ) {
					$nfw_['nfw_options']['tzstring'] = 'UTC';
				}
				@date_default_timezone_set($nfw_['nfw_options']['tzstring']);
			}

			// Log the request :
			if (! empty($nfw_['nfw_options']['liveformat']) ) {
				// User-defined format :
				$nfw_['tmp'] = str_replace(
					array( '%time', '%name', '%client', '%method', '%uri', '%referrer', '%ua', '%forward', '%host' ),
					array( date('d/M/y:H:i:s O', time()), $PHP_AUTH_USER, $_SERVER["REMOTE_ADDR"], $_SERVER["REQUEST_METHOD"], $_SERVER["REQUEST_URI"], $HTTP_REFERER, $HTTP_USER_AGENT, $HTTP_X_FORWARDED_FOR, $HTTP_HOST ), $nfw_['nfw_options']['liveformat']	);
				@file_put_contents( $nfw_['log_dir'] . '/cache/livelog.php', htmlentities($nfw_['tmp'], ENT_NOQUOTES) ."\n", FILE_APPEND | LOCK_EX);
			} else {
				// Default format :
				@file_put_contents( $nfw_['log_dir'] . '/cache/livelog.php',
				'['. @date('d/M/y:H:i:s O', time()) .'] '.	htmlentities(
				$PHP_AUTH_USER .' '.	$_SERVER['REMOTE_ADDR'] .' "'. $_SERVER['REQUEST_METHOD'] .' '.
				$_SERVER['REQUEST_URI'] .'" "'. $HTTP_REFERER .'" "'. $HTTP_USER_AGENT .'" "'.
				$HTTP_X_FORWARDED_FOR .'" "'. $HTTP_HOST, ENT_NOQUOTES) ."\"\n", FILE_APPEND | LOCK_EX);
			}
		}
	}
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
	// Look for exclusion :
	if ( empty($nfw_['nfw_options']['fg_exclude']) || strpos($_SERVER['SCRIPT_FILENAME'], $nfw_['nfw_options']['fg_exclude']) === FALSE ) {
		// Stat() the requested script :
		if ( $nfw_['nfw_options']['fg_stat'] = stat( $_SERVER['SCRIPT_FILENAME'] ) ) {
			// Was is created/modified lately ?
			if ( time() - $nfw_['nfw_options']['fg_mtime'] * 3660 < $nfw_['nfw_options']['fg_stat']['ctime'] ) {
				// Did we check it already ?
				if (! file_exists( $nfw_['log_dir'] . '/cache/fg_' . $nfw_['nfw_options']['fg_stat']['ino'] . '.php' ) ) {
					// We need to alert the admin :
					if (! $nfw_['nfw_options']['tzstring'] = ini_get('date.timezone') ) {
						$nfw_['nfw_options']['tzstring'] = 'UTC';
					}
					date_default_timezone_set($nfw_['nfw_options']['tzstring']);
					$nfw_['nfw_options']['m_headers'] = 'From: "NinjaFirewall" <postmaster@'. $_SERVER['SERVER_NAME'] . ">\r\n";
					$nfw_['nfw_options']['m_headers'] .= "Content-Transfer-Encoding: 7bit\r\n";
					$nfw_['nfw_options']['m_headers'] .= "Content-Type: text/plain; charset=\"UTF-8\"\r\n";
					$nfw_['nfw_options']['m_headers'] .= "MIME-Version: 1.0\r\n";
					$nfw_['nfw_options']['m_subject'] = '[NinjaFirewall] Alert: File Guard detection';
					$nfw_['nfw_options']['m_msg'] = 	'Someone accessed a script that was modified or created less than ' .
						$nfw_['nfw_options']['fg_mtime'] . ' hour(s) ago:' . "\n\n".
						'SERVER_NAME    : ' . $_SERVER['SERVER_NAME'] . "\n" .
						'SCRIPT_FILENAME: ' . $_SERVER['SCRIPT_FILENAME'] . "\n" .
						'Last changed on: ' . date('F j, Y @ H:i:s', $nfw_['nfw_options']['fg_stat']['ctime'] ) . ' (UTC '. date('O') . ")\n" .
						'REQUEST_URI    : ' . $_SERVER['REQUEST_URI'] . "\n" .
						'REMOTE_ADDR    : ' . $_SERVER['REMOTE_ADDR'] . "\n\n" .
						'NinjaFirewall (WP edition) - http://ninjafirewall.com/' . "\n" .
						'Support forum: http://wordpress.org/support/plugin/ninjafirewall' . "\n";
					mail( $nfw_['nfw_options']['alert_email'], $nfw_['nfw_options']['m_subject'], $nfw_['nfw_options']['m_msg'], $nfw_['nfw_options']['m_headers']);
					// Remember it so that we don't spam the admin each time the script is requested :
					touch($nfw_['log_dir'] . '/cache/fg_' . $nfw_['nfw_options']['fg_stat']['ino'] . '.php');
					// Log it :
					nfw_log('Access to a script modified/created less than ' . $nfw_['nfw_options']['fg_mtime'] . ' hour(s) ago', $_SERVER['SCRIPT_FILENAME'], 6, 0);
				}
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
if (! $nfw_['result'] = @$nfw_['mysqli']->query('SELECT * FROM `' . $nfw_['mysqli']->real_escape_string($nfw_['table_prefix']) . "options` WHERE `option_name` = 'nfw_rules'")) {
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

// This will be returned to the admin only if (s)he is not whitelisted obviously :
if (! $nfw_['nfw_rules'] = @unserialize($nfw_['rules']->option_value) ) {
	$nfw_['mysqli']->close();
	define( 'NFW_STATUS', 12 );
	unset($nfw_);
	return;
}

// Parse all requests and server variables :
nfw_check_request( $nfw_['nfw_rules'], $nfw_['nfw_options'] );

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
	$_COOKIE = nfw_sanitise( $_COOKIE, 3, 'COOKIE');
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

// =====================================================================

function nfw_check_session() {

	if (version_compare(PHP_VERSION, '5.4', '<') ) {
		if (session_id() ) return;
	} else {
		if (session_status() === PHP_SESSION_ACTIVE) return;
	}

	// Prepare session :
	@ini_set('session.cookie_httponly', 1);
	@ini_set('session.use_only_cookies', 1);
	if ($_SERVER['SERVER_PORT'] == 443) {
		@ini_set('session.cookie_secure', 1);
	}
	session_start();
}

// =====================================================================

function nfw_check_upload() {

	if ( defined('NFW_STATUS') ) { return; }

	global $nfw_;

	// Fetch uploaded files, if any :
	$f_uploaded = nfw_fetch_uploads();
	$tmp = '';
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
			nfw_log('Allowing file upload' . $tmp , $f_uploaded[$key]['name'] . ', ' . number_format($f_uploaded[$key]['size']) . ' bytes', 5, 0);
		}
	}
}

// =====================================================================

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

// =====================================================================

function nfw_check_request( $nfw_rules, $nfw_options ) {

	if ( defined('NFW_STATUS') ) { return; }

	$b64_post = array();

	foreach ($nfw_rules as $rules_id => $rules_values) {
		// Ignored disabled rules :
		if ( empty( $rules_values['on']) ) { continue; }
		$wherelist = explode('|', $rules_values['where']);
		foreach ($wherelist as $where) {

			// Global GET/POST/COOKIE/SERVER requests :
			if ( ($where == 'POST' && ! empty($nfw_options['post_scan'])) || ($where == 'GET' && ! empty($nfw_options['get_scan'])) || ($where == 'COOKIE' && ! empty($nfw_options['cookies_scan'])) || $where == 'SERVER' ) {
				foreach ($GLOBALS['_' . $where] as $reqkey => $reqvalue) {
					// Look for an array() :
					if ( is_array($reqvalue) ) {
						$res = nfw_flatten( "\n", $reqvalue );
						$reqvalue = $res;
						$rules_values['what'] = '(?m:'. $rules_values['what'] .')';
					} else {
						if (! empty($nfw_options['post_b64']) && $where == 'POST' && $reqvalue && ! isset($b64_post[$reqkey]) ) {
							$b64_post[$reqkey] = 1;
							nfw_check_b64($reqkey, $reqvalue);
						}
					}
					if (! $reqvalue) { continue; }
					if ( preg_match('`'. $rules_values['what'] .'`', $reqvalue) ) {
						// Extra rule :
						if (! empty($rules_values['extra'])) {
							if ( empty($GLOBALS['_' . $rules_values['extra'][1]] [$rules_values['extra'][2]]) || ! preg_match('`'. $rules_values['extra'][3] .'`', $GLOBALS['_' . $rules_values['extra'][1]] [$rules_values['extra'][2]]) ) { continue;	}
						}
						nfw_log($rules_values['why'], $where .':' . $reqkey . ' = ' . $reqvalue, $rules_values['level'], $rules_id);
						nfw_block();
               }
				}
				continue;
			}

			// HTTP_USER_AGENT & HTTP_REFERER variables :
			if ( isset($_SERVER[$where]) ) {
				if ( ($where == 'HTTP_USER_AGENT' && empty($nfw_options['ua_scan'])) || ($where == 'HTTP_REFERER' && empty($nfw_options['referer_scan'])) ) { continue; }
				if ( preg_match('`'. $rules_values['what'] .'`', $_SERVER[$where]) ) {
					// Extra rule :
					if (! empty($rules_values['extra'])) {
						if ( empty($GLOBALS['_' . $rules_values['extra'][1]] [$rules_values['extra'][2]]) || ! preg_match('`'. $rules_values['extra'][3] .'`', $GLOBALS['_' . $rules_values['extra'][1]] [$rules_values['extra'][2]]) ) { continue;	}
					}
					nfw_log($rules_values['why'], $where. ' = ' .$_SERVER[$where], $rules_values['level'], $rules_id);
					nfw_block();
            }
				continue;
			}

			// Specific POST:xx, GET:xx, COOKIE:xxx, SERVER:xxx requests etc :
			$sub_value = explode(':', $where);
			if ( ($sub_value[0] == 'POST' && empty($nfw_options['post_scan'])) || ($sub_value[0] == 'GET' && empty($nfw_options['get_scan'])) || ($sub_value[0] == 'COOKIE' && empty($nfw_options['cookies_scan'])) ) { continue; }
			if (! empty($sub_value[1]) && @isset($GLOBALS['_' . $sub_value[0]] [$sub_value[1]]) ) {
				if ( is_array($GLOBALS['_' . $sub_value[0]] [$sub_value[1]]) ) {
					$res = nfw_flatten( "\n", $GLOBALS['_' . $sub_value[0]] [$sub_value[1]] );
					$GLOBALS['_' . $sub_value[0]] [$sub_value[1]] = $res;
					$rules_values['what'] = '(?m:'. $rules_values['what'] .')';
				}
				if (! $GLOBALS['_' . $sub_value[0]][$sub_value[1]] ) { continue; }
				if ( preg_match('`'. $rules_values['what'] .'`', $GLOBALS['_' . $sub_value[0]][$sub_value[1]]) ) {
					// Extra rule :
					if (! empty($rules_values['extra'])) {
						if ( empty($GLOBALS['_' . $rules_values['extra'][1]] [$rules_values['extra'][2]]) || ! preg_match('`'. $rules_values['extra'][3] .'`', $GLOBALS['_' . $rules_values['extra'][1]] [$rules_values['extra'][2]]) ) { continue;	}
					}
					nfw_log($rules_values['why'], $sub_value[0]. ':' .$sub_value[1]. ' = ' .$GLOBALS['_' . $sub_value[0]][$sub_value[1]], $rules_values['level'], $rules_id);
					nfw_block();
				}
			}
		}
	}
}

// =====================================================================

function nfw_flatten( $glue, $pieces ) {

	if ( defined('NFW_STATUS') ) { return; }

	$ret = array();

   foreach ($pieces as $r_pieces) {
      if ( is_array($r_pieces)) {
         $ret[] = nfw_flatten($glue, $r_pieces);
      } else {
         $ret[] = $r_pieces;
      }
   }
   return implode($glue, $ret);
}

// =====================================================================

function nfw_check_b64( $reqkey, $string ) {

	if ( defined('NFW_STATUS') ) { return; }

	// clean-up the string before testing it :
	$string = preg_replace( '`[^A-Za-z0-9+/=]`', '', $string);
	if ( (! $string) || (strlen($string) % 4 != 0) ) { return; }

	if ( base64_encode( $decoded = base64_decode($string) ) === $string ) {
		if ( preg_match( '`\b(?:\$?_(COOKIE|ENV|FILES|(?:GE|POS|REQUES)T|SE(RVER|SSION))|HTTP_(?:(?:POST|GET)_VARS|RAW_POST_DATA)|GLOBALS)\s*[=\[)]|\b(?i:array_map|assert|base64_(?:de|en)code|chmod|curl_exec|(?:ex|im)plode|error_reporting|eval|file(?:_get_contents)?|f(?:open|write|close)|fsockopen|function_exists|gzinflate|md5|move_uploaded_file|ob_start|passthru|preg_replace|phpinfo|stripslashes|strrev|(?:shell_)?exec|system|unlink)\s*\(|\becho\s*[\'"]|<\s*(?i:applet|div|embed|i?frame(?:set)?|img|meta|marquee|object|script|textarea)\b|\W\$\{\s*[\'"]\w+[\'"]|<\?(?i:php)`', $decoded) ) {
			nfw_log('base64-encoded injection', 'POST:' . $reqkey . ' = ' . $string, '3', 0);
			nfw_block();
		}
	}
}

// =====================================================================

function nfw_sanitise( $str, $how, $msg ) {

	if ( defined('NFW_STATUS') ) { return; }

	if (! isset($str) ) { return null; }

	global $nfw_;

	// String :
	if (is_string($str) ) {
		if (get_magic_quotes_gpc() ) { $str = stripslashes($str); }
		// We sanitise variables **value** either with :
		// -mysql_real_escape_string to escape [\x00], [\n], [\r], [\],
		//	 ['], ["] and [\x1a]
		//	-str_replace to escape backtick [`]
		//	Applies to $_GET, $_POST, $_SERVER['HTTP_USER_AGENT']
		//	and $_SERVER['HTTP_REFERER']
		//
		// Or:
		//
		// -str_replace to escape [<], [>], ["], ['], [`] and , [\]
		//	-str_replace to replace [\n], [\r], [\x1a] and [\x00] with [X]
		//	Applies to $_SERVER['PATH_INFO'], $_SERVER['PATH_TRANSLATED']
		//	and $_SERVER['PHP_SELF']
		//
		// Or:
		//
		// -str_replace to escape ['], [`] and , [\]
		//	-str_replace to replace [\x1a] and [\x00] with [X]
		//	Applies to $_COOKIE only
		//
		if ($how == 1) {
			$str2 = $nfw_['mysqli']->real_escape_string($str);
			$str2 = str_replace('`', '\`', $str2);
		} elseif ($how == 2) {
			$str2 = str_replace(	array('\\', "'", '"', "\x0d", "\x0a", "\x00", "\x1a", '`', '<', '>'),
				array('\\\\', "\\'", '\\"', 'X', 'X', 'X', 'X', '\\`', '\\<', '\\>'),	$str);
		} else {
			$str2 = str_replace(	array('\\', "'", "\x00", "\x1a", '`'),
				array('\\\\', "\\'", 'X', 'X', '\\`'),	$str);
		}
		// Don't sanitised the string if we are running in Debugging Mode :
		if (! empty($nfw_['nfw_options']['debug']) ) {
			if ($str2 != $str) {
				nfw_log('Sanitising user input', $msg . ': ' . $str, 7, 0);
			}
			return $str;
		}
		// Log and return the sanitised string :
		if ($str2 != $str) {
			nfw_log('Sanitising user input', $msg . ': ' . $str, 6, 0);
		}
		return $str2;

	// Array :
	} else if (is_array($str) ) {
		foreach($str as $key => $value) {
			if (get_magic_quotes_gpc() ) {$key = stripslashes($key);}
			// COOKIE ?
			if ($how == 3) {
				$key2 = str_replace(	array('\\', "'", "\x00", "\x1a", '`', '<', '>'),
					array('\\\\', "\\'", 'X', 'X', '\\`', '&lt;', '&gt;'),	$key, $occ);
			} else {
				// We sanitise variables **name** using :
				// -str_replace to escape [\], ['] and ["]
				// -str_replace to replace [\n], [\r], [\x1a] and [\x00] with [X]
				//	-str_replace to replace [`], [<] and [>] with their HTML entities (&#96; &lt; &gt;)
				$key2 = str_replace(	array('\\', "'", '"', "\x0d", "\x0a", "\x00", "\x1a", '`', '<', '>'),
					array('\\\\', "\\'", '\\"', 'X', 'X', 'X', 'X', '&#96;', '&lt;', '&gt;'),	$key, $occ);
			}
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

// =====================================================================

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
   if (! isset($http_codes[$nfw_['nfw_options']['ret_code']]) ) {
		$nfw_['nfw_options']['ret_code'] = 403;
	}

	// Prepare the page to display to the blocked user :
	if (empty($nfw_['num_incident']) ) { $nfw_['num_incident'] = '000000'; }
	$tmp = str_replace( '%%NUM_INCIDENT%%', $nfw_['num_incident'],  base64_decode($nfw_['nfw_options']['blocked_msg']) );
	$tmp = @str_replace( '%%NINJA_LOGO%%', '<img title="NinjaFirewall" src="' . $nfw_['nfw_options']['logo'] . '" width="75" height="75">', $tmp );
	$tmp = str_replace( '%%REM_ADDRESS%%', $_SERVER['REMOTE_ADDR'], $tmp );

	if (! headers_sent() ) {
		header('HTTP/1.1 ' . $http_codes[$nfw_['nfw_options']['ret_code']] );
		header('Status: ' .  $http_codes[$nfw_['nfw_options']['ret_code']] );
		// Prevent caching :
		header('Pragma: no-cache');
		header('Cache-Control: private, no-cache, no-store, max-age=0, must-revalidate, proxy-revalidate');
		header('Expires: Mon, 01 Sep 2014 01:01:01 GMT');
	}

	echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">' . "\n" .
		'<html><head><title>NinjaFirewall: ' . $http_codes[$nfw_['nfw_options']['ret_code']] .
		'</title><style>body{font-family:sans-serif;font-size:13px;color:#000000;}</style><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body bgcolor="white">' . $tmp . '</body></html>';
	exit;
}

// =====================================================================

function nfw_log($loginfo, $logdata, $loglevel, $ruleid) {

	if ( defined('NFW_STATUS') ) { return; }

	global $nfw_;

	// Info/sanitise ? Don't block and do not issue any incident number :
	if ( $loglevel == 6) {
		$nfw_['num_incident'] = '0000000';
		$http_ret_code = '200';
	} else {
		// Debugging ? Don't block and do not issue any incident number
		// but set loglevel to 7 (will display 'DEBUG_ON' in log) :
		if (! empty($nfw_['nfw_options']['debug']) ) {
			$nfw_['num_incident'] = '0000000';
			$loglevel = 7;
			$http_ret_code = '200';
		// Create a random incident number :
		} else {
			$nfw_['num_incident'] = mt_rand(1000000, 9000000);
			$http_ret_code = $nfw_['nfw_options']['ret_code'];
		}
	}

	// Prepare the line to log :
   if (strlen($logdata) > 200) { $logdata = mb_substr($logdata, 0, 200, 'utf-8') . '...'; }
	$res = '';
	$string = str_split($logdata);
	foreach ( $string as $char ) {
		// Allow only ASCII printable characters :
		if ( ord($char) < 32 || ord($char) > 126 ) {
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

	$stat_file = $nfw_['log_dir']. '/stats_' . $cur_month . '.php';
	$log_file = $nfw_['log_dir']. '/firewall_' . $cur_month . '.php';

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
		$nfw_stat_arr[8] . ':' . $nfw_stat_arr[9], LOCK_EX );

	if (! file_exists($log_file) ) {
		$tmp = '<?php exit; ?>' . "\n";
	} else {
		$tmp = '';
	}

	@file_put_contents( $log_file,
		$tmp . '[' . time() . '] ' . '[' . round( (microtime(true) - $nfw_['fw_starttime']), 5) . '] ' .
      '[' . $_SERVER['SERVER_NAME'] . '] ' . '[#' . $nfw_['num_incident'] . '] ' .
      '[' . $ruleid . '] ' .
      '[' . $loglevel . '] ' . '[' . $_SERVER['REMOTE_ADDR'] . '] ' .
      '[' . $http_ret_code . '] ' . '[' . $_SERVER['REQUEST_METHOD'] . '] ' .
      '[' . $_SERVER['SCRIPT_NAME'] . '] ' . '[' . $loginfo . '] ' .
      '[' . $res . ']' . "\n", FILE_APPEND | LOCK_EX );
}

// =====================================================================

function nfw_bfd($where) {

	if ( defined('NFW_STATUS') ) { return; }

	global $nfw_;
	$bf_conf_dir = $nfw_['log_dir'] . '/cache';

	// Is brute-force protection enabled ?
	if (! file_exists($bf_conf_dir . '/bf_conf.php') ) {
		return;
	}

	$now = time();
	// Get config :
	require($bf_conf_dir . '/bf_conf.php');

	// Should it apply to the xmlrpc.php script as well ?
	if ( $where == 2 && empty($bf_xmlrpc) ) {
		return;
	}

	// Shall we always force HTTP authentication ?
	if ( $bf_enable == 2 ) {
		nfw_check_auth($auth_name, $auth_pass, $auth_msg);
		return;
	}

	// Has protection already been triggered ?
	if ( file_exists($bf_conf_dir . '/bf_blocked' . $where . $_SERVER['SERVER_NAME'] . $bf_rand) ) {
		// Ensure the banning period is not over :
		$fstat = stat( $bf_conf_dir . '/bf_blocked' . $where . $_SERVER['SERVER_NAME'] . $bf_rand );
		if ( ($now - $fstat['mtime']) < $bf_bantime * 60 ) {
			// User authentication required :
			nfw_check_auth($auth_name, $auth_pass, $auth_msg);
			return;
		} else {
			// Reset counter :
			@unlink($bf_conf_dir . '/bf_blocked' . $where . $_SERVER['SERVER_NAME'] . $bf_rand);
		}
	}

	// Are we supposed to handle that HTTP request (GET or POST or both) ?
	if ( strpos($bf_request, $_SERVER['REQUEST_METHOD']) === false ) {
		return;
	}

	// Read our log, if any :
	if ( file_exists($bf_conf_dir . '/bf_' . $where . $_SERVER['SERVER_NAME'] . $bf_rand ) ) {
		$tmp_log = file( $bf_conf_dir . '/bf_' . $where . $_SERVER['SERVER_NAME'] . $bf_rand, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		if ( count( $tmp_log) >= $bf_attempt ) {
			if ( ($tmp_log[count($tmp_log) - 1] - $tmp_log[count($tmp_log) - $bf_attempt]) <= $bf_maxtime ) {
				// Threshold has been reached, lock down access to the page :
				$bfdh = fopen( $bf_conf_dir . '/bf_blocked' . $where . $_SERVER['SERVER_NAME'] . $bf_rand, 'w');
				fclose( $bfdh );
				// Clear the log :
				unlink( $bf_conf_dir . '/bf_' . $where . $_SERVER['SERVER_NAME'] . $bf_rand );
				// Setup HTTP ret code here, because we do not have access
				// to the DB yet :
				$nfw_['nfw_options']['ret_code'] = '401';
				if ($where == 1) {
					$where = 'wp-login.php';
				} else {
					$where = 'XML-RPC API';
				}
				nfw_log('Brute-force attack detected on ' . $where, 'enabling HTTP authentication for ' . $bf_bantime . 'mn', 3, 0);
				// Shall we write to the AUTH log as well ?
				if (! empty($bf_authlog) ) {
					if (defined('LOG_AUTHPRIV') ) { $tmp = LOG_AUTHPRIV; }
					else { $tmp = LOG_AUTH;	}
					@openlog('ninjafirewall', LOG_NDELAY|LOG_PID, $tmp);
					@syslog(LOG_INFO, 'Possible brute-force attack from '. $_SERVER['REMOTE_ADDR'] .
							' on '. $_SERVER['SERVER_NAME'] .' ('. $where .'). Blocking access for ' . $bf_bantime . 'mn.');
					@closelog();
				}
				// Force HTTP authentication :
				nfw_check_auth($auth_name, $auth_pass, $auth_msg);
				return;

			}
		}
		// If the logfile is too old, flush it :
		$fstat = stat( $bf_conf_dir . '/bf_' . $where . $_SERVER['SERVER_NAME'] . $bf_rand );
		if ( ($now - $fstat['mtime']) > $bf_bantime * 60 ) {
			unlink( $bf_conf_dir . '/bf_' . $where . $_SERVER['SERVER_NAME'] . $bf_rand );
		}
	}

	// Let it go, but record the request :
	@file_put_contents($bf_conf_dir . '/bf_' . $where . $_SERVER['SERVER_NAME'] . $bf_rand, $now . "\n", FILE_APPEND | LOCK_EX);

}
// =====================================================================

function nfw_check_auth($auth_name, $auth_pass, $auth_msg) {

	if ( defined('NFW_STATUS') ) { return; }

	nfw_check_session();
	// Good guy already authenticated ?
	if (! empty($_SESSION['nfw_bfd']) ) {
		return;
	}

	// Is this an authentication request ?
	if (! empty($_REQUEST['u']) && ! empty($_REQUEST['p']) ) {
		if ( $_REQUEST['u'] === $auth_name && sha1($_REQUEST['p']) === $auth_pass ) {
			// Good guy :
			$_SESSION['nfw_bfd'] = 1;
			return;
		}
	}
	session_destroy();

	// Ask for authentication :
	header('HTTP/1.0 401 Unauthorized');
	header('Content-Type: text/html; charset=utf-8');
	header('X-Frame-Options: DENY');
	echo '<html><head><link rel="stylesheet" href="./wp-includes/css/buttons.min.css" type="text/css"><link rel="stylesheet" href="./wp-admin/css/login.min.css" type="text/css"></head><body class="login wp-core-ui"><div id="login"><center><h3>' . $auth_msg . '</h3><form method="post"><p><input class="input" type="text" name="u" placeholder="Username"></p><p><input class="input" type="password" name="p" placeholder="Password"></p><p align="right"><input type="submit" value="WP Login Page&nbsp;&#187;" class="button-secondary"></p></form><p>Brute-force protection by NinjaFirewall</p></center></div></body></html>';
	exit;
}

// =====================================================================
// From WP db_connect() :
function nfw_check_dbhost() {

	global $nfw_;

	$nfw_['port'] = null;
	$nfw_['socket'] = null;
	$port_or_socket = strstr( $nfw_['DB_HOST'], ':' );
	if ( ! empty( $port_or_socket ) ) {
		$nfw_['DB_HOST'] = substr( $nfw_['DB_HOST'], 0, strpos( $nfw_['DB_HOST'], ':' ) );
		$port_or_socket = substr( $port_or_socket, 1 );
		if ( 0 !== strpos( $port_or_socket, '/' ) ) {
			$nfw_['port'] = intval( $port_or_socket );
			$maybe_socket = strstr( $port_or_socket, ':' );
			if ( ! empty( $maybe_socket ) ) {
				$nfw_['socket'] = substr( $maybe_socket, 1 );
			}
		} else {
			$nfw_['socket'] = $port_or_socket;
		}
	}
}

// =====================================================================

function nfw_response_headers() {

	if (! defined('NFW_RESHEADERS') ) { return; }
	$NFW_RESHEADERS = NFW_RESHEADERS;
	// NFW_RESHEADERS:
	// 000000
	// ||||||_ Strict-Transport-Security (includeSubDomains) [0-1]
	// |||||__ Strict-Transport-Security [0-3]
	// ||||___ X-XSS-Protection [0-1]
	// |||____ X-Frame-Options [0-2]
	// ||_____ X-Content-Type-Options [0-1]
	// |______ HttpOnly cookies [0-1]

	$rewrite = array();

	if ($NFW_RESHEADERS[0] == 1) {
		// Parse all response headers :
		foreach (@headers_list() as $header) {
			// Ignore it if it is not a cookie :
			if (strpos($header, 'Set-Cookie:') === false) { continue; }
			// Does it have the HttpOnly flag on ?
			if (stripos($header, '; httponly') !== false) {
				// Save it...
				$rewrite[] = $header;
				// and check next header :
				continue;
			}
			// Append the HttpOnly flag and save it :
			$rewrite[] = $header . '; httponly';
		}
		// Shall we rewrite cookies ?
		if (! empty($rewrite) ) {
			// Remove all original cookies first:
			@header_remove('Set-Cookie');
			foreach($rewrite as $cookie) {
				// Inject ours instead :
				header($cookie, false);
			}
		}
	}

	if ($NFW_RESHEADERS[1] == 1) {
		header('X-Content-Type-Options: nosniff');
	}

	if ($NFW_RESHEADERS[2] == 1) {
		header('X-Frame-Options: SAMEORIGIN');
	} elseif ($NFW_RESHEADERS[2] == 2) {
		header('X-Frame-Options: DENY');
	}

	if ($NFW_RESHEADERS[3] == 1) {
		header('X-XSS-Protection: 1; mode=block');
	}

	// We don't send HSTS headers over HTTP :
	if ( $_SERVER['SERVER_PORT'] != 443 &&
	(! isset( $_SERVER['HTTP_X_FORWARDED_PROTO']) ||
	$_SERVER['HTTP_X_FORWARDED_PROTO'] != 'https') ) {
		return;
	}
	if ($NFW_RESHEADERS[4] == 0) {
		// Send an empty max-age to signal the UA to
		// cease regarding the host as a known HSTS Host :
		$max_age = 'max-age=0';
	} else {
		if ($NFW_RESHEADERS[4] == 1) {
			// 1 month :
			$max_age = 'max-age=2628000';
		} elseif ($NFW_RESHEADERS[4] == 2) {
			// 6 months :
			$max_age = 'max-age=15768000';
		} else {
			// 12 months
			$max_age = 'max-age=31536000';
		}
		if ($NFW_RESHEADERS[5] == 1) {
			$max_age .= ' ; includeSubDomains';
		}
	}
	header('Strict-Transport-Security: '. $max_age);
}

// =====================================================================
// EOF
