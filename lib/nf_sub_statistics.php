<?php
/*
 +---------------------------------------------------------------------+
 | NinjaFirewall (WP edition)                                          |
 |                                                                     |
 | (c) NinTechNet - http://nintechnet.com/                             |
 +---------------------------------------------------------------------+
 | REVISION: 2015-08-01 17:43:40                                       |
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
 +---------------------------------------------------------------------+ i18n+ / sa
*/

if (! defined( 'NFW_ENGINE_VERSION' ) ) { die( 'Forbidden' ); }

// Block immediately if user is not allowed :
nf_not_allowed( 'block', __LINE__ );

echo '
<div class="wrap">
		<div style="width:54px;height:52px;background-image:url( ' . plugins_url() . '/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2>' . __('Statistics', 'ninjafirewall') . '</h2>
	<br />';

$critical = $high = $medium = $slow = $benchmark =
$tot_bench = $speed = $upload = $banned_ip = 0;
$fast = 1000;

// Which monthly log should we read ?
$xtr = @$_GET['xtr'];
if ( empty($xtr) || ! preg_match('/^firewall_\d{4}-\d{2}\.php$/D', $xtr) ) {
	$xtr = 'firewall_' . date('Y-m') . '.php';
}
$fw_log = NFW_LOG_DIR . '/nfwlog/' . $xtr;

if (! file_exists($fw_log) ) {
	goto NO_STATS_FILE;
}

if ($fh = @fopen($fw_log, 'r') ) {
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
} else {
	echo '<div class="error notice is-dismissible"><p>' . __('Cannot open logfile', 'ninjafirewall') . ' : <code>' . $fw_log . '</code></p></div></div>';
	summary_stats_combo($xtr);
	return;
}

NO_STATS_FILE:

$total = $critical + $high + $medium;
if ($total == 1) {$fast = $slow;}

if (! $total ) {
	echo '<div class="error notice is-dismissible"><p>' . __('You do not have any stats for the current month yet.', 'ninjafirewall') . '</p></div>';
	$fast = 0;
} else {
	$coef = 100 / $total;
	$critical = round($critical * $coef, 2);
	$high = round($high * $coef, 2);
	$medium = round($medium * $coef, 2);
	// Avoid divide error :
	if ($tot_bench) {
		$speed = round($speed / $tot_bench, 4);
	} else {
		$fast = 0;
	}
}
// Prepare select box :
$ret = summary_stats_combo($xtr);

echo '
<script>
	function stat_redir(where) {
		if (where == "") { return false;}
		document.location.href="?page=nfsubstat&xtr=" + where;
	}
</script>
	<table class="form-table">
		<tr>
			<th scope="row"><h3>' . __('Monthly stats', 'ninjafirewall') . '</h3></th>
			<td align="left">' . $ret . '</td>
		</tr>
		<tr>
			<th scope="row">' . __('Blocked hacking attempts', 'ninjafirewall') . '</th>
			<td align="left">' . $total . '</td>
		</tr>
		<tr>
			<th scope="row">' . __('Hacking attempts severity', 'ninjafirewall') . '</th>
			<td align="left">
				' . __('Critical', 'ninjafirewall') . ' : ' . $critical . '%<br />
				<table bgcolor="#DFDFDF" border="0" cellpadding="0" cellspacing="0" height="14" width="250" align="left" style="height:14px;">
					<tr>
						<td width="' . round( $critical) . '%" background="' . plugins_url() . '/ninjafirewall/images/bar-critical.png" style="padding:0px"></td><td width="' . round(100 - $critical) . '%" style="padding:0px"></td>
					</tr>
				</table>
				<br /><br />' . __('High', 'ninjafirewall') . ' : ' . $high . '%<br />
				<table bgcolor="#DFDFDF" border="0" cellpadding="0" cellspacing="0" height="14" width="250" align="left" style="height:14px;">
					<tr>
						<td width="' . round( $high) . '%" background="' . plugins_url() . '/ninjafirewall/images/bar-high.png" style="padding:0px"></td><td width="' . round(100 - $high) . '%" style="padding:0px"></td>
					</tr>
				</table>
				<br /><br />' . __('Medium', 'ninjafirewall') . ' : ' . $medium . '%<br />
				<table bgcolor="#DFDFDF" border="0" cellpadding="0" cellspacing="0" height="14" width="250" align="left" style="height:14px;">
					<tr>
						<td width="' . round( $medium) . '%" background="' . plugins_url() . '/ninjafirewall/images/bar-medium.png" style="padding:0px;"></td><td width="' . round(100 - $medium) . '%" style="padding:0px;"></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<th scope="row">' . __('Uploaded files', 'ninjafirewall') . '</th>
			<td align="left">' . $upload . '</td>
		</tr>
		<tr><th scope="row"><h3>' . __('Benchmarks', 'ninjafirewall') . '</h3></th><td>&nbsp;</td><td>&nbsp;</td></tr>
		<tr>
			<th scope="row">' . __('Average time per request', 'ninjafirewall') . '</th>
			<td align="left">' . $speed . 's</td>
		</tr>
		<tr>
			<th scope="row">' . __('Fastest request', 'ninjafirewall') . '</th>
			<td align="left">' . round( $fast, 4) . 's</td>
		</tr>
		<tr>
			<th scope="row">' . __('Slowest request', 'ninjafirewall') . '</th>
			<td align="left">' . round( $slow, 4) . 's</td>
		</tr>
	</table>
</div>';

/* ------------------------------------------------------------------ */
function summary_stats_combo( $xtr ) {

	// Find all available logs :
	$avail_logs = array();
	if ( is_dir( NFW_LOG_DIR . '/nfwlog/' ) ) {
		if ( $dh = opendir( NFW_LOG_DIR . '/nfwlog/' ) ) {
			while ( ($file = readdir($dh) ) !== false ) {
				if (preg_match( '/^(firewall_(\d{4})-(\d\d)\.php)$/', $file, $match ) ) {
					$log_stat = stat( NFW_LOG_DIR . '/nfwlog/' . $file );
					if ( $log_stat['size'] < 10 ) { continue; }
					$month = ucfirst( date_i18n('F', mktime(0, 0, 0, $match[3], 1, 2000) ) );
					$avail_logs[$match[1] ] = $month . ' ' . $match[2];
				}
			}
			closedir($dh);
		}
	}
	krsort($avail_logs);

	$ret = '<form>
			<select class="input" name="xtr" onChange="return stat_redir(this.value);">
				<option value="">' . __('Select monthly stats to view...', 'ninjafirewall') . '</option>';
   foreach ($avail_logs as $file => $text) {
      $ret .= '<option value="' . $file . '"';
      if ($file === $xtr ) {
         $ret .= ' selected';
      }
      $ret .= '>' . $text . '</option>';
   }
   $ret .= '</select>
		</form>';
	return $ret;
}

/* ------------------------------------------------------------------ */
// EOF
