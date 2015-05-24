<?php
/*
Plugin Name: NinjaFirewall (WP edition)
Plugin URI: http://NinjaFirewall.com/
Description: A true Web Application Firewall.
Version: 1.4.2
Author: The Ninja Technologies Network
Author URI: http://NinTechNet.com/
License: GPLv2 or later
Network: true
Text Domain: ninjafirewall
*/

/*
 +---------------------------------------------------------------------+
 | NinjaFirewall (WP edition)                                          |
 |                                                                     |
 | (c) NinTechNet - http://nintechnet.com/                             |
 +---------------------------------------------------------------------+
 | REVISION: 2015-05-23 18:14:38                                       |
 +---------------------------------------------------------------------+
*/
define( 'NFW_ENGINE_VERSION', '1.4.2' );
define( 'NFW_RULES_VERSION',  '20150522.1' );
 /*
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

if (! defined( 'ABSPATH' ) ) { die( 'Forbidden' ); }

if (! headers_sent() ) {
	if (version_compare(PHP_VERSION, '5.4', '<') ) {
		if (! session_id() ) {
			session_start();
			$_SESSION['nfw_st'] = 1;
		}
	} else {
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
			$_SESSION['nfw_st'] = 2;
		}
	}
}

/* ------------------------------------------------------------------ */

// Some constants & variables first :
define('NFI18N', 'ninjafirewall');
define('NFW_NULL_BYTE', 2);
define('NFW_SCAN_BOTS', 531);
define('NFW_ASCII_CTRL', 500);
define('NFW_DOC_ROOT', 510);
define('NFW_WRAPPERS', 520);
define('NFW_LOOPBACK', 540);
$err_fw = array(
	1	=> __('Cannot find WordPress configuration file'),
	2	=>	__('Cannot read WordPress configuration file'),
	3	=>	__('Cannot retrieve WordPress database credentials'),
	4	=>	__('Cannot connect to WordPress database'),
	5	=>	__('Cannot retrieve user options from database (#2)'),
	6	=>	__('Cannot retrieve user options from database (#3)'),
	7	=>	__('Cannot retrieve user rules from database (#2)'),
	8	=>	__('Cannot retrieve user rules from database (#3)'),
	9	=>	__('The firewall has been disabled from the <a href="admin.php?page=nfsubopt">administration console</a>'),
	10	=> __('Unable to communicate with the firewall. Please check your PHP INI settings'),
	11	=>	__('Cannot retrieve user options from database (#1)'),
	12	=>	__('Cannot retrieve user rules from database (#1)'),
	13 => sprintf( __("The firewall cannot access its log and cache folders. If you changed the name of WordPress <code>/wp-content/</code> or <code>/plugins/</code> folders, you <b>must</b> define NinjaFirewall's built-in <code>NFW_LOG_DIR</code> constant (see %s for more info)"), "<a href='http://ninjafirewall.com/wordpress/htninja/#nfwlogdir' target='_blank'>Path to NinjaFirewall's log and cache directory</a>"),
);

if (! defined('NFW_LOG_DIR') ) {
	define('NFW_LOG_DIR', WP_CONTENT_DIR);
}

/* ------------------------------------------------------------------ */

require( plugin_dir_path(__FILE__) . 'lib/nfw_misc.php' );

/* ------------------------------------------------------------------ */

