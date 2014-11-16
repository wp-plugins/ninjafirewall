<?php
/*
 +---------------------------------------------------------------------+
 | NinjaFirewall (WP  edition)                                         |
 |                                                                     |
 | (c) NinTechNet - http://nintechnet.com/ - wordpress@nintechnet.com  |
 |                                                                     |
 +---------------------------------------------------------------------+
 | REVISION: 2014-11-13 20:31:48                                       |
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
 +---------------------------------------------------------------------+ i18n
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
	if (! file_exists(WP_CONTENT_DIR . '/nfwlog') ) {
		@mkdir( WP_CONTENT_DIR . '/nfwlog', 0755);
		@touch( WP_CONTENT_DIR . '/nfwlog/index.html' );
		@file_put_contents(WP_CONTENT_DIR . '/nfwlog/.htaccess', "Order Deny,Allow\nDeny from all");
		if (! file_exists(WP_CONTENT_DIR . '/nfwlog/cache') ) {
			@mkdir( WP_CONTENT_DIR . '/nfwlog/cache', 0755);
			@touch( WP_CONTENT_DIR . '/nfwlog/cache/index.html' );
			@file_put_contents(WP_CONTENT_DIR . '/nfwlog/cache/.htaccess', "Order Deny,Allow\nDeny from all");
		}
	}
	if (! file_exists(WP_CONTENT_DIR . '/nfwlog') ) {
		echo '<div class="error"><p>' . sprintf( __('<strong>NinjaFirewall error :</strong> <code>%s/nfwlog/</code> directory cannot be created. Please review your installation and ensure that <code>/wp-content/</code> is writable.', 'ninjafirewall'), WP_CONTENT_DIR) . '</p></div>';
	}
	if (! is_writable(WP_CONTENT_DIR . '/nfwlog') ) {
		echo '<div class="error"><p>' . sprintf( __('<strong>NinjaFirewall error :</strong> <code>%s/nfwlog/</code> directory is read-only. Please review your installation and ensure that <code>/nfwlog/</code> is writable.', 'ninjafirewall'), WP_CONTENT_DIR) . '</p></div>';
	}

	if (! NF_DISABLED) {
		// OK
		return;
	}

	// Don't display anything if we are looking at the main page (error message will
	// be displayed already) or during the installation process :
	if (isset($_GET['page']) && $_GET['page'] == 'NinjaFirewall' ) {
		return;
	}

	$nfw_options = get_option('nfw_options');
	if ( empty($nfw_options['ret_code']) ) {
		// we will assume that NinjaFirewall it is not installed yet :
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
// EOF
