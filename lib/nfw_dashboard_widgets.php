<?php
/*
 +---------------------------------------------------------------------+
 | NinjaFirewall (WP edition)                                          |
 |                                                                     |
 | (c) NinTechNet - http://nintechnet.com/                             |
 +---------------------------------------------------------------------+
 | REVISION: 2015-05-28 19:59:56                                       |
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

// Return immediately if user is not allowed :
if (nf_not_allowed( 0, __LINE__ ) ) { return; }

wp_add_dashboard_widget( 'nfw_dashboard_welcome', __('NinjaFirewall Statistics'), 'nfw_stats_widget' );

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
			<th width="50%" align="left">' . __('Blocked hacking attempts') .'</th>
			<td width="50%" align="left">' . $total . '</td>
		</tr>
		<tr>
			<th width="50%" align="left">' . __('Hacking attempts severity') .'</th>
			<td width="50%" align="left">
				<i>' . __('Critical : ') . $critical . '%</i>
				<br />
				<table bgcolor="#DFDFDF" border="0" cellpadding="0" cellspacing="0" height="14" width="100%" align="left" style="height:14px;">
					<tr>
						<td width="' . round( $critical) . '%" background="' . plugins_url() . '/ninjafirewall/images/bar-critical.png" style="padding:0px"></td><td width="' . round(100 - $critical) . '%" style="padding:0px"></td>
					</tr>
				</table>
				<br />
				<i>' . __('High : ') . $high . '%</i>
				<br />
				<table bgcolor="#DFDFDF" border="0" cellpadding="0" cellspacing="0" height="14" width="100%" align="left" style="height:14px;">
					<tr>
						<td width="' . round( $high) . '%" background="' . plugins_url() . '/ninjafirewall/images/bar-high.png" style="padding:0px"></td><td width="' . round(100 - $high) . '%" style="padding:0px"></td>
					</tr>
				</table>
				<br />
				<i>' . __('Medium : ') . $medium . '%</i>
				<br />
				<table bgcolor="#DFDFDF" border="0" cellpadding="0" cellspacing="0" height="14" width="100%" align="left" style="height:14px;">
					<tr>
						<td width="' . round( $medium) . '%" background="' . plugins_url() . '/ninjafirewall/images/bar-medium.png" style="padding:0px;"></td><td width="' . round(100 - $medium) . '%" style="padding:0px;"></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<th width="50%" align="left">' . __('Uploaded files') .'</th>
			<td width="50%" align="left">' . round($upload) . '</td>
		</tr>
	</table>';
	// Display the link to the log page only if the log is not empty :
	if ( $total || $upload ) {
		echo '<div align="right"><small><a href="admin.php?page=nfsublog">' . __('View firewall log') .'</a></small></div>';
	}
}
// EOF
