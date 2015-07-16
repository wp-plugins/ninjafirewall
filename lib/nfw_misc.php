<?php
/*
 +---------------------------------------------------------------------+
 | NinjaFirewall (WP edition)                                          |
 |                                                                     |
 | (c) NinTechNet - http://nintechnet.com/                             |
 +---------------------------------------------------------------------+
 | REVISION: 2015-05-01 00:55:43                                       |
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
 +---------------------------------------------------------------------+ i18n / sa
*/

if (! defined( 'NFW_ENGINE_VERSION' ) ) { die( 'Forbidden' ); }

/* ------------------------------------------------------------------ */

function nfw_admin_notice(){

	// display a big red warning and returned an error if:
	// -the firewall is not enabled.
	// -log dir does not exist or is not writable.

	// we don't display any fatal error message to users :
	if (nf_not_allowed( 0, __LINE__ ) ) { return; }

	if (! defined('NF_DISABLED') ) {
		is_nfw_enabled();
	}

	// Ensure we have our cache/log folder, or attempt to create it :
	if (! file_exists(NFW_LOG_DIR . '/nfwlog') ) {
		@mkdir( NFW_LOG_DIR . '/nfwlog', 0755);
		@touch( NFW_LOG_DIR . '/nfwlog/index.html' );
		@file_put_contents(NFW_LOG_DIR . '/nfwlog/.htaccess', "Order Deny,Allow\nDeny from all", LOCK_EX);
		if (! file_exists(NFW_LOG_DIR . '/nfwlog/cache') ) {
			@mkdir( NFW_LOG_DIR . '/nfwlog/cache', 0755);
			@touch( NFW_LOG_DIR . '/nfwlog/cache/index.html' );
			@file_put_contents(NFW_LOG_DIR . '/nfwlog/cache/.htaccess', "Order Deny,Allow\nDeny from all", LOCK_EX);
		}
	}
	if (! file_exists(NFW_LOG_DIR . '/nfwlog') ) {
		echo '<div class="error"><p>' . sprintf( __('<strong>NinjaFirewall error :</strong> <code>%s/nfwlog/</code> directory cannot be created. Please review your installation and ensure that <code>/wp-content/</code> is writable.', 'ninjafirewall'), htmlspecialchars(NFW_LOG_DIR) ) . '</p></div>';
	}
	if (! is_writable(NFW_LOG_DIR . '/nfwlog') ) {
		echo '<div class="error"><p>' . sprintf( __('<strong>NinjaFirewall error :</strong> <code>%s/nfwlog/</code> directory is read-only. Please review your installation and ensure that <code>/nfwlog/</code> is writable.', 'ninjafirewall'), htmlspecialchars(NFW_LOG_DIR) ) . '</p></div>';
	}

	if (! NF_DISABLED) {
		// OK
		return;
	}

	// Don't display anything if we are looking at the main/options pages
	// (error message will be displayed already) or during the installation
	// process :
	if (isset($_GET['page']) && preg_match('/^(?:NinjaFirewall|nfsubopt)$/', $_GET['page']) ) {
		return;
	}

	$nfw_options = get_option('nfw_options');
	// If we cannot find options and if the firewall did not return
	// a #11 status code (corrupted DB/tables)...
	if ( empty($nfw_options['ret_code']) && NF_DISABLED != 11 ) {
		// ...we will assume that NinjaFirewall it is not installed yet :
		return;
	}

	if (! empty($GLOBALS['err_fw'][NF_DISABLED]) ) {
		$msg = $GLOBALS['err_fw'][NF_DISABLED];
	} else {
		$msg = 'unknown error #' . NF_DISABLED;
	}
	echo '<div class="error"><p><strong>' . __('NinjaFirewall fatal error :', 'ninjafirewall') . '</strong> ' . $msg .
		'. ' . __('Review your installation, your site is not protected.', 'ninjafirewall') . '</p></div>';
}

add_action('all_admin_notices', 'nfw_admin_notice');

/* ------------------------------------------------------------------ */

function nfw_query( $query ) { // i18n

	$nfw_options = get_option( 'nfw_options' );
	if ( empty($nfw_options['enum_archives']) || empty($nfw_options['enabled']) ) {
		return;
	}

	if ( $query->is_main_query() && $query->is_author() ) {
		if (! empty($_REQUEST['author']) ) {
			$tmp = 'author=' . $_REQUEST['author'];
		} elseif (! empty($_REQUEST['author_name']) ) {
			$tmp = 'author_name=' . $_REQUEST['author_name'];
		} else {
			return;
		}
		$query->set('author_name', '0');
		nfw_log2( __('User enumeration scan (author archives)', 'ninjafirewall'), $tmp, 2, 0);
		wp_redirect( home_url('/') );
		exit;
	}
}

if (! isset($_SESSION['nfw_goodguy']) ) {
	add_action('pre_get_posts','nfw_query');
}

/* ------------------------------------------------------------------ */

