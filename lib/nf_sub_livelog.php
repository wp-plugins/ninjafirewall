<?php
/*
 +---------------------------------------------------------------------+
 | NinjaFirewall (WP edition)                                          |
 |                                                                     |
 | (c) NinTechNet - http://nintechnet.com/                             |
 +---------------------------------------------------------------------+
 | REVISION: 2015-03-13 12:32:57                                       |
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

if (nf_not_allowed( 1, __LINE__ ) ) { exit; }

if (! defined('NF_DISABLED') ) {
	is_nfw_enabled();
}
if (NF_DISABLED) {
	$err_msg = __('Error: NinjaFirewall must be enabled and working in order to use the Live Log feature.' );
}
if ( empty($_SESSION['nfw_goodguy']) ) {
	$err_msg = __('Error: You must be whitelisted in order to use that feature: click on the <a href="?page=nfsubpolicies">Firewall Policies</a> menu and ensure that the "Do not block WordPress administrator" option is enabled.' );
}
if (! empty($err_msg) ) {
	?>
	<div class="wrap">
	<div style="width:54px;height:52px;background-image:url( <?php echo plugins_url() ?>/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2><?php _e('Live Log') ?></h2>
	<br />
	<div class="error settings-error"><p><?php echo $err_msg ?></p></div>
	</div>
	<?php
	return;
}

// Create an empty log :
$fh = fopen( WP_CONTENT_DIR . '/nfwlog/cache/livelog.php', 'w');
fclose($fh);
$_SESSION['nfw_livelog'] = 1;

// jQuery ? No, thanks :
?>
<script>
var count = 0;
var lines = 0;
var scroll = 1;
var liveon = 1;
var liveint = 10000;
var livecls = 0;
var myinterval;
var ajaxURL = '<?php
if ( $_SERVER['SERVER_PORT'] == 443 ) {
	echo site_url( '', 'https' );
} else {
	echo site_url();
}
?>/index.php';
function getHTTPObject(){
   var http;
   if(window.XMLHttpRequest){
      http = new XMLHttpRequest();
   } else if(window.ActiveXObject){
      http = new ActiveXObject("Microsoft.XMLHTTP");
   }
   return http;
}
var http = getHTTPObject();
function live_fetch() {
	if (count) {
		document.getElementById("loading").innerHTML = "<?php _e('Loading...') ?>";
		document.getElementById('radioon').style.background = 'orange';
		document.getElementById('radiooff').disabled = true;
	}
	http.open("POST", ajaxURL, true);
   http.onreadystatechange = live_fetchRes;
   http.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
	http.send('livecls=' + livecls + '&lines=' + lines);
	count = 1;
	livecls = 0;
}
live_fetch();
myinterval = setInterval(live_fetch, liveint);

function live_fetchRes() {
	if (http.readyState == 4) {
		if (http.status == 200) {
			if (http.responseText == '') {
				document.liveform.txtlog.value = '<?php _e('No traffic yet, please wait...') ?>' + "\n";
			} else if (http.responseText != '*') {
				if ( http.responseText.charAt(0) != '^' ) {
					document.liveform.txtlog.value = '<?php _e('Error: Live Log did not receive the expected response from your server:') ?>' + "\n\n" + http.responseText;
				} else {
					var line = http.responseText.substr(1);
					// Get number of lines :
					var res = line.split(/\n/).length - 1;
					// Work around for old IE bug :
					if (! res) { res = 1; }
					if (lines == 0) {
						document.liveform.txtlog.value = line;
					} else {
						document.liveform.txtlog.value += line;
					}
					lines += res;
					if (scroll) {
						document.getElementById("idtxtlog").scrollTop = document.getElementById("idtxtlog").scrollHeight;
					}
				}
			}
		} else if (http.status == 404) {
			document.liveform.txtlog.value += '<?php _e('Error: URL does not seem to exist: ') ?>' + ajaxURL + "\n";
		} else if (http.status == 503) {
			document.liveform.txtlog.value += '<?php _e('Error: cannot find your log file. Try to reload this page.') ?>' + "\n";
		} else {
			document.liveform.txtlog.value += '<?php _e('Error: the HTTP server returned the following error code: ') ?>' + http.status + "\n";
		}
		if (document.liveform.txtlog.value == '') {
			document.liveform.txtlog.value = '<?php _e('No traffic yet, please wait...') ?>' + "\n";
		}
		document.getElementById('loading').innerHTML = "<?php _e('Sleeping') ?> " + liveint/1000 + " <?php _e('seconds') ?>...";
		document.getElementById('radioon').style.background = 'green';
		document.getElementById('radiooff').disabled = false;
		return false;
   }
}
function on_off(onoff) {
	if (onoff == 1 && liveon != 1) {
		liveon = 1;
		live_fetch();
		if (scroll == 1) {
			document.getElementById("idtxtlog").scrollTop = document.getElementById("idtxtlog").scrollHeight;
		}
		document.getElementById("loading").innerHTML = "<?php _e('Sleeping') ?> " + liveint/1000 + " <?php _e('seconds') ?>...";
		document.getElementById("liveint").disabled = false;
		document.getElementById("livescroll").disabled = false;
		document.getElementById('radioon').style.background = 'green';
		document.getElementById('radioon').style.color = 'white';
		myinterval = setInterval(live_fetch, liveint);
	} else if (onoff != 1 && liveon == 1) {
		liveon = 0;
		lines = 0;
		document.getElementById("loading").innerHTML = "&nbsp;";
		document.getElementById("liveint").disabled = true;
		document.getElementById("livescroll").disabled = true;
		clearInterval(myinterval);
		document.getElementById('radioon').style.background = '';
		document.getElementById('radioon').style.color = '';
	}
}
function change_int(intv) {
	clearInterval(myinterval);
	liveint = intv;
	document.getElementById("loading").innerHTML = "<?php _e('Sleeping') ?> " + liveint/1000 + " <?php _e('seconds') ?>...";
	myinterval = setInterval(live_fetch, liveint);
}
function cls() {
	document.liveform.txtlog.value = '';
	livecls = 1;
	lines = 0;
}
function is_scroll() {
	if (document.liveform.livescroll.checked == true) {
		scroll = 1;
		if (liveon == 1) {
			document.getElementById("idtxtlog").scrollTop = document.getElementById("idtxtlog").scrollHeight;
		}
	} else {
		scroll = 0;
	}
}
</script>

<div class="wrap">
	<div style="width:54px;height:52px;background-image:url( <?php echo plugins_url() ?>/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2><?php _e('Live Log') ?></h2>
	<br />
<?php
if ( isset($_POST['lf']) ) {
	$res = nf_sub_liveloge_save();
	if ($res) {
		echo '<div class="error settings-error"><p><strong>' . $res . '</strong></p></div>';
	} else {
		echo '<div class="updated settings-error"><p><strong>Your changes have been saved.</strong></p></div>';
	}
}
$nfw_options = get_option('nfw_options');
?>
<form name="liveform">
	<table class="form-table">
		<tr>
			<td style="width:100%;text-align:center;">
				<span class="description" id="loading">&nbsp;</span><br />
				<textarea name="txtlog" id="idtxtlog" class="small-text code" style="width:100%;height:325px;" wrap="off"><?php _e('No traffic yet, please wait...'); echo "\n"; ?></textarea>
				<br />
				<center>
					<p>
					<label><input type="radio" name="liveon" value="1" onclick="on_off(1)" checked="checked"><font style="color:white;background-color:green;padding:3px;border-radius:15px;" id="radioon"><?php _e('On') ?></font></label>&nbsp;&nbsp;<label><input type="radio" name="liveon" value="0" onclick="on_off(0)" id="radiooff"><?php _e('Off') ?></label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('Refresh rate:') ?>
					<select name="liveint" id="liveint" onchange="change_int(this.value);">
						<option value="5000"><?php _e('5 seconds') ?></option>
						<option value="10000" selected="selected"><?php _e('10 seconds') ?></option>
						<option value="20000"><?php _e('20 seconds') ?></option>
						<option value="45000"><?php _e('45 seconds') ?></option>
					</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" class="button-secondary" name="livecls" value="Clear screen" onClick="cls()"/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label><input type="checkbox" name="livescroll" id="livescroll" value="1" onchange="is_scroll()" checked="checked"><?php _e('Autoscrolling') ?></label>
				</p>
				</center>
			</td>
		</tr>
	</table>
	<div align="right"><span class="description"><?php _e('Live Log will not include yourself or any other whitelisted users.') ?></span></div>
</form>
<?php
if ( empty($nfw_options['liveformat']) ) {
	$lf = 0;
	$liveformat = '';
} else {
	$lf = 1;
	$liveformat = htmlspecialchars($nfw_options['liveformat']);
}

if ( empty($nfw_options['liveport'])  || ! preg_match('/^[1-2]$/', $nfw_options['liveport']) ) {
	$liveport = 0;
} else {
	$liveport = $nfw_options['liveport'];
}
?>
<form method="post">
	<h3><?php _e('Live Log options') ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row"><?php _e('Format') ?></th>
			<td align="left">
				<p><label><input type="radio" name="lf" value="0"<?php checked($lf, 0) ?> onclick="document.getElementById('liveformat').disabled=true"><code>[%time] %name %client &quot;%method %uri&quot; &quot;%referrer&quot; &quot;%ua&quot; &quot;%forward&quot; &quot;%host&quot;</code></label></p>
				<p><label><input type="radio" name="lf" value="1"<?php checked($lf, 1) ?> onclick="document.getElementById('liveformat').disabled=false"><?php _e('Custom') ?> </label><input id="liveformat" type="text" class="regular-text" name="liveformat" value="<?php echo $liveformat ?>"<?php disabled($lf, 0) ?> autocomplete="off"></p>
				<span class="description"><?php _e('See contextual help for available log format.') ?></span>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('Display') ?></th>
			<td align="left">
				<select name="liveport">
					<option value="0"<?php selected($liveport, 0) ?>><?php _e('HTTP and HTTPS traffic (default)') ?></option>
					<option value="1"<?php selected($liveport, 1) ?>><?php _e('HTTP traffic only') ?></option>
					<option value="2"<?php selected($liveport, 2) ?>><?php _e('HTTPS traffic only') ?></option>
				</select>
			</td>
		</tr>
	</table>
	<p><input type="submit" class="button-primary" value="<?php _e('Save Live Log Options') ?>" /></p>
	<?php wp_nonce_field('livelog_save', 'nfwnonce', 0); ?>
</form>
</div>
<?php

/* ------------------------------------------------------------------ */
function nf_sub_liveloge_save() {

	if ( empty($_POST['nfwnonce']) || ! wp_verify_nonce($_POST['nfwnonce'], 'livelog_save') ) {
		wp_nonce_ays('livelog_save');
	}

	$nfw_options = get_option('nfw_options');

	if ( empty($_POST['lf']) ) {
		$nfw_options['liveformat'] = '';
	} else {
		if (! empty($_POST['liveformat']) ) {
			$tmp = stripslashes($_POST['liveformat']);
			// Keep only the allowed characters :
			$nfw_options['liveformat'] = preg_replace('`[^a-z%[\]\'"\x20]`', '', $tmp);
		}
		if (empty($_POST['liveformat']) ) {
			return __('Error: please enter the custom log format.');
		}
	}

	if ( empty($_POST['liveport'])  || ! preg_match('/^[1-2]$/', $_POST['liveport']) ) {
		$nfw_options['liveport'] = 0;
	} else {
		$nfw_options['liveport'] = $_POST['liveport'];
	}

	$nfw_options = update_option('nfw_options', $nfw_options);
}

/* ------------------------------------------------------------------ */
// EOF
