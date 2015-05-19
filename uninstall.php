<?php
/*
 +---------------------------------------------------------------------+
 | NinjaFirewall (WP edition)                                          |
 |                                                                     |
 | (c) NinTechNet - http://nintechnet.com/                             |
 +---------------------------------------------------------------------+
 | REVISION: 2015-04-16 22:11:29                                       |
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

if (! defined('WP_UNINSTALL_PLUGIN') || ! WP_UNINSTALL_PLUGIN ||
	dirname( WP_UNINSTALL_PLUGIN ) != dirname( plugin_basename( __FILE__ )) ) {
	exit;
}

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

nfw_uninstall();

/* ------------------------------------------------------------------ */

function nfw_uninstall() {

	// Unset the goodguy flag :
	if ( isset( $_SESSION['nfw_goodguy'] ) ) {
		unset( $_SESSION['nfw_goodguy'] );
	}

	define( 'HTACCESS_BEGIN', '# BEGIN NinjaFirewall' );
	define( 'HTACCESS_END', '# END NinjaFirewall' );
	define( 'PHPINI_BEGIN', '; BEGIN NinjaFirewall' );
	define( 'PHPINI_END', '; END NinjaFirewall' );

	// Retrieve installation info :
	global $nfw_install;
	if (! isset( $nfw_install) ) {
		$nfw_install = get_option( 'nfw_install' );
	}

	// clean-up .htaccess :
	if (! empty($nfw_install['htaccess']) && file_exists($nfw_install['htaccess']) ) {
		$htaccess_file = $nfw_install['htaccess'];
	} elseif ( file_exists( ABSPATH . '.htaccess' ) ) {
		$htaccess_file = ABSPATH . '.htaccess';
	} else {
		$htaccess_file = '';
	}

	// Ensure it is writable :
	if (! empty($htaccess_file) && is_writable( $htaccess_file ) ) {
		$data = file_get_contents( $htaccess_file );
		// Find / delete instructions :
		$data = preg_replace( '/\s?'. HTACCESS_BEGIN .'.+?'. HTACCESS_END .'[^\r\n]*\s?/s' , "\n", $data);
		@file_put_contents( $htaccess_file,  $data, LOCK_EX );
	}

	// Clean up PHP INI file :
	if (! empty($nfw_install['phpini']) && file_exists($nfw_install['phpini']) ) {
		if ( is_writable( $nfw_install['phpini'] ) ) {
			$phpini[] = $nfw_install['phpini'];
		}
	}
	if ( file_exists( ABSPATH . 'php.ini' ) ) {
		if ( is_writable( ABSPATH . 'php.ini' ) ) {
			$phpini[] = ABSPATH . 'php.ini';
		}
	}
	if ( file_exists( ABSPATH . 'php5.ini' ) ) {
		if ( is_writable( ABSPATH . 'php5.ini' ) ) {
			$phpini[] = ABSPATH . 'php5.ini';
		}
	}
	if ( file_exists( ABSPATH . '.user.ini' ) ) {
		if ( is_writable( ABSPATH . '.user.ini' ) ) {
			$phpini[] = ABSPATH . '.user.ini';
		}
	}
	foreach( $phpini as $ini ) {
		$data = file_get_contents( $ini );
		$data = preg_replace( '/\s?'. PHPINI_BEGIN .'.+?'. PHPINI_END .'[^\r\n]*\s?/s' , "\n", $data);
		@file_put_contents( $ini, $data, LOCK_EX );
	}

	// Remove any scheduled cron job :
	if ( wp_next_scheduled('nfscanevent') ) {
		wp_clear_scheduled_hook('nfscanevent');
	}
	if ( wp_next_scheduled('nfsecupdates') ) {
		wp_clear_scheduled_hook('nfsecupdates');
	}

	// Delete DB rows :
	delete_option('nfw_options');
	delete_option('nfw_rules');
	delete_option('nfw_install');
	delete_option( 'nfw_tmp' );

}

/* ------------------------------------------------------------------ */
// EOF