function nfw_activate() {

	// Install/activate NinjaFirewall :

	// Block immediately if user is not allowed :
	nf_not_allowed( 'block', __LINE__ );

	// We need at least WP 3.3 :
	global $wp_version;
	if ( version_compare( $wp_version, '3.3', '<' ) ) {
		exit( "NinjaFirewall requires <strong>WordPress 3.3 or " . "
		greater</strong> but your current version is " . $wp_version );
	}

	// We need at least PHP 5.3 :
	if ( version_compare( PHP_VERSION, '5.3.0', '<' ) ) {
		exit( "NinjaFirewall requires <strong>PHP 5.3 or greater</strong> " . "
		but your current version is " . PHP_VERSION );
	}

	// We need the mysqli extension loaded :
	if (! function_exists('mysqli_connect') ) {
		exit( sprintf( __('NinjaFirewall requires the PHP %s extension.'), '<code>mysqli</code>') );
	}

	// Yes, there are still some people who have SAFE_MODE enabled with
	// PHP 5.3 ! We must check that right away otherwise the user may lock
	// himself/herself out of the site as soon as NinjaFirewall will be
	// activated :
	if ( ini_get( 'safe_mode' ) ) {
		exit( "You have SAFE_MODE enabled. Please <strong>disable it</strong>, " .
		"it is deprecated as of PHP 5.3.0 (see http://php.net/safe-mode)" );
	}

	// Multisite installation requires superadmin privileges :
	if ( ( is_multisite() ) && (! current_user_can( 'manage_network' ) ) ) {
		exit( "You are not allowed to activate NinjaFirewall.");
	}

	// We don't do Windows :
	if ( PATH_SEPARATOR == ';' ) {
		exit( "NinjaFirewall is not compatible with Windows." );
	}

	// If already installed/setup, just enable the firewall... :
	if ( $nfw_options = get_option( 'nfw_options' ) ) {
		$nfw_options['enabled'] = 1;
		update_option( 'nfw_options', $nfw_options);

		// Re-enable scheduled scan, if needed :
		if (! empty($nfw_options['sched_scan']) ) {
			if ($nfw_options['sched_scan'] == 1) {
				$schedtype = 'hourly';
			} elseif ($nfw_options['sched_scan'] == 2) {
				$schedtype = 'twicedaily';
			} else {
				$schedtype = 'daily';
			}
			if ( wp_next_scheduled('nfscanevent') ) {
				wp_clear_scheduled_hook('nfscanevent');
			}
			wp_schedule_event( time() + 3600, $schedtype, 'nfscanevent');
		}
		// Re-enable auto updates, if needed :
		if (! empty($nfw_options['enable_updates']) ) {
			if ($nfw_options['sched_updates'] == 1) {
				$schedtype = 'hourly';
			} elseif ($nfw_options['sched_updates'] == 2) {
				$schedtype = 'twicedaily';
			} else {
				$schedtype = 'daily';
			}
			if ( wp_next_scheduled('nfsecupdates') ) {
				wp_clear_scheduled_hook('nfsecupdates');
			}
			wp_schedule_event( time() + 90, $schedtype, 'nfsecupdates');
		}
		// Re-enable brute-force protection :
		if ( file_exists( NFW_LOG_DIR . '/nfwlog/cache/bf_conf_off.php' ) ) {
			rename(NFW_LOG_DIR . '/nfwlog/cache/bf_conf_off.php', NFW_LOG_DIR . '/nfwlog/cache/bf_conf.php');
		}

		// ...and whitelist the admin if needed :
		if (! empty( $nfw_options['wl_admin']) ) {
			$_SESSION['nfw_goodguy'] = true;
		}
	}
}

register_activation_hook( __FILE__, 'nfw_activate' );

/* ------------------------------------------------------------------ */

function nfw_deactivate() {

	// Block immediately if user is not allowed :
	nf_not_allowed( 'block', __LINE__ );

	// Disable the firewall (NinjaFirewall will keep running
	// in the background but will not do anything) :
	$nfw_options = get_option( 'nfw_options' );
	$nfw_options['enabled'] = 0;

	// Clear scheduled scan (if any) :
	if ( wp_next_scheduled('nfscanevent') ) {
		wp_clear_scheduled_hook('nfscanevent');
	}
	// Clear auto updates (if any) :
	if ( wp_next_scheduled('nfsecupdates') ) {
		wp_clear_scheduled_hook('nfsecupdates');
	}
	// and disable brute-force protection :
	if ( file_exists( NFW_LOG_DIR . '/nfwlog/cache/bf_conf.php' ) ) {
		rename(NFW_LOG_DIR . '/nfwlog/cache/bf_conf.php', NFW_LOG_DIR . '/nfwlog/cache/bf_conf_off.php');
	}

	update_option( 'nfw_options', $nfw_options);

}

register_deactivation_hook( __FILE__, 'nfw_deactivate' );

/* ------------------------------------------------------------------ */

function nfw_upgrade() {	//i18n

	// Only used when upgrading NinjaFirewall, sending alerts
	// and exporting/downloading files :

	// Return immediately if user is not allowed :
	if ( nf_not_allowed(0, __LINE__) ) { return; }

	$is_update = 0;

	$nfw_options = get_option( 'nfw_options' );
	$nfw_rules = get_option( 'nfw_rules' );

	// Export configuration :
	if ( isset($_POST['nf_export']) ) {
		if ( empty($_POST['nfwnonce']) || ! wp_verify_nonce($_POST['nfwnonce'], 'options_save') ) {
			wp_nonce_ays('options_save');
		}
		// Export login protection if it exists too :
		$nfwbfd_log = NFW_LOG_DIR . '/nfwlog/cache/bf_conf.php';
		if ( file_exists($nfwbfd_log) ) {
			$bd_data = serialize( file_get_contents($nfwbfd_log) );
		} else {
			$bd_data = '';
		}
		$data = serialize($nfw_options) . "\n:-:\n" . serialize($nfw_rules) . "\n:-:\n" . $bd_data;
		// Download :
		header('Content-Type: application/txt');
		header('Content-Length: '. strlen( $data ) );
		header('Content-Disposition: attachment; filename="nfwp.' . NFW_ENGINE_VERSION . '.dat"');
		echo $data;
		exit;
	}

	// Download File Check modified files list :
	if ( isset($_POST['dlmods']) ) {
		if ( empty($_POST['nfwnonce']) || ! wp_verify_nonce($_POST['nfwnonce'], 'filecheck_save') ) {
			wp_nonce_ays('filecheck_save');
		}
		if (file_exists(NFW_LOG_DIR . '/nfwlog/cache/nfilecheck_diff.php') ) {
			$stat = stat(NFW_LOG_DIR . '/nfwlog/cache/nfilecheck_diff.php');
			nfw_get_blogtimezone();
			$data = '== NinjaFirewall File Check (diff)'. "\n";
			$data.= '== ' . site_url() . "\n";
			$data.= '== ' . date_i18n('M d, Y @ H:i:s O', $stat['ctime']) . "\n\n";
			$data.= '[+] = ' . __('New file') .
						'      [-] = ' . __('Deleted file') .
						'      [!] = ' . __('Modified file') .
						"\n\n";
			$fh = fopen(NFW_LOG_DIR . '/nfwlog/cache/nfilecheck_diff.php', 'r');
			while (! feof($fh) ) {
				$res = explode('::', fgets($fh) );
				if ( empty($res[1]) ) { continue; }
				// New file :
				if ($res[1] == 'N') {
					$data .= '[+] ' . $res[0] . "\n";
				// Deleted file :
				} elseif ($res[1] == 'D') {
					$data .= '[-] ' . $res[0] . "\n";
				// Modified file:
				} elseif ($res[1] == 'M') {
					$data .= '[!] ' . $res[0] . "\n";
				}
			}
			fclose($fh);
			$data .= "\n== EOF\n";

			// Download :
			header('Content-Type: application/txt');
			header('Content-Length: '. strlen( $data ) );
			header('Content-Disposition: attachment; filename="'. $_SERVER['SERVER_NAME'] .'_diff.txt"');
			echo $data;
			exit;
		}
	}

	// Download File Check snapshot :
	if ( isset($_POST['dlsnap']) ) {
		if ( empty($_POST['nfwnonce']) || ! wp_verify_nonce($_POST['nfwnonce'], 'filecheck_save') ) {
			wp_nonce_ays('filecheck_save');
		}
		if (file_exists(NFW_LOG_DIR . '/nfwlog/cache/nfilecheck_snapshot.php') ) {
			$stat = stat(NFW_LOG_DIR . '/nfwlog/cache/nfilecheck_snapshot.php');
			nfw_get_blogtimezone();
			$data = '== NinjaFirewall File Check (snapshot)'. "\n";
			$data.= '== ' . site_url() . "\n";
			$data.= '== ' . date_i18n('M d, Y @ H:i:s O', $stat['ctime']) . "\n\n";
			$fh = fopen(NFW_LOG_DIR . '/nfwlog/cache/nfilecheck_snapshot.php', 'r');
			while (! feof($fh) ) {
				$res = explode('::', fgets($fh) );
				if (! empty($res[0][0]) && $res[0][0] == '/') {
					$data .= $res[0] . "\n";
				}
			}
			fclose($fh);
			$data .= "\n== EOF\n";
			// Download :
			header('Content-Type: application/txt');
			header('Content-Length: '. strlen( $data ) );
			header('Content-Disposition: attachment; filename="'. $_SERVER['SERVER_NAME'] .'_snapshot.txt"');
			echo $data;
			exit;
		}
	}

	// update engine version number if needed :
	if (! empty($nfw_options['engine_version']) && version_compare($nfw_options['engine_version'], NFW_ENGINE_VERSION, '<') ) {
		// v1.0.4 update -------------------------------------------------
		if ( empty( $nfw_options['alert_email']) ) {
			$nfw_options['a_0']  = 1; $nfw_options['a_11'] = 1;
			$nfw_options['a_12'] = 1; $nfw_options['a_13'] = 0;
			$nfw_options['a_14'] = 0; $nfw_options['a_15'] = 1;
			$nfw_options['a_16'] = 0; $nfw_options['a_21'] = 1;
			$nfw_options['a_22'] = 1; $nfw_options['a_23'] = 0;
			$nfw_options['a_24'] = 0; $nfw_options['a_31'] = 1;
			$nfw_options['alert_email'] = get_option('admin_email');
		}
		// v1.1.0 update -------------------------------------------------
		if (! isset( $nfw_options['post_b64'] ) ) {
			$nfw_options['alert_sa_only']  = 2;
			$nfw_options['nt_show_status'] = 1;
			$nfw_options['post_b64']       = 1;
		}
		// v1.1.2 update -------------------------------------------------
		if (! isset( $nfw_options['no_xmlrpc'] ) ) {
			$nfw_options['no_xmlrpc'] = 0;
		}
		// v1.1.3 update -------------------------------------------------
		if (! isset( $nfw_options['enum_archives'] ) ) {
			$nfw_options['enum_archives'] = 0;
			$nfw_options['enum_login'] = 1;
		}
		// v1.1.6 update -------------------------------------------------
		if (! isset( $nfw_options['request_sanitise'] ) ) {
			$nfw_options['request_sanitise'] = 0;
		}
		// v1.1.9 update -------------------------------------------------
		if ( empty( $nfw_options['logo']) ) {
			$nfw_options['logo'] = plugins_url() . '/ninjafirewall/images/ninjafirewall_75.png';
		}
		// v1.2.1 update -------------------------------------------------
		if ( empty( $nfw_options['fg_mtime']) ) {
			$nfw_options['fg_enable'] = 0;
			$nfw_options['fg_mtime'] = 10;
		}
		// v1.2.3 update -------------------------------------------------
		if ( version_compare( $nfw_options['engine_version'], '1.2.3', '<' ) ) {
			$nfw_options['blocked_msg'] = base64_encode($nfw_options['blocked_msg']);
		}
		// v1.2.4 update -------------------------------------------------
		// Error from v1.2.3 to delete :
		if ( isset($nfw_options['$auth_msg']) ) {
			unset($nfw_options['$auth_msg']);
		}
		// v1.2.7 update -------------------------------------------------
		if ( version_compare( $nfw_options['engine_version'], '1.2.7', '<' ) ) {
			// Create 'wp-content/nfwlog/' directories and files :
			if ( is_writable(NFW_LOG_DIR) ) {
				if (! file_exists(NFW_LOG_DIR . '/nfwlog') ) {
					mkdir( NFW_LOG_DIR . '/nfwlog', 0755);
				}
				if (! file_exists(NFW_LOG_DIR . '/nfwlog/cache') ) {
					mkdir( NFW_LOG_DIR . '/nfwlog/cache', 0755);
				}
				touch( NFW_LOG_DIR . '/nfwlog/index.html' );
				touch( NFW_LOG_DIR . '/nfwlog/cache/index.html' );
				@file_put_contents(NFW_LOG_DIR . '/nfwlog/.htaccess', "Order Deny,Allow\nDeny from all", LOCK_EX);
				@file_put_contents(NFW_LOG_DIR . '/nfwlog/cache/.htaccess', "Order Deny,Allow\nDeny from all", LOCK_EX);

				// Restore brute-force protection configuration from the DB:
				$nfwbfd_log = NFW_LOG_DIR . '/nfwlog/cache/bf_conf.php';
				if ((! empty($nfw_options['bf_request'])) && (! empty($nfw_options['bf_bantime'])) &&
					 (! empty($nfw_options['bf_attempt'])) && (! empty($nfw_options['bf_maxtime'])) &&
					 (! empty($nfw_options['auth_name'])) && (! empty($nfw_options['auth_pass'])) &&
					 (! empty($nfw_options['bf_rand'])) ) {
					if ( empty($nfw_options['bf_enable'])) {
						$nfw_options['bf_enable'] = 1;
					}
					if ( empty($nfw_options['auth_msg']) ) {
						$nfw_options['auth_msg'] = 'Access restricted';
					}
					// xmlrpc option (added to v1.2.3) :
					if (! isset($nfw_options['bf_xmlrpc']) ) {
						$nfw_options['bf_xmlrpc'] = 0;
					}
					// AUTH log (added to v1.2.6) :
					if (! isset($nfw_options['bf_authlog']) ) {
						$nfw_options['bf_authlog'] = 0;
					}
					$data = '<?php $bf_enable=' . $nfw_options['bf_enable'] .
					';$bf_request=\'' . $nfw_options['bf_request'] . '\'' .
					';$bf_bantime=' . $nfw_options['bf_bantime'] .
					';$bf_attempt=' . $nfw_options['bf_attempt'] .
					';$bf_maxtime=' . $nfw_options['bf_maxtime'] .
					';$bf_xmlrpc=' . $nfw_options['bf_xmlrpc'] .
					';$auth_name=\'' . $nfw_options['auth_name'] . '\'' .
					';$auth_pass=\'' . $nfw_options['auth_pass'] . '\';' .
					'$auth_msg=\'' . $nfw_options['auth_msg'] . '\'' .
					';$bf_rand=\'' . $nfw_options['bf_rand'] . '\';'.
					'$bf_authlog='. $nfw_options['bf_authlog'] . '; ?>';
					$fh = fopen( $nfwbfd_log, 'w' );
					fwrite( $fh, $data );
					fclose( $fh );
				}
			}
			// We don't need to backup the brute-force protection data to the DB anymore
			// because we're now using the new log/cache directory in the wp-content folder:
			unset($nfw_options['bf_enable']);
			unset($nfw_options['bf_request']);
			unset($nfw_options['bf_bantime']);
			unset($nfw_options['bf_attempt']);
			unset($nfw_options['bf_maxtime']);
			unset($nfw_options['bf_xmlrpc']);
			unset($nfw_options['auth_name']);
			unset($nfw_options['auth_pass']);
			unset($nfw_options['auth_msg']);
			unset($nfw_options['bf_rand']);
			unset($nfw_options['bf_authlog']);
		}
		// v1.3.1 update -------------------------------------------------
		if ( version_compare( $nfw_options['engine_version'], '1.3.1', '<' ) ) {
			if ( function_exists('header_register_callback') && function_exists('headers_list') && function_exists('header_remove') ) {
				$nfw_options['response_headers'] = '000000';
			}
		}
		// v1.3.3 update -------------------------------------------------
		if ( version_compare( $nfw_options['engine_version'], '1.3.3', '<' ) ) {
			$nfw_options['a_41'] = 1;
			$nfw_options['sched_scan'] = 0;
			$nfw_options['report_scan'] = 0;
		}
		// v1.3.4 update -------------------------------------------------
		if ( version_compare( $nfw_options['engine_version'], '1.3.4', '<' ) ) {
			$nfw_options['a_51'] = 1;
		}
		// v1.3.5 update -------------------------------------------------
		if ( version_compare( $nfw_options['engine_version'], '1.3.5', '<' ) ) {
			$nfw_options['fg_exclude'] = '';
		}
		// v1.3.6 update -------------------------------------------------
		if ( version_compare( $nfw_options['engine_version'], '1.3.6', '<' ) ) {
			// Remove all old nfdbhash* files :
			$path = NFW_LOG_DIR . '/nfwlog/cache/';
			$glob = glob($path . "nfdbhash*php");
			if ( is_array($glob)) {
				foreach($glob as $file) {
					unlink($file);
				}
			}
		}
		// ---------------------------------------------------------------

		$nfw_options['engine_version'] = NFW_ENGINE_VERSION;
		$is_update = 1;
	}

	// do we need to update rules as well ?
	if (! empty($nfw_options['rules_version']) && version_compare($nfw_options['rules_version'], NFW_RULES_VERSION, '<') ) {
		// fetch new set of rules :
		$_REQUEST['nfw_act'] = 'x';
		require_once( plugin_dir_path(__FILE__) . 'install.php' );
		$nfw_rules_new = unserialize( nfw_default_rules() );

		foreach ( $nfw_rules_new as $new_key => $new_value ) {
			foreach ( $new_value as $key => $value ) {
				// if that rule exists already, we keep its 'on' flag value
				// as it may have been changed by the user with the rules editor :
				if ( ( isset( $nfw_rules[$new_key]['on'] ) ) && ( $key == 'on' ) ) {
					$nfw_rules_new[$new_key]['on'] = $nfw_rules[$new_key]['on'];
				}
			}
		}
		$nfw_rules_new[NFW_DOC_ROOT]['what']= $nfw_rules[NFW_DOC_ROOT]['what'];
		$nfw_rules_new[NFW_DOC_ROOT]['on']	= $nfw_rules[NFW_DOC_ROOT]['on'];

		// v1.2.7:20140925 update ----------------------------------------
		// We delete rules #151 and #152
		if ( version_compare( $nfw_options['rules_version'], '20140925', '<' ) ) {
			if ( isset($nfw_rules_new[151]) ) {
				unset($nfw_rules_new[151]);
			}
			if ( isset($nfw_rules_new[152]) ) {
				unset($nfw_rules_new[152]);
			}
		}
		// ---------------------------------------------------------------

		// update rules... :
		update_option( 'nfw_rules', $nfw_rules_new);
		// ...and rules version number :
		$nfw_options['rules_version'] = NFW_RULES_VERSION;
		$is_update = 1;
	}

	if ( $is_update ) {
		$tmp_data = '';
		// up to v1.2.7  -------------------------------------------------
		if ( version_compare( $nfw_options['engine_version'], '1.2.8', '<' ) ) {
			// Check if we need to restore the log which was saved to the DB
			// before starting NinjaFirewall's update :
			if ( isset($nfw_options['nfw_tmp']) ) {
				unset( $nfw_options['nfw_tmp'] );
				// Fetch it, unpack it, and save it to disk...
				$log_file = NFW_LOG_DIR . '/nfwlog/firewall_' . date( 'Y-m' ) . '.php';
				if ( $tmp_data = @gzinflate( base64_decode( get_option('nfw_tmp') ) ) ) {
					@file_put_contents( $log_file, $tmp_data, LOCK_EX);
				}
				// ... and clear it from the DB :
				delete_option( 'nfw_tmp' );
			}
			if ( $tmp_data ) {
				// Try to re-create the widget stats file :
				$stat_file = NFW_LOG_DIR . '/nfwlog/stats_' . date( 'Y-m' ) . '.php';
				$nfw_stat = array('0', '0', '0', '0', '0', '0', '0', '0', '0', '0');
				$stats_lines = explode( PHP_EOL, $tmp_data );
				foreach ( $stats_lines as $line ) {
					if (preg_match( '/^\[.+?\]\s+\[.+?\]\s+(?:\[.+?\]\s+){3}\[([0-9])\]/', $line, $match) ) {
						$nfw_stat[$match[1]]++;
					}
				}
				@file_put_contents( $stat_file, $nfw_stat[0] . ':' . $nfw_stat[1] . ':' .
					$nfw_stat[2] . ':' . $nfw_stat[3] . ':' . $nfw_stat[4] . ':' .
					$nfw_stat[5] . ':' . $nfw_stat[6] . ':' . $nfw_stat[7] . ':' .
					$nfw_stat[8] . ':' . $nfw_stat[9], LOCK_EX );
			}
		}

		// Update options :
		update_option( 'nfw_options', $nfw_options);
	}

	// E-mail alert ?
	if ( defined( 'NFW_ALERT' ) ) {
		nfw_check_emailalert();
	}

	// If admin is whitelisted, update the goodguy flag (helps to avoid
	// potential session timeout) :
	if (! empty( $nfw_options['wl_admin']) ) {
		$_SESSION['nfw_goodguy'] = true;
		return;
	}
	// clear it otherwise :
	if ( isset( $_SESSION['nfw_goodguy'] ) ) {
		unset( $_SESSION['nfw_goodguy'] );
	}
}

add_action('admin_init', 'nfw_upgrade' );

/* ------------------------------------------------------------------ */

function nfw_login_hook( $user_login, $user ) {

	// Check if the user is an admin and if we must whitelist him/her :

	$nfw_options = get_option( 'nfw_options' );

	// Don't do anything if NinjaFirewall is disabled :
	if ( empty( $nfw_options['enabled'] ) ) { return; }

	if ( empty( $user->roles[0] ) ) {
		// This can occur in multisite mode, when the Super Admin logs in
		// to the admin console of a child site but is not in the users
		// list of that site :
		$whoami = 'not in users list';
		$admin_flag = 1;
	} elseif ( $user->roles[0] == 'administrator' ) {
		$whoami = 'administrator';
		$admin_flag = 2;
	} else {
		$whoami = $user->roles[0];
		$admin_flag = 0;
	}

	// Are we supposed to send an alert ?
	if (! empty($nfw_options['a_0']) ) {
		// User login:
		if ( ( ( $nfw_options['a_0'] == 1) && ( $admin_flag )  ) ||	( $nfw_options['a_0'] == 2 ) ) {
			nfw_send_loginemail( $user_login, $whoami );
			if (! empty($nfw_options['a_41']) ) {
				nfw_log2( __('Logged in user'), $user_login .' ('. $whoami .')', 6, 0);
			}
		}
	}

	// Do some housework if needed :
	nfw_housework();

	if ( $admin_flag == 2 ) {
		if (! empty( $nfw_options['wl_admin']) ) {
			// Set the goodguy flag :
			$_SESSION['nfw_goodguy'] = true;
			return;
		}
	}
	if ( isset( $_SESSION['nfw_goodguy'] ) ) {
		unset( $_SESSION['nfw_goodguy'] );
	}
}

add_action( 'wp_login', 'nfw_login_hook', 10, 2 );
/* ------------------------------------------------------------------ */

function nfw_housework() {

	// Clean/delete cache folder & temp files :

	$nfw_options = get_option( 'nfw_options' );

	// File Guard temp files :
	if (! empty( $nfw_options['fg_enable']) ) {
		$path = NFW_LOG_DIR . '/nfwlog/cache/';
		$glob = glob($path . "fg_*.php");
		if ( is_array($glob)) {
			foreach($glob as $file) {
				$stat = stat( $file );
				// Delete it if is too old :
				if ( time() - $nfw_options['fg_mtime'] * 3660 > $stat['ctime'] ) {
					unlink($file);
				}
			}
		}
	}
}
/* ------------------------------------------------------------------ */

function nfw_send_loginemail( $user_login, $whoami ) {

	$nfw_options = get_option( 'nfw_options' );

	if ( ( is_multisite() ) && ( $nfw_options['alert_sa_only'] == 2 ) ) {
		$recipient = get_option('admin_email');
	} else {
		$recipient = $nfw_options['alert_email'];
	}

	// Get timezone :
	nfw_get_blogtimezone();

	if ( is_multisite() ) {
		$url = '- URL  : ' . network_site_url() . "\n";
	} else {
		$url = '- URL  : ' . site_url() . "\n";
	}

	$subject = '[NinjaFirewall] Alert: WordPress console login';
	$message = 'Someone just logged in to your WordPress admin console:' . "\n\n".
				'- User : ' . $user_login . ' (' . $whoami . ")\n" .
				'- IP   : ' . $_SERVER['REMOTE_ADDR'] . "\n" .
				$url .
				'- Date : ' . date('F j, Y @ H:i:s') . ' (UTC '. date('O') . ")\n\n" .
				'NinjaFirewall (WP edition) - http://ninjafirewall.com/' . "\n" .
				'Support forum: http://wordpress.org/support/plugin/ninjafirewall' . "\n";
	wp_mail( $recipient, $subject, $message );

}
/* ------------------------------------------------------------------ */

function nfw_logout_hook() {

	// Whoever it was, we clear the goodguy flag :
	if ( isset( $_SESSION['nfw_goodguy'] ) ) {
		unset( $_SESSION['nfw_goodguy'] );
	}
	// And the Live Log flag as well :
	if (isset($_SESSION['nfw_livelog']) ) {
		unset($_SESSION['nfw_livelog']);
	}
}

add_action( 'wp_logout', 'nfw_logout_hook' );

/* ------------------------------------------------------------------ */

function is_nfw_enabled() {

	// Checks whether NF is enabled and/or active and/or debugging mode :

	$nfw_options = get_option( 'nfw_options' );

	// Check whether NF is running.

	// No communication from the firewall :
	if (! defined('NFW_STATUS') ) {
		define('NF_DISABLED', 10);
		return;
	}

	// NF was disabled by the admin :
	if ( isset($nfw_options['enabled']) && $nfw_options['enabled'] == '0' ) {
		define('NF_DISABLED', 9);
		return;
	}

	// There is another instance of NinjaFirewall firewall running,
	// maybe in the parent directory:
	if (NFW_STATUS == 21 || NFW_STATUS == 22 || NFW_STATUS == 23) {
		define('NF_DISABLED', 10);
		return;
	}

	// OK :
	if (NFW_STATUS == 20) {
		define('NF_DISABLED', 0);
		return;
	}

	// Error :
	define('NF_DISABLED', NFW_STATUS);
	return;

}

/* ------------------------------------------------------------------ */

function ninjafirewall_admin_menu() {

	// Return immediately if user is not allowed :
	if (nf_not_allowed( 0, __LINE__ ) ) { return; }

	// Display phpinfo for the installer :
	if (! empty($_REQUEST['nfw_act']) && $_REQUEST['nfw_act'] == 99) {
		if ( empty($_GET['nfwnonce']) || ! wp_verify_nonce($_GET['nfwnonce'], 'show_phpinfo') ) {
			wp_nonce_ays('show_phpinfo');
		}
		phpinfo(33);
		exit;
	}

	define( 'NFW_DEFAULT_MSG', '<br /><br /><br /><br /><center>Sorry <b>%%REM_ADDRESS%%</b>, ' .
		'your request cannot be proceeded.<br />For security reason, it was blocked and logged.' .
		'<br /><br />%%NINJA_LOGO%%<br /><br />If you think that was a mistake, please contact the<br />' .
		'webmaster and enclose the following incident ID:<br /><br />[ <b>#%%NUM_INCIDENT%%</b> ]</center>'
	);

	// Setup our admin menus :

	if (! defined('NF_DISABLED') ) {
		is_nfw_enabled();
	}

	// Run the install process if not installed yet :
	if (NF_DISABLED == 10) {
		add_menu_page( 'NinjaFirewall', 'NinjaFirewall', 'manage_options',
			'NinjaFirewall', 'nf_menu_install',	plugins_url( '/images/nf_icon.png', __FILE__ )
		);
		add_submenu_page( 'NinjaFirewall', 'Installation', 'Installation', 'manage_options',
			'NinjaFirewall', 'nf_menu_install' );
		return;
	}

	// Our main menu :
	add_menu_page( 'NinjaFirewall', 'NinjaFirewall', 'manage_options',
		'NinjaFirewall', 'nf_menu_main',	plugins_url( '/images/nf_icon.png', __FILE__ )
	);

	// All our submenus :
	global $menu_hook;

	// Admin menus contextual help :
	require_once( plugin_dir_path(__FILE__) . 'help.php' );

	// Overview menu :
	$menu_hook = add_submenu_page( 'NinjaFirewall', 'NinjaFirewall: Overview', 'Overview', 'manage_options',
		'NinjaFirewall', 'nf_menu_main' );
	add_action( 'load-' . $menu_hook, 'help_nfsubmain' );

	// Stats menu :
	$menu_hook = add_submenu_page( 'NinjaFirewall', 'NinjaFirewall: Statistics', 'Statistics', 'manage_options',
		'nfsubstat', 'nf_sub_statistics' );
	add_action( 'load-' . $menu_hook, 'help_nfsubstat' );

	// Firewall options menu :
	$menu_hook = add_submenu_page( 'NinjaFirewall', 'NinjaFirewall: Firewall Options', 'Firewall Options', 'manage_options',
		'nfsubopt', 'nf_sub_options' );
	add_action( 'load-' . $menu_hook, 'help_nfsubopt' );

	// Firewall policies menu :
	$menu_hook = add_submenu_page( 'NinjaFirewall', 'NinjaFirewall: Firewall Policies', 'Firewall Policies', 'manage_options',
		'nfsubpolicies', 'nf_sub_policies' );
	add_action( 'load-' . $menu_hook, 'help_nfsubpolicies' );

	// File Guard menu :
	$menu_hook = add_submenu_page( 'NinjaFirewall', 'NinjaFirewall: File Guard', 'File Guard', 'manage_options',
		'nfsubfileguard', 'nf_sub_fileguard' );
	add_action( 'load-' . $menu_hook, 'help_nfsubfileguard' );

	// Network menu (multisite only) :
	$menu_hook = add_submenu_page( 'NinjaFirewall', 'NinjaFirewall: Network', 'Network', 'manage_network',
		'nfsubnetwork', 'nf_sub_network' );
	add_action( 'load-' . $menu_hook, 'help_nfsubnetwork' );

	// File Check menu :
	$menu_hook = add_submenu_page( 'NinjaFirewall', 'NinjaFirewall: File Check', 'File Check', 'manage_options',
		'nfsubfilecheck', 'nf_sub_filecheck' );
	add_action( 'load-' . $menu_hook, 'help_nfsubfilecheck' );

	// Event Notifications menu :
	$menu_hook = add_submenu_page( 'NinjaFirewall', 'NinjaFirewall: Event Notifications', 'Event Notifications', 'manage_options',
		'nfsubevent', 'nf_sub_event' );
	add_action( 'load-' . $menu_hook, 'help_nfsubevent' );

	// Login protection menu :
	$menu_hook = add_submenu_page( 'NinjaFirewall', 'NinjaFirewall: Log-in Protection', 'Login Protection', 'manage_options',
		'nfsubloginprot', 'nf_sub_loginprot' );
	add_action( 'load-' . $menu_hook, 'help_nfsublogin' );

	// Firewall log menu :
	$menu_hook = add_submenu_page( 'NinjaFirewall', 'NinjaFirewall: Firewall Log', 'Firewall Log', 'manage_options',
		'nfsublog', 'nf_sub_log' );
	add_action( 'load-' . $menu_hook, 'help_nfsublog' );

	// Live log menu :
	$menu_hook = add_submenu_page( 'NinjaFirewall', 'NinjaFirewall: Live Log', 'Live Log', 'manage_options',
		'nfsublive', 'nf_sub_live' );
	add_action( 'load-' . $menu_hook, 'help_nfsublivelog' );

	// Rules Editor menu :
	$menu_hook = add_submenu_page( 'NinjaFirewall', 'NinjaFirewall: Rules Editor', 'Rules Editor', 'manage_options',
		'nfsubedit', 'nf_sub_edit' );
	add_action( 'load-' . $menu_hook, 'help_nfsubedit' );

	// Updates menu :
	$menu_hook = add_submenu_page( 'NinjaFirewall', 'NinjaFirewall: Updates', 'Updates', 'manage_options',
		'nfsubupdates', 'nf_sub_updates' );
	add_action( 'load-' . $menu_hook, 'help_nfsubupdates' );

	// WP+ menu :
	$menu_hook = add_submenu_page( 'NinjaFirewall', 'NinjaFirewall: WP+ Edition', 'WP+ Edition', 'manage_options',
		'nfsubwplus', 'nf_sub_wplus' );

	// About menu :
	$menu_hook = add_submenu_page( 'NinjaFirewall', 'NinjaFirewall: About', 'About...', 'manage_options',
		'nfsubabout', 'nf_sub_about' );
	add_action( 'load-' . $menu_hook, 'help_nfsubabout' );

}

if (! is_multisite() )  {
	add_action( 'admin_menu', 'ninjafirewall_admin_menu' );
} else {
	// In multisite mode, menu is only available to the Super Admin:
	add_action( 'network_admin_menu', 'ninjafirewall_admin_menu' );
}

/* ------------------------------------------------------------------ */

function nf_admin_bar_status() {

	// Display the status icon to administrators (multi-site mode only) :
	if (! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Check whether the option is enabled or not :
	$nfw_options = get_option( 'nfw_options' );
	// Disable it, unless this is the superadmin :
	if ( @$nfw_options['nt_show_status'] != 1 && ! current_user_can('manage_network') ) {
		return;
	}

	// Obviously, we don't put any icon if NinjaFirewall isn't running :
	if (! defined('NF_DISABLED') ) {
		is_nfw_enabled();
	}
	if (NF_DISABLED) { return; }

	global $wp_admin_bar;
	$wp_admin_bar->add_menu( array(
		'id'    => 'nfw_ntw1',
		'title' => __( '<img src="' . plugins_url() . '/ninjafirewall/images/ninjafirewall_20.png" ' .
				'style="vertical-align:middle;margin-right:5px" />'),
	) );

	// Add sub menu link for Super Admin only :
	if ( current_user_can( 'manage_network' ) ) {
		$wp_admin_bar->add_menu( array(
			'parent' => 'nfw_ntw1',
			'id'     => 'nfw_ntw2',
			'title'  => __( 'NinjaFirewall Settings'),
			'href'   => __( network_admin_url() . 'admin.php?page=NinjaFirewall'),
		) );
	// else, show status only (unless error) :
	} else {
		if ( defined('NFW_STATUS') ) {
			$wp_admin_bar->add_menu( array(
				'parent' => 'nfw_ntw1',
				'id'     => 'nfw_ntw2',
				'title'  => __( 'NinjaFirewall is enabled'),
			) );
		}
	}
}

if ( is_multisite() )  {
	add_action('admin_bar_menu', 'nf_admin_bar_status', 95);
}

/* ------------------------------------------------------------------ */

function nf_menu_install() {

	// Installer :

	// Block immediately if user is not allowed :
	nf_not_allowed( 'block', __LINE__ );

	require_once( plugin_dir_path(__FILE__) . 'install.php' );
}

/* ------------------------------------------------------------------ */

function nf_menu_main() {

	// Main menu (Overview) :

	// Block immediately if user is not allowed :
	nf_not_allowed( 'block', __LINE__ );

	$nfw_options = get_option( 'nfw_options' );

	// Is NF enabled/working ?
	if (! defined('NF_DISABLED') ) {
		is_nfw_enabled();
	}

?>

<div class="wrap">
	<div style="width:54px;height:52px;background-image:url(<?php echo plugins_url() ?>/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2>NinjaFirewall (WP edition)</h2>
	<br />
	<?php
	// first run ?
	if ( @NFW_STATUS == 20 && ! empty( $_REQUEST['nfw_firstrun']) ) {
		echo '<br><div class="updated settings-error"><p>Congratulations&nbsp;! NinjaFirewall is up and running.<br />If you need help, click on the contextual <code>Help</code> menu tab located in the upper right corner of each page.</p></div>';
		unset($_SESSION['abspath']); unset($_SESSION['http_server']);
		unset($_SESSION['php_ini_type']); unset($_SESSION['abspath_writable']);
		unset($_SESSION['ini_write']); unset($_SESSION['htaccess_write']);
	}
	?>
	<br />
	<table class="form-table">

	<?php
	if (NF_DISABLED) {
		if (! empty($GLOBALS['err_fw'][NF_DISABLED]) ) {
			$msg = $GLOBALS['err_fw'][NF_DISABLED];
		} else {
			$msg = 'unknown error #' . NF_DISABLED;
		}
	?>
		<tr>
			<th scope="row">Firewall</th>
			<td width="20" align="left"><img src="<?php echo plugins_url( '/images/icon_error_16.png', __FILE__ ) ?>" border="0" height="16" width="16"></td>
			<td><?php echo $msg ?></td>
		</tr>

	<?php
	} else {
	?>

		<tr>
			<th scope="row">Firewall</th>
			<td width="20" align="left"><img src="<?php echo plugins_url( '/images/icon_ok_16.png', __FILE__ ) ?>" border="0" height="16" width="16"></td>
			<td>Enabled</td>
		</tr>

	<?php
	}

	if (! empty( $nfw_options['debug']) ) {
	?>
		<tr>
			<th scope="row">Debugging mode</th>
			<td width="20" align="left"><img src="<?php echo plugins_url( '/images/icon_error_16.png', __FILE__ ) ?>" border="0" height="16" width="16"></td>
			<td>Enabled.&nbsp;<a href="?page=nfsubopt">Click here to turn Debugging Mode off</a></td>
		</tr>
	<?php
	}
	?>
		<tr>
			<th scope="row">PHP SAPI</th>
			<td width="20" align="left">&nbsp;</td>
			<td>
				<?php
				if ( defined('HHVM_VERSION') ) {
					echo 'HHVM';
				} else {
					echo strtoupper(PHP_SAPI);
				}
				?>
			</td>
		</tr>
		<tr>
			<th scope="row">Version</th>
			<td width="20" align="left">&nbsp;</td>
			<td><?php echo NFW_ENGINE_VERSION . ' (security rules: ' . preg_replace('/(\d{4})(\d\d)(\d\d)/', '$1-$2-$3', $nfw_options['rules_version']) . ')' ?></td>
		</tr>
	<?php
	// Check if the admin is whitelisted, and warn if it is not :
	if ( empty($_SESSION['nfw_goodguy']) ) {
		?>
		<tr>
			<th scope="row"><?php _e('Admin user') ?></th>
			<td width="20" align="left"><img src="<?php echo plugins_url( '/images/icon_warn_16.png', __FILE__ )?>" border="0" height="16" width="16"></td>
			<td><?php _e('You are not whitelisted. Ensure that the <i class="description">Do not block WordPress administrator</i> option is enabled in the <a href="?page=nfsubpolicies">Firewall Policies menu</a>, otherwise you will likely get blocked by the firewall while working from the WordPress administration dashboard.') ?></td>
		</tr>
	<?php
	} else {
		$current_user = wp_get_current_user();
		?>
		<tr>
			<th scope="row"><?php _e('Admin user') ?></th>
			<td width="20" align="left">&nbsp;</td>
			<td><code><?php echo htmlspecialchars($current_user->user_login) ?></code> (<?php _e('you are whitelisted by the firewall') ?>)</td>
		</tr>
	<?php
	}
	// Try to find out if there is any "lost" session between the firewall
	// and the plugin part of NinjaFirewall (could be a buggy plugin killing
	// the session etc), unless we just installed it :
	if (! empty($_SESSION['nfw_st']) && ! NF_DISABLED && empty($_REQUEST['nfw_firstrun']) ) {
		?>
		<tr>
			<th scope="row"><?php _e('User session') ?></th>
			<td width="20" align="left"><img src="<?php echo plugins_url() . '/ninjafirewall/images/icon_warn_16.png' ?>" border="0" height="16" width="16"></td>
			<td><?php _e('It seems the user session was not set by the firewall script or may have been destroyed by another plugin. You may get blocked by the firewall while working from the WordPress administration dashboard.') ?></td>
		</tr>
		<?php
		unset($_SESSION['nfw_st']);
	}
	if ( defined('NFW_SWL') && ! empty($_SESSION['nfw_goodguy']) && empty($_REQUEST['nfw_firstrun']) ) {
		?>
		<tr>
			<th scope="row"><?php _e('User session') ?></th>
			<td width="20" align="left"><img src="<?php echo plugins_url() . '/ninjafirewall/images/icon_warn_16.png' ?>" border="0" height="16" width="16"></td>
			<td><?php _e('It seems that the user session set by NinjaFirewall was not found by the firewall script. You may get blocked by the firewall while working from the WordPress administration dashboard.') ?></td>
		</tr>
		<?php
	}

	// Check IP and warn if localhost or private IP :
	if (! filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) ) {
		?>
		<tr>
			<th scope="row"><?php _e('Source IP') ?></th>
			<td width="20" align="left"><img src="<?php echo plugins_url( '/images/icon_warn_16.png', __FILE__ )?>" border="0" height="16" width="16"></td>
			<td><?php printf( __('You have a private IP&nbsp;: %s<br />If your site is behind a reverse proxy or a load balancer, ensure that you have setup your HTTP server or PHP to forward the correct visitor IP, otherwise use the NinjaFirewall <code><a href="%s">.htninja</a></code> configuration file.'), htmlentities($_SERVER['REMOTE_ADDR']), 'http://ninjafirewall.com/wordpress/htninja/') ?></td>
		</tr>
		<?php
	}
	// Look for CDN's (Incapsula/Cloudflare) and warn the user about using
	// the correct IPs, unless it was already copied to $_SERVER['REMOTE_ADDR'] :
	if (! empty($_SERVER["HTTP_CF_CONNECTING_IP"]) ) {
		// CloudFlare :
		if ( $_SERVER['REMOTE_ADDR'] != $_SERVER["HTTP_CF_CONNECTING_IP"] ) {
		?>
		<tr>
			<th scope="row"><?php _e('CDN detection') ?></th>
			<td width="20" align="left"><img src="<?php echo plugins_url( '/images/icon_warn_16.png', __FILE__ )?>" border="0" height="16" width="16"></td>
			<td><?php printf( __('<code>HTTP_CF_CONNECTING_IP</code> detected: you seem to be using Cloudflare CDN services. Ensure that you have setup your HTTP server or PHP to forward the correct visitor IP, otherwise use the NinjaFirewall <code><a href="%s">.htninja</a></code> configuration file.'), 'http://ninjafirewall.com/wordpress/htninja/?#variables') ?></td>
		</tr>
		<?php
		}
	}
	if (! empty($_SERVER["HTTP_INCAP_CLIENT_IP"]) ) {
		// Incapsula :
		if ( $_SERVER['REMOTE_ADDR'] != $_SERVER["HTTP_INCAP_CLIENT_IP"] ) {
		?>
		<tr>
			<th scope="row"><?php _e('CDN detection') ?></th>
			<td width="20" align="left"><img src="<?php echo plugins_url( '/images/icon_warn_16.png', __FILE__ )?>" border="0" height="16" width="16"></td>
			<td><?php printf( __('<code>HTTP_INCAP_CLIENT_IP</code> detected: you seem to be using Incapsula CDN services. Ensure that you have setup your HTTP server or PHP to forward the correct visitor IP, otherwise use the NinjaFirewall <code><a href="%s">.htninja</a></code> configuration file.'), 'http://ninjafirewall.com/wordpress/htninja/?#variables') ?></td>
		</tr>
		<?php
		}
	}

	// Ensure /log/ dir is writable :
	if (! is_writable( NFW_LOG_DIR . '/nfwlog') ) {
		?>
			<tr>
			<th scope="row">Log dir</th>
			<td width="20" align="left"><img src="<?php echo plugins_url( '/images/icon_error_16.png', __FILE__ )?>" border="0" height="16" width="16"></td>
			<td><code><?php echo htmlspecialchars(NFW_LOG_DIR) . '/nfwlog/' ?></code> directory is not writable&nbsp;! Please chmod it to 0777 or equivalent.</td>
		</tr>
	<?php
	}

	// Ensure /log/cache dir is writable :
	if (! is_writable( NFW_LOG_DIR . '/nfwlog/cache') ) {
		?>
			<tr>
			<th scope="row">Log dir</th>
			<td width="20" align="left"><img src="<?php echo plugins_url( '/images/icon_error_16.png', __FILE__ )?>" border="0" height="16" width="16"></td>
			<td><code><?php echo htmlspecialchars(NFW_LOG_DIR) . '/nfwlog/cache/' ?></code> directory is not writable&nbsp;! Please chmod it to 0777 or equivalent.</td>
		</tr>
	<?php
	}

	// check for NinjaFirewall optional config file :
	$doc_root = rtrim(getenv('DOCUMENT_ROOT'), '/');
	if ( @file_exists( $file = dirname( $doc_root ) . '/.htninja') ||
		@file_exists( $file = $doc_root . '/.htninja') ) {
		echo '<tr><th scope="row">Optional configuration file</th>';
		if ( is_writable( $file ) ) {
			echo '<td width="20" align="left"><img src="' . plugins_url( '/images/icon_warn_16.png', __FILE__ ) . '" border="0" height="16" width="16"></td>
			<td><code>' .  htmlentities($file) . '</code> is writable. Consider changing its permissions to read-only.</td>';
		} else {
			echo '<td width="20">&nbsp;</td>
				<td><code>' .  htmlentities($file) . '</code></td>';
		}
		echo '</tr>';
	}

	echo '</table>';

	$ro_msg = '<h3>System Files</h3>
	<table class="form-table">';
	// If the user files (.htaccess & PHP INI) are read-only, we display a warning,
	// otherwise, if (s)he wanted to uninstall NinjaFirewall, the uninstall process
	// could not restore them to their initial state and the site would crash :/
	$ro = 0;
	if ( ( file_exists( ABSPATH . '.htaccess' ) ) && (! is_writable( ABSPATH . '.htaccess' ) ) ) {
		$ro_msg .= '<tr>
		<th scope="row">.htaccess</th>
		<td width="20" align="left"><img src="' . plugins_url( '/images/icon_warn_16.png', __FILE__ ) . '" border="0" height="16" width="16"></td>
		<td><code>' . htmlentities(ABSPATH) . '.htaccess</code> is read-only</td>
		</tr>';
		$ro++;
	}
	$phpini = '';
	if ( file_exists( ABSPATH . 'php.ini' ) ) {
		$phpini = ABSPATH . 'php.ini';
	} elseif ( file_exists( ABSPATH . 'php5.ini' ) ) {
		$phpini = ABSPATH . 'php5.ini';
	} elseif ( file_exists( ABSPATH . '.user.ini' ) ) {
		$phpini = ABSPATH . '.user.ini';
	}
	if ( $phpini ) {
		if (! is_writable( $phpini ) ) {
			$ro_msg .= '<tr>
			<th scope="row">PHP INI</th>
			<td width="20" align="left"><img src="' . plugins_url( '/images/icon_warn_16.png', __FILE__ ) . '" border="0" height="16" width="16"></td>
			<td><code>' . htmlentities($phpini) . '</code> is read-only</td>
			</tr>';
			$ro++;
		}
	}
	if ( $ro++ ) {
		echo $ro_msg . '<tr>
			<th scope="row">&nbsp;</th>
			<td width="20">&nbsp;</td>
			<td><span class="description">&nbsp;Warning: you have some read-only system files; please <a href="http://ninjafirewall.com/wordpress/help.php#ro_sysfile" target="_blank">read this</a> if you want to uninstall NinjaFirewall.</span></td>
			</tr></table>';
	}
	?>
</div>

<?php
}

/* ------------------------------------------------------------------ */

function nf_sub_statistics() {

	// Stats / benchmarks menu :

	require( plugin_dir_path(__FILE__) . 'lib/nf_sub_statistics.php' );

}

/* ------------------------------------------------------------------ */

function nf_sub_options() { // i18n

	// Firewall Options menu :
	require( plugin_dir_path(__FILE__) . 'lib/nf_sub_options.php' );

}

/* ------------------------------------------------------------------ */

function nf_sub_policies() {

	// Firewall Policies menu :

	// Block immediately if user is not allowed :
	nf_not_allowed( 'block', __LINE__ );

	$yes = __('Yes');
	$no =  __('No');
	$default =  __(' (default)');

	$nfw_options = get_option( 'nfw_options' );
	$nfw_rules = get_option( 'nfw_rules' );

	echo '
<script>
function escalert() {
	if (document.fwrules.escpost.checked){
		if (confirm("Warning : if you needed to edit comments or articles, enabling this options for POST requests could corrupt them with excessive backslashes.\nGo ahead ?")){
			return true;
		}
	}
	document.fwrules.escpost.checked=false;
   return true;
}
function restore() {
   if (confirm("All fields will be restored to their default values.\nGo ahead ?")){
      return true;
   }else{
		return false;
   }
}
function chksubmenu() {
	if (document.fwrules.elements[\'nfw_options[uploads]\'].value > 0) {
      document.fwrules.san.disabled = false;
      document.getElementById("santxt").style.color = "#000000";
   } else {
      document.fwrules.san.disabled = true;
      document.getElementById("santxt").style.color = "#bbbbbb";
   }
}
function ssl_warn() {';
	// Obviously, if we are already in HTTPS mode, we don't send any warning:
	if ($_SERVER['SERVER_PORT'] == 443 ) {
		echo 'return true;';
	} else {
		echo '
		if (confirm("WARNING: ensure that you can access your admin console over HTTPS before enabling this option, otherwise you will lock yourself out of your site.\nGo ahead ?")){
			return true;
		}
		return false;';
	}
echo '
}
function httponly() {
	if (confirm("' . __('If your PHP scripts send cookies that need to be accessed from JavaScript, you should keep this option disabled.\nGo ahead ?') . '")){
		return true;
	}
	return false;
}
</script>

<div class="wrap">
	<div style="width:54px;height:52px;background-image:url( ' . plugins_url() . '/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2>Firewall Policies</h2>
	<br />';

	// Saved options ?
	if ( isset( $_POST['nfw_options']) ) {
		if ( empty($_POST['nfwnonce']) || ! wp_verify_nonce($_POST['nfwnonce'], 'policies_save') ) {
			wp_nonce_ays('policies_save');
		}
		if ( $_POST['Save'] == 'Save Firewall Policies' ) {
			nf_sub_policies_save();
			echo '<div class="updated settings-error"><p><strong>Your changes have been saved.</strong></p></div>';
		} elseif ( $_POST['Save'] == 'Restore Default Values' ) {
			nf_sub_policies_default();
			echo '<div class="updated settings-error"><p><strong>Default values were restored.</strong></p></div>';
		} else {
			echo '<div class="error settings-error"><p><strong>No action taken.</strong></p></div>';
		}
		$nfw_options = get_option( 'nfw_options' );
	}

	echo '<form method="post" name="fwrules">';
	wp_nonce_field('policies_save', 'nfwnonce', 0);

	if ( ( isset( $nfw_options['scan_protocol']) ) &&
		( preg_match( '/^[123]$/', $nfw_options['scan_protocol']) ) ) {
		$scan_protocol = $nfw_options['scan_protocol'];
	} else {
		$scan_protocol = 3;
	}

	?>
	<h3>HTTP / HTTPS</h3>
	<table class="form-table">
		<tr>
			<th scope="row">Enable NinjaFirewall for</th>
			<td width="20">&nbsp;</td>
			<td align="left">
			<p><label><input type="radio" name="nfw_options[scan_protocol]" value="3"<?php checked($scan_protocol, 3 ) ?>>&nbsp;<code>HTTP</code> and <code>HTTPS</code> traffic (default)</label></p>
			<p><label><input type="radio" name="nfw_options[scan_protocol]" value="1"<?php checked($scan_protocol, 1 ) ?>>&nbsp;<code>HTTP</code> traffic only</label></p>
			<p><label><input type="radio" name="nfw_options[scan_protocol]" value="2"<?php checked($scan_protocol, 2 ) ?>>&nbsp;<code>HTTPS</code> traffic only</label></p>
			</td>
		</tr>
	</table>

	<?php
	if ( empty( $nfw_options['sanitise_fn']) ) {
		$sanitise_fn = 0;
	} else {
		$sanitise_fn = 1;
	}
	if ( empty( $nfw_options['uploads']) ) {
		$uploads = 0;
		$sanitise_fn = 0;
	} else {
		$uploads = 1;
	}
	?>
	<h3>Uploads</h3>
	<table class="form-table">
		<tr>
			<th scope="row">File Uploads</th>
			<td width="20">&nbsp;</td>
			<td align="left">
				<select name="nfw_options[uploads]" onchange="chksubmenu();">
					<option value="1"<?php selected( $uploads, 1 ) ?>>Allow uploads</option>
					<option value="0"<?php selected( $uploads, 0 ) ?>>Disallow uploads (default)</option>
				</select>&nbsp;&nbsp;&nbsp;&nbsp;<label id="santxt"<?php if (! $uploads) { echo ' style="color:#bbbbbb;"'; }?>><input type="checkbox" name="nfw_options[sanitise_fn]"<?php checked( $sanitise_fn, 1 ); disabled( $uploads, 0 ) ?> id="san">&nbsp;Sanitise filenames</label>
			</td>
		</tr>
	</table>

	<br /><br />

	<?php
	if ( empty( $nfw_options['get_scan']) ) {
		$get_scan = 0;
	} else {
		$get_scan = 1;
	}
	if ( empty( $nfw_options['get_sanitise']) ) {
		$get_sanitise = 0;
	} else {
		$get_sanitise = 1;
	}
	?>
	<h3>HTTP GET variable</h3>
	<table class="form-table">
		<tr>
			<th scope="row">Scan <code>GET</code> variable</th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[get_scan]" value="1"<?php checked( $get_scan, 1 ) ?>>&nbsp;Yes (default)</label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[get_scan]" value="0"<?php checked( $get_scan, 0 ) ?>>&nbsp;No</label>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('Sanitise <code>GET</code> variable') ?></th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[get_sanitise]" value="1"<?php checked( $get_sanitise, 1 ) ?>>&nbsp;<?php _e('Yes') ?></label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[get_sanitise]" value="0"<?php checked( $get_sanitise, 0 ) ?>>&nbsp;<?php _e('No (default)') ?></label>
			</td>
		</tr>
	</table>

	<br /><br />

	<?php
	if ( empty( $nfw_options['post_scan']) ) {
		$post_scan = 0;
	} else {
		$post_scan = 1;
	}
	if ( empty( $nfw_options['post_sanitise']) ) {
		$post_sanitise = 0;
	} else {
		$post_sanitise = 1;
	}
	if ( empty( $nfw_options['post_b64']) ) {
		$post_b64 = 0;
	} else {
		$post_b64 = 1;
	}
	?>
	<h3>HTTP POST variable</h3>
	<table class="form-table">
		<tr valign="top">
			<th scope="row">Scan <code>POST</code> variable</th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[post_scan]" value="1"<?php checked( $post_scan, 1 ) ?>>&nbsp;Yes (default)</label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[post_scan]" value="0"<?php checked( $post_scan, 0 ) ?>>&nbsp;No</label>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">Sanitise <code>POST</code> variable</th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120" style="vertical-align:top;">
				<label><input type="radio" name="nfw_options[post_sanitise]" value="1"<?php checked( $post_sanitise, 1 ) ?>>&nbsp;Yes</label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[post_sanitise]" value="0"<?php checked( $post_sanitise, 0 ) ?>>&nbsp;No (default)</label><br /><span class="description">&nbsp;Do not enable this option unless you know what you are doing!</span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">Decode Base64-encoded <code>POST</code> variable</th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[post_b64]" value="1"<?php checked( $post_b64, 1 ) ?>>&nbsp;Yes (default)</label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[post_b64]" value="0"<?php checked( $post_b64, 0 ) ?>>&nbsp;No</label>
			</td>
		</tr>
	</table>

	<br /><br />

	<?php
	if ( empty( $nfw_options['request_sanitise']) ) {
		$request_sanitise = 0;
	} else {
		$request_sanitise = 1;
	}
	?>
	<h3>HTTP REQUEST variable</h3>
	<table class="form-table">
		<tr>
			<th scope="row">Sanitise <code>REQUEST</code> variable</th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[request_sanitise]" value="1"<?php checked( $request_sanitise, 1 ) ?>>&nbsp;Yes</label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[request_sanitise]" value="0"<?php checked( $request_sanitise, 0 ) ?>>&nbsp;No (default)</label>
			</td>
		</tr>
	</table>

	<br /><br />

	<?php
	if ( empty( $nfw_options['cookies_scan']) ) {
		$cookies_scan = 0;
	} else {
		$cookies_scan = 1;
	}
	if ( empty( $nfw_options['cookies_sanitise']) ) {
		$cookies_sanitise = 0;
	} else {
		$cookies_sanitise = 1;
	}
	?>
	<h3>Cookies</h3>
	<table class="form-table">
		<tr>
			<th scope="row">Scan cookies</th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[cookies_scan]" value="1"<?php checked( $cookies_scan, 1 ) ?>>&nbsp;Yes (default)</label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[cookies_scan]" value="0"<?php checked( $cookies_scan, 0 ) ?>>&nbsp;No</label>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('Sanitise cookies') ?></th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[cookies_sanitise]" value="1"<?php checked( $cookies_sanitise, 1 ) ?>>&nbsp;<?php _e('Yes') ?></label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[cookies_sanitise]" value="0"<?php checked( $cookies_sanitise, 0 ) ?>>&nbsp;<?php _e('No (default)') ?></label>
			</td>
		</tr>
	</table>

	<br /><br />

	<?php
	if ( empty( $nfw_options['ua_scan']) ) {
		$ua_scan = 0;
	} else {
		$ua_scan = 1;
	}
	if ( empty( $nfw_options['ua_sanitise']) ) {
		$ua_sanitise = 0;
	} else {
		$ua_sanitise = 1;
	}


	if ( empty( $nfw_rules[NFW_SCAN_BOTS]['on']) ) {
		$block_bots = 0;
	} else {
		$block_bots = 1;
	}
	?>
	<h3>HTTP_USER_AGENT server variable</h3>
	<table class="form-table">
		<tr>
			<th scope="row">Scan <code>HTTP_USER_AGENT</code></th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[ua_scan]" value="1"<?php checked( $ua_scan, 1 ) ?>>&nbsp;Yes (default)</label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[ua_scan]" value="0"<?php checked( $ua_scan, 0 ) ?>>&nbsp;No</label>
			</td>
		</tr>
		<tr>
			<th scope="row">Sanitise <code>HTTP_USER_AGENT</code></th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[ua_sanitise]" value="1"<?php checked( $ua_sanitise, 1 ) ?>>&nbsp;Yes (default)</label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[ua_sanitise]" value="0"<?php checked( $ua_sanitise, 0 ) ?>>&nbsp;No</label>
			</td>
		</tr>
		<tr>
			<th scope="row">Block suspicious bots/scanners</th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_rules[block_bots]" value="1"<?php checked( $block_bots, 1 ) ?>>&nbsp;Yes (default)</label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_rules[block_bots]" value="0"<?php checked( $block_bots, 0 ) ?>>&nbsp;No</label>
			</td>
		</tr>
	</table>

	<br /><br />

	<?php
	if ( empty( $nfw_options['referer_scan']) ) {
		$referer_scan = 0;
	} else {
		$referer_scan = 1;
	}
	if ( empty( $nfw_options['referer_sanitise']) ) {
		$referer_sanitise = 0;
	} else {
		$referer_sanitise = 1;
	}
	if ( empty( $nfw_options['referer_post']) ) {
		$referer_post = 0;
	} else {
		$referer_post = 1;
	}
	?>
	<h3><?php _e('HTTP_REFERER server variable') ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row"><?php _e('Scan <code>HTTP_REFERER</code>') ?></th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[referer_scan]" value="1"<?php checked( $referer_scan, 1 ) ?>>&nbsp;<?php _e('Yes') ?></label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[referer_scan]" value="0"<?php checked( $referer_scan, 0 ) ?>>&nbsp;<?php _e('No (default)') ?></label>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('Sanitise <code>HTTP_REFERER</code>') ?></th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[referer_sanitise]" value="1"<?php checked( $referer_sanitise, 1 ) ?>>&nbsp;<?php _e('Yes (default)') ?></label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[referer_sanitise]" value="0"<?php checked( $referer_sanitise, 0 ) ?>>&nbsp;<?php _e('No') ?></label>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Block <code>POST</code> requests that do not have an <code>HTTP_REFERER</code> header') ?></th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120" style="vertical-align:top;">
				<label><input type="radio" name="nfw_options[referer_post]" value="1"<?php checked( $referer_post, 1 ) ?>>&nbsp;<?php _e('Yes') ?></label>
			</td>
			<td align="left" style="vertical-align:top;">
				<label><input type="radio" name="nfw_options[referer_post]" value="0"<?php checked( $referer_post, 0 ) ?>>&nbsp;<?php _e('No (default)') ?></label><br /><span class="description">&nbsp;Keep this option disabled if you are using scripts like Paypal IPN, WordPress WP-Cron...</span>
			</td>
		</tr>
	</table>

	<br /><br />
	<?php

	// Some compatibility checks:
	// 1. header_register_callback(): requires PHP >=5.4
	// 2. headers_list() and header_remove(): some hosts may disable them.
	$err_msg = $err = '';
	$err_img = '<p><span class="description"><img src="' . plugins_url() . '/ninjafirewall/images/icon_warn_16.png" border="0" height="16" width="16">&nbsp;';
	$msg = __('This option is disabled because the %s PHP function is not available on your server.');
	if (! function_exists('header_register_callback') ) {
		$err_msg = $err_img . sprintf($msg, '<code>header_register_callback()</code>') . '</span></p>';
		$err = 1;
	} elseif (! function_exists('headers_list') ) {
		$err_msg = $err_img . sprintf($msg, '<code>headers_list()</code>') . '</span></p>';
		$err = 1;
	} elseif (! function_exists('header_remove') ) {
		$err_msg = $err_img . sprintf($msg, '<code>header_remove()</code>') . '</span></p>';
		$err = 1;
	}
	if ( empty($nfw_options['response_headers']) || strlen($nfw_options['response_headers']) != 6 || $err_msg ) {
		$nfw_options['response_headers'] = '000000';
	}
	?>
	<h3><?php _e('HTTP response headers', NFI18N)  ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row"><?php printf( __('Set %s to protect against MIME type confusion attacks', NFI18N), '<code><a href="https://www.owasp.org/index.php/List_of_useful_HTTP_headers" target="_blank">X-Content-Type-Options</a></code>') ?></th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[x_content_type_options]" value="1"<?php checked( $nfw_options['response_headers'][1], 1 ); disabled($err, 1); ?>><?php echo $yes; ?></label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[x_content_type_options]" value="0"<?php checked( $nfw_options['response_headers'][1], 0 ); disabled($err, 1); ?>><?php echo $no . $default; ?></label><?php echo $err_msg ?>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php printf( __('Set %s to protect against clickjacking attempts', NFI18N), '<code><a href="https://www.owasp.org/index.php/List_of_useful_HTTP_headers" target="_blank">X-Frame-Options</a></code>') ?></th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120" style="vertical-align:top;">
				<p><label><input type="radio" name="nfw_options[x_frame_options]" value="1"<?php checked( $nfw_options['response_headers'][2], 1 ); disabled($err, 1); ?>><code>SAMEORIGIN</code></label></p>
				<p><label><input type="radio" name="nfw_options[x_frame_options]" value="2"<?php checked( $nfw_options['response_headers'][2], 2 ); disabled($err, 1); ?>><code>DENY</code></label></p>
			</td>
			<td align="left" style="vertical-align:top;"><p><label><input type="radio" name="nfw_options[x_frame_options]" value="0"<?php checked( $nfw_options['response_headers'][2], 0 ); disabled($err, 1); ?>><?php echo $no . $default; ?></label><?php echo $err_msg ?></p></td>
		</tr>
		<tr>
			<th scope="row"><?php printf( __("Set %s to enable browser's built-in XSS filter (IE, Chrome and Safari)", NFI18N), '<code><a href="https://www.owasp.org/index.php/List_of_useful_HTTP_headers" target="_blank">X-XSS-Protection</a></code>') ?></th>
			<td width="20"></td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[x_xss_protection]" value="1"<?php checked( $nfw_options['response_headers'][3], 1 ); disabled($err, 1); ?>><?php echo $yes ?></label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[x_xss_protection]" value="0"<?php checked( $nfw_options['response_headers'][3], 0 ); disabled($err, 1); ?>><?php echo $no . $default; ?></label><?php echo $err_msg ?>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php printf( __('Force %s flag on all cookies to mitigate XSS attacks', NFI18N), '<code><a href="https://www.owasp.org/index.php/HttpOnly" target="_blank">HttpOnly</a></code>') ?></th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[cookies_httponly]" value="1"<?php checked( $nfw_options['response_headers'][0], 1 ); disabled($err, 1); ?> onclick="return httponly();">&nbsp;<?php echo $yes ?></label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[cookies_httponly]" value="0"<?php checked( $nfw_options['response_headers'][0], 0 ); disabled($err, 1); ?>>&nbsp;<?php echo $no . $default; ?></label><?php echo $err_msg ?>
			</td>
		</tr>
		<?php
		// We don't send HSTS headers over HTTP (only display this message if there
		// is no other warning to display, $err==0 ):
		if ($_SERVER['SERVER_PORT'] != 443 && ! $err && (! isset( $_SERVER['HTTP_X_FORWARDED_PROTO']) || $_SERVER['HTTP_X_FORWARDED_PROTO'] != 'https') ) {
			$err = 1;
			$hsts_msg = '<br /><img src="' . plugins_url() . '/ninjafirewall/images/icon_warn_16.png" border="0" height="16" width="16">&nbsp;<span class="description">' . __('HSTS headers can only be set when you are accessing your site over HTTPS.', NFI18N) . '</span>';
		} else {
			$hsts_msg = '';
		}
		?>
		<tr>
			<th scope="row"><?php printf( __('Set %s (HSTS) to enforce secure connections to the server', NFI18N), '<code><a href="https://www.owasp.org/index.php/List_of_useful_HTTP_headers" target="_blank">Strict-Transport-Security</a></code>') ?></th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120" style="vertical-align:top;">
				<p><label><input type="radio" name="nfw_options[strict_transport]" value="1"<?php checked( $nfw_options['response_headers'][4], 1 ); disabled($err, 1); ?>><?php _e('1 month', NFI18N) ?></label></p>
				<p><label><input type="radio" name="nfw_options[strict_transport]" value="2"<?php checked( $nfw_options['response_headers'][4], 2 ); disabled($err, 1); ?>><?php _e('6 months', NFI18N) ?></label></p>
				<p><label><input type="radio" name="nfw_options[strict_transport]" value="3"<?php checked( $nfw_options['response_headers'][4], 3 ); disabled($err, 1); ?>><?php _e('1 year', NFI18N) ?></label></p>
				<br />
				<label><input type="checkbox" name="nfw_options[strict_transport_sub]" value="1"<?php checked( $nfw_options['response_headers'][5], 1 ); disabled($err, 1); ?>><?php _e('Apply to subdomains', NFI18N) ?></label>
			</td>
			<td align="left" style="vertical-align:top;"><p><label><input type="radio" name="nfw_options[strict_transport]" value="0"<?php checked( $nfw_options['response_headers'][4], 0 ); disabled($err, 1); ?>><?php echo $no . $default; ?></label><?php echo $err_msg ?></p>
			<?php echo $hsts_msg; ?>
			</td>
		</tr>
	</table>

	<br /><br />

	<?php
	if ( empty( $nfw_rules[NFW_LOOPBACK]['on']) ) {
		$no_localhost_ip = 0;
	} else {
		$no_localhost_ip = 1;
	}
	if ( empty( $nfw_options['no_host_ip']) ) {
		$no_host_ip = 0;
	} else {
		$no_host_ip = 1;
	}
	if ( empty( $nfw_options['allow_local_ip']) ) {
		$allow_local_ip = 0;
	} else {
		$allow_local_ip = 1;
	}
	?>
	<h3>IP</h3>
	<table class="form-table" border=0>
		<tr>
			<th scope="row">Block localhost IP in <code>GET/POST</code> request</th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_rules[no_localhost_ip]" value="1"<?php checked( $no_localhost_ip, 1 ) ?>>&nbsp;Yes (default)</label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_rules[no_localhost_ip]" value="0"<?php checked( $no_localhost_ip, 0 ) ?>>&nbsp;No</label>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('Block HTTP requests with an IP in the <code>HTTP_HOST</code> header') ?></th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[no_host_ip]" value="1"<?php checked( $no_host_ip, 1 ) ?>>&nbsp;<?php _e('Yes') ?></label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[no_host_ip]" value="0"<?php checked( $no_host_ip, 0 ) ?>>&nbsp;<?php _e('No (default)') ?></label>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('Scan traffic coming from localhost and private IP address spaces') ?></th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[allow_local_ip]" value="0"<?php checked( $allow_local_ip, 0 ) ?>>&nbsp;<?php _e('Yes (default)') ?></label>
				</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[allow_local_ip]" value="1"<?php checked( $allow_local_ip, 1 ) ?>>&nbsp;<?php _e('No') ?></label>
			</td>
		</tr>
	</table>

	<br /><br />

	<?php
	if ( empty( $nfw_rules[NFW_WRAPPERS]['on']) ) {
		$php_wrappers = 0;
	} else {
		$php_wrappers = 1;
	}
	if ( empty( $nfw_options['php_errors']) ) {
		$php_errors = 0;
	} else {
		$php_errors = 1;
	}
	if ( empty( $nfw_options['php_self']) ) {
		$php_self = 0;
	} else {
		$php_self = 1;
	}
	if ( empty( $nfw_options['php_path_t']) ) {
		$php_path_t = 0;
	} else {
		$php_path_t = 1;
	}
	if ( empty( $nfw_options['php_path_i']) ) {
		$php_path_i = 0;
	} else {
		$php_path_i = 1;
	}
	?>
	<h3>PHP</h3>
	<table class="form-table">
		<tr>
			<th scope="row">Block PHP built-in wrappers</th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_rules[php_wrappers]" value="1"<?php checked( $php_wrappers, 1 ) ?>>&nbsp;Yes (default)</label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_rules[php_wrappers]" value="0"<?php checked( $php_wrappers, 0 ) ?>>&nbsp;No</label>
			</td>
		</tr>
		<tr>
			<th scope="row">Hide PHP notice &amp; error messages</th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[php_errors]" value="1"<?php checked( $php_errors, 1 ) ?>>&nbsp;Yes (default)</label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[php_errors]" value="0"<?php checked( $php_errors, 0 ) ?>>&nbsp;No</label>
			</td>
		</tr>
		<tr>
			<th scope="row">Sanitise <code>PHP_SELF</code></th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[php_self]" value="1"<?php checked( $php_self, 1 ) ?>>&nbsp;Yes (default)</label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[php_self]" value="0"<?php checked( $php_self, 0 ) ?>>&nbsp;No</label>
			</td>
		</tr>
		<tr>
			<th scope="row">Sanitise <code>PATH_TRANSLATED</code></th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[php_path_t]" value="1"<?php checked( $php_path_t, 1 ) ?>>&nbsp;Yes (default)</label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[php_path_t]" value="0"<?php checked( $php_path_t, 0 ) ?>>&nbsp;No</label>
			</td>
		</tr>
		<tr>
			<th scope="row">Sanitise <code>PATH_INFO</code></th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[php_path_i]" value="1"<?php checked( $php_path_i, 1 ) ?>>&nbsp;Yes (default)</label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[php_path_i]" value="0"<?php checked( $php_path_i, 0 ) ?>>&nbsp;No</label>
			</td>
		</tr>
	</table>

	<br /><br />

	<?php

	// If the document root is < 5 characters, grey out that option:
	if ( strlen( getenv( 'DOCUMENT_ROOT' ) ) < 5 ) {
		$nfw_rules[NFW_DOC_ROOT]['on'] = 0;
		$greyed = 'style="color:#bbbbbb"';
		$disabled = 'disabled ';
		$disabled_msg = '<br /><span class="description">&nbsp;This option is not compatible with your actual configuration.</span>';
	} else {
		$greyed = '';
		$disabled = '';
		$disabled_msg = '';
	}

	if ( empty( $nfw_rules[NFW_DOC_ROOT]['on']) ) {
		$block_doc_root = 0;
	} else {
		$block_doc_root = 1;
	}
	if ( empty( $nfw_rules[NFW_NULL_BYTE]['on']) ) {
		$block_null_byte = 0;
	} else {
		$block_null_byte = 1;
	}
	if ( empty( $nfw_rules[NFW_ASCII_CTRL]['on']) ) {
		$block_ctrl_chars = 0;
	} else {
		$block_ctrl_chars = 1;
	}
	?>
	<h3>Various</h3>
	<table class="form-table">
		<tr valign="top">
			<th scope="row">Block the <code>DOCUMENT_ROOT</code> server variable in HTTP request</th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label <?php echo $greyed ?>><input type="radio" name="nfw_rules[block_doc_root]" value="1"<?php checked( $block_doc_root, 1 ) ?>>&nbsp;Yes (default)</label>
			</td>
			<td align="left">
				<label <?php echo $greyed ?>><input <?php echo $disabled ?>type="radio" name="nfw_rules[block_doc_root]" value="0"<?php checked( $block_doc_root, 0 ) ?>>&nbsp;No</label><?php echo $disabled_msg ?>
			</td>
		</tr>
		<tr>
			<th scope="row">Block ASCII character 0x00 (NULL byte)</th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_rules[block_null_byte]" value="1"<?php checked( $block_null_byte, 1 ) ?>>&nbsp;Yes (default)</label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_rules[block_null_byte]" value="0"<?php checked( $block_null_byte, 0 ) ?>>&nbsp;No</label>
			</td>
		</tr>
		<tr>
			<th scope="row">Block ASCII control characters 1 to 8 and 14 to 31</th>
			<td width="20">&nbsp;</td>
			<td align="left">
				<label><input type="radio" name="nfw_rules[block_ctrl_chars]" value="1"<?php checked( $block_ctrl_chars, 1 ) ?>>&nbsp;Yes (default)</label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_rules[block_ctrl_chars]" value="0"<?php checked( $block_ctrl_chars, 0 ) ?>>&nbsp;No</label>
			</td>
		</tr>
	</table>

	<br /><br />

	<?php

	if ( @strpos( $nfw_options['wp_dir'], 'wp-admin' ) !== FALSE ) {
		$wp_admin = 1;
	} else {
		$wp_admin = 0;
	}
	if ( @strpos( $nfw_options['wp_dir'], 'wp-includes' ) !== FALSE ) {
		$wp_inc = 1;
	} else {
		$wp_inc = 0;
	}
	if ( @strpos( $nfw_options['wp_dir'], 'uploads' ) !== FALSE ) {
		$wp_upl = 1;
	} else {
		$wp_upl = 0;
	}
	if ( @strpos( $nfw_options['wp_dir'], 'cache' ) !== FALSE ) {
		$wp_cache = 1;
	} else {
		$wp_cache = 0;
	}
	if ( empty( $nfw_options['enum_archives']) ) {
		$enum_archives = 0;
	} else {
		$enum_archives = 1;
	}
	if ( empty( $nfw_options['enum_login']) ) {
		$enum_login = 0;
	} else {
		$enum_login = 1;
	}
	if ( empty( $nfw_options['no_xmlrpc']) ) {
		$no_xmlrpc = 0;
	} else {
		$no_xmlrpc = 1;
	}
	if ( empty( $nfw_options['no_post_themes']) ) {
		$no_post_themes = 0;
	} else {
		$no_post_themes = 1;
	}

	if ( empty( $nfw_options['force_ssl']) ) {
		$force_ssl = 0;
	} else {
		$force_ssl = 1;
	}
	if ( empty( $nfw_options['disallow_edit']) ) {
		$disallow_edit = 0;
	} else {
		$disallow_edit = 1;
	}
	if ( empty( $nfw_options['disallow_mods']) ) {
		$disallow_mods = 0;
	} else {
		$disallow_mods = 1;
	}

	?>
	<h3>WordPress</h3>
	<table class="form-table">
		<tr>
			<th scope="row">Block direct access to any PHP file located in one of these directories</th>
			<td width="20">&nbsp;</td>
			<td align="left">
				<table class="form-table">
					<tr style="border: solid 1px #DFDFDF;">
						<td align="center" width="10"><input type="checkbox" name="nfw_options[wp_admin]" id="wp_01"<?php checked( $wp_admin, 1 ) ?>></td>
						<td>
						<label for="wp_01">
						<p><code>/wp-admin/css/*</code></p>
						<p><code>/wp-admin/images/*</code></p>
						<p><code>/wp-admin/includes/*</code></p>
						<p><code>/wp-admin/js/*</code></p>
						</label>
						</td>
					</tr>
					<tr style="border: solid 1px #DFDFDF;">
						<td align="center" width="10"><input type="checkbox" name="nfw_options[wp_inc]" id="wp_02"<?php checked( $wp_inc, 1 ) ?>></td>
						<td>
						<label for="wp_02">
						<p><code>/wp-includes/*.php</code></p>
						<p><code>/wp-includes/css/*</code></p>
						<p><code>/wp-includes/images/*</code></p>
						<p><code>/wp-includes/js/*</code></p>
						<p><code>/wp-includes/theme-compat/*</code></p>
						</label>
						<br />
						<span class="description">NinjaFirewall will not block access to the TinyMCE WYSIWYG editor even if this option is enabled.</span>
						</td>
					</tr>
					<tr style="border: solid 1px #DFDFDF;">
						<td align="center" width="10"><input type="checkbox" name="nfw_options[wp_upl]" id="wp_03"<?php checked( $wp_upl, 1 ) ?>></td>
						<td><label for="wp_03">
							<p><code>/<?php echo basename(WP_CONTENT_DIR); ?>/uploads/*</code></p>
							<p><code>/<?php echo basename(WP_CONTENT_DIR); ?>/blogs.dir/*</code></p>
						</label></td>
					</tr>
					<tr style="border: solid 1px #DFDFDF;">
						<td align="center" style="vertical-align:top" width="10"><input type="checkbox" name="nfw_options[wp_cache]" id="wp_04"<?php checked( $wp_cache, 1 ) ?>></td>
						<td style="vertical-align:top"><label for="wp_04"><code>*/cache/*</code></label>
						<br />
						<br />
						<span class="description"><?php _e('Unless you have PHP scripts in a "/cache/" folder that need to be accessed by your visitors, we recommend to enable this option.' ) ?></span>
						</td>
					</tr>
				</table>
				<br />&nbsp;
			</td>
		</tr>
	</table>

	<table class="form-table">
		<tr>
			<th scope="row"><?php _e('Protect against username enumeration') ?></th>
			<td width="20">&nbsp;</td>
			<td align="left">
				<p><label><input type="checkbox" name="nfw_options[enum_archives]" value="1"<?php checked( $enum_archives, 1 ) ?>>&nbsp;<?php _e('Through the author archives (default)') ?></label></p>
				<p><label><input type="checkbox" name="nfw_options[enum_login]" value="1"<?php checked( $enum_login, 1 ) ?>>&nbsp;<?php _e('Through the login page') ?></label></p>
			</td>
		</tr>
	</table>

	<table class="form-table">
		<tr valign="top">
			<th scope="row">Block access to WordPress XML-RPC API</th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[no_xmlrpc]" value="1"<?php checked( $no_xmlrpc, 1 ) ?>>&nbsp;Yes</label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[no_xmlrpc]" value="0"<?php checked( $no_xmlrpc, 0 ) ?>>&nbsp;No (default)</label>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">Block <code>POST</code> requests in the themes folder <code>/<?php echo basename(WP_CONTENT_DIR); ?>/themes</code></th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[no_post_themes]" value="1"<?php checked( $no_post_themes, 1 ) ?>>&nbsp;Yes</label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[no_post_themes]" value="0"<?php checked( $no_post_themes, 0 ) ?>>&nbsp;No (default)</label>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><a name="builtinconstants"></a>Force SSL for admin and logins <code><a href="http://codex.wordpress.org/Editing_wp-config.php#Require_SSL_for_Admin_and_Logins" target="_blank">FORCE_SSL_ADMIN</a></code></th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[force_ssl]" value="1"<?php checked( $force_ssl, 1 ) ?> onclick="return ssl_warn();">&nbsp;Yes</label>
			</td>
			<td align="left">
				<label><input type="radio" id="ssl_0" name="nfw_options[force_ssl]" value="0"<?php checked( $force_ssl, 0 ) ?>>&nbsp;No (default)</label>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">Disable the plugin and theme editor <code><a href="http://codex.wordpress.org/Editing_wp-config.php#Disable_the_Plugin_and_Theme_Editor" target="_blank">DISALLOW_FILE_EDIT</a></code></th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[disallow_edit]" value="1"<?php checked( $disallow_edit, 1 ) ?>>&nbsp;Yes</label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[disallow_edit]" value="0"<?php checked( $disallow_edit, 0 ) ?>>&nbsp;No (default)</label>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">Disable plugin and theme update/installation <code><a href="http://codex.wordpress.org/Editing_wp-config.php#Disable_Plugin_and_Theme_Update_and_Installation" target="_blank">DISALLOW_FILE_MODS</a></code></th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[disallow_mods]" value="1"<?php checked( $disallow_mods, 1 ) ?>>&nbsp;Yes</label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[disallow_mods]" value="0"<?php checked( $disallow_mods, 0 ) ?>>&nbsp;No (default)</label>
			</td>
		</tr>

	</table>
	<a name="donotblockadmin"></a>
	<br />

	<?php
	if ( empty( $nfw_options['wl_admin']) ) {
		$wl_admin = 0;
	} else {
		$wl_admin = 1;
	}
	?>
	<table class="form-table">
		<tr style="background-color:#F9F9F9;border: solid 1px #DFDFDF;">
			<th scope="row">Do not block WordPress administrator (must be logged in)</th>
			<td width="20">&nbsp;</td>
			<td align="left">
			<p><label><input type="radio" name="nfw_options[wl_admin]" value="1"<?php checked( $wl_admin, 1 ) ?>>&nbsp;Yes, do not block the Administrator (default)</label></p>
			<p><label><input type="radio" name="nfw_options[wl_admin]" value="0"<?php checked( $wl_admin, 0 ) ?>>&nbsp;No, block everyone, including the Admin if needed !</label></p>
			<p><span class="description">Note : does not apply to <code>FORCE_SSL_ADMIN</code>, <code>DISALLOW_FILE_EDIT</code> and <code>DISALLOW_FILE_MODS</code> options which, if enabled, are always enforced.</span></p>
			</td>
		</tr>
	</table>

	<br />
	<br />
	<input class="button-primary" type="submit" name="Save" value="Save Firewall Policies" />
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<input class="button-secondary" type="submit" name="Save" value="Restore Default Values" onclick="return restore();" />
	</form>
</div>

<?php
}

/* ------------------------------------------------------------------ */

function nf_sub_policies_save() {

	// Save policies :

	// Block immediately if user is not allowed :
	nf_not_allowed( 'block', __LINE__ );

	$nfw_options = get_option( 'nfw_options' );
	$nfw_rules = get_option( 'nfw_rules' );

	// Options

	// HTTP/S traffic to scan :
	if ( (isset( $_POST['nfw_options']['scan_protocol'])) &&
		( preg_match( '/^[123]$/', $_POST['nfw_options']['scan_protocol'])) ) {
			$nfw_options['scan_protocol'] = $_POST['nfw_options']['scan_protocol'];
	} else {
		// Default : HTTP + HTTPS
		$nfw_options['scan_protocol'] = 3;
	}

	// Allow uploads ?
	if ( empty( $_POST['nfw_options']['uploads']) ) {
		// Default: no
		$nfw_options['uploads'] = 0;
	} else {
		$nfw_options['uploads'] = 1;
	}

	// Sanitise filenames (if uploads are allowed) ?
	if ( (isset( $_POST['nfw_options']['sanitise_fn']) ) && ( $nfw_options['uploads'] == 1) ) {
		$nfw_options['sanitise_fn'] = 1;
	} else {
		$nfw_options['sanitise_fn'] = 0;
	}

	// Scan GET requests ?
	if ( empty( $_POST['nfw_options']['get_scan']) ) {
		$nfw_options['get_scan'] = 0;
	} else {
		// Default: yes
		$nfw_options['get_scan'] = 1;
	}
	// Sanitise GET requests ?
	if ( empty( $_POST['nfw_options']['get_sanitise']) ) {
		// Default: no
		$nfw_options['get_sanitise'] = 0;
	} else {
		$nfw_options['get_sanitise'] = 1;
	}


	// Scan POST requests ?
	if ( empty( $_POST['nfw_options']['post_scan']) ) {
		$nfw_options['post_scan'] = 0;
	} else {
		// Default: yes
		$nfw_options['post_scan'] = 1;
	}
	// Sanitise POST requests ?
	if ( empty( $_POST['nfw_options']['post_sanitise']) ) {
		// Default: no
		$nfw_options['post_sanitise'] = 0;
	} else {
		$nfw_options['post_sanitise'] = 1;
	}
	// Decode base64 values in POST requests ?
	if ( empty( $_POST['nfw_options']['post_b64']) ) {
		$nfw_options['post_b64'] = 0;
	} else {
		// Default: yes
		$nfw_options['post_b64'] = 1;
	}


	// Sanitise REQUEST requests ?
	if ( empty( $_POST['nfw_options']['request_sanitise']) ) {
		// Default: yes
		$nfw_options['request_sanitise'] = 0;
	} else {
		$nfw_options['request_sanitise'] = 1;
	}


	// HTTP response headers:
	if ( function_exists('header_register_callback') && function_exists('headers_list') && function_exists('header_remove') ) {
		$nfw_options['response_headers'] = '000000';
		// X-Content-Type-Options
		if ( empty( $_POST['nfw_options']['x_content_type_options']) ) {
			$nfw_options['response_headers'][1] = 0;
		} else {
			$nfw_options['response_headers'][1] = 1;
		}
		// X-Frame-Options
		if ( empty( $_POST['nfw_options']['x_frame_options']) ) {
			$nfw_options['response_headers'][2] = 0;
		} elseif ( $_POST['nfw_options']['x_frame_options'] == 1) {
			$nfw_options['response_headers'][2] = 1;
		} else {
			$nfw_options['response_headers'][2] = 2;
		}
		// X-XSS-Protection
		if ( empty( $_POST['nfw_options']['x_xss_protection']) ) {
			$nfw_options['response_headers'][3] = 0;
		} else {
			$nfw_options['response_headers'][3] = 1;
		}
		// HttpOnly cookies ?
		if ( empty( $_POST['nfw_options']['cookies_httponly']) ) {
			$nfw_options['response_headers'][0] = 0;
		} else {
			$nfw_options['response_headers'][0] = 1;
		}
		// Strict-Transport-Security ?
		if (! isset( $_POST['nfw_options']['strict_transport_sub']) ) {
			$nfw_options['response_headers'][5] = 0;
		} else {
			$nfw_options['response_headers'][5] = 1;
		}
		if ( empty( $_POST['nfw_options']['strict_transport']) ) {
			$nfw_options['response_headers'][4] = 0;
			$nfw_options['response_headers'][5] = 0;
		} elseif ( $_POST['nfw_options']['strict_transport'] == 1) {
			$nfw_options['response_headers'][4] = 1;
		} elseif ( $_POST['nfw_options']['strict_transport'] == 2) {
			$nfw_options['response_headers'][4] = 2;
		} else {
			$nfw_options['response_headers'][4] = 3;
		}
	}


	// Scan COOKIES requests ?
	if ( empty( $_POST['nfw_options']['cookies_scan']) ) {
		$nfw_options['cookies_scan'] = 0;
	} else {
		// Default: yes
		$nfw_options['cookies_scan'] = 1;
	}
	// Sanitise COOKIES requests ?
	if ( empty( $_POST['nfw_options']['cookies_sanitise']) ) {
		// Default: no
		$nfw_options['cookies_sanitise'] = 0;
	} else {
		$nfw_options['cookies_sanitise'] = 1;
	}


	// Scan HTTP_USER_AGENT requests ?
	if ( empty( $_POST['nfw_options']['ua_scan']) ) {
		$nfw_options['ua_scan'] = 0;
	} else {
		// Default: yes
		$nfw_options['ua_scan'] = 1;
	}
	// Sanitise HTTP_USER_AGENT requests ?
	if ( empty( $_POST['nfw_options']['ua_sanitise']) ) {
		$nfw_options['ua_sanitise'] = 0;
	} else {
		// Default: yes
		$nfw_options['ua_sanitise'] = 1;
	}


	// Scan HTTP_REFERER requests ?
	if ( empty( $_POST['nfw_options']['referer_scan']) ) {
		$nfw_options['referer_scan'] = 0;
		// Default: no
	} else {
		$nfw_options['referer_scan'] = 1;
	}
	// Sanitise HTTP_REFERER requests ?
	if ( empty( $_POST['nfw_options']['referer_sanitise']) ) {
		$nfw_options['referer_sanitise'] = 0;
	} else {
		// Default: yes
		$nfw_options['referer_sanitise'] = 1;
	}
	// Block POST requests without HTTP_REFERER ?
	if ( empty( $_POST['nfw_options']['referer_post']) ) {
		// Default: NO
		$nfw_options['referer_post'] = 0;
	} else {
		$nfw_options['referer_post'] = 1;
	}


	// Block HTTP requests with an IP in the Host header ?
	if ( empty( $_POST['nfw_options']['no_host_ip']) ) {
		// Default: NO
		$nfw_options['no_host_ip'] = 0;
	} else {
		$nfw_options['no_host_ip'] = 1;
	}
	// Scan server/local IPs ?
	if ( empty( $_POST['nfw_options']['allow_local_ip']) ) {
		// Default: yes
		$nfw_options['allow_local_ip'] = 0;
	} else {
		$nfw_options['allow_local_ip'] = 1;
	}


	// Hide PHP notice & error messages :
	if ( empty( $_POST['nfw_options']['php_errors']) ) {
		$nfw_options['php_errors'] = 0;
	} else {
		// Default: yes
		$nfw_options['php_errors'] = 1;
	}

	// Sanitise PHP_SELF ?
	if ( empty( $_POST['nfw_options']['php_self']) ) {
		$nfw_options['php_self'] = 0;
	} else {
		// Default: yes
		$nfw_options['php_self'] = 1;
	}
	// Sanitise PATH_TRANSLATED ?
	if ( empty( $_POST['nfw_options']['php_path_t']) ) {
		$nfw_options['php_path_t'] = 0;
	} else {
		// Default: yes
		$nfw_options['php_path_t'] = 1;
	}
	// Sanitise PATH_INFO ?
	if ( empty( $_POST['nfw_options']['php_path_i']) ) {
		$nfw_options['php_path_i'] = 0;
	} else {
		// Default: yes
		$nfw_options['php_path_i'] = 1;
	}

	// WordPress directories PHP restrictions :
	$nfw_options['wp_dir'] = $tmp = '';
	if ( isset( $_POST['nfw_options']['wp_admin']) ) {
		$tmp .= '/wp-admin/(?:css|images|includes|js)/|';
	}
	if ( isset( $_POST['nfw_options']['wp_inc']) ) {
		$tmp .= '/wp-includes/(?:(?:css|images|js(?!/tinymce/wp-tinymce\.php)|theme-compat)/|[^/]+\.php)|';
	}
	if ( isset( $_POST['nfw_options']['wp_upl']) ) {
		$tmp .= '/' . basename(WP_CONTENT_DIR) .'/(?:uploads|blogs\.dir)/|';
	}
	if ( isset( $_POST['nfw_options']['wp_cache']) ) {
		$tmp .= '/cache/|';
	}
	if ( $tmp ) {
		$nfw_options['wp_dir'] = rtrim( $tmp, '|' );
	}

	// Protect against username enumeration attempts ?
	if (! isset( $_POST['nfw_options']['enum_archives']) ) {
		$nfw_options['enum_archives'] = 0;
	} else {
		// Default : yes
		$nfw_options['enum_archives'] = 1;
	}
	if (! isset( $_POST['nfw_options']['enum_login']) ) {
		// Default : no
		$nfw_options['enum_login'] = 0;
	} else {
		$nfw_options['enum_login'] = 1;
	}


	// Block WordPress XML-RPC API ?
	if ( empty( $_POST['nfw_options']['no_xmlrpc']) ) {
		// Default : no
		$nfw_options['no_xmlrpc'] = 0;
	} else {
		$nfw_options['no_xmlrpc'] = 'xmlrpc.php';
	}

	// Block POST requests in the themes folder ?
	if ( empty( $_POST['nfw_options']['no_post_themes']) ) {
		// Default : no
		$nfw_options['no_post_themes'] = 0;
	} else {
		$nfw_options['no_post_themes'] = '/'. basename(WP_CONTENT_DIR) .'/themes/';
	}

	// Force SSL for admin and logins ?
	if ( empty( $_POST['nfw_options']['force_ssl']) ) {
		// Default : no
		$nfw_options['force_ssl'] = 0;
	} else {
		$nfw_options['force_ssl'] = 1;
	}

	// Disable the plugin and theme editor
	if ( empty( $_POST['nfw_options']['disallow_edit']) ) {
		// Default : no
		$nfw_options['disallow_edit'] = 0;
	} else {
		$nfw_options['disallow_edit'] = 1;
	}

	// Disable plugin and theme update/installation
	if ( empty( $_POST['nfw_options']['disallow_mods']) ) {
		// Default : no
		$nfw_options['disallow_mods'] = 0;
	} else {
		$nfw_options['disallow_mods'] = 1;
	}


	// Whitelist WP admin :
	if ( empty( $_POST['nfw_options']['wl_admin']) ) {
		$nfw_options['wl_admin'] = 0;
		// Clear the goodguy flag :
		if ( isset( $_SESSION['nfw_goodguy']) ) {
			unset( $_SESSION['nfw_goodguy']);
		}
	} else {
		// Default: don't block admin...
		$nfw_options['wl_admin'] = 1;
		// ...and set the goodguy flag :
		$_SESSION['nfw_goodguy'] = true;
	}


	// Rules

	// Block NULL byte 0x00 (#ID 2) :
	if ( empty( $_POST['nfw_rules']['block_null_byte']) ) {
		$nfw_rules[NFW_NULL_BYTE]['on'] = 0;
	} else {
		// Default: yes
		$nfw_rules[NFW_NULL_BYTE]['on'] = 1;
	}
	// Block bots & script kiddies' scanners (#ID 531) :
	if ( empty( $_POST['nfw_rules']['block_bots']) ) {
		$nfw_rules[NFW_SCAN_BOTS]['on'] = 0;
	} else {
		// Default: yes
		$nfw_rules[NFW_SCAN_BOTS]['on'] = 1;
	}
	// Block ASCII control characters 1 to 8 and 14 to 31 (#ID 500) :
	if ( empty( $_POST['nfw_rules']['block_ctrl_chars']) ) {
		$nfw_rules[NFW_ASCII_CTRL]['on'] = 0;
	} else {
		// Default: yes
		$nfw_rules[NFW_ASCII_CTRL]['on'] = 1;
	}


	// Block the DOCUMENT_ROOT server variable in GET/POST requests (#ID 510) :
	if ( empty( $_POST['nfw_rules']['block_doc_root']) ) {
		$nfw_rules[NFW_DOC_ROOT]['on'] = 0;
	} else {
		// Default: yes

		// We need to ensure that the document root is at least
		// 5 characters, otherwise this option could block a lot
		// of legitimate requests:
		if ( strlen( getenv( 'DOCUMENT_ROOT' ) ) > 5 ) {
			$nfw_rules[NFW_DOC_ROOT]['what'] = getenv( 'DOCUMENT_ROOT' );
			$nfw_rules[NFW_DOC_ROOT]['on']	= 1;
		} elseif ( strlen( $_SERVER['DOCUMENT_ROOT'] ) > 5 ) {
			$nfw_rules[NFW_DOC_ROOT]['what'] = $_SERVER['DOCUMENT_ROOT'];
			$nfw_rules[NFW_DOC_ROOT]['on']	= 1;
		// we must disable that option:
		} else {
			$nfw_rules[NFW_DOC_ROOT]['on']	= 0;
		}
	}


	// Block PHP built-in wrappers (#ID 520) :
	if ( empty( $_POST['nfw_rules']['php_wrappers']) ) {
		$nfw_rules[NFW_WRAPPERS]['on'] = 0;
	} else {
		// Default: yes
		$nfw_rules[NFW_WRAPPERS]['on'] = 1;
	}
	// Block localhost IP in GET/POST requests (#ID 540) :
	if ( empty( $_POST['nfw_rules']['no_localhost_ip']) ) {
		$nfw_rules[NFW_LOOPBACK]['on'] = 0;
	} else {
		// Default: yes
		$nfw_rules[NFW_LOOPBACK]['on'] = 1;
	}


	// Save option + rules :
	update_option( 'nfw_options', $nfw_options );
	update_option( 'nfw_rules', $nfw_rules );

}

/* ------------------------------------------------------------------ */

function nf_sub_policies_default() {

	// Restore default firewall policies :

	// Block immediately if user is not allowed :
	nf_not_allowed( 'block', __LINE__ );

	$nfw_options = get_option( 'nfw_options' );
	$nfw_rules = get_option( 'nfw_rules' );

	$nfw_options['scan_protocol']		= 3;
	$nfw_options['uploads']				= 0;
	$nfw_options['sanitise_fn']		= 1;
	$nfw_options['get_scan']			= 1;
	$nfw_options['get_sanitise']		= 0;
	$nfw_options['post_scan']			= 1;
	$nfw_options['post_sanitise']		= 0;
	$nfw_options['request_sanitise'] = 0;
	if ( function_exists('header_register_callback') && function_exists('headers_list') && function_exists('header_remove') ) {
		$nfw_options['response_headers'] = '000000';
	}
	$nfw_options['cookies_scan']		= 1;
	$nfw_options['cookies_sanitise']	= 0;
	$nfw_options['ua_scan']				= 1;
	$nfw_options['ua_sanitise']		= 1;
	$nfw_options['referer_scan']		= 0;
	$nfw_options['referer_sanitise']	= 1;
	$nfw_options['referer_post']		= 0;
	$nfw_options['no_host_ip']			= 0;
	$nfw_options['allow_local_ip']	= 0;
	$nfw_options['php_errors']			= 1;
	$nfw_options['php_self']			= 1;
	$nfw_options['php_path_t']			= 1;
	$nfw_options['php_path_i']			= 1;
	$nfw_options['wp_dir'] 				= '/wp-admin/(?:css|images|includes|js)/|' .
		'/wp-includes/(?:(?:css|images|js(?!/tinymce/wp-tinymce\.php)|theme-compat)/|[^/]+\.php)|' .
		'/'. basename(WP_CONTENT_DIR) .'/(?:uploads|blogs\.dir)/';
	$nfw_options['enum_archives']		= 1;
	$nfw_options['enum_login']			= 0;
	$nfw_options['no_xmlrpc']			= 0;
	$nfw_options['no_post_themes']	= 0;
	$nfw_options['force_ssl'] 			= 0;
	$nfw_options['disallow_edit'] 	= 0;
	$nfw_options['disallow_mods'] 	= 0;
	$nfw_options['post_b64']			= 1;
	$nfw_options['wl_admin']			= 1;
	$_SESSION['nfw_goodguy'] 			= true;

	$nfw_rules[NFW_SCAN_BOTS]['on']	= 1;
	$nfw_rules[NFW_LOOPBACK]['on']	= 1;
	$nfw_rules[NFW_WRAPPERS]['on']	= 1;

	if ( strlen( getenv( 'DOCUMENT_ROOT' ) ) > 5 ) {
		$nfw_rules[NFW_DOC_ROOT]['what'] = getenv( 'DOCUMENT_ROOT' );
		$nfw_rules[NFW_DOC_ROOT]['on'] = 1;
	} elseif ( strlen( $_SERVER['DOCUMENT_ROOT'] ) > 5 ) {
		$nfw_rules[NFW_DOC_ROOT]['what'] = $_SERVER['DOCUMENT_ROOT'];
		$nfw_rules[NFW_DOC_ROOT]['on'] = 1;
	} else {
		$nfw_rules[NFW_DOC_ROOT]['on']  = 0;
	}

	$nfw_rules[NFW_NULL_BYTE]['on']  = 1;
	$nfw_rules[NFW_ASCII_CTRL]['on'] = 1;

	update_option( 'nfw_options', $nfw_options);
	update_option( 'nfw_rules', $nfw_rules);

}

/* ------------------------------------------------------------------ */

function nf_sub_fileguard() {

	// File Guard :

	// Block immediately if user is not allowed :
	nf_not_allowed( 'block', __LINE__ );

	$nfw_options = get_option( 'nfw_options' );

	?>
	<script>
	function toogle_table(off) {
		if ( off == 1 ) {
			document.getElementById('fg_table').style.display = '';
		} else if ( off == 2 ) {
			document.getElementById('fg_table').style.display = 'none';
		}
		return;
	}
	function is_number(id) {
		var e = document.getElementById(id);
		if (! e.value ) { return }
		if (! /^[1-9][0-9]?$/.test(e.value) ) {
			alert("<?php _e('Please enter a number from 1 to 99.', NFI18N) ?>");
			e.value = e.value.substring(0, e.value.length-1);
		}
	}
	function check_fields() {
		if (! document.nfwfilefuard.elements["nfw_options[fg_mtime]"]){
			alert("<?php _e('Please enter a number from 1 to 99.', NFI18N) ?>");
			return false;
		}
		return true;
	}
	</script>

	<div class="wrap">
		<div style="width:54px;height:52px;background-image:url(<?php echo plugins_url() ?>/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
		<h2><?php _e('File Guard', NFI18N) ?></h2>
		<br />
	<?php

	// Ensure cache folder is writable :
	if (! is_writable( NFW_LOG_DIR . '/nfwlog/cache/') ) {
		echo '<div class="error settings-error"><p><strong>The cache directory ('. htmlspecialchars(NFW_LOG_DIR) . '/nfwlog/cache/) is not writable. Please change its permissions (0777 or equivalent).</strong></p></div>';
	}

	// Saved ?
	if ( isset( $_POST['nfw_options']) ) {
		if ( empty($_POST['nfwnonce']) || ! wp_verify_nonce($_POST['nfwnonce'], 'fileguard_save') ) {
			wp_nonce_ays('fileguard_save');
		}
		nf_sub_fileguard_save();
		$nfw_options = get_option( 'nfw_options' );
		echo '<div class="updated settings-error"><p><strong>Your changes have been saved.</strong></p></div>';
	}

	if ( empty($nfw_options['fg_enable']) ) {
		$nfw_options['fg_enable'] = 0;
	} else {
		$nfw_options['fg_enable'] = 1;
	}
	if ( empty($nfw_options['fg_mtime']) || ! preg_match('/^[1-9][0-9]?$/', $nfw_options['fg_mtime']) ) {
		$nfw_options['fg_mtime'] = 10;
	}
	if ( empty($nfw_options['fg_exclude']) ) {
		$nfw_options['fg_exclude'] = '';
	}

	?>
	<br />
	<form method="post" name="nfwfilefuard" onSubmit="return check_fields();">
		<?php wp_nonce_field('fileguard_save', 'nfwnonce', 0); ?>
		<table class="form-table">
			<tr style="background-color:#F9F9F9;border: solid 1px #DFDFDF;">
				<th scope="row"><?php _e('Enable File Guard') ?></th>
				<td align="left">
				<label><input type="radio" id="fgenable" name="nfw_options[fg_enable]" value="1"<?php checked($nfw_options['fg_enable'], 1) ?> onclick="toogle_table(1);">&nbsp;<?php _e('Yes') ?></label>
				</td>
				<td align="left">
				<label><input type="radio" name="nfw_options[fg_enable]" value="0"<?php checked($nfw_options['fg_enable'], 0) ?> onclick="toogle_table(2);">&nbsp;<?php _e('No') ?></label>
				</td>
			</tr>
		</table>

		<br />

		<table class="form-table" border="0" id="fg_table"<?php echo $nfw_options['fg_enable'] == 1 ? '' : ' style="display:none"' ?>>
			<tr valign="top">
				<th scope="row">Real-time detection</th>
				<td align="left">
					Monitor file activity and send an alert when someone is accessing a PHP script that was modified or created less than <input maxlength="2" size="2" value="<?php echo $nfw_options['fg_mtime'] ?>" name="nfw_options[fg_mtime]" id="mtime" onkeyup="is_number('mtime')" type="text" title="Enter a value from 1 to 99" /> hour(s) ago.
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Exclude the following folder (optional)', NFI18N) ?></th>
				<td align="left"><input class="regular-text" type="text" name="nfw_options[fg_exclude]" value="<?php echo htmlspecialchars($nfw_options['fg_exclude']); ?>" placeholder="<?php _e('e.g.,', NFI18N) ?> /foo/bar/cache/" maxlength="150"><br /><span class="description"><?php _e('A full or partial case-sensitive string, max 150 characters.', NFI18N) ?></span></td>
			</tr>
		</table>
		<br />
		<input class="button-primary" type="submit" name="Save" value="Save File Guard options" />
	</form>
	</div>
<?php

}

/* ------------------------------------------------------------------ */

function nf_sub_fileguard_save() {

	// Block immediately if user is not allowed :
	nf_not_allowed( 'block', __LINE__ );

	$nfw_options = get_option( 'nfw_options' );

	// Disable or enable the File Guard ?
	if ( empty($_POST['nfw_options']['fg_enable']) ) {
		$nfw_options['fg_enable'] = 0;
	} else {
		$nfw_options['fg_enable'] = $_POST['nfw_options']['fg_enable'];
	}

	if ( empty($_POST['nfw_options']['fg_mtime']) || ! preg_match('/^[1-9][0-9]?$/', $_POST['nfw_options']['fg_mtime']) ) {
		$nfw_options['fg_mtime'] = 10;
	} else {
		$nfw_options['fg_mtime'] = $_POST['nfw_options']['fg_mtime'];
	}

	if ( empty($_POST['nfw_options']['fg_exclude']) || strlen($_POST['nfw_options']['fg_exclude']) > 150 ) {
		$nfw_options['fg_exclude'] = '';
	} else {
		$nfw_options['fg_exclude'] = stripslashes($_POST['nfw_options']['fg_exclude']);
	}

	// Update :
	update_option( 'nfw_options', $nfw_options );

}
/* ------------------------------------------------------------------ */

function nf_sub_network() {

	// Network menu (multi-site only) :

	if (! current_user_can( 'manage_network' ) ) {
		die( '<br /><br /><br /><div class="error settings-error"><p><strong>' .
			sprintf( __('You are not allowed to perform this task (%s).'), __LINE__) .
			'</strong></p></div>' );
	}

	$nfw_options = get_option( 'nfw_options' );

	echo '
<div class="wrap">
	<div style="width:54px;height:52px;background-image:url( ' . plugins_url() . '/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2>Network</h2>
	<br />';
	if (! is_multisite() ) {
		echo '<div class="updated settings-error"><p>You do not have a multisite network.</p></div></div>';
		return;
	}

	// Saved ?
	if ( isset( $_POST['nfw_options']) ) {
		if ( empty($_POST['nfwnonce']) || ! wp_verify_nonce($_POST['nfwnonce'], 'network_save') ) {
			wp_nonce_ays('network_save');
		}
		if ( $_POST['nfw_options']['nt_show_status'] == 2 ) {
			$nfw_options['nt_show_status'] = 2;
		} else {
			$nfw_options['nt_show_status'] = 1;
		}
		// Update options :
		update_option( 'nfw_options', $nfw_options );
		echo '<div class="updated settings-error"><p><strong>Your changes have been saved.</strong></p></div>';
		$nfw_options = get_option( 'nfw_options' );
	}

	if ( empty($nfw_options['nt_show_status']) ) {
		$nfw_options['nt_show_status'] = 1;
	}
?>
<form method="post" name="nfwnetwork">
<?php wp_nonce_field('network_save', 'nfwnonce', 0); ?>
<h3>NinjaFirewall Status</h3>
	<table class="form-table">
		<tr>
			<th scope="row">Display NinjaFirewall status icon in the admin bar of all sites in the network</th>
			<td align="left" width="200"><label><input type="radio" name="nfw_options[nt_show_status]" value="1"<?php echo $nfw_options['nt_show_status'] != 2 ? ' checked' : '' ?>>&nbsp;Yes (default)</label></td>
			<td align="left"><label><input type="radio" name="nfw_options[nt_show_status]" value="2"<?php echo $nfw_options['nt_show_status'] == 2 ? ' checked' : '' ?>>&nbsp;No</label></td>
		</tr>
	</table>

	<br />
	<br />
	<input class="button-primary" type="submit" name="Save" value="Save Network options" />
</form>
</div>
<?php
}

/* ------------------------------------------------------------------ */

function nf_sub_filecheck() {	// i18n

	// File Check menu :
	require( plugin_dir_path(__FILE__) . 'lib/nf_sub_filecheck.php' );

}

add_action('nfscanevent', 'nfscando');

function nfscando() {

	define('NFSCANDO', 1);
	nf_sub_filecheck();
}

/* ------------------------------------------------------------------ */

function nf_sub_event() {

	// Event Notifications menu :
	require( plugin_dir_path(__FILE__) . 'lib/nf_sub_event.php' );

}

add_action('init', 'nf_check_dbdata', 1);

/* ------------------------------------------------------------------ */

function nf_sub_log() {

	// Firewall Log menu :
	require( plugin_dir_path(__FILE__) . 'lib/nf_sub_log.php' );

}
/* ------------------------------------------------------------------ */

function nf_sub_live() {

	// Firewall Log menu :
	require( plugin_dir_path(__FILE__) . 'lib/nf_sub_livelog.php' );

}
/* ------------------------------------------------------------------ */

function nf_sub_loginprot() {

	// WordPress login form protection :

	// Block immediately if user is not allowed :
	nf_not_allowed( 'block', __LINE__ );

	echo '
<div class="wrap">
	<div style="width:54px;height:52px;background-image:url( ' . plugins_url() . '/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2>Login Protection</h2>
	<br />';

	// Saved ?
	if ( isset( $_POST['nfw_options']) ) {
		if ( empty($_POST['nfwnonce']) || ! wp_verify_nonce($_POST['nfwnonce'], 'bfd_save') ) {
			wp_nonce_ays('bfd_save');
		}
		$res = nf_sub_loginprot_save();
		if (! $res ) {
			echo '<div class="updated settings-error"><p><strong>Your changes have been saved.</strong></p></div>';
		} else {

			echo '<div class="error settings-error"><p><strong>' . $res . '</strong></p></div>';
		}
	}

	// Fetch the current configuration, if any :
	if ( file_exists( NFW_LOG_DIR . '/nfwlog/cache/bf_conf.php' ) ) {
		require( NFW_LOG_DIR . '/nfwlog/cache/bf_conf.php' );

		if (! @preg_match('/^[1-2]$/', $bf_enable) ) {
			$bf_enable = 0;
		}
		if (! @preg_match('/^(GET|POST|GETPOST)$/', $bf_request ) ) {
			$bf_request = 'POST';
		}
		if ( $bf_request == 'GETPOST' ) {
			$get_post = 'GET/POST';
		} else {
			$get_post = $bf_request;
		}
		if (! @preg_match('/^[1-9][0-9]?$/', $bf_bantime ) ) {
			$bf_bantime = 5;
		}
		if (! @preg_match('/^[1-9][0-9]?$/', $bf_attempt ) ) {
			$bf_attempt = 8;
		}
		if (! @preg_match('/^[1-9][0-9]?$/', $bf_maxtime ) ) {
			$bf_maxtime = 15;
		}
		if ( ( empty($auth_name) ) ||  ( @strlen( $auth_pass ) != 40 ) ) {
			$auth_name= '';
		}
		if ( ( empty($auth_msg) ) || ( @strlen( $auth_msg ) > 150 ) ) {
			$auth_msg = 'Access restricted';
		}
		if (empty($bf_xmlrpc) ) {
			$bf_xmlrpc = 0;
		} else {
			$bf_xmlrpc = 1;
		}
		if (empty($bf_authlog) ) {
			$bf_authlog = 0;
		} else {
			$bf_authlog = 1;
		}
	}

	if ( empty( $bf_enable ) ) {
		// Default values :
		$bf_enable   = 0;
		$get_post = $bf_request  = 'POST';
		$bf_bantime  = 5;
		$bf_attempt  = 8;
		$bf_maxtime  = 15;
		$auth_name = '';
		$auth_msg = 'Access restricted';
		$bf_xmlrpc = 0;
		$bf_authlog = 0;
	}
	?>
	<script type="text/javascript">
	function is_number(id) {
		var e = document.getElementById(id);
		if (! e.value ) { return }
		if (! /^[1-9][0-9]?$/.test(e.value) ) {
			alert("Please enter a number from 1 to 99 in 'Password-protect' field.");
			e.value = e.value.substring(0, e.value.length-1);
		}
	}
	function auth_user_valid() {
		var e = document.bp_form.elements['nfw_options[auth_name]'];
		if ( e.value.match(/[^-\/\\_.a-zA-Z0-9]/) ) {
			alert('Invalid character.');
			e.value = e.value.replace(/[^-\/\\_.a-zA-Z0-9]/g,'');
			return false;
		}
		if (e.value == 'admin') {
			alert('"admin" is not acceptable, please choose another user name.');
			e.value = '';
			return false;
		}
	}
	function realm_valid() {
		var e = document.bp_form.elements['nfw_options[auth_msg]'];
		if ( e.value.match(/[^\x20-\x7e\x80-\xff]/) ) {
			alert('Invalid character.');
			e.value = e.value.replace(/[^\x20-\x7e\x80-\xff]/g,'');
			return false;
		}
	}
	function toogle_table(off) {
		if ( off == 1 ) {
			document.getElementById('bf_table').style.display = '';
			document.getElementById('bf_table1').style.display = '';
			document.getElementById('bf_table2').style.display = '';
			document.getElementById('bf_table3').style.display = '';
		} else if ( off == 2 ) {
			document.getElementById('bf_table').style.display = 'none';
			document.getElementById('bf_table3').style.display = 'none';
			document.getElementById('bf_table1').style.display = '';
			document.getElementById('bf_table2').style.display = '';
		} else {
			document.getElementById('bf_table').style.display = 'none';
			document.getElementById('bf_table1').style.display = 'none';
			document.getElementById('bf_table2').style.display = 'none';
			document.getElementById('bf_table3').style.display = 'none';
		}
		return;
	}
	function getpost(request){
		if ( request == 'GETPOST' ) {
			request = 'GET/POST';
		}
		document.getElementById('get_post').innerHTML = request;
	}
	</script>
<br />
<form method="post" name="bp_form">
	<?php wp_nonce_field('bfd_save', 'nfwnonce', 0); ?>
	<table class="form-table">
		<tr style="background-color:#F9F9F9;border: solid 1px #DFDFDF;">
			<th scope="row"><?php _e('Enable brute force attack protection') ?></th>
			<td>&nbsp;</td>
			<td align="left">
			<label><input type="radio" name="nfw_options[bf_enable]" value="1"<?php checked($bf_enable, 1) ?> onclick="toogle_table(1);">&nbsp;<?php _e('Yes, if under attack') ?></label>
			</td>
			<td align="left">
			<label><input type="radio" name="nfw_options[bf_enable]" value="2"<?php checked($bf_enable, 2) ?> onclick="toogle_table(2);">&nbsp;<?php _e('Always ON') ?></label>
			</td>
			<td align="left">
			<label><input type="radio" name="nfw_options[bf_enable]" value="0"<?php checked($bf_enable, 0) ?> onclick="toogle_table(0);">&nbsp;<?php _e('No (default)') ?></label>
			</td>
		</tr>
	</table>
	<br />
	<table class="form-table" id="bf_table"<?php echo $bf_enable == 1 ? '' : ' style="display:none"' ?>>
		<tr>
			<th scope="row"><?php _e('Protect the login page against') ?></th>
			<td align="left">
			<p><label><input onclick="getpost(this.value);" type="radio" name="nfw_options[bf_request]" value="GET"<?php checked($bf_request, 'GET') ?>>&nbsp;<?php _e('<code>GET</code> request attacks') ?></label></p>
			<p><label><input onclick="getpost(this.value);" type="radio" name="nfw_options[bf_request]" value="POST"<?php checked($bf_request, 'POST') ?>>&nbsp;<?php _e('<code>POST</code> request attacks (default)') ?></label></p>
			<p><label><input onclick="getpost(this.value);" type="radio" name="nfw_options[bf_request]" value="GETPOST"<?php checked($bf_request, 'GETPOST') ?>>&nbsp;<?php _e('<code>GET</code> and <code>POST</code> requests attacks') ?></label></p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">Password-protect it</th>
			<td align="left">
				For <input maxlength="2" size="2" value="<?php echo $bf_bantime ?>" name="nfw_options[bf_bantime]" id="ban1" onkeyup="is_number('ban1')" type="text" title="Enter a value from 1 to 99" /> minutes, if more than <input maxlength="2" size="2" value="<?php echo $bf_attempt ?>" name="nfw_options[bf_attempt]" id="ban2" onkeyup="is_number('ban2')" type="text" title="Enter a value from 1 to 99" /> <code id="get_post"><?php echo $get_post; ?></code> requests within <input maxlength="2" size="2" value="<?php echo $bf_maxtime ?>" name="nfw_options[bf_maxtime]" id="ban3" onkeyup="is_number('ban3')" type="text" title="Enter a value from 1 to 99" /> seconds.
			</td>
		</tr>
	</table>
	<table class="form-table" id="bf_table1"<?php echo $bf_enable ? '' : ' style="display:none"' ?>>
		<tr>
			<th scope="row">&nbsp;</th>
			<td align="left">
			<label><input type="checkbox" name="nfw_options[bf_xmlrpc]" value="1"<?php checked($bf_xmlrpc, 1) ?>>&nbsp;Apply the protection to the <code>xmlrpc.php</code> script as well.</label>
			</td>
		</tr>
	</table>

	<?php
	if ( empty($auth_pass) ) {
		$placeholder = '';
	} else {
		$placeholder = '&#149;&#149;&#149;&#149;&#149;&#149;&#149;&#149;';
	}
	?>
	<table class="form-table" id="bf_table2"<?php echo $bf_enable ? '' : ' style="display:none"' ?>>
		<tr valign="top">
			<th scope="row">HTTP authentication</th>
			<td align="left">
				User:&nbsp;<input maxlength="32" type="text" autocomplete="off" value="<?php echo $auth_name ?>" size="12" name="nfw_options[auth_name]" onkeyup="auth_user_valid();" />&nbsp;&nbsp;&nbsp;&nbsp;Password:&nbsp;<input maxlength="32" placeholder="<?php echo $placeholder ?>" type="password" autocomplete="off" value="" size="12" name="nfw_options[auth_pass]" />
				<br /><span class="description">&nbsp;<?php _e('User and Password must be from 6 to 32 characters.', NFI18N) ?></span>
				<br /><br />Message (max. 150 ASCII characters):<br />
				<input type="text" autocomplete="off" value="<?php echo htmlspecialchars($auth_msg) ?>" maxlength="150" size="50" name="nfw_options[auth_msg]" onkeyup="realm_valid();" />
			</td>
		</tr>
	</table>
	<table class="form-table" id="bf_table3"<?php echo $bf_enable == 1 ? '' : ' style="display:none"' ?>>
		<tr valign="top">
			<th scope="row">AUTH log</th>
			<td align="left">
				<?php
				// Ensure that openlog() and syslog() are not disabled:
				if (! function_exists('syslog') || ! function_exists('openlog') ) {
					$bf_authlog = 0;
					$bf_msg = __('Your server configuration is not compatible with that option.');
					$enabled = 0;
				} else {
					$bf_msg = __('See contextual help before enabling this option.');
					$enabled = 1;
				}
				?>
				<label><input type="checkbox" name="nfw_options[bf_authlog]" value="1"<?php checked($bf_authlog, 1) ?><?php disabled($enabled, 0)?>>&nbsp;<?php _e('Write incident to the server <code>AUTH</code> log.') ?></label>
				<br />
				<span class="description"><?php echo $bf_msg ?></span>
			</td>
		</tr>
	</table>
	<br />
	<br />
	<input id="save_login" class="button-primary" type="submit" name="Save" value="<?php _e('Save Login Protection') ?>" />
	<div align="right">See our benchmark and stress-test:
	<br />
	<a href="http://blog.nintechnet.com/wordpress-brute-force-attack-detection-plugins-comparison/" target="_blank">WordPress brute-force attack detection plugins comparison.</a>
	<br />
	<a href="http://blog.nintechnet.com/brute-force-attack-protection-in-a-production-environment/" target="_blank">WordPress brute-force attack protection in a production environment.</a>
	<br />
	<a href="http://blog.nintechnet.com/installing-ninjafirewall-with-hhvm-hiphop-virtual-machine/#benchmarks" target="_blank">Benchmarks with PHP 5.5.6 and Hip-Hop VM 3.4.2.</a>
	</div>
</form>
</div>

<?php

}

/* ------------------------------------------------------------------ */

function nf_sub_loginprot_save() {

	// Block immediately if user is not allowed :
	nf_not_allowed( 'block', __LINE__ );

	// The directory must be writable :
	if (! is_writable( NFW_LOG_DIR . '/nfwlog/cache' ) ) {
		return( 'Error : <code>' . htmlspecialchars(NFW_LOG_DIR) .
			'/nfwlog/cache</code> directory is not writable. Please chmod it to 0777.');
	}

	$nfw_options = get_option( 'nfw_options' );

	$bf_rand = '';
	if ( file_exists( NFW_LOG_DIR . '/nfwlog/cache/bf_conf.php' ) ) {
		require( NFW_LOG_DIR . '/nfwlog/cache/bf_conf.php' );
	}

	// Disable or enable the protection ?
	if ( empty( $_POST['nfw_options']['bf_enable']) ) {
		// Remove all files :
		if ( file_exists( NFW_LOG_DIR . '/nfwlog/cache/bf_conf.php' ) ) {
			if (! unlink( NFW_LOG_DIR . '/nfwlog/cache/bf_conf.php' ) ) {
				return( 'Error : <code>' . htmlspecialchars(NFW_LOG_DIR) .
					'/nfwlog/cache/bf_conf.php</code> is read-only and cannot be deleted. Please chmod it to 0777.');
			}
		}
		if ( file_exists( NFW_LOG_DIR . '/nfwlog/cache/bf_blocked' . $_SERVER['SERVER_NAME'] . $bf_rand ) ) {
			if (! unlink( NFW_LOG_DIR . '/nfwlog/cache/bf_blocked' . $_SERVER['SERVER_NAME'] . $bf_rand )) {
				return( 'Error : <code>' . htmlspecialchars(NFW_LOG_DIR) .
					'/nfwlog/cache/bf_blocked</code> is read-only and cannot be deleted. Please chmod it to 0777.');
			}
		}
		if ( file_exists( NFW_LOG_DIR . '/nfwlog/cache/bf_' . $_SERVER['SERVER_NAME'] . $bf_rand ) ) {
			if (! unlink( NFW_LOG_DIR . '/nfwlog/cache/bf_' . $_SERVER['SERVER_NAME'] . $bf_rand )) {
				return( 'Error : <code>' . htmlspecialchars(NFW_LOG_DIR) .
					'/nfwlog/cache/bf_' . $_SERVER['SERVER_NAME'] . $bf_rand . '</code> is read-only and cannot be deleted. Please chmod it to 0777.');
			}
		}
		return 0;
	}

	if ( preg_match( '/^[12]$/', $_POST['nfw_options']['bf_enable'] ) ) {
		$bf_enable = $_POST['nfw_options']['bf_enable'];
	} else {
		$bf_enable = 1;
	}

	// Ensure we have all values, otherwise set the default ones :
	if ( @preg_match('/^(GET|POST|GETPOST)$/', $_POST['nfw_options']['bf_request'] ) ) {
		$bf_request = $_POST['nfw_options']['bf_request'];
	} else {
		// Default value :
		$bf_request = 'POST';
	}

	if ( @preg_match('/^[1-9][0-9]?$/', $_POST['nfw_options']['bf_bantime'] ) ) {
		$bf_bantime = $_POST['nfw_options']['bf_bantime'];
	} else {
		// Default value :
		$bf_bantime = 5;
	}
	if ( @preg_match('/^[1-9][0-9]?$/', $_POST['nfw_options']['bf_attempt'] ) ) {
		$bf_attempt = $_POST['nfw_options']['bf_attempt'];
	} else {
		// Default value :
		$bf_attempt = 8;
	}
	if ( @preg_match('/^[1-9][0-9]?$/', $_POST['nfw_options']['bf_maxtime'] ) ) {
		$bf_maxtime = $_POST['nfw_options']['bf_maxtime'];
	} else {
		// Default value :
		$bf_maxtime = 15;
	}

	if ( empty($_POST['nfw_options']['bf_xmlrpc']) ) {
		$bf_xmlrpc = 0;
	} else {
		$bf_xmlrpc = 1;
	}

	if ( empty($_POST['nfw_options']['bf_authlog']) ) {
		$bf_authlog = 0;
	} else {
		$bf_authlog = 1;
	}

	if ( empty($_POST['nfw_options']['auth_name']) ) {
		return( 'Error : please enter a user name for HTTP authentication.');
	} elseif (! preg_match('`^[-/\\_.a-zA-Z0-9]{6,32}$`', $_POST['nfw_options']['auth_name']) ) {
		return( 'Error : HTTP authentication user name is not valid.');
	}
	$auth_name = $_POST['nfw_options']['auth_name'];

	if ( empty($_POST['nfw_options']['auth_pass']) ) {
		if ( empty($auth_name) || empty($auth_pass) ) {
			return( 'Error : please enter a user name and password for HTTP authentication.');
		}
	} elseif ( (strlen($_POST['nfw_options']['auth_pass']) < 6 ) || (strlen($_POST['nfw_options']['auth_pass']) > 32 ) ) {
		return( __('Error : password must be from 6 to 32 characters.', NFI18N) );
	} else {
		// Use stripslashes() to prevent WordPress from escaping the password:
		$auth_pass = sha1( stripslashes( $_POST['nfw_options']['auth_pass'] ) );
	}

	if ( ( empty($_POST['nfw_options']['auth_msg']) ) || ( @strlen( $_POST['nfw_options']['auth_msg'] ) > 150 ) ) {
		$auth_msg = 'Access restricted';
	} else {
		$auth_msg = str_replace( array('\\', "'", '"', '<', '>', '&'),	"",  stripslashes( $_POST['nfw_options']['auth_msg']) );
	}

	if ( empty( $bf_rand ) ) {
		$bf_rand = mt_rand(100000, 999999);
	}
	// Save it :
	$data = '<?php $bf_enable=' . $bf_enable . ';$bf_request=\'' . $bf_request .
		'\';$bf_bantime=' . $bf_bantime . ';' . '$bf_attempt=' . $bf_attempt .
		';$bf_maxtime=' . $bf_maxtime . ';$bf_xmlrpc=' . $bf_xmlrpc. ';' .
		'$auth_name=\'' . $auth_name . '\';$auth_pass=\'' . $auth_pass . '\';' .
		'$auth_msg=\'' . $auth_msg . '\';$bf_rand=\'' . $bf_rand . '\';' .
		'$bf_authlog=' . $bf_authlog . '; ?>';

	$fh = fopen( NFW_LOG_DIR . '/nfwlog/cache/bf_conf.php', 'w' );
	if (! $fh) {
		return( 'Error : unable to write the configuration to <code>' .
			htmlspecialchars(NFW_LOG_DIR) . '/nfwlog/cache/bf_conf.php</code>!');
	}
	fwrite( $fh, $data );
	fclose( $fh );

	// We reset the brute-force protection flag for the logged in user :
	if (! empty($_SESSION['nfw_bfd']) ) {
		unset($_SESSION['nfw_bfd']);
	}

}

/* ------------------------------------------------------------------ */

function nfw_log2($loginfo, $logdata, $loglevel, $ruleid) { // i18n

	// Write incident to the firewall log :

	$nfw_options = get_option( 'nfw_options' );

	if (! empty($nfw_options['debug']) ) {
		$num_incident = '0000000';
		$loglevel = 7;
		$http_ret_code = '200';
	// Create a random incident number :
	} else {
		$num_incident = mt_rand(1000000, 9000000);
		$http_ret_code = $nfw_options['ret_code'];
	}
   if (strlen($logdata) > 200) { $logdata = mb_substr($logdata, 0, 200, 'utf-8') . '...'; }
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
	nfw_get_blogtimezone();

	$cur_month = date('Y-m');
	$stat_file = NFW_LOG_DIR . '/nfwlog/stats_' . $cur_month . '.php';
	$log_file  = NFW_LOG_DIR . '/nfwlog/firewall_' . $cur_month . '.php';

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

	// if $loglevel == 4, we don't log/need SCRIPT_NAME, IP and method :
	if ( $loglevel == 4 ) {
		$SCRIPT_NAME = '-';
		$REQUEST_METHOD = 'N/A';
		$REMOTE_ADDR = '0.0.0.0';
		$loglevel = 6;
	} else {
		$SCRIPT_NAME = $_SERVER['SCRIPT_NAME'];
		$REQUEST_METHOD = $_SERVER['REQUEST_METHOD'];
		$REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
	}

	if (! file_exists($log_file) ) {
		$tmp = '<?php exit; ?>' . "\n";
	} else {
		$tmp = '';
	}

	@file_put_contents( $log_file,
      $tmp . '[' . time() . '] ' . '[0] ' .
      '[' . $_SERVER['SERVER_NAME'] . '] ' . '[#' . $num_incident . '] ' .
      '[' . $ruleid . '] ' .
      '[' . $loglevel . '] ' . '[' . $REMOTE_ADDR . '] ' .
      '[' . $http_ret_code . '] ' . '[' . $REQUEST_METHOD . '] ' .
      '[' . $SCRIPT_NAME . '] ' . '[' . $loginfo . '] ' .
      '[' . $res . ']' . "\n", FILE_APPEND | LOCK_EX);
}

/* ------------------------------------------------------------------ */

function nf_sub_edit() {

	// Rules Editor menu :

	// Block immediately if user is not allowed :
	nf_not_allowed( 'block', __LINE__ );

	echo '
<div class="wrap">
	<div style="width:54px;height:52px;background-image:url( ' . plugins_url() . '/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2>Rules Editor</h2>
	<br />';

	$nfw_rules = get_option( 'nfw_rules' );
	$is_update = 0;

	if ( isset($_POST['sel_e_r']) ) {
		if ( empty($_POST['nfwnonce']) || ! wp_verify_nonce($_POST['nfwnonce'], 'editor_save') ) {
			wp_nonce_ays('editor_save');
		}
		if ( $_POST['sel_e_r'] < 1 ) {
			echo '<div class="error settings-error"><p><strong>Error : you did not select a rule to disable</strong></p></div>';
		} else if ( ( $_POST['sel_e_r'] == 2 ) || ( $_POST['sel_e_r'] > 499 ) && ( $_POST['sel_e_r'] < 600 ) ) {
			echo '<div class="error settings-error"><p><strong>Error : to change this rule, use the "Firewall Policies" menu.</strong></p></div>';
		} else if (! isset( $nfw_rules[$_POST['sel_e_r']] ) ) {
			echo '<div class="error settings-error"><p><strong>Error : this rule does not exist&nbsp;!</strong></p></div>';
		} elseif ($_POST['sel_e_r'] != 999) {
			$nfw_rules[$_POST['sel_e_r']]['on'] = 0;
			$is_update = 1;
			echo '<div class="updated settings-error"><p><strong>Rule ID ' . htmlentities($_POST['sel_e_r']) . ' has been disabled.</strong></p></div>';
		}
	} else if ( isset($_POST['sel_d_r']) ) {
		if ( empty($_POST['nfwnonce']) || ! wp_verify_nonce($_POST['nfwnonce'], 'editor_save') ) {
			wp_nonce_ays('editor_save');
		}
		if ( $_POST['sel_d_r'] < 1 ) {
			echo '<div class="error settings-error"><p><strong>Error : you did not select a rule to enable</strong></p></div>';
		} else if ( ( $_POST['sel_d_r'] == 2 ) || ( $_POST['sel_d_r'] > 499 ) && ( $_POST['sel_d_r'] < 600 ) ) {
			echo '<div class="error settings-error"><p><strong>Error : to change this rule, use the "Firewall Policies" menu.</strong></p></div>';
		} else if (! isset( $nfw_rules[$_POST['sel_d_r']] ) ) {
			echo '<div class="error settings-error"><p><strong>Error : this rule does not exist&nbsp;!</strong></p></div>';
		} elseif ($_POST['sel_d_r'] != 999) {
			$nfw_rules[$_POST['sel_d_r']]['on'] = 1;
			$is_update = 1;
			echo '<div class="updated settings-error"><p><strong>Rule ID ' . htmlentities($_POST['sel_d_r']) . ' has been enabled.</strong></p></div>';
		}
	}
	if ( $is_update ) {
		update_option( 'nfw_rules', $nfw_rules);
	}

	$disabled_rules = $enabled_rules = array();
	foreach ( $nfw_rules as $rule_key => $rule_value ) {
		if ( $rule_key == 999 ) { continue; }
		if (! empty( $nfw_rules[$rule_key]['on'] ) ) {
			$enabled_rules[] =  $rule_key;
		} else {
			$disabled_rules[] = $rule_key;
		}
	}

	echo '<br /><h3>NinjaFirewall built-in security rules</h3>
	<table class="form-table">
		<tr>
			<th scope="row">Select the rule you want to disable or enable</th>
			<td align="left">
			<form method="post">'. wp_nonce_field('editor_save', 'nfwnonce', 0) . '
			<select name="sel_e_r" style="font-family:Consolas,Monaco,monospace;">
				<option value="0">Total rules enabled : ' . count( $enabled_rules ) . '</option>';
	sort( $enabled_rules );
	$count = 0;

	$desr = '';
	foreach ( $enabled_rules as $key ) {
		if ( $key == 999 ) { continue; }
		// grey-out those ones, they can be changed in the Firewall Policies section:
		if ( ( $key == 2 ) || ( $key > 499 ) && ( $key < 600 ) ) {
			echo '<option value="0" disabled="disabled">Rule ID : ' . htmlspecialchars($key) . ' Firewall policies</option>';
		} else {
			if ( $key < 100 ) {
				$desc = __(' (remote/local file inclusion)');
			} elseif ( $key < 150 ) {
				$desc = __(' (cross-site scripting/XSS)');
			} elseif ( $key < 200 ) {
				$desc = __(' (code injection)');
			} elseif ( $key < 250 ) {
				$desc = __(' (SQL injection)');
			} elseif ( $key < 350 ) {
				$desc = __(' (various)');
			} elseif ( $key < 400 ) {
				$desc = __(' (backdoor shells)');
			} elseif ( $key > 1299 ) {
				$desc = __(' (WP vulnerabilities)');
			}
			echo '<option value="' . htmlspecialchars($key) . '">Rule ID : ' . htmlspecialchars($key) . $desc . '</option>';
			$count++;
		}
	}
	echo '</select>&nbsp;&nbsp;<input class="button-secondary" type="submit" name="disable" value="Disable it"' . disabled( $count, 0) .'>
		</form>
		<br />
		<form method="post">'. wp_nonce_field('editor_save', 'nfwnonce', 0) . '
		<select name="sel_d_r" style="font-family:Consolas,Monaco,monospace;">
		<option value="0">Total rules disabled : ' . count( $disabled_rules ) . '</option>';
	sort( $disabled_rules );
	$count = 0;
	foreach ( $disabled_rules as $key ) {
		if ( $key == 999 ) { continue; }
		// grey-out those ones, they can be changed in the Firewall Policies section:
		if ( ( $key == 2 ) || ( $key > 499 ) && ( $key < 600 ) ) {
			echo '<option value="0" disabled="disabled">Rule ID #' . htmlspecialchars($key) . ' Firewall policies</option>';
		} else {
			if ( $key < 100 ) {
				$desc = __(' (remote/local file inclusion)');
			} elseif ( $key < 150 ) {
				$desc = __(' (cross-site scripting/XSS)');
			} elseif ( $key < 200 ) {
				$desc = __(' (code injection)');
			} elseif ( $key < 250 ) {
				$desc = __(' (SQL injection)');
			} elseif ( $key < 350 ) {
				$desc = __(' (various)');
			} elseif ( $key < 400 ) {
				$desc = __(' (backdoor shells)');
			} elseif ( $key > 1299 ) {
				$desc = __(' (WP vulnerabilities)');
			}
			echo '<option value="' . htmlspecialchars($key) . '">Rule ID #' . htmlspecialchars($key) . $desc . '</option>';
			$count++;
		}
	}

	echo '</select>&nbsp;&nbsp;<input class="button-secondary" type="submit" name="disable" value="Enable it"' . disabled( $count, 0) .'>
				</form>
				<br /><span class="description">Greyed out rules can be changed in the <a href="?page=nfsubpolicies">Firewall Policies</a> page.</span>
			</td>
		</tr>
	</table>
</div>';

}

/* ------------------------------------------------------------------ */

function nf_sub_updates() {

	// Updates

	require( plugin_dir_path(__FILE__) . 'lib/nf_sub_updates.php');

}

add_action('nfsecupdates', 'nfupdatesdo');

function nfupdatesdo() {
	define('NFUPDATESDO', 1);
	nf_sub_updates();
}

/* ------------------------------------------------------------------ */

function nf_sub_wplus() {

	// WP+ intro

	require( plugin_dir_path(__FILE__) . 'lib/nf_sub_wplus.php' );
}

/* ------------------------------------------------------------------ */

function nf_sub_about() {

	// About menu :

	// Block immediately if user is not allowed :
	nf_not_allowed( 'block', __LINE__ );

	if ( $data = @file_get_contents( plugin_dir_path(__FILE__) . 'readme.txt' ) ) {
		$what = '== Changelog ==';
		$pos_start = strpos( $data, $what );
		$changelog = substr( $data, $pos_start + strlen( $what ) + 1 );
	} else {
		$changelog = 'Error : cannot find changelog :(';
	}

	echo '<script>
function show_table(table_id) {
	var av_table = [11, 12, 13, 14];
	for (var i = 0; i < av_table.length; i++) {
		if ( table_id == av_table[i] ) {
			document.getElementById(table_id).style.display = "";
		} else {
			document.getElementById(av_table[i]).style.display = "none";
		}
	};
}
</script>
<div class="wrap">
	<div style="width:54px;height:52px;background-image:url( ' . plugins_url() . '/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;" title="NinTechNet"></div>
	<h2>About</h2>
	<br />
	<br />
	<center>
		<table border="0" width="500" style="border: 1px solid #DFDFDF;padding:10px;-moz-box-shadow:-3px 5px 5px #999;-webkit-box-shadow:-3px 5px 5px #999;box-shadow:-3px 5px 5px #999;background-color:#FCFCFC;">
			<tr style="text-align:center">
				<td>
					<font style="font-size: 1.2em; font-weight: bold;">NinjaFirewall (WP edition) v' . NFW_ENGINE_VERSION . '</font>
					<br />
					<br />
					<a href="http://nintechnet.com/" target="_blank" title="The Ninja Technologies Network"><img src="' . plugins_url() . '/ninjafirewall/images/nintechnet.png" border="0" width="190" height="60" title="The Ninja Technologies Network"></a>
					<br />
					&copy; 2012-' . date( 'Y' ) . ' <a href="http://nintechnet.com/" target="_blank" title="The Ninja Technologies Network"><strong>NinTechNet</strong></a>
					<br />
					The Ninja Technologies Network
					<p><a href="https://twitter.com/nintechnet"><img border="0" src="'. plugins_url( '/images/twitter_ntn.png', __FILE__ ) .'" width="116" height="28" target="_blank"></a></p>
					<table border="0" cellspacing="2" cellpadding="10" width="100%">
						<tr valign=top>
							<td align="center" width="33%">
								<img src="' . plugins_url( '/images/logo_nm_65.png', __FILE__ ) . '" width="65" height="65" border=0>
								<br />
								<a href="http://ninjamonitoring.com/" title="NinjaMonitoring: monitor your website for suspicious activities"><b>NinjaMonitoring.com</b></a>
								<br />
								Monitor your website for just $4.99 per month.
							</td>
							<td align="center" width="34%">
								<img src="' . plugins_url( '/images/logo_pro_65.png', __FILE__ ) . '" width="65" height="65" border=0>
								<br />
								<a href="http://ninjafirewall.com/" title="NinjaFirewall: advanced firewall software for all your PHP applications"><b>NinjaFirewall.com</b></a>
								<br />
								Advanced firewall software for all your PHP applications.
							</td>
							<td align="center" width="33%">
								<img src="' . plugins_url( '/images/logo_nr_65.png', __FILE__ ) . '" width="65" height="65" border=0>
								<br />
								<a href="http://ninjarecovery.com/" title="NinjaRecovery: Incident response, malware removal and hacking recovery"><b>NinjaRecovery.com</b></a>
								<br />
								Incident response, malware removal and hacking recovery.
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<br />
		<br />
		<input class="button-secondary" type="button" value="Changelog" onclick="show_table(12);">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input class="button-primary" type="button" value="Spread the word about the Ninja !" onclick="show_table(11);" autofocus>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input class="button-secondary" type="button" value="System Info" onclick="show_table(13);">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input class="button-secondary" type="button" value="Privacy Policy" onclick="show_table(14);">
		<br />
		<br />

		<table id="11" border="0" width="500">
			<tr style="text-align:center;">
				<td><a href="http://www.facebook.com/sharer.php?u=http://ninjafirewall.com/" target="_blank"><img src="' . plugins_url( '/images/facebook.png', __FILE__ ) . '" width="90" height="90" style="border: 0px solid #DFDFDF;padding:0px;-moz-box-shadow:-3px 5px 5px #999;-webkit-box-shadow:-3px 5px 5px #999;box-shadow:-3px 5px 5px #999;background-color:#FCFCFC;"></a></td>
				<td><a href="https://plus.google.com/share?url=http://ninjafirewall.com/" target="_blank"><img src="' . plugins_url( '/images/google.png', __FILE__ ) . '" width="90" height="90" style="border: 0px solid #DFDFDF;padding:0px;-moz-box-shadow:-3px 5px 5px #999;-webkit-box-shadow:-3px 5px 5px #999;box-shadow:-3px 5px 5px #999;background-color:#FCFCFC;"></a></td>
				<td><a href="http://twitter.com/share?text=NinjaFirewall&url=http://ninjafirewall.com/" target="_blank"><img src="' . plugins_url( '/images/twitter.png', __FILE__ ) . '" width="90" height="90" style="border: 0px solid #DFDFDF;padding:0px;-moz-box-shadow:-3px 5px 5px #999;-webkit-box-shadow:-3px 5px 5px #999;box-shadow:-3px 5px 5px #999;background-color:#FCFCFC;"></a></td>
			</tr>
		</table>

		<table id="12" style="display:none;" width="500">
			<tr>
				<td>
					<textarea class="small-text code" cols="60" rows="8">' . htmlspecialchars($changelog) . '</textarea>
				</td>
			</tr>
		</table>

		<table id="13" border="0" style="display:none;" width="500">
			<tr valign="top"><td width="47%;" align="right">REMOTE_ADDR</td><td width="3%">&nbsp;</td><td width="50%" align="left">' . htmlspecialchars($_SERVER['REMOTE_ADDR']) . '</td></tr>
			<tr valign="top"><td width="47%;" align="right">SERVER_ADDR</td><td width="3%">&nbsp;</td><td width="50%" align="left">' .htmlspecialchars($_SERVER['SERVER_ADDR']) . '</td></tr>';

	if ( PHP_VERSION ) {
		echo '<tr valign="top"><td width="47%;" align="right">PHP version</td><td width="3%">&nbsp;</td><td width="50%" align="left">'. PHP_VERSION . ' (';
		if ( defined('HHVM_VERSION') ) {
			echo 'HHVM';
		} else {
			echo strtoupper(PHP_SAPI);
		}
		echo ')</td></tr>';
	}
	if ( $_SERVER['SERVER_SOFTWARE'] ) {
		echo '<tr valign="top"><td width="47%;" align="right">HTTP server</td><td width="3%">&nbsp;</td><td width="50%" align="left">' . htmlspecialchars($_SERVER['SERVER_SOFTWARE']) . '</td></tr>';
	}
	if ( PHP_OS ) {
		echo '<tr valign="top"><td width="47%;" align="right">Operating System</td><td width="3%">&nbsp;</td><td width="50%" align="left">' . PHP_OS . '</td></tr>';
	}
	if ( $load = sys_getloadavg() ) {
		echo '<tr valign="top"><td width="47%;" align="right">Load Average</td><td width="3%">&nbsp;</td><td width="50%" align="left">' . $load[0] . ', '. $load[1] . ', '. $load[2] . '</td></tr>';
	}
	if (! preg_match( '/^win/i', PHP_OS ) ) {
		$MemTotal = $MemFree = $Buffers = $Cached = 0;
		$data = @explode( "\n", `cat /proc/meminfo` );
		foreach ( $data as $line ) {
			if ( preg_match( '/^MemTotal:\s+?(\d+)\s/', $line, $match ) ) {
				$MemTotal = $match[1] / 1024;
			} elseif ( preg_match( '/^MemFree:\s+?(\d+)\s/', $line, $match ) ) {
				$MemFree = $match[1];
			} elseif ( preg_match( '/^Buffers:\s+?(\d+)\s/', $line, $match ) ) {
				$Buffers = $match[1];
			} elseif ( preg_match( '/^Cached:\s+?(\d+)\s/', $line, $match ) ) {
				$Cached = $match[1];
			}
		}
		$free = ( $MemFree + $Buffers + $Cached ) / 1024;
		if ( $free ) {
			echo '<tr valign="top"><td width="47%;" align="right">RAM</td><td width="3%">&nbsp;</td><td width="50%" align="left">' . number_format( $free ) . ' MB free / '. number_format( $MemTotal ) . ' MB total</td></tr>';
		}

		$cpu = @explode( "\n", `grep 'model name' /proc/cpuinfo` );
		if (! empty( $cpu[0] ) ) {
			array_pop( $cpu );
			echo '<tr valign="top"><td width="47%;" align="right">Processor(s)</td><td width="3%">&nbsp;</td><td width="50%" align="left">' . count( $cpu ) . '</td></tr>';
			echo '<tr valign="top"><td width="47%;" align="right">CPU model</td><td width="3%">&nbsp;</td><td width="50%" align="left">' . str_replace ("model name\t:", '', htmlspecialchars($cpu[0])) . '</td></tr>';
		}
	}

	echo '
		</table>
		<table id="14" style="display:none;" width="500">
			<tr>
				<td>
					<textarea class="small-text code" cols="60" rows="8">NinTechNet strictly follows the WordPress Plugin Developer guidelines &lt;http://wordpress.org/plugins/about/guidelines/&gt;: NinjaFirewall (WP edition) is 100% free, 100% open source and 100% fully functional, no "trialware", no "obfuscated code", no "crippleware", no "phoning home". It does not require a registration process or an activation key to be installed or used.' . "\n" . 'Because we do not collect any user data, we do not even know that you are using (and hopefully enjoying!) our product.</textarea>
				</td>
			</tr>
		</table>
	</center>
</div>';

}
/* ------------------------------------------------------------------ */

function ninjafirewall_settings_link( $links ) {

	// Block immediately if user is not allowed :
	nf_not_allowed( 'block', __LINE__ );

   $links[] = '<a href="'. get_admin_url(null, 'admin.php?page=NinjaFirewall') .'">Settings</a>';
   $links[] = '<a href="http://ninjafirewall.com/wordpress/nfwplus.php" target="_blank">WP+ edition</a>';
   return $links;
}

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'ninjafirewall_settings_link' );

/* ------------------------------------------------------------------ */

function nfw_get_blogtimezone() {

	// Try to get the user timezone from WP configuration...
	$tzstring = get_option( 'timezone_string' );
	// ...or from PHP...
	if (! $tzstring ) {
		$tzstring = ini_get( 'date.timezone' );
		// ...or use UTC :
		if (! $tzstring ) {
			$tzstring = 'UTC';
		}
	}
	// Set the timezone :
	date_default_timezone_set( $tzstring );
}
/* ------------------------------------------------------------------ */

function nfw_check_emailalert() {

	$nfw_options = get_option( 'nfw_options' );

	if ( ( is_multisite() ) && ( $nfw_options['alert_sa_only'] == 2 ) ) {
		$recipient = get_option('admin_email');
	} else {
		$recipient = $nfw_options['alert_email'];
	}

	global $current_user;
	$current_user = wp_get_current_user();

	// Check what it is :
	list( $a_1, $a_2, $a_3 ) = explode( ':', NFW_ALERT . ':' );

	// Shall we alert the admin ?
	if (! empty($nfw_options['a_' . $a_1 . $a_2]) ) {
		$alert_array = array(
			'1' => array (
				'0' => 'Plugin', '1' => 'uploaded',	'2' => 'installed', '3' => 'activated',
				'4' => 'updated', '5' => 'deactivated', '6' => 'deleted', 'label' => 'Name'
			),
			'2' => array (
				'0' => 'Theme', '1' => 'uploaded', '2' => 'installed', '3' => 'activated',
				'4' => 'deleted', 'label' => 'Name'
			),
			'3' => array (
				'0' => 'WordPress', '1' => 'upgraded',	'label' => 'Version'
			)
		);

		// Get timezone :
		nfw_get_blogtimezone();

		if ( substr_count($a_3, ',') ) {
			$alert_array[$a_1][0] .= 's';
			$alert_array[$a_1]['label'] .= 's';
		}
		$subject = __('[NinjaFirewall] Alert: ') . $alert_array[$a_1][0] . ' ' . $alert_array[$a_1][$a_2];
		if ( is_multisite() ) {
			$url = __('- Blog : ') . network_home_url('/') . "\n\n";
		} else {
			$url = __('- Blog : ') . home_url('/') . "\n\n";
		}
		$message = __('NinjaFirewall has detected the following activity on your account:') . "\n\n".
			'- ' . $alert_array[$a_1][0] . ' ' . $alert_array[$a_1][$a_2] . "\n" .
			'- ' . $alert_array[$a_1]['label'] . ' : ' . $a_3 . "\n\n" .
			__('- User : ') . $current_user->user_login . ' (' . $current_user->roles[0] . ")\n" .
			__('- IP   : ') . $_SERVER['REMOTE_ADDR'] . "\n" .
			__('- Date : ') . date('F j, Y @ H:i:s') . ' (UTC '. date('O') . ")\n" .
			$url .
			'NinjaFirewall (WP edition) - http://ninjafirewall.com/' . "\n" .
			'Support forum: http://wordpress.org/support/plugin/ninjafirewall' . "\n";
		wp_mail( $recipient, $subject, $message );

		if (! empty($nfw_options['a_41']) ) {
			nfw_log2(
				$alert_array[$a_1][0] . ' ' . $alert_array[$a_1][$a_2] . ' by '. $current_user->user_login,
				$alert_array[$a_1]['label'] . ' : ' . $a_3,
				6,
				0
			);
		}

	}
}
/* ------------------------------------------------------------------ */

function nfw_dashboard_widgets() {	//i18n / sa

	// Add dashboard widgets

	// Return immediately if user is not allowed :
	if (nf_not_allowed( 0, __LINE__ ) ) { return; }

    wp_add_dashboard_widget( 'nfw_dashboard_welcome', __('NinjaFirewall Statistics'), 'nfw_stats_widget' );
 }

function nfw_stats_widget(){

	$critical = $high = $medium = $upload = $total = 0;
	$stat_file = NFW_LOG_DIR . '/nfwlog/stats_' . date( 'Y-m' ) . '.php';
	if ( file_exists( $stat_file ) ) {
		$nfw_stat = file_get_contents( $stat_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
	} else {
		$nfw_stat = '0:0:0:0:0:0:0:0:0:0';
	}
	list($tmp, $medium, $high, $critical, $tmp, $upload, $tmp, $tmp, $tmp, $tmp) = explode(':', $nfw_stat . ':');
	$total = $critical + $high + $medium;
	if ( $total ) {
		$coef = 100 / $total;
		$critical = round( $critical * $coef, 2);
		$high = round( $high * $coef, 2);
		$medium = round( $medium * $coef, 2);
	}
	echo '
	<table border="0" width="100%">
		<tr>
			<th width="50%" align="left">' . __('Blocked hacking attempts') . '</th>
			<td width="50%" align="left">' . $total . '</td>
		</tr>
		<tr>
			<th width="50%" align="left">' . __('Hacking attempts severity') . '</th>
			<td width="50%" align="left">
				<i>' . __('Critical : ') . $critical . '%</i>
				<br />
				<table bgcolor="#DFDFDF" border="0" cellpadding="0" cellspacing="0" height="14" width="100%" align="left" style="height:14px;">
					<tr>
						<td width="' . round( $critical) . '%" background="' . plugins_url( '/images/bar-critical.png', __FILE__ ) . '" style="padding:0px"></td><td width="' . round(100 - $critical) . '%" style="padding:0px"></td>
					</tr>
				</table>
				<br />
				<i>' . __('High : ') . $high . '%</i>
				<br />
				<table bgcolor="#DFDFDF" border="0" cellpadding="0" cellspacing="0" height="14" width="100%" align="left" style="height:14px;">
					<tr>
						<td width="' . round( $high) . '%" background="' . plugins_url( '/images/bar-high.png', __FILE__ ) . '" style="padding:0px"></td><td width="' . round(100 - $high) . '%" style="padding:0px"></td>
					</tr>
				</table>
				<br />
				<i>' . __('Medium : ') . $medium . '%</i>
				<br />
				<table bgcolor="#DFDFDF" border="0" cellpadding="0" cellspacing="0" height="14" width="100%" align="left" style="height:14px;">
					<tr>
						<td width="' . round( $medium) . '%" background="' . plugins_url( '/images/bar-medium.png', __FILE__ ) . '" style="padding:0px;"></td><td width="' . round(100 - $medium) . '%" style="padding:0px;"></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<th width="50%" align="left">' . __('Uploaded files') . '</th>
			<td width="50%" align="left">' . $upload . '</td>
		</tr>
	</table>';
	// Display the link to the log page only if the log is not empty :
	if ( $total || $upload ) {
		echo '<div align="right"><small><a href="admin.php?page=nfsublog">' . __('View firewall log') . '</a></small></div>';
	}

}

if ( is_multisite() ) {
	add_action( 'wp_network_dashboard_setup', 'nfw_dashboard_widgets' );
} else {
	add_action( 'wp_dashboard_setup', 'nfw_dashboard_widgets' );
}

/* ------------------------------------------------------------------ */

function nf_not_allowed($block, $line) {

	if ( is_multisite() ) {
		if ( current_user_can('manage_network') ) {
			return false;
		}
	} else {
		if ( current_user_can('manage_options') ) {
			return false;
		}
	}

	if ($block) {
		die( '<br /><br /><br /><div class="error settings-error"><p><strong>' .
			sprintf( __('You are not allowed to perform this task (%s).'), $line) .
			'</strong></p></div>' );
	}
	return true;
}

/* ------------------------------------------------------------------ */
// EOF //