function nfw_authenticate( $user ) { // i18n

	// User enumeration (login page) :

	$nfw_options = get_option( 'nfw_options' );

	if ( empty( $nfw_options['enum_login']) || empty($nfw_options['enabled']) ) {
		return $user;
	}

	if ( is_wp_error( $user ) ) {
		if ( preg_match( '/^(?:in(?:correct_password|valid_username)|authentication_failed)$/', $user->get_error_code() ) ) {
			$user = new WP_Error( 'denied', sprintf( __( '<strong>ERROR</strong>: Invalid username or password.<br /><a href="%s">Lost your password</a>?', 'ninjafirewall' ), wp_lostpassword_url() ) );
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

/* ------------------------------------------------------------------ */

function nf_check_dbdata() {

	$nfw_options = get_option( 'nfw_options' );

	// Don't do anything if NinjaFirewall is disabled or DB monitoring option is off :
	if ( empty( $nfw_options['enabled'] ) || empty($nfw_options['a_51']) ) { return; }

	if ( is_multisite() ) {
		global $current_blog;
		$nfdbhash = NFW_LOG_DIR .'/nfwlog/cache/nfdbhash.'. $current_blog->site_id .'-'. $current_blog->blog_id .'.php';
	} else {
		global $blog_id;
		$nfdbhash = NFW_LOG_DIR .'/nfwlog/cache/nfdbhash.'. $blog_id .'.php';
	}

	$adm_users = nf_get_dbdata();
	if (! $adm_users) { return; }

	if (! file_exists($nfdbhash) ) {
		// We don't have any hash yet, let's create one and quit
		// (md5 is faster than sha1 or crc32 with long strings) :
		@file_put_contents( $nfdbhash, md5( serialize( $adm_users) ), LOCK_EX );
		return;
	}

	$old_hash = trim (file_get_contents($nfdbhash) );

	// Compare both hashes :
	if ( $old_hash == md5( serialize($adm_users)) ) {
		return;
	} else {
		$fstat = stat($nfdbhash);
		// We don't want to spam the admin, do we ?
		if ( ( time() - $fstat['mtime']) < 60 ) {
			return;
		}

		// Save the new hash :
		$tmp = @file_put_contents( $nfdbhash, md5( serialize( $adm_users) ), LOCK_EX );
		if ( $tmp === FALSE ) {
			return;
		}

		// Get timezone :
		nfw_get_blogtimezone();

		// Send an email to the admin :
		if ( ( is_multisite() ) && ( $nfw_options['alert_sa_only'] == 2 ) ) {
			$recipient = get_option('admin_email');
		} else {
			$recipient = $nfw_options['alert_email'];
		}

		$subject = __('[NinjaFirewall] Alert: Database changes detected', 'ninjafirewall');
		$message = __('NinjaFirewall has detected that one or more administrator accounts were modified in the database:', 'ninjafirewall') . "\n\n";
		if ( is_multisite() ) {
			$message.= __('Blog : ', 'ninjafirewall') . network_home_url('/') . "\n";
		} else {
			$message.= __('Blog : ', 'ninjafirewall') . home_url('/') . "\n";
		}
		$message.= __('Date : ', 'ninjafirewall') . date_i18n('F j, Y @ H:i:s') . ' (UTC '. date('O') . ")\n\n";
		$message.= sprintf(__('Total administrators : %s', 'ninjafirewall'), count($adm_users) ) . "\n\n";
		foreach( $adm_users as $obj => $adm ) {
			$message.= 'Admin ID : ' . $adm->ID . "\n";
			$message.= '- user_login : ' . $adm->user_login . "\n";
			$message.= '- user_nicename : ' . $adm->user_nicename . "\n";
			$message.= '- user_email : ' . $adm->user_email . "\n";
			$message.= '- user_registered : ' . $adm->user_registered . "\n";
			$message.= '- display_name : ' . $adm->display_name . "\n\n";
		}
		$message.= "\n" . __('If you cannot see any modifications in the above fields, it is likely that the administrator password was changed.', 'ninjafirewall'). "\n\n";
		$message.= 	'NinjaFirewall (WP edition) - http://ninjafirewall.com/' . "\n" .
						'Support forum: http://wordpress.org/support/plugin/ninjafirewall' . "\n";
		wp_mail( $recipient, $subject, $message );

		// Log event if required :
		if (! empty($nfw_options['a_41']) ) {
			nfw_log2( __('Database changes detected', 'ninjafirewall'), __('administrator account', 'ninjafirewall'), 4, 0);
		}
	}

}

/* ------------------------------------------------------------------ */

function nf_get_dbdata() {

	return get_users(
		array( 'role' => 'administrator',
			'fields' => array(
				'ID',
				'user_login',
				'user_pass',
				'user_nicename',
				'user_email',
				'user_registered',
				'display_name'
			)
		)
	);

}
/* ------------------------------------------------------------------ */
// EOF
