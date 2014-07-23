<?php
/*
Plugin Name: NinjaFirewall (WP edition)
Plugin URI: http://NinjaFirewall.com/
Description: A true Web Application Firewall.
Version: 1.2.4
Author: The Ninja Technologies Network
Author URI: http://NinTechNet.com/
License: GPLv2 or later
Network: true
Text Domain: ninjafirewall
*/

/*
 +---------------------------------------------------------------------+
 | NinjaFirewall (WordPress edition)                                   |
 |                                                                     |
 | (c)2012-2013 NinTechNet                                             |
 | <wordpress@nintechnet.com>                                          |
 +---------------------------------------------------------------------+
 | http://nintechnet.com/                                              |
 +---------------------------------------------------------------------+
 | REVISION: 2014-07-23 13:40:19                                       |
 +---------------------------------------------------------------------+
*/
define( 'NFW_ENGINE_VERSION', '1.2.4' );
define( 'NFW_RULES_VERSION',  '20140723' );
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

add_action('init', 'nfw_sessionstart', 1);
function nfw_sessionstart() {
	if (! session_id() ) { session_start(); }
}

/* ================================================================== */

// Some constants first :
define( 'NFW_NULL_BYTE', 2);
define( 'NFW_SCAN_BOTS', 531);
define( 'NFW_ASCII_CTRL', 500);
define( 'NFW_DOC_ROOT', 510);
define( 'NFW_WRAPPERS', 520);
define( 'NFW_LOOPBACK', 540);

function nfw_activate() {

	// Install/activate NinjaFirewall :

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
		exit( "You are not allowed to activate NinjaFirewall");
	}

	// We don't do Windows :
	if ( PATH_SEPARATOR == ';' ) {
		exit( "NinjaFirewall is not compatible with Windows." );
	}

	// If already installed/setup, just enable the firewall... :
	if ( $nfw_options = get_option( 'nfw_options' ) ) {
		$nfw_options['enabled'] = 1;
		update_option( 'nfw_options', $nfw_options);

		// ...and whitelist the admin if needed :
		if (! empty( $nfw_options['wl_admin']) ) {
			$_SESSION['nfw_goodguy'] = true;
		}
	}
}

register_activation_hook( __FILE__, 'nfw_activate' );

/* ================================================================== */

function nfw_deactivate() {

	// Disable the firewall (NinjaFirewall will keep running
	// in the background but will not do anything) :
	$nfw_options = get_option( 'nfw_options' );
	$nfw_options['enabled'] = 0;
	update_option( 'nfw_options', $nfw_options);

}

register_deactivation_hook( __FILE__, 'nfw_deactivate' );

/* ================================================================== */

function nfw_upgrade() {

	// Only used when upgrading NinjaFirewall and sending alerts:

	if (! current_user_can( 'manage_options' ) ) {
		return;
	}

	$is_update = 0;

	$nfw_options = get_option( 'nfw_options' );
	$nfw_rules = get_option( 'nfw_rules' );

	// update engine version number if needed :
	if ( ( $nfw_options ) && ( $nfw_options['engine_version'] != NFW_ENGINE_VERSION ) ) {

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
		$nfwbfd_log = plugin_dir_path(__FILE__) . 'log/nfwbfd.php';
		// v1.1.1 update -------------------------------------------------
		if ((! empty($nfw_options['bf_request'])) && (! empty($nfw_options['bf_bantime'])) &&
		    (! empty($nfw_options['bf_attempt'])) && (! empty($nfw_options['bf_maxtime'])) &&
		    (! empty($nfw_options['auth_name'])) && (! empty($nfw_options['auth_pass'])) &&
		    (! empty($nfw_options['bf_rand'])) ) {
			if ( is_writable( plugin_dir_path(__FILE__) . 'log' ) ) {
				// v1.1.3 update -------------------------------------------
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
				// ---------------------------------------------------------
				$data = '<?php $bf_enable=' . $nfw_options['bf_enable'] .
				';$bf_request=\'' . $nfw_options['bf_request'] . '\'' .
				';$bf_bantime=' . $nfw_options['bf_bantime'] .
				';$bf_attempt=' . $nfw_options['bf_attempt'] .
				';$bf_maxtime=' . $nfw_options['bf_maxtime'] .
				';$bf_xmlrpc=' . $nfw_options['bf_xmlrpc'] .
				';$auth_name=\'' . $nfw_options['auth_name'] . '\'' .
				';$auth_pass=\'' . $nfw_options['auth_pass'] . '\';' .
				'$auth_msg=\'' . $nfw_options['auth_msg'] . '\'' .
				';$bf_rand=\'' . $nfw_options['bf_rand'] . '\'; ?>';
				$fh = fopen( $nfwbfd_log, 'w' );
				fwrite( $fh, $data );
				fclose( $fh );
			}
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
		// ---------------------------------------------------------------

		$nfw_options['engine_version'] = NFW_ENGINE_VERSION;
		$is_update = 1;
	}

	// do we need to update rules as well ?
	if ( ( $nfw_options ) && ( $nfw_options['rules_version'] < NFW_RULES_VERSION ) ) {
		// fetch new set of rules :
		$_POST['nfw_act'] = 'x';
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

		// update rules... :
		update_option( 'nfw_rules', $nfw_rules_new);
		// ...and rules version number :
		$nfw_options['rules_version'] = NFW_RULES_VERSION;
		$is_update = 1;
	}

	if ( $is_update ) {
		$tmp_data = '';
		// Check if we need to restore the log which was saved to the DB
		// before starting NinjaFirewall's update :
		if ( isset($nfw_options['nfw_tmp']) ) {
			unset( $nfw_options['nfw_tmp'] );
			// Fetch it, unpack it, and save it to disk...
			$log_file = plugin_dir_path(__FILE__) . 'log/firewall_' . date( 'Y-m' ) . '.php';
			if ( $tmp_data = @gzinflate( base64_decode( get_option('nfw_tmp') ) ) ) {
				file_put_contents( $log_file, $tmp_data );
			}
			// ... and clear it from the DB :
			delete_option( 'nfw_tmp' );
		}
		if ( $tmp_data ) {
			// Try to re-create the widget stats file :
			$stat_file = plugin_dir_path(__FILE__) . 'log/stats_' . date( 'Y-m' ) . '.php';
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
				$nfw_stat[8] . ':' . $nfw_stat[9] );
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

/* ================================================================== */

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
/* ================================================================== */

function nfw_housework() {

	// Clean/delete cache folder & temp files :

	$nfw_options = get_option( 'nfw_options' );

	// File Guard temp files :
	if (! empty( $nfw_options['fg_enable']) ) {
		$path = plugin_dir_path(__FILE__) . '/log/cache/';
		foreach( glob($path . "fg_*.php") as $file) {
			$stat = stat( $file );
			// Delete it if is too old :
			if ( time() - $nfw_options['fg_mtime'] * 3660 > $stat['ctime'] ) {
				unlink($file);
			}
		}
	}
}
/* ================================================================== */

function nfw_send_loginemail( $user_login, $whoami ) {

	$nfw_options = get_option( 'nfw_options' );

	if ( ( is_multisite() ) && ( $nfw_options['alert_sa_only'] == 2 ) ) {
		$recipient = get_option('admin_email');
	} else {
		$recipient = $nfw_options['alert_email'];
	}

	// Get timezone :
	nfw_get_blogtimezone();

	$subject = '[NinjaFirewall] Alert: WordPress console login';
	$message = 'Someone just logged in to your WordPress admin console:' . "\n\n".
				'- User : ' . $user_login . ' (' . $whoami . ")\n" .
				'- IP   : ' . $_SERVER['REMOTE_ADDR'] . "\n" .
				'- Date : ' . date('F j, Y @ H:i:s') . ' (UTC '. date('O') . ")\n" .
				'- URL  : ' . site_url() . "\n\n" .
				'NinjaFirewall (WP edition) - http://ninjafirewall.com/' . "\n" .
				'Support forum: http://wordpress.org/support/plugin/ninjafirewall' . "\n";
	wp_mail( $recipient, $subject, $message );

}
/* ================================================================== */

function nfw_logout_hook() {

	// Whoever it was, we clear the goodguy flag :
	if ( isset( $_SESSION['nfw_goodguy'] ) ) {
		unset( $_SESSION['nfw_goodguy'] );
	}
}

add_action( 'wp_logout', 'nfw_logout_hook' );

/* ================================================================== */

function is_nfw_enabled() {

	// Checks whether NF is enabled and/or active and/or debugging mode :

	$user_enabled = $hook_enabled = $debug_enabled = 0;

	$nfw_options = get_option( 'nfw_options' );

	if (! empty( $nfw_options['enabled']) ) {
		$user_enabled = 1;
	}
	if ( plugin_dir_path(__FILE__) . 'lib/firewall.php' === ini_get( 'auto_prepend_file' ) ) {
		$hook_enabled = 1;
	}
	if (! empty( $nfw_options['debug']) ) {
		$debug_enabled = 1;
	}

	return array( $user_enabled, $hook_enabled, $debug_enabled );

}

/* ================================================================== */

function ninjafirewall_admin_menu() {

	if ( ( is_multisite() ) && (! current_user_can( 'manage_network' ) ) ) {
		return;
	}

	define( 'NFW_DEFAULT_MSG', '<br /><br /><br /><br /><center>Sorry <b>%%REM_ADDRESS%%</b>, ' .
		'your request cannot be proceeded.<br />For security reason, it was blocked and logged.' .
		'<br /><br />%%NINJA_LOGO%%<br /><br />If you think that was a mistake, please contact the<br />' .
		'webmaster and enclose the following incident ID:<br /><br />[ <b>#%%NUM_INCIDENT%%</b> ]</center>'
	);

	// Only used for NinjaFirewall beta releases :
//	 define( 'IS_BETA', ' <sup style="color:red;">Beta ' . NFW_ENGINE_VERSION . '</sup> ');
	define( 'IS_BETA', '');

	// Setup our admin menus :

	list ( $user_enabled, $hook_enabled, $debug_enabled ) = is_nfw_enabled();

	// Run the install process if not installed yet :
	if (! $hook_enabled ) {
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

	// Alerts menu :
	$menu_hook = add_submenu_page( 'NinjaFirewall', 'NinjaFirewall: Event Notifications', 'Event Notifications', 'manage_options',
		'nfsubalerts', 'nf_sub_alerts' );
	add_action( 'load-' . $menu_hook, 'help_nfsubalerts' );

	// Login protection menu :
	$menu_hook = add_submenu_page( 'NinjaFirewall', 'NinjaFirewall: Log-in Protection', 'Login Protection', 'manage_options',
		'nfsubloginprot', 'nf_sub_loginprot' );
	add_action( 'load-' . $menu_hook, 'help_nfsublogin' );

	// Firewall log menu :
	$menu_hook = add_submenu_page( 'NinjaFirewall', 'NinjaFirewall: Firewall Log', 'Firewall Log', 'manage_options',
		'nfsublog', 'nf_sub_log' );
	add_action( 'load-' . $menu_hook, 'help_nfsublog' );

	// Rules Editor menu :
	$menu_hook = add_submenu_page( 'NinjaFirewall', 'NinjaFirewall: Rules Editor', 'Rules Editor', 'manage_options',
		'nfsubedit', 'nf_sub_edit' );
	add_action( 'load-' . $menu_hook, 'help_nfsubedit' );

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

/* ================================================================== */

function nf_admin_bar_status() {

	// Display the status icon to administrators (multi-site mode only) :
	if (! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Check whether the option is enabled or not :
	$nfw_options = get_option( 'nfw_options' );
	// Disable it, unless this is the superadmin :
	if ( $nfw_options['nt_show_status'] != 1 && ! current_user_can('manage_network') ) {
		return;
	}

	// Obviously, we don't put any icon if NinjaFirewall isn't running
	// or is in debugging mode :
	list ( $user_enabled, $hook_enabled, $debug_enabled ) = is_nfw_enabled();
	if ( (! $user_enabled) || (! $hook_enabled ) || ( $debug_enabled ) ) {
		return;
	}

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

/* ================================================================== */

function nf_menu_install() {

	// Installer :

	if (! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.',
			'', array( 'response' => 403 ) );
	}

	require_once( plugin_dir_path(__FILE__) . 'install.php' );
}

/* ================================================================== */

function nf_admin_notice(){

	// display a big red warning if the firewall returned an error :

	// we don't display any fatal error message to users :
	if (! current_user_can( 'manage_options' ) ) {
		return;
	}

	list ( $user_enabled, $hook_enabled, $debug_enabled ) = is_nfw_enabled();
	if ( (! $user_enabled) || (! $hook_enabled ) || ( $debug_enabled ) ) {
		// we will assume that NinjaFirewall it is not installed yet :
		return;
	}

	if ( defined('NFW_STATUS') ) {
		if ( NFW_STATUS == 20 ) {
			// OK
			return;
		}
		$err_fw = array(
			1	=> 'cannot find WordPress configuration file',
			2	=>	'cannot read WordPress configuration file',
			3	=>	'cannot retrieve WordPress database credentials',
			4	=>	'cannot connect to WordPress database',
			5	=>	'cannot retrieve user options from database (#1)',
			6	=>	'cannot retrieve user options from database (#2)',
			7	=>	'cannot retrieve user rules from database (#1)',
			8	=>	'cannot retrieve user rules from database (#2)'
		);
		$err = $err_fw[NFW_STATUS];
	} else {
		// something wrong, here :
		$err = 'communication with the firewall failed';
	}
	echo '<div class="error"><p><strong>NinjaFirewall fatal error :</strong> ' . $err .
		'. Please review your installation. Your site is <strong>not</strong> protected.</p></div>';
}

add_action('all_admin_notices', 'nf_admin_notice');

/* ================================================================== */

function nf_menu_main() {

	// Main menu (Overview) :

	if (! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.',
			'', array( 'response' => 403 ) );
	}

	list ( $user_enabled, $hook_enabled, $debug_enabled ) = is_nfw_enabled();

	$warn_msg = '';
	if ( $user_enabled ) {
		$img = 'icon_ok_16.png';
		$txt = 'Enabled';
	} else {
		$img = 'icon_error_16.png';
		$txt = 'Disabled';
		$warn_msg = 1;
	}
	if ( $hook_enabled ) {
		$img2 = 'icon_ok_16.png';
		$txt2 = 'Enabled';
	} else {
		$img2 = 'icon_error_16.png';
		$txt2 = 'Disabled';
		$warn_msg = 2;
	}
?>

<div class="wrap">
	<div style="width:54px;height:52px;background-image:url(<?php echo plugins_url() ?>/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2>NinjaFirewall (WP edition)<?php echo IS_BETA ?></h2>
	<br />
	<?php
	if ( $warn_msg ) {
		echo '<div class="error settings-error"><p><strong>Warning :</strong> you are at risk ! Your site is not protected as long as the problems below aren\'t solved.</p></div>';
	}
	// first run ?
	if ( ( defined( 'NFW_IT_WORKS' )) || (! empty( $_GET['nfw_firstrun']) ) ) {
		echo '<br><div class="updated settings-error"><p><strong>Congratulations&nbsp;!</strong> NinjaFirewall is up and running. Use the menu in the left frame to configure it according to your needs.<br />If you need help, click on the contextual <strong>Help</strong> menu tab located in the upper right corner of each page.</p></div>';
	}
	?>
	<br />
	<table class="form-table">
		<tr>
			<th scope="row">Firewall</th>
			<td width="20" align="left"><img src="<?php echo plugins_url( '/images/' . $img, __FILE__ ) ?>" border="0" height="16" width="16"></td>
			<td><?php echo $txt; if ( $warn_msg == 1) {echo '&nbsp;&nbsp;&nbsp;&nbsp;<a href="?page=nfsubopt">Click here to enable NinjaFirewall</a>';} ?></td>
		</tr>
		<tr>
			<th scope="row">PHP hook</th>
			<td width="20" align="left"><img src="<?php echo plugins_url( '/images/' . $img2, __FILE__ ) ?>" border="0" height="16" width="16"></td>
			<td><?php echo $txt2 ?></td>
		</tr>
		<tr>
			<th scope="row">PHP SAPI</th>
			<td width="20" align="left">&nbsp;</td>
			<td><?php echo strtoupper(PHP_SAPI) ?></td>
		</tr>
		<tr>
			<th scope="row">Engine version</th>
			<td width="20" align="left">&nbsp;</td>
			<td><?php echo NFW_ENGINE_VERSION ?></td>
		</tr>
		<tr>
			<th scope="row">Rules version</th>
			<td width="20" align="left">&nbsp;</td>
			<td><?php echo NFW_RULES_VERSION ?></td>
		</tr>
	<?php
	// Look for CDN's (Incapsula/Cloudflare) and
	// warn the user about using the correct IPs :
	if (! empty($_SERVER["HTTP_CF_CONNECTING_IP"]) ) {
		// CloudFlare :
		?>
		<tr>
			<th scope="row"><?php _e('CDN detection', 'ninjafirewall') ?></th>
			<td width="20" align="left"><img src="<?php echo plugins_url( '/images/icon_warn_16.png', __FILE__ )?>" border="0" height="16" width="16"></td>
			<td><?php printf( __('<code>HTTP_CF_CONNECTING_IP</code> detected: you seem to be using Cloudflare CDN services. Ensure that you have setup your HTTP server or PHP to forward the correct visitor IP, otherwise use the NinjaFirewall <code><a href="%s">.htninja</a></code> configuration file.', 'ninjafirewall'), 'http://nintechnet.com/nfwp/1.1.3/?#variables') ?></td>
		</tr>
		<?php
	}
	if (! empty($_SERVER["HTTP_INCAP_CLIENT_IP"]) ) {
		// Incapsula :
		?>
		<tr>
			<th scope="row"><?php _e('CDN detection', 'ninjafirewall') ?></th>
			<td width="20" align="left"><img src="<?php echo plugins_url( '/images/icon_warn_16.png', __FILE__ )?>" border="0" height="16" width="16"></td>
			<td><?php printf( __('<code>HTTP_INCAP_CLIENT_IP</code> detected: you seem to be using Incapsula CDN services. Ensure that you have setup your HTTP server or PHP to forward the correct visitor IP, otherwise use the NinjaFirewall <code><a href="%s">.htninja</a></code> configuration file.', 'ninjafirewall'), 'http://nintechnet.com/nfwp/1.1.3/?#variables') ?></td>
		</tr>
		<?php
	}
	// Ensure /log/ dir is writable :
	if (! is_writable( plugin_dir_path(__FILE__) .  'log' ) ) {
		?>
			<tr>
			<th scope="row">Log dir</th>
			<td width="20" align="left"><img src="<?php echo plugins_url( '/images/icon_error_16.png', __FILE__ )?>" border="0" height="16" width="16"></td>
			<td><code><?php echo plugin_dir_path(__FILE__) .  'log/' ?></code> directory is not writable&nbsp;! Please chmod it to 0777 or equivalent.</td>
		</tr>
	<?php
	}

	// check for NinjaFirewall optional config file :
	if ( @file_exists( $file = dirname(getenv('DOCUMENT_ROOT') ) . '/.htninja') ) {
		echo '<tr><th scope="row">Optional configuration file</th>';
		if ( is_writable(dirname(getenv('DOCUMENT_ROOT') ) . '/.htninja') ) {
			echo '<td width="20" align="left"><img src="' . plugins_url( '/images/icon_warn_16.png', __FILE__ ) . '" border="0" height="16" width="16"></td>
			<td><code>' .  dirname(getenv('DOCUMENT_ROOT') ) . '/.htninja</code> is writable. Consider changing its permissions to read-only.</td>';
		} else {
			echo '<td width="20">&nbsp;</td>
				<td><code>' .  dirname(getenv('DOCUMENT_ROOT') ) . '/.htninja</code></td>';
		}
		echo '</tr>';
	}

	if ( $debug_enabled ) {
	?>
		<tr>
			<th scope="row">Debugging mode</th>
			<td width="20" align="left"><img src="<?php echo plugins_url( '/images/icon_error_16.png', __FILE__ ) ?>" border="0" height="16" width="16"></td>
			<td>On&nbsp;&nbsp;&nbsp;&nbsp;<a href="?page=nfsubopt">Click here to turn off Debugging mode</a></td>
		</tr>
	<?php
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
		<td><code>' . ABSPATH . '.htaccess</code> is read-only</td>
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
			<td><code>' . $phpini . '</code> is read-only</td>
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

/* ================================================================== */

function nf_sub_statistics() {

	// Stats / benchmarks menu :

	if (! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.',
			'', array( 'response' => 403 ) );
	}

	echo '
<div class="wrap">
		<div style="width:54px;height:52px;background-image:url( ' . plugins_url() . '/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2>Statistics' . IS_BETA . '</h2>
	<br />';

	$critical = $high = $medium = $slow = $benchmark = $tot_bench = $speed = $upload = $total = 0;

	// Do we have any log for this month ?
	if (! file_exists( plugin_dir_path(__FILE__) . 'log/firewall_' . date( 'Y-m' ) . '.php' ) ) {
		echo '<div class="updated settings-error"><p>You do not have any stats for the current month yet.</p></div>';
		$fast = 0;
	} else {
		$fast = 1000;

		if (! $fh = @fopen( plugin_dir_path(__FILE__) . 'log/firewall_' . date( 'Y-m' ) . '.php', 'r') ) {
			echo '<div class="error settings-error"><p><strong>Cannot open logfile :</strong> ' .
				plugin_dir_path(__FILE__) . 'log/firewall_' . date( 'Y-m' ) . '.php</p></div></div>';
			return;
		}
		// Retrieve all lines :
		while (! feof( $fh) ) {
			$line = fgets( $fh);
			if (preg_match( '/^\[.+?\]\s+\[(.+?)\]\s+(?:\[.+?\]\s+){3}\[(1|2|3|4|5|6)\]/', $line, $match) ) {
				if ( $match[2] == 1) {
					$medium++;
				} elseif ( $match[2] == 2) {
					$high++;
				} elseif ( $match[2] == 3) {
					$critical++;
				} elseif ( $match[2] == 5) {
					$upload++;
				}
				if ($match[1]) {
					if ( $match[1] > $slow) {
						$slow = $match[1];
					}
					if ( $match[1] < $fast) {
						$fast = $match[1];
					}
					$speed += $match[1];
					$tot_bench++;
				}
			}
		}
		fclose( $fh);

		$total = $critical + $high + $medium;
		if ( $total ) {
			if ( $total == 1) {$fast = $slow;}
			$coef = 100 / $total;
			$critical = round( $critical * $coef, 2);
			$high = round( $high * $coef, 2);
			$medium = round( $medium * $coef, 2);
			if ($tot_bench) {
				$speed = round( $speed / $tot_bench, 4);
			} else {
				$speed = 0;
				$fast = 0;
			}
		}
	}

	echo '
	<table class="form-table">
		<tr>
			<th scope="row"><h3>Monthly stats</h3></th>
			<td align="left">' . date("F Y") . '</td>
		</tr>
		<tr>
			<th scope="row">Blocked hacking attempts</th>
			<td align="left">' . $total . '</td>
		</tr>
		<tr>
			<th scope="row">Hacking attempts severity</th>
			<td align="left">
				Critical : ' . $critical . '%<br />
				<table bgcolor="#DFDFDF" border="0" cellpadding="0" cellspacing="0" height="14" width="250" align="left" style="height:14px;">
					<tr>
						<td width="' . round( $critical) . '%" background="' . plugins_url( '/images/bar-critical.png', __FILE__ ) . '" style="padding:0px"></td><td width="' . round(100 - $critical) . '%" style="padding:0px"></td>
					</tr>
				</table>
				<br /><br />High : ' . $high . '%<br />
				<table bgcolor="#DFDFDF" border="0" cellpadding="0" cellspacing="0" height="14" width="250" align="left" style="height:14px;">
					<tr>
						<td width="' . round( $high) . '%" background="' . plugins_url( '/images/bar-high.png', __FILE__ ) . '" style="padding:0px"></td><td width="' . round(100 - $high) . '%" style="padding:0px"></td>
					</tr>
				</table>
				<br /><br />Medium : ' . $medium . '%<br />
				<table bgcolor="#DFDFDF" border="0" cellpadding="0" cellspacing="0" height="14" width="250" align="left" style="height:14px;">
					<tr>
						<td width="' . round( $medium) . '%" background="' . plugins_url( '/images/bar-medium.png', __FILE__ ) . '" style="padding:0px;"></td><td width="' . round(100 - $medium) . '%" style="padding:0px;"></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<th scope="row">Uploaded files</th>
			<td align="left">' . $upload . '</td>
		</tr>

		<tr><th scope="row"><h3>Benchmarks</h3></th><td>&nbsp;</td><td>&nbsp;</td></tr>
		<tr>
			<th scope="row">Average time per request</th>
			<td align="left">' . $speed . 's</td>
		</tr>
		<tr>
			<th scope="row">Fastest request</th>
			<td align="left">' . round( $fast, 4) . 's</td>
		</tr>
		<tr>
			<th scope="row">Slowest request</th>
			<td align="left">' . round( $slow, 4) . 's</td>
		</tr>
	</table>
</div>';

}

/* ================================================================== */

function nf_sub_options() {

	// Firewall Options menu :

	if (! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.',
			'', array( 'response' => 403 ) );
	}

	$nfw_options = get_option( 'nfw_options' );

	echo '
<script>
function preview_msg() {
	var t1 = document.option_form.elements[\'nfw_options[blocked_msg]\'].value.replace(\'%%REM_ADDRESS%%\',\'' . $_SERVER['REMOTE_ADDR'] . '\');
	var t2 = t1.replace(\'%%NUM_INCIDENT%%\',\'1234567\');
	var t3 = t2.replace(\'%%NINJA_LOGO%%\',\'<img src="' . plugins_url( '/images/ninjafirewall_75.png', __FILE__ ) . '" width="75" height="75" title="NinjaFirewall">\');
	document.getElementById(\'out_msg\').innerHTML = t3;
	document.getElementById(\'td_msg\').style.display = \'\';
	document.getElementById(\'btn_msg\').value = \'Refresh preview\';
}
function default_msg() {
	document.option_form.elements[\'nfw_options[blocked_msg]\'].value = "' . preg_replace( '/[\r\n]/', '\n', NFW_DEFAULT_MSG) .'";
}
</script>

<div class="wrap">
	<div style="width:54px;height:52px;background-image:url( ' . plugins_url() . '/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2>Firewall Options' . IS_BETA . '</h2>
	<br />';

	// Saved options ?
	if ( isset( $_POST['nfw_options']) ) {
		nf_sub_options_save();
		$nfw_options = get_option( 'nfw_options' );
		echo '<div class="updated settings-error"><p><strong>Your changes have been saved.</strong></p></div>';
	}

	echo '<br />
	<form method="post" name="option_form">
	<table class="form-table">
		<tr>
			<th scope="row">Firewall protection</th>';
	// Enabled :
	if (! empty( $nfw_options['enabled']) ) {
		echo '
			<td width="20" align="left"><img src="' . plugins_url( '/images/icon_ok_16.png', __FILE__ ) . '" border="0" height="16" width="16"></td>
			<td align="left">
				<select name="nfw_options[enabled]" style="width:200px">
					<option value="1" selected>Enabled</option>
					<option value="0">Disabled</option>
				</select>';
	// Disabled :
	} else {
		echo '
			<td width="20" align="left"><img src="' . plugins_url( '/images/icon_error_16.png', __FILE__ ) . '" border="0" height="16" width="16"></td>
			<td align="left">
				<select name="nfw_options[enabled]" style="width:200px">
					<option value="1">Enabled</option>
					<option value="0" selected>Disabled</option>
				</select>&nbsp;<span class="description">&nbsp;Warning: your site is not protected !</span>';
	}
	echo '
			</td>
		</tr>
		<tr>
			<th scope="row">Debugging mode</th>';

	// Debugging enabled ?
	if (! empty( $nfw_options['debug']) ) {
	echo '<td width="20" align="left"><img src="' . plugins_url( '/images/icon_error_16.png', __FILE__ ) . '" border="0" height="16" width="16"></td>
			<td align="left">
				<select name="nfw_options[debug]" style="width:200px">
				<option value="1" selected>Enabled</option>
					<option value="0">Disabled (default)</option>
				</select>&nbsp;<span class="description">&nbsp;Warning: your site is not protected !</span>
			</td>';

	} else {
	// Debugging disabled ?
	echo '<td width="20">&nbsp;</td>
			<td align="left">
				<select name="nfw_options[debug]" style="width:200px">
				<option value="1">Enabled</option>
					<option value="0" selected>Disabled (default)</option>
				</select>
			</td>';
	}

	// Get (if any) the HTTP error code to return :
	if (! @preg_match( '/^(?:40[0346]|50[03])$/', $nfw_options['ret_code']) ) {
		$nfw_options['ret_code'] = '403';
	}
	?>
		</tr>
		<tr>
			<th scope="row">HTTP error code to return</th>
			<td width="20">&nbsp;</td>
			<td align="left">
			<select name="nfw_options[ret_code]" style="width:200px">
			<option value="400"<?php selected($nfw_options['ret_code'], 400) ?>>400 Bad Request</option>
			<option value="403"<?php selected($nfw_options['ret_code'], 403) ?>>403 Forbidden (default)</option>
			<option value="404"<?php selected($nfw_options['ret_code'], 404) ?>>404 Not Found</option>
			<option value="406"<?php selected($nfw_options['ret_code'], 406) ?>>406 Not Acceptable</option>
			<option value="500"<?php selected($nfw_options['ret_code'], 500) ?>>500 Internal Server Error</option>
			<option value="503"<?php selected($nfw_options['ret_code'], 503) ?>>503 Service Unavailable</option>
			</select>
			</td>
		</tr>
	<?php
	echo '
		<tr>
			<th scope="row">Blocked user message</th>
			<td width="20">&nbsp;</td>
			<td align="left">
				<textarea name="nfw_options[blocked_msg]" class="small-text code" cols="60" rows="5">';

	if (! empty( $nfw_options['blocked_msg']) ) {
		echo base64_decode($nfw_options['blocked_msg']);
	} else {
		echo NFW_DEFAULT_MSG;
	}
	echo '</textarea>
				<p><input class="button-secondary" type="button" id="btn_msg" value="Preview message" onclick="javascript:preview_msg();" />&nbsp;&nbsp;<input class="button-secondary" type="button" id="btn_msg" value="Default message" onclick="javascript:default_msg();" /></p>
			</td>
		</tr>
	</table>

	<table class="form-table" border=1>
		<tr id="td_msg" style="display:none"><td id="out_msg" style="border:1px solid #DFDFDF;background-color:#ffffff;" width="100%"></td></tr>
	</table>

	<br />
	<input class="button-primary" type="submit" name="Save" value="Save Firewall Options" />
	</form>
</div>';

}

/* ================================================================== */

function nf_sub_options_save() {

	// Save options :

	if (! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.',
			'', array( 'response' => 403 ) );
	}

	$nfw_options = get_option( 'nfw_options' );

	if ( empty( $_POST['nfw_options']['enabled']) ) {
		$nfw_options['enabled'] = 0;
	} else {
		$nfw_options['enabled'] = 1;
	}

	if ( (isset( $_POST['nfw_options']['ret_code'])) &&
		(preg_match( '/^(?:40[0346]|50[03])$/', $_POST['nfw_options']['ret_code'])) ) {
		$nfw_options['ret_code'] = $_POST['nfw_options']['ret_code'];
	} else {
		$nfw_options['ret_code'] = '403';
	}

	if ( empty( $_POST['nfw_options']['blocked_msg']) ) {
		$nfw_options['blocked_msg'] = base64_encode(NFW_DEFAULT_MSG);
	} else {
		$nfw_options['blocked_msg'] = base64_encode(stripslashes($_POST['nfw_options']['blocked_msg']));
	}

	if ( empty( $_POST['nfw_options']['debug']) ) {
		$nfw_options['debug'] = 0;
	} else {
		$nfw_options['debug'] = 1;
	}

	// Save them :
	update_option( 'nfw_options', $nfw_options);

}

/* ================================================================== */

function nf_sub_policies() {

	// Firewall Policies menu :

	if (! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.',
			'', array( 'response' => 403 ) );
	}

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
function ssl_warn() {
	if (confirm("WARNING: ensure that you can access your admin console from HTTPS (' . admin_url('/','https') . ') before enabling this option, otherwise you will lock yourself out of your site !\nGo ahead ?")){
		return true;
	}
	return false;
}
</script>

<div class="wrap">
	<div style="width:54px;height:52px;background-image:url( ' . plugins_url() . '/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2>Firewall Policies' . IS_BETA . '</h2>
	<br />';

	// Saved options ?
	if ( isset( $_POST['nfw_options']) ) {
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
			<p><label><input type="radio" name="nfw_options[scan_protocol]" value="3"<?php checked($scan_protocol, 3 ) ?>>&nbsp;<code>HTTP</code> and <code>HTTPS/SSL</code> traffic (default)</label></p>
			<p><label><input type="radio" name="nfw_options[scan_protocol]" value="1"<?php checked($scan_protocol, 1 ) ?>>&nbsp;<code>HTTP</code> traffic only</label></p>
			<p><label><input type="radio" name="nfw_options[scan_protocol]" value="2"<?php checked($scan_protocol, 2 ) ?>>&nbsp;<code>HTTPS/SSL</code> traffic only</label></p>
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
			<th scope="row">Sanitise <code>GET</code> variable</th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[get_sanitise]" value="1"<?php checked( $get_sanitise, 1 ) ?>>&nbsp;Yes (default)</label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[get_sanitise]" value="0"<?php checked( $get_sanitise, 0 ) ?>>&nbsp;No</label>
			</td>
		</tr>
	</table>

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
			<th scope="row">Decode base64-encoded <code>POST</code> variable</th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[post_b64]" value="1"<?php checked( $post_b64, 1 ) ?>>&nbsp;Yes (default)</label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[post_b64]" value="0"<?php checked( $post_b64, 0 ) ?>>&nbsp;No</label>
			</td>
		</tr>
	</table>

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
			<th scope="row">Sanitise cookies</th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[cookies_sanitise]" value="1"<?php checked( $cookies_sanitise, 1 ) ?>>&nbsp;Yes (default)</label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[cookies_sanitise]" value="0"<?php checked( $cookies_sanitise, 0 ) ?>>&nbsp;No</label>
			</td>
		</tr>
	</table>

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
	<h3>HTTP_REFERER server variable</h3>
	<table class="form-table">
		<tr>
			<th scope="row">Scan <code>HTTP_REFERER</code></th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[referer_scan]" value="1"<?php checked( $referer_scan, 1 ) ?>>&nbsp;Yes (default)</label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[referer_scan]" value="0"<?php checked( $referer_scan, 0 ) ?>>&nbsp;No</label>
			</td>
		</tr>
		<tr>
			<th scope="row">Sanitise <code>HTTP_REFERER</code></th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[referer_sanitise]" value="1"<?php checked( $referer_sanitise, 1 ) ?>>&nbsp;Yes (default)</label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[referer_sanitise]" value="0"<?php checked( $referer_sanitise, 0 ) ?>>&nbsp;No</label>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">Block <code>POST</code> requests that do not have an <code>HTTP_REFERER</code> header</th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120" style="vertical-align:top;">
				<label><input type="radio" name="nfw_options[referer_post]" value="1"<?php checked( $referer_post, 1 ) ?>>&nbsp;Yes</label>
			</td>
			<td align="left" style="vertical-align:top;">
				<label><input type="radio" name="nfw_options[referer_post]" value="0"<?php checked( $referer_post, 0 ) ?>>&nbsp;No (default)</label><br /><span class="description">&nbsp;Keep this option disabled if you are using scripts like Paypal IPN, WordPress WP-Cron...</span>
			</td>
		</tr>
	</table>

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
	<h3>IPs</h3>
	<table class="form-table" border=0>
		<tr>
			<th scope="row">Block localhost IP in <code>GET/POST</code> requests</th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_rules[no_localhost_ip]" value="1"<?php checked( $no_localhost_ip, 1 ) ?>>&nbsp;Yes (default)</label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_rules[no_localhost_ip]" value="0"<?php checked( $no_localhost_ip, 0 ) ?>>&nbsp;No</label>
			</td>
		</tr>
		<tr>
			<th scope="row">Block HTTP requests with an IP in the <code>Host</code> header</th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[no_host_ip]" value="1"<?php checked( $no_host_ip, 1 ) ?>>&nbsp;Yes</label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[no_host_ip]" value="0"<?php checked( $no_host_ip, 0 ) ?>>&nbsp;No (default)</label>
			</td>
		</tr>
		<tr>
			<th scope="row">Scan traffic coming from localhost and private IP address spaces</th>
			<td width="20">&nbsp;</td>
			<td align="left" width="120">
				<label><input type="radio" name="nfw_options[allow_local_ip]" value="0"<?php checked( $allow_local_ip, 0 ) ?>>&nbsp;Yes (default)</label>
				</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[allow_local_ip]" value="1"<?php checked( $allow_local_ip, 1 ) ?>>&nbsp;No</label>
			</td>
		</tr>
	</table>

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

	<br />

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
			<th scope="row">Block the <code>DOCUMENT_ROOT</code> server variable in HTTP requests</th>
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

	<br />

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
						<span class="description">Uncheck this option if you have users with Editor, Author or Contributor roles, otherwise it could prevent them from using the TinyMCE WYSIWYG editor.</span>
						</td>
					</tr>
					<tr style="border: solid 1px #DFDFDF;">
						<td align="center" width="10"><input type="checkbox" name="nfw_options[wp_upl]" id="wp_03"<?php checked( $wp_upl, 1 ) ?>></td>
						<td><label for="wp_03"><code>/<?php echo basename(WP_CONTENT_DIR); ?>/uploads/*</code></label></td>
					</tr>
					<tr style="border: solid 1px #DFDFDF;">
						<td align="center" width="10"><input type="checkbox" name="nfw_options[wp_cache]" id="wp_04"<?php checked( $wp_cache, 1 ) ?>></td>
						<td><label for="wp_04"><code>*/cache/*</code></label></td>
					</tr>
				</table>
				<br />&nbsp;
			</td>
		</tr>
	</table>

	<table class="form-table">
		<tr>
			<th scope="row">Protect against username enumeration</th>
			<td width="20">&nbsp;</td>
			<td align="left">
				<p><label><input type="checkbox" name="nfw_options[enum_archives]" value="1"<?php checked( $enum_archives, 1 ) ?>>&nbsp;Through the author archives</label></p>
				<p><label><input type="checkbox" name="nfw_options[enum_login]" value="1"<?php checked( $enum_login, 1 ) ?>>&nbsp;Through the login page (default)</label></p>
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
				<label><input type="radio" name="nfw_options[disallow_edit]" value="1"<?php checked( $disallow_edit, 1 ) ?>>&nbsp;Yes (default)</label>
			</td>
			<td align="left">
				<label><input type="radio" name="nfw_options[disallow_edit]" value="0"<?php checked( $disallow_edit, 0 ) ?>>&nbsp;No</label>
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

/* ================================================================== */

function nf_sub_policies_save() {

	// Save policies :

	if (! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.',
			'', array( 'response' => 403 ) );
	}

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
		$nfw_options['get_sanitise'] = 0;
	} else {
		// Default: yes
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

	// Scan COOKIES requests ?
	if ( empty( $_POST['nfw_options']['cookies_scan']) ) {
		$nfw_options['cookies_scan'] = 0;
	} else {
		// Default: yes
		$nfw_options['cookies_scan'] = 1;
	}
	// Sanitise COOKIES requests ?
	if ( empty( $_POST['nfw_options']['cookies_sanitise']) ) {
		$nfw_options['cookies_sanitise'] = 0;
	} else {
		// Default: yes
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
	} else {
		// Default: yes
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
		$tmp .= '/wp-includes/(?:(?:css|images|js|theme-compat)/|[^/]+\.php)|';
	}
	if ( isset( $_POST['nfw_options']['wp_upl']) ) {
		$tmp .= '/' . basename(WP_CONTENT_DIR) .'/uploads/|';
	}
	if ( isset( $_POST['nfw_options']['wp_cache']) ) {
		$tmp .= '/cache/|';
	}
	if ( $tmp ) {
		$nfw_options['wp_dir'] = rtrim( $tmp, '|' );
	}

	// Protect against username enumeration attempts ?
	if (! isset( $_POST['nfw_options']['enum_archives']) ) {
		// Default : no
		$nfw_options['enum_archives'] = 0;
	} else {
		$nfw_options['enum_archives'] = 1;
	}
	if (! isset( $_POST['nfw_options']['enum_login']) ) {
		$nfw_options['enum_login'] = 0;
	} else {
		// Default : yes
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
		$nfw_options['disallow_edit'] = 0;
	} else {
		// Default : yes
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

/* ================================================================== */

function nf_sub_policies_default() {

	// Restore default firewall policies :

	if (! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.',
			'', array( 'response' => 403 ) );
	}

	$nfw_options = get_option( 'nfw_options' );
	$nfw_rules = get_option( 'nfw_rules' );

	$nfw_options['scan_protocol']		= 3;
	$nfw_options['uploads']				= 0;
	$nfw_options['sanitise_fn']		= 1;
	$nfw_options['get_scan']			= 1;
	$nfw_options['get_sanitise']		= 1;
	$nfw_options['post_scan']			= 1;
	$nfw_options['post_sanitise']		= 0;
	$nfw_options['request_sanitise'] = 0;
	$nfw_options['cookies_scan']		= 1;
	$nfw_options['cookies_sanitise']	= 1;
	$nfw_options['ua_scan']				= 1;
	$nfw_options['ua_sanitise']		= 1;
	$nfw_options['referer_scan']		= 1;
	$nfw_options['referer_sanitise']	= 1;
	$nfw_options['referer_post']		= 0;
	$nfw_options['no_host_ip']			= 0;
	$nfw_options['allow_local_ip']	= 0;
	$nfw_options['php_errors']			= 1;
	$nfw_options['php_self']			= 1;
	$nfw_options['php_path_t']			= 1;
	$nfw_options['php_path_i']			= 1;
	$nfw_options['wp_dir'] 				= '/wp-admin/(?:css|images|includes|js)/|' .
		'/wp-includes/(?:(?:css|images|js|theme-compat)/|[^/]+\.php)|' .
		'/'. basename(WP_CONTENT_DIR) .'/uploads/|/cache/';
	$nfw_options['enum_archives']		= 0;
	$nfw_options['enum_login']			= 1;
	$nfw_options['no_xmlrpc']			= 0;
	$nfw_options['no_post_themes']	= 0;
	$nfw_options['force_ssl'] 			= 0;
	$nfw_options['disallow_edit'] 	= 1;
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

/* ================================================================== */

function nf_sub_fileguard() {

	// File Guard :

	if (! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.',
			'', array( 'response' => 403 ) );
	}

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
			alert("Please enter a number from 1 to 99.");
			e.value = e.value.substring(0, e.value.length-1);
		}
	}
	function check_fields() {
		if (! document.nfwfilefuard.elements["nfw_options[fg_mtime]"]){
			alert("Please enter a number from 1 to 99.");
			return false;
		}
		return true;
	}
	</script>

	<div class="wrap">
		<div style="width:54px;height:52px;background-image:url(<?php echo plugins_url() ?>/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
		<h2>File Guard</h2>
		<br />
	<?php

	// Ensure cache folder is writable :
	if (! is_writable( plugin_dir_path(__FILE__) . '/log/cache/') ) {
		echo '<div class="error settings-error"><p><strong>The cache directory ('. plugin_dir_path(__FILE__) . '/log/cache/) is not writable. Please change its permissions (0777 or equivalent).</strong></p></div>';
	}

	// Saved ?
	if ( isset( $_POST['nfw_options']) ) {
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

	?>
	<br />
	<form method="post" name="nfwfilefuard" onSubmit="return check_fields();">
		<table class="form-table">
			<tr style="background-color:#F9F9F9;border: solid 1px #DFDFDF;">
				<th scope="row">Enable File Guard</th>
				<td align="left">
				<label><input type="radio" id="fgenable" name="nfw_options[fg_enable]" value="1"<?php checked($nfw_options['fg_enable'], 1) ?> onclick="toogle_table(1);">&nbsp;Yes</label>
				</td>
				<td align="left">
				<label><input type="radio" name="nfw_options[fg_enable]" value="0"<?php checked($nfw_options['fg_enable'], 0) ?> onclick="toogle_table(2);">&nbsp;No (default)</label>
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
		</table>
		<br />
		<input class="button-primary" type="submit" name="Save" value="Save File Guard options" />
	</form>
	</div>
<?php

}

/* ================================================================== */

function nf_sub_fileguard_save() {

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
	// Update :
	update_option( 'nfw_options', $nfw_options );

}
/* ================================================================== */

function nf_sub_network() {

	// Network menu (multi-site only) :

	if (! current_user_can( 'manage_network' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.',
			'', array( 'response' => 403 ) );
	}

	$nfw_options = get_option( 'nfw_options' );

	echo '
<div class="wrap">
	<div style="width:54px;height:52px;background-image:url( ' . plugins_url() . '/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2>Network' . IS_BETA . '</h2>
	<br />';
	if (! is_multisite() ) {
		echo '<div class="updated settings-error"><p>You do not have a multisite network.</p></div></div>';
		return;
	}

	// Saved ?
	if ( isset( $_POST['nfw_options']) ) {
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

/* ================================================================== */

function nf_sub_alerts() {

	// Alerts menu :

	if (! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.',
			'', array( 'response' => 403 ) );
	}

	$nfw_options = get_option( 'nfw_options' );

	echo '<div class="wrap">
	<div style="width:54px;height:52px;background-image:url( ' . plugins_url() . '/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2>Event Notifications' . IS_BETA .'</h2>
	<br />';

	// Saved ?
	if ( isset( $_POST['nfw_options']) ) {
		nf_sub_alerts_save();
		echo '<div class="updated settings-error"><p><strong>Your changes have been saved.</strong></p></div>';
		$nfw_options = get_option( 'nfw_options' );
	}

	if (! isset( $nfw_options['a_0'] ) ) {
		$nfw_options['a_0'] = 1;
	}
	?>
	<form method="post" name="nfwalerts">

	<h3>WordPress admin console</h3>
	<table class="form-table">
		<tr>
			<th scope="row">Send me an alert whenever</th>
			<td align="left">
			<p><label><input type="radio" name="nfw_options[a_0]" value="1"<?php checked( $nfw_options['a_0'], 1) ?>>&nbsp;An administrator logs in (default)</label></p>
			<p><label><input type="radio" name="nfw_options[a_0]" value="2"<?php checked( $nfw_options['a_0'], 2) ?>>&nbsp;Someone (user, admin, editor...) logs in</label></p>
			<p><label><input type="radio" name="nfw_options[a_0]" value="0"<?php checked( $nfw_options['a_0'], 0) ?>>&nbsp;No, thanks</label></p>
			</td>
		</tr>
	</table>

	<br />

	<h3>Plugins</h3>
	<table class="form-table">
		<tr>
			<th scope="row">Send me an alert whenever someone</th>
			<td align="left">
			<p><label><input type="checkbox" name="nfw_options[a_11]" value="1"<?php checked( $nfw_options['a_11'], 1) ?>>&nbsp;Uploads a plugin (default)</label></p>
			<p><label><input type="checkbox" name="nfw_options[a_12]" value="1"<?php checked( $nfw_options['a_12'], 1) ?>>&nbsp;Installs a plugin (default)</label></p>
			<p><label><input type="checkbox" name="nfw_options[a_13]" value="1"<?php checked( $nfw_options['a_13'], 1) ?>>&nbsp;Activates a plugin</label></p>
			<p><label><input type="checkbox" name="nfw_options[a_14]" value="1"<?php checked( $nfw_options['a_14'], 1) ?>>&nbsp;Updates a plugin</label></p>
			<p><label><input type="checkbox" name="nfw_options[a_15]" value="1"<?php checked( $nfw_options['a_15'], 1) ?>>&nbsp;Deactivates a plugin (default)</label></p>
			<p><label><input type="checkbox" name="nfw_options[a_16]" value="1"<?php checked( $nfw_options['a_16'], 1) ?>>&nbsp;Deletes a plugin</label></p>
			</td>
		</tr>
	</table>

	<br />

	<h3>Themes</h3>
	<table class="form-table">
		<tr>
			<th scope="row">Send me an alert whenever someone</th>
			<td align="left">
			<p><label><input type="checkbox" name="nfw_options[a_21]" value="1"<?php checked( $nfw_options['a_21'], 1) ?>>&nbsp;Uploads a theme (default)</label></p>
			<p><label><input type="checkbox" name="nfw_options[a_22]" value="1"<?php checked( $nfw_options['a_22'], 1) ?>>&nbsp;Installs a theme (default)</label></p>
			<p><label><input type="checkbox" name="nfw_options[a_23]" value="1"<?php checked( $nfw_options['a_23'], 1) ?>>&nbsp;Activates a theme</label></p>
			<p><label><input type="checkbox" name="nfw_options[a_24]" value="1"<?php checked( $nfw_options['a_24'], 1) ?>>&nbsp;Deletes a theme</label></p>
			</td>
		</tr>
	</table>

	<br />

	<h3>Core</h3>
	<table class="form-table">
		<tr>
			<th scope="row">Send me an alert whenever someone</th>
			<td align="left">
			<p><label><input type="checkbox" name="nfw_options[a_31]" value="1"<?php checked( $nfw_options['a_31'], 1) ?>>&nbsp;Updates WordPress (default)</label></p>
			</td>
		</tr>
	</table>

	<br />

	<?php
	if (! is_multisite() ) {
	?>
	<h3>Contact email</h3>
	<table class="form-table">
		<tr style="background-color:#F9F9F9;border: solid 1px #DFDFDF;">
			<th scope="row">Alerts should be sent to</th>
			<td align="left">
			<input class="regular-text" type="text" name="nfw_options[alert_email]" size="45" maxlength="250" value="<?php echo empty( $nfw_options['alert_email']) ? get_option('admin_email') : $nfw_options['alert_email'] ?>">
			<input type="hidden" name="nfw_options[alert_sa_only]" value="2">
			</td>
		</tr>
	</table>

	<?php
	} else {
		// Select which admin(s) will recevied alerts in multi-site mode :
		if (! isset( $nfw_options['alert_sa_only'] ) ) {
			$nfw_options['alert_sa_only'] = 2;
		}
	?>
	<h3>Contact email</h3>
	<table class="form-table">
		<tr style="background-color:#F9F9F9;border: solid 1px #DFDFDF;">
			<th scope="row">Alerts should be sent to</th>
			<td align="left">
			<p><label><input type="radio" name="nfw_options[alert_sa_only]" value="1"<?php checked( $nfw_options['alert_sa_only'], 1 ) ?>>&nbsp;Only to me, the Super Admin (<?php echo get_option('admin_email'); ?>)</label></p>
			<p><label><input type="radio" name="nfw_options[alert_sa_only]" value="2"<?php checked( $nfw_options['alert_sa_only'], 2) ?>>&nbsp;To the administrator of the site where originated the alert (default)</label></p>
			<input type="hidden" name="nfw_options[alert_email]" value="<?php echo get_option('admin_email'); ?>">
			</td>
		</tr>
	</table>
	<?php
	}
	?>

	<br />
	<br />
	<input class="button-primary" type="submit" name="Save" value="Save Event Notifications" />

	</form>

</div>
<?php

}
/* ================================================================== */

function nf_sub_alerts_save() {

	// Save Event Notifications :

	if (! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.',
			'', array( 'response' => 403 ) );
	}

	$nfw_options = get_option( 'nfw_options' );

	if (! preg_match('/^[012]$/', $_POST['nfw_options']['a_0']) ) {
		$nfw_options['a_0'] = 1;
	} else {
		$nfw_options['a_0'] = $_POST['nfw_options']['a_0'];
	}

	if (! preg_match('/^[12]$/', $_POST['nfw_options']['alert_sa_only']) ) {
		$nfw_options['alert_sa_only'] = 2;
	} else {
		$nfw_options['alert_sa_only'] = $_POST['nfw_options']['alert_sa_only'];
	}

	if ( empty( $_POST['nfw_options']['a_11']) ) {
		$nfw_options['a_11'] = 0;
	} else {
		$nfw_options['a_11'] = $_POST['nfw_options']['a_11'];
	}
	if ( empty( $_POST['nfw_options']['a_12']) ) {
		$nfw_options['a_12'] = 0;
	} else {
		$nfw_options['a_12'] = $_POST['nfw_options']['a_12'];
	}
	if ( empty( $_POST['nfw_options']['a_13']) ) {
		$nfw_options['a_13'] = 0;
	} else {
		$nfw_options['a_13'] = $_POST['nfw_options']['a_13'];
	}
	if ( empty( $_POST['nfw_options']['a_14']) ) {
		$nfw_options['a_14'] = 0;
	} else {
		$nfw_options['a_14'] = $_POST['nfw_options']['a_14'];
	}
	if ( empty( $_POST['nfw_options']['a_15']) ) {
		$nfw_options['a_15'] = 0;
	} else {
		$nfw_options['a_15'] = $_POST['nfw_options']['a_15'];
	}
	if ( empty( $_POST['nfw_options']['a_16']) ) {
		$nfw_options['a_16'] = 0;
	} else {
		$nfw_options['a_16'] = $_POST['nfw_options']['a_16'];
	}

	if ( empty( $_POST['nfw_options']['a_21']) ) {
		$nfw_options['a_21'] = 0;
	} else {
		$nfw_options['a_21'] = $_POST['nfw_options']['a_21'];
	}
	if ( empty( $_POST['nfw_options']['a_22']) ) {
		$nfw_options['a_22'] = 0;
	} else {
		$nfw_options['a_22'] = $_POST['nfw_options']['a_22'];
	}
	if ( empty( $_POST['nfw_options']['a_23']) ) {
		$nfw_options['a_23'] = 0;
	} else {
		$nfw_options['a_23'] = $_POST['nfw_options']['a_23'];
	}
	if ( empty( $_POST['nfw_options']['a_24']) ) {
		$nfw_options['a_24'] = 0;
	} else {
		$nfw_options['a_24'] = $_POST['nfw_options']['a_24'];
	}

	if ( empty( $_POST['nfw_options']['a_31']) ) {
		$nfw_options['a_31'] = 0;
	} else {
		$nfw_options['a_31'] = $_POST['nfw_options']['a_31'];
	}

	if (! empty( $_POST['nfw_options']['alert_email']) ) {
		$nfw_options['alert_email'] = sanitize_email( $_POST['nfw_options']['alert_email'] );
	}
	if ( empty( $nfw_options['alert_email'] ) ) {
		$nfw_options['alert_email'] = get_option('admin_email');
	}

	// Update options :
	update_option( 'nfw_options', $nfw_options );

}

/* ================================================================== */

function nf_sub_log() {

	// Firewall Log menu :

	if (! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.',
			'', array( 'response' => 403 ) );
	}
	$log = plugin_dir_path(__FILE__) . 'log/firewall_' . date( 'Y-m' ) . '.php';

	$err = '';
	if ( file_exists( $log ) ) {
		if (! is_writable( $log ) ) {
			$err = 'logfile is not writable. Please chmod it and its parent directory to 0777';
		}
	} else {
		if (! is_writable( plugin_dir_path(__FILE__) . 'log' ) ) {
			$err = 'log directory is not writable. Please chmod it to 0777';
		}
	}

	echo '
<div class="wrap">
	<div style="width:54px;height:52px;background-image:url( ' . plugins_url() . '/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2>Firewall Log' . IS_BETA . '</h2>
	<br />';

	if ( $err ) {
		echo '<div class="error settings-error"><p><strong>Error : </strong>' . $err . '</p></div>';
	}

	// Do we have any log for this month ?
	if (! file_exists( $log ) ) {
		echo '<div class="updated settings-error"><p>You do not have any log for the current month yet.</p></div></div>';
		return;
	}

	if (! $fh = @fopen( $log, 'r' ) ) {
		echo '<div class="error settings-error"><p><strong>Fatal error :</strong> cannot open the log ( ' . $log .' )</p></div></div>';
		return;
	}
	// We will only display the last $max_lines lines, and will warn
	// about it if the log is bigger :
	$count = 0;
	$max_lines = 500;
	while (! feof( $fh ) ) {
		fgets( $fh );
		$count++;
	}
	// Skip last empty line :
	$count--;
	fclose( $fh );
	if ( $count < $max_lines ) {
		$skip = 0;
	} else  {
		echo '<div class="updated settings-error"><p><strong>Warning :</strong> your log has ' .
			$count . ' lines. I will display the last 500 lines only.</p></div>';
		$skip = $count - $max_lines;
	}

	// Get timezone :
	nfw_get_blogtimezone();

	$levels = array( '', 'medium', 'high', 'critical', 'error', 'upload', 'info', 'DEBUG_ON' );
	echo '
	<table class="form-table">
		<tr>
			<td width="100%">
				<textarea class="small-text code" style="width:100%;height:320px;" wrap="off">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' .
				'DATE&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;INCIDENT&nbsp;&nbsp;LEVEL&nbsp;&nbsp;' .
				'&nbsp;&nbsp;&nbsp;RULE&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;IP&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' .
				'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;REQUEST' . "\n";

	$fh = fopen( $log, 'r' );
	while (! feof( $fh ) ) {
		$line = fgets( $fh );
		if ( $skip <= 0 ) {
			if ( preg_match( '/^\[(\d{10})\]\s+\[.+?\]\s+\[(.+?)\]\s+\[(#\d{7})\]\s+\[(\d+)\]\s+\[(\d)\]\s+\[([\d.:a-fA-F]+?)\]\s+\[.+?\]\s+\[(.+?)\]\s+\[(.+?)\]\s+\[(.+?)\]\s+\[(.+)\]$/', $line, $match ) ) {
				if ( empty( $match[4]) ) { $match[4] = '-'; }
				$res = date( 'd/M/y H:i:s', $match[1] ) . '  ' . $match[3] . '  ' . str_pad( $levels[$match[5]], 8 , ' ', STR_PAD_RIGHT) .'  ' .
				str_pad( $match[4], 4 , ' ', STR_PAD_LEFT) . '  ' . str_pad( $match[6], 15, ' ', STR_PAD_RIGHT) . '  ' .
				$match[7] . ' ' . $match[8] . ' - ' .	$match[9] . ' - [' . $match[10] . ']';
				// If multi-site mode, append the domain name :
				if ( is_multisite() ) {
					$res .= ' - ' . $match[2];
				}
				echo htmlentities( $res ."\n" );
			}
		}
		$skip--;
	}

	fclose( $fh );

	$log_stat = stat( $log );
	echo '</textarea>
				<br />
				<center><span class="description">The log is rotated monthly - Current size: ' . number_format( $log_stat['size'] ) .' bytes, '. $count . ' lines.</span></center>
			</td>
		</tr>
	</table>
</div>';

}
/* ================================================================== */

function nf_sub_loginprot() {

	// WordPress login form protection :

	if (! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.',
			'', array( 'response' => 403 ) );
	}

	echo '
<div class="wrap">
	<div style="width:54px;height:52px;background-image:url( ' . plugins_url() . '/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2>Login Protection' . IS_BETA .'</h2>
	<br />';

	// Saved ?
	if ( isset( $_POST['nfw_options']) ) {
		$res = nf_sub_loginprot_save();
		if (! $res ) {
			echo '<div class="updated settings-error"><p><strong>Your changes have been saved.</strong></p></div>';
		} else {

			echo '<div class="error settings-error"><p><strong>' . $res . '</strong></p></div>';
		}
	}

	// Fetch the current configuration, if any :
	if ( file_exists( plugin_dir_path(__FILE__) . 'log/nfwbfd.php' ) ) {
		require( plugin_dir_path(__FILE__) . 'log/nfwbfd.php' );

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
			$bf_attempt = 5;
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
	}

	if ( empty( $bf_enable ) ) {
		// Default values :
		$bf_enable   = 0;
		$get_post = $bf_request  = 'POST';
		$bf_bantime  = 5;
		$bf_attempt  = 5;
		$bf_maxtime  = 15;
		$auth_name = '';
		$auth_msg = 'Access restricted';
		$bf_xmlrpc = 0;
	}
	if ($bf_xmlrpc) {
		$it_them = 'them';
	} else {
		$it_them = 'it';
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
			document.getElementById('bf_table2').style.display = '';
		} else if ( off == 2 ) {
			document.getElementById('bf_table').style.display = 'none';
			document.getElementById('bf_table2').style.display = '';
		} else {
			document.getElementById('bf_table').style.display = 'none';
			document.getElementById('bf_table2').style.display = 'none';
		}
		return;
	}
	function getpost(request){
		if ( request == 'GETPOST' ) {
			request = 'GET/POST';
		}
		document.getElementById('get_post').innerHTML = request;
	}
	function itthem(it_them) {
		if (it_them.checked == true) {
			document.getElementById('itthem').innerHTML = 'Password-protect them';
		} else {
			document.getElementById('itthem').innerHTML = 'Password-protect it';
		}
	}
	</script>
<br />
<form method="post" name="bp_form">
	<table class="form-table">
		<tr style="background-color:#F9F9F9;border: solid 1px #DFDFDF;">
			<th scope="row">Enable brute force attack protection</th>
			<td>&nbsp;</td>
			<td align="left">
			<label><input type="radio" name="nfw_options[bf_enable]" value="1"<?php checked($bf_enable, 1) ?> onclick="toogle_table(1);">&nbsp;Yes, if under attack</label>
			</td>
			<td align="left">
			<label><input type="radio" name="nfw_options[bf_enable]" value="2"<?php checked($bf_enable, 2) ?> onclick="toogle_table(2);">&nbsp;Always ON</label>
			</td>
			<td align="left">
			<label><input type="radio" name="nfw_options[bf_enable]" value="0"<?php checked($bf_enable, 0) ?> onclick="toogle_table(0);">&nbsp;No (default)</label>
			</td>
		</tr>
	</table>
	<br />
	<table class="form-table" id="bf_table"<?php echo $bf_enable == 1 ? '' : ' style="display:none"' ?>>
		<tr>
			<th scope="row">Protect the login page against</th>
			<td align="left">
			<p><label><input onclick="getpost(this.value);" type="radio" name="nfw_options[bf_request]" value="GET"<?php checked($bf_request, 'GET') ?>>&nbsp;<code>GET</code> request attacks</label></p>
			<p><label><input onclick="getpost(this.value);" type="radio" name="nfw_options[bf_request]" value="POST"<?php checked($bf_request, 'POST') ?>>&nbsp;<code>POST</code> request attacks (default)</label></p>
			<p><label><input onclick="getpost(this.value);" type="radio" name="nfw_options[bf_request]" value="GETPOST"<?php checked($bf_request, 'GETPOST') ?>>&nbsp;<code>GET</code> and <code>POST</code> requests attacks</label></p>
			<br /><br />
			<label><input type="checkbox" onclick="itthem(this);" name="nfw_options[bf_xmlrpc]" value="1"<?php checked($bf_xmlrpc, 1) ?>>&nbsp;Protect the <code>xmlrpc.php</code> script against brute force attacks as well.</label>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row" id="itthem">Password-protect <?php echo $it_them; ?></th>
			<td align="left">
				For <input maxlength="2" size="2" value="<?php echo $bf_bantime ?>" name="nfw_options[bf_bantime]" id="ban1" onkeyup="is_number('ban1')" type="text" title="Enter a value from 1 to 99" /> minutes, if more than <input maxlength="2" size="2" value="<?php echo $bf_attempt ?>" name="nfw_options[bf_attempt]" id="ban2" onkeyup="is_number('ban2')" type="text" title="Enter a value from 1 to 99" /> <code id="get_post"><?php echo $get_post; ?></code> requests within <input maxlength="2" size="2" value="<?php echo $bf_maxtime ?>" name="nfw_options[bf_maxtime]" id="ban3" onkeyup="is_number('ban3')" type="text" title="Enter a value from 1 to 99" /> seconds.
			</td>
		</tr>
	</table>
	<table class="form-table" id="bf_table2"<?php echo $bf_enable ? '' : ' style="display:none"' ?>>
		<tr valign="top">
			<th scope="row">HTTP authentication</th>
			<td align="left">
				User:&nbsp;<input maxlength="20" type="text" autocomplete="off" value="<?php echo $auth_name ?>" size="12" name="nfw_options[auth_name]" title="Enter user name (from 6 to 20 characters)" onkeyup="auth_user_valid();" />&nbsp;&nbsp;&nbsp;&nbsp;Password:&nbsp;<input maxlength="20" type="password" autocomplete="off" value="" size="12" name="nfw_options[auth_pass]" title="Enter password (from 6 to 20 characters)" />
				<br /><span class="description">&nbsp;User and Password must be from 6 to 20 characters.</span>
				<br /><br />Message (max. 150 ASCII characters):<br />
				<input type="text" autocomplete="off" value="<?php echo $auth_msg ?>" maxlength="150" size="50" name="nfw_options[auth_msg]" onkeyup="realm_valid();" />
			</td>
		</tr>
	</table>
	<br />
	<br />
	<input id="save_login" class="button-primary" type="submit" name="Save" value="Save Login Protection" /><div align="right">See benchmark and stress-test: <a href="http://nintechnet.com/1.1.1/" target="_blank">WordPress brute-force detection plugins comparison</a>.</div>
</form>
</div>

<?php

}

/* ================================================================== */

function nf_sub_loginprot_save() {

	// The directory must be writable :
	if (! is_writable( plugin_dir_path(__FILE__) . 'log' ) ) {
		return( 'Error : <code>' . plugin_dir_path(__FILE__) .
			'log/</code> directory is not writable. Please chmod it to 0777.');
	}

	$nfw_options = get_option( 'nfw_options' );

	$bf_rand = '';
	if ( file_exists( plugin_dir_path(__FILE__) . 'log/nfwbfd.php' ) ) {
		require( plugin_dir_path(__FILE__) . 'log/nfwbfd.php' );
	}

	// Disable or enable the protection ?
	if ( empty( $_POST['nfw_options']['bf_enable']) ) {
		// Remove all files :
		if ( file_exists( plugin_dir_path(__FILE__) . 'log/nfwbfd.php' ) ) {
			if (! unlink( plugin_dir_path(__FILE__) . 'log/nfwbfd.php' ) ) {
				return( 'Error : <code>' . plugin_dir_path(__FILE__) .
					'log/nfwbfd.php</code> is read-only and cannot be deleted. Please chmod it to 0777.');
			}
		}
		if ( file_exists( plugin_dir_path(__FILE__) . 'log/nfwblocked' . $_SERVER['SERVER_NAME'] . $bf_rand ) ) {
			if (! unlink( plugin_dir_path(__FILE__) . 'log/nfwblocked' . $_SERVER['SERVER_NAME'] . $bf_rand )) {
				return( 'Error : <code>' . plugin_dir_path(__FILE__) .
					'log/nfwblocked</code> is read-only and cannot be deleted. Please chmod it to 0777.');
			}
		}
		if ( file_exists( plugin_dir_path(__FILE__) . 'log/nfwlog' . $_SERVER['SERVER_NAME'] . $bf_rand ) ) {
			if (! unlink( plugin_dir_path(__FILE__) . 'log/nfwlog' . $_SERVER['SERVER_NAME'] . $bf_rand )) {
				return( 'Error : <code>' . plugin_dir_path(__FILE__) .
					'log/nfwlog' . $_SERVER['SERVER_NAME'] . $bf_rand . '</code> is read-only and cannot be deleted. Please chmod it to 0777.');
			}
		}
		// Clear the backed up values from the DB:
		$nfw_options['bf_enable']  = 0;
		$nfw_options['bf_request'] = 0; $nfw_options['bf_bantime'] = 0;
		$nfw_options['bf_attempt'] = 0; $nfw_options['bf_maxtime'] = 0;
		$nfw_options['auth_name']  = 0; $nfw_options['auth_pass']  = 0;
		$nfw_options['bf_rand']    = 0; $nfw_options['auth_msg']  = 0;
		update_option( 'nfw_options', $nfw_options );
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
		$bf_attempt = 5;
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

	if ( empty($_POST['nfw_options']['auth_name']) ) {
		return( 'Error : please enter a user name for HTTP authentication.');
	} elseif (! preg_match('`^[-/\\_.a-zA-Z0-9]{6,20}$`', $_POST['nfw_options']['auth_name']) ) {
		return( 'Error : HTTP authentication user name is not valid.');
	}
	$auth_name = $_POST['nfw_options']['auth_name'];

	if ( empty($_POST['nfw_options']['auth_pass']) ) {
		if ( (! $auth_name) || (! $auth_pass) ) {
			return( 'Error : please enter a user name and password for HTTP authentication.');
		}
	} elseif ( (strlen($_POST['nfw_options']['auth_pass']) < 6 ) || (strlen($_POST['nfw_options']['auth_pass']) > 20 ) ) {
		return( 'Error : password must be from 6 to 20 characters.');
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
		'$auth_msg=\'' . $auth_msg . '\';$bf_rand=\'' . $bf_rand . '\'; ?>';

	$fh = fopen( plugin_dir_path(__FILE__) . 'log/nfwbfd.php', 'w' );
	if (! $fh) {
		return( 'Error : unable to write the configuration to <code>' .
			plugin_dir_path(__FILE__) . 'log/nfwbfd.php</code>!');
	}
	fwrite( $fh, $data );
	fclose( $fh );

	// Save a copy to the DB, so that we could restore the configuration
	// file after an update :
	$nfw_options['bf_enable']  = $bf_enable;
	$nfw_options['bf_request'] = $bf_request;
	$nfw_options['bf_bantime'] = $bf_bantime;
	$nfw_options['bf_attempt'] = $bf_attempt;
	$nfw_options['bf_maxtime'] = $bf_maxtime;
	$nfw_options['bf_xmlrpc']  = $bf_xmlrpc;
	$nfw_options['auth_name']  = $auth_name;
	$nfw_options['auth_pass']  = $auth_pass;
	$nfw_options['bf_rand']    = $bf_rand;
	$nfw_options['auth_msg']   = $auth_msg;
	update_option( 'nfw_options', $nfw_options );

	// We reset the brute-force protection flag for the logged in user :
	if (! empty($_SESSION['nfw_bfd']) ) {
		unset($_SESSION['nfw_bfd']);
	}

}

/* ================================================================== */

function nfw_query( $query ) { // I18n

	$nfw_options = get_option( 'nfw_options' );
	if ( empty($nfw_options['enum_archives']) || empty($nfw_options['enabled']) ) {
		return;
	}

	if ( $query->is_main_query() && $query->is_author() ) {
		$query->set('author_name', '0');
		nfw_log2( __('User enumeration scan (author archives)', 'ninjafirewall'), $_SERVER['REQUEST_URI'], 2, 0);
		wp_redirect( home_url('/') );
		exit;
	}
}

if (! isset($_SESSION['nfw_goodguy']) ) {
	add_action('pre_get_posts','nfw_query');
}

/* ================================================================== */

function nfw_authenticate( $user ) { // I18n

	// User enumeration (login page) :

	$nfw_options = get_option( 'nfw_options' );

	if ( empty( $nfw_options['enum_login']) || empty($nfw_options['enabled']) ) {
		return $user;
	}

	if ( is_wp_error( $user ) ) {
		if ( preg_match( '/^(?:in(?:correct_password|valid_username)|authentication_failed)$/', $user->get_error_code() ) ) {
			$user = new WP_Error( 'denied', sprintf( __( '<strong>ERROR</strong>: Invalid username or password.<br /><a href="%s">Lost your password</a>?' ), wp_lostpassword_url() ) );
			add_filter('shake_error_codes', 'nfw_err_shake');
		}
	}
	return $user;
}

add_filter( 'authenticate', 'nfw_authenticate', 90, 3 );

function nfw_err_shake( $shake_codes ) {
	// shake the login box :
	$shake_codes[] = 'denied';
	return $shake_codes;
}

/* ================================================================== */

function nfw_log2($loginfo, $logdata, $loglevel, $ruleid) { // I18n

	// Write incident to the firewall log :

	$nfw_options = get_option( 'nfw_options' );

	if (! empty($nfw_options['debug']) ) {
		$num_incident = '0000000';
		$loglevel = 7;
		$http_ret_code = '200 OK';
	// Create a random incident number :
	} else {
		$num_incident = mt_rand(1000000, 9000000);
		$http_ret_code = $nfw_options['ret_code'];
	}
   if (strlen($logdata) > 200) { $logdata = substr($logdata, 0, 200) . '...'; }
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
	$stat_file = plugin_dir_path(__FILE__) . 'log/stats_' . $cur_month . '.php';
	$log_file = plugin_dir_path(__FILE__) . 'log/firewall_' . $cur_month . '.php';

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
      '[' . time() . '] ' . '[0] ' .
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

function nf_sub_edit() {

	// Rules Editor menu :

	if (! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.',
			'', array( 'response' => 403 ) );
	}

	echo '
<div class="wrap">
	<div style="width:54px;height:52px;background-image:url( ' . plugins_url() . '/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2>Rules Editor' . IS_BETA . '</h2>
	<br />';

	$nfw_rules = get_option( 'nfw_rules' );
	$is_update = 0;

	if ( isset($_POST['sel_e_r']) ) {
		if ( $_POST['sel_e_r'] < 1 ) {
			echo '<div class="error settings-error"><p><strong>Error : you did not select a rule to disable</strong></p></div>';
		} else if ( ( $_POST['sel_e_r'] == 2 ) || ( $_POST['sel_e_r'] > 499 ) && ( $_POST['sel_e_r'] < 600 ) ) {
			echo '<div class="error settings-error"><p><strong>Error : to change this rule, use the "Firewall Policies" menu.</strong></p></div>';
		} else if (! isset( $nfw_rules[$_POST['sel_e_r']] ) ) {
			echo '<div class="error settings-error"><p><strong>Error : this rule does not exist&nbsp;!</strong></p></div>';
		} else {
			$nfw_rules[$_POST['sel_e_r']]['on'] = 0;
			$is_update = 1;
			echo '<div class="updated settings-error"><p><strong>Rule ID ' . $_POST['sel_e_r'] . ' has been disabled.</strong></p></div>';
		}
	} else if ( isset($_POST['sel_d_r']) ) {
		if ( $_POST['sel_d_r'] < 1 ) {
			echo '<div class="error settings-error"><p><strong>Error : you did not select a rule to enable</strong></p></div>';
		} else if ( ( $_POST['sel_d_r'] == 2 ) || ( $_POST['sel_d_r'] > 499 ) && ( $_POST['sel_d_r'] < 600 ) ) {
			echo '<div class="error settings-error"><p><strong>Error : to change this rule, use the "Firewall Policies" menu.</strong></p></div>';
		} else if (! isset( $nfw_rules[$_POST['sel_d_r']] ) ) {
			echo '<div class="error settings-error"><p><strong>Error : this rule does not exist&nbsp;!</strong></p></div>';
		} else {
			$nfw_rules[$_POST['sel_d_r']]['on'] = 1;
			$is_update = 1;
			echo '<div class="updated settings-error"><p><strong>Rule ID ' . $_POST['sel_d_r'] . ' has been enabled.</strong></p></div>';
		}
	}
	if ( $is_update ) {
		update_option( 'nfw_rules', $nfw_rules);
	}

	$disabled_rules = $enabled_rules = array();
	foreach ( $nfw_rules as $rule_key => $rule_value ) {
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
			<form method="post">
			<select name="sel_e_r" style="font-family:Consolas,Monaco,monospace;">
				<option value="0">Total rules enabled : ' . count( $enabled_rules ) . '</option>';
	sort( $enabled_rules );
	$count = 0;
	foreach ( $enabled_rules as $key ) {
		// grey-out those ones, they can be changed in the Firewall Policies section:
		if ( ( $key == 2 ) || ( $key > 499 ) && ( $key < 600 ) ) {
			echo '<option value="0" disabled="disabled">Rule ID : ' . $key . '</option>';
		} else {
			echo '<option value="' . $key . '">Rule ID : ' . $key . '</option>';
			$count++;
		}
	}
	echo '</select>&nbsp;&nbsp;<input class="button-secondary" type="submit" name="disable" value="Disable it"' . disabled( $count, 0) .'>
		</form>
		<br />
		<form method="post">
		<select name="sel_d_r" style="font-family:Consolas,Monaco,monospace;">
		<option value="0">Total rules disabled : ' . count( $disabled_rules ) . '</option>';
	sort( $disabled_rules );
	$count = 0;
	foreach ( $disabled_rules as $key ) {
		// grey-out those ones, they can be changed in the Firewall Policies section:
		if ( ( $key == 2 ) || ( $key > 499 ) && ( $key < 600 ) ) {
			echo '<option value="0" disabled="disabled">Rule ID : ' . $key . '</option>';
		} else {
			echo '<option value="' . $key . '">Rule ID : ' . $key . '</option>';
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

/* ================================================================== */

function nf_sub_wplus() {

	// WP+ intro

	if (! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.',
			'', array( 'response' => 403 ) );
	}


	echo '<div class="wrap">
	<div style="width:54px;height:52px;background-image:url( ' . plugins_url() . '/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;" title="NinTechNet"></div>
	<h2><font color="#21759B">WP+</font> Edition' . IS_BETA . '</h2>
	<br />
	<br />
	<center>
		<table style="border: 1px solid #DFDFDF;padding:10px;-moz-box-shadow:-3px 5px 5px #999;-webkit-box-shadow:-3px 5px 5px #999;box-shadow:-3px 5px 5px #999;background-color:#FCFCFC;">
			<tr style="text-align:center">
				<td>
					<table border="0" cellspacing="2" cellpadding="5" width="100%">
						<tr valign=top>
							<td align="center">
							<font style="font-size: 1.2em; font-weight: bold;">
							Need more security ?
							<br />
							Check out our new supercharged edition :
							<br /><br />
							NinjaFirewall (<font color="#21759B">WP+</font> edition)
							</font>
							</td>
						</tr>
						<tr>
							<td align="left">
							<li>Unix shared memory use for inter-process communication and blazing fast performances.</li>
							<li>IP-based Access Control.</li>
							<li>Country-based Access Control via geolocation.</li>
							<li>Role-based Access Control.</li>
							<li>URL-based Access Control.</li>
							<li>Bot-based Access Control.</li>
							<li>Antispam for comment and user regisration forms.</li>
							<li>Rate limiting option to block aggressive bots, crawlers, web scrapers and HTTP DoS attacks.</li>
							<li>Response body filter to scan the output of the HTML page right before it is sent to your visitors browser.</li>
							<li>Better File uploads management.</li>
							<li>Better logs management.</li>
							<li>Full IPv6 compatibility.</li>
							<center>
								<h3><a href="http://ninjafirewall.com/wordpress/nfwplus.php">Learn more</a> about the <font color="#21759B">WP+</font> edition unique features.</h3>
								<h3><a href="http://ninjafirewall.com/wordpress/overview.php">Compare</a> the WP and <font color="#21759B">WP+</font> editions.</h3>
							</center>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>

	</center>
</div>';

}

/* ================================================================== */

function nf_sub_about() {

	// About menu :

	if (! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.',
			'', array( 'response' => 403 ) );
	}


	if ( $data = @file_get_contents( plugin_dir_path(__FILE__) . 'readme.txt' ) ) {
		$what = '== Changelog ==';
		$pos_start = strpos( $data, $what );
		$changelog = substr( $data, $pos_start + strlen( $what ) + 1 );
	} else {
		$changelog = 'Error : cannot find changelog :(';
	}

	echo '
<script>
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
	<h2>About' . IS_BETA . '</h2>
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
					<br />
					<table border="0" cellspacing="2" cellpadding="10" width="100%">
						<tr valign=top>
							<td align=center style="border-right:dotted 0px #FDCD25;" width="33%">
								<img src="' . plugins_url( '/images/logo_nm_65.png', __FILE__ ) . '" width="65" height="65" border=0>
								<br />
								<a href="http://ninjamonitoring.com/" title="NinjaMonitoring: monitor your website for suspicious activities"><b>NinjaMonitoring.com</b></a>
								<br />
								Monitor your website for suspicious activities for just $4.99 per month.
							</td>
							<td align=center style="border-right:dotted 0px #FDCD25;" width="34%">
								<img src="' . plugins_url( '/images/logo_pro_65.png', __FILE__ ) . '" width="65" height="65" border=0>
								<br />
								<a href="http://ninjafirewall.com/" title="NinjaFirewall: advanced firewall software for all your PHP applications"><b>NinjaFirewall.com</b></a>
								<br />
								Advanced firewall software for all your PHP applications.
							</td>
							<td align=center width="33%">
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
		<input class="button-secondary" type="button" value="Changelog" onclick="show_table(12);">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input class="button-primary" type="button" value="Spread the word about the Ninja !" onclick="show_table(11);">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input class="button-secondary" type="button" value="System Info" onclick="show_table(13);">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input class="button-secondary" type="button" value="Privacy Policy" onclick="show_table(14);">
		<br />
		<br />
		<table id="11" border="0" style="display:none;" width="500">
			<tr style="text-align:center">
				<td style="border: solid 1px #DFDFDF;width:25%;"><img src="' . plugins_url( '/images/ninjafirewall_32.png', __FILE__ ) . '" width="32" height="32"></td>
				<td style="border: solid 1px #DFDFDF;width:25%;"><img src="' . plugins_url( '/images/ninjafirewall_50.png', __FILE__ ) . '" width="50" height="50"></td>
				<td style="border: solid 1px #DFDFDF;width:25%;"><img src="' . plugins_url( '/images/ninjafirewall_75.png', __FILE__ ) . '" width="75" height="75"></td>
				<td style="border: solid 1px #DFDFDF;width:25%;"><img src="' . plugins_url( '/images/ninjafirewall_100.png', __FILE__ ) . '" width="100" height="100"></td>
			</tr>
			<tr style="text-align:center" valign="top">
				<td><a href="' . plugins_url( '/images/ninjafirewall_32.png', __FILE__ ) . '">ninjafirewall_32.png</a><br />32x32</td>
				<td><a href="' . plugins_url( '/images/ninjafirewall_50.png', __FILE__ ) . '">ninjafirewall_50.png</a><br />50x50</td>
				<td><a href="' . plugins_url( '/images/ninjafirewall_75.png', __FILE__ ) . '">ninjafirewall_75.png</a><br />75x75</td>
				<td><a href="' . plugins_url( '/images/ninjafirewall_100.png', __FILE__ ) . '">ninjafirewall_100.png</a><br />100x100</td>
			</tr>
		</table>

		<table id="12" style="display:none;" width="500">
			<tr>
				<td>
					<textarea class="small-text code" cols="60" rows="8">' . $changelog . '</textarea>
				</td>
			</tr>
		</table>

		<table id="13" border="0" style="display:none;" width="500">';
	if ( PHP_VERSION ) {
		echo '<tr valign="top"><td width="47%;" align="right">PHP version</td><td width="3%">&nbsp;</td><td width="50%" align="left">' . PHP_VERSION . ' (' . strtoupper( PHP_SAPI ) . ')</td></tr>';
	}
	if ( $_SERVER['SERVER_SOFTWARE'] ) {
		echo '<tr valign="top"><td width="47%;" align="right">HTTP server</td><td width="3%">&nbsp;</td><td width="50%" align="left">' . $_SERVER['SERVER_SOFTWARE'] . '</td></tr>';
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
			echo '<tr valign="top"><td width="47%;" align="right">CPU model</td><td width="3%">&nbsp;</td><td width="50%" align="left">' . str_replace ("model name\t:", '', $cpu[0]) . '</td></tr>';
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

/* ================================================================== */

function ninjafirewall_settings_link( $links, $file ) {

	// Settings link :

	static $this_plugin;
	if (! $this_plugin ) {
		$this_plugin = plugin_basename( __FILE__ );
	}

	if ( $file == $this_plugin ) {
		$settings_link = '<a href="admin.php?page=NinjaFirewall">Settings</a>';
		array_unshift( $links, $settings_link );
	}
	return $links;
}

add_filter( 'plugin_action_links', 'ninjafirewall_settings_link', 10, 2);

/* ================================================================== */

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
/* ================================================================== */

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

	// We try to save the log if this is an update of NinjaFirewall
	// because WP will delete all its files and directories :
	if ( NFW_ALERT == '1:4:ninjafirewall/ninjafirewall.php' && empty( $nfw_options['nfw_tmp'] ) ) {
		// Read, pack and save the log to DB :
		$log_file = plugin_dir_path(__FILE__) . 'log/firewall_' . date( 'Y-m' ) . '.php';
		if ( file_exists( $log_file ) ) {
			update_option( 'nfw_tmp', base64_encode( gzdeflate( file_get_contents($log_file), 9 ) ) );
			$nfw_options['nfw_tmp'] = 1;
			update_option( 'nfw_options', $nfw_options);
		}
	}

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
		$subject = '[NinjaFirewall] Alert: ' . $alert_array[$a_1][0] . ' ' . $alert_array[$a_1][$a_2];
		$message = 'NinjaFirewall has detected the following activity on your account:' . "\n\n".
			'- ' . $alert_array[$a_1][0] . ' ' . $alert_array[$a_1][$a_2] . "\n" .
			'- ' . $alert_array[$a_1]['label'] . ' : ' . $a_3 . "\n\n" .
			'- User : ' . $current_user->user_login . ' (' . $current_user->roles[0] . ")\n" .
			'- IP   : ' . $_SERVER['REMOTE_ADDR'] . "\n" .
			'- Date : ' . date('F j, Y @ H:i:s') . ' (UTC '. date('O') . ")\n" .
			'- URL  : ' . site_url() . "\n\n" .
			'NinjaFirewall (WP edition) - http://ninjafirewall.com/' . "\n" .
			'Support forum: http://wordpress.org/support/plugin/ninjafirewall' . "\n";
		wp_mail( $recipient, $subject, $message );
	}
}
/* ================================================================== */

function nfw_dashboard_widgets() {

	// Add dashboard widgets

	if (! current_user_can( 'manage_options' ) ) {
		return;
	}

    wp_add_dashboard_widget( 'nfw_dashboard_welcome', 'NinjaFirewall Statistics', 'nfw_stats_widget' );
 }

function nfw_stats_widget(){

	$critical = $high = $medium = $upload = $total = 0;
	$stat_file = plugin_dir_path(__FILE__) . 'log/stats_' . date( 'Y-m' ) . '.php';
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
			<th width="50%" align="left">Blocked hacking attempts</th>
			<td width="50%" align="left">' . $total . '</td>
		</tr>
		<tr>
			<th width="50%" align="left">Hacking attempts severity</th>
			<td width="50%" align="left">
				<i>Critical : ' . $critical . '%</i>
				<br />
				<table bgcolor="#DFDFDF" border="0" cellpadding="0" cellspacing="0" height="14" width="100%" align="left" style="height:14px;">
					<tr>
						<td width="' . round( $critical) . '%" background="' . plugins_url( '/images/bar-critical.png', __FILE__ ) . '" style="padding:0px"></td><td width="' . round(100 - $critical) . '%" style="padding:0px"></td>
					</tr>
				</table>
				<br />
				<i>High : ' . $high . '%</i>
				<br />
				<table bgcolor="#DFDFDF" border="0" cellpadding="0" cellspacing="0" height="14" width="100%" align="left" style="height:14px;">
					<tr>
						<td width="' . round( $high) . '%" background="' . plugins_url( '/images/bar-high.png', __FILE__ ) . '" style="padding:0px"></td><td width="' . round(100 - $high) . '%" style="padding:0px"></td>
					</tr>
				</table>
				<br />
				<i>Medium : ' . $medium . '%</i>
				<br />
				<table bgcolor="#DFDFDF" border="0" cellpadding="0" cellspacing="0" height="14" width="100%" align="left" style="height:14px;">
					<tr>
						<td width="' . round( $medium) . '%" background="' . plugins_url( '/images/bar-medium.png', __FILE__ ) . '" style="padding:0px;"></td><td width="' . round(100 - $medium) . '%" style="padding:0px;"></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<th width="50%" align="left">Uploaded files</th>
			<td width="50%" align="left">' . $upload . '</td>
		</tr>
	</table>
	<div align="right"><small><a href="admin.php?page=nfsublog">View firewall log</a></small></div>
';
}

if ( is_multisite() ) {
	add_action( 'wp_network_dashboard_setup', 'nfw_dashboard_widgets' );
} else {
	add_action( 'wp_dashboard_setup', 'nfw_dashboard_widgets' );
}

/* ================================================================== */

// EOF //
?>