<?php
/*
 +---------------------------------------------------------------------+
 | NinjaFirewall (WP edition)                                          |
 |                                                                     |
 | (c) NinTechNet - http://nintechnet.com/                             |
 +---------------------------------------------------------------------+
 | REVISION: 2015-07-31 19:39:44                                       |
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

if (! defined('NF_DISABLED') ) {
	is_nfw_enabled();
}
if (NF_DISABLED) {
	$err_msg = __('Error: NinjaFirewall must be enabled and working in order to use the Live Log feature.', 'ninjafirewall');
}
if ( empty($_SESSION['nfw_goodguy']) ) {
	$err_msg = __('Error: You must be whitelisted in order to use that feature: click on the <a href="?page=nfsubpolicies">Firewall Policies</a> menu and ensure that the "Do not block WordPress administrator" option is enabled.', 'ninjafirewall');
}
if (! empty($err_msg) ) {
	?>
	<div class="wrap">
	<div style="width:54px;height:52px;background-image:url( <?php echo plugins_url() ?>/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2><?php _e('Live Log', 'ninjafirewall') ?></h2>
	<br />
	<div class="error notice is-dismissible"><p><?php echo $err_msg ?></p></div>
	</div>
	<?php
	return;
}

// Create an empty log :
$fh = fopen( NFW_LOG_DIR . '/nfwlog/cache/livelog.php', 'w');
fclose($fh);
$_SESSION['nfw_livelog'] = 1;

// jQuery ? No, thanks :
?>
<script>
var count = 0;
var lines = 0;
var liveon = 1;
<?php
if (! isset($_COOKIE['nfwscroll']) || ! empty($_COOKIE['nfwscroll']) ) {
	// Default
	echo 'var scroll = 1;';
	$nfwscroll = 1;
} else {
	echo 'var scroll = 0;';
	$nfwscroll = 0;
}
if ( isset($_COOKIE['nfwintval']) && preg_match('/^(5|10|20|45)000$/', $_COOKIE['nfwintval']) ) {
	echo "var liveint = {$_COOKIE['nfwintval']};";
	$nfwintval = $_COOKIE['nfwintval'];
} else {
	echo 'var liveint = 10000;';
	$nfwintval = 10000;
}
?>
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
		document.getElementById("loading").innerHTML = "<?php
		// translators: quotes ('") must be escaped
		_e('Loading...', 'ninjafirewall') ?>";
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
				document.liveform.txtlog.value = '<?php
				// translators: quotes ('") must be escaped
				_e('No traffic yet, please wait...', 'ninjafirewall') ?>' + "\n";
			} else if (http.responseText != '*') {
				if ( http.responseText.charAt(0) != '^' ) {
					document.liveform.txtlog.value = '<?php
					// translators: quotes ('") must be escaped
					_e('Error: Live Log did not receive the expected response from your server:', 'ninjafirewall') ?>' + "\n\n" + http.responseText;
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
			document.liveform.txtlog.value += '<?php
			// translators: quotes ('") must be escaped
			_e('Error: URL does not seem to exist:', 'ninjafirewall') ?> ' + ajaxURL + "\n";
		} else if (http.status == 503) {
			document.liveform.txtlog.value += '<?php
			// translators: quotes ('") must be escaped
			_e('Error: cannot find your log file. Try to reload this page.', 'ninjafirewall') ?>' + "\n";
		} else {
			document.liveform.txtlog.value += '<?php
			// translators: quotes ('") must be escaped
			_e('Error: the HTTP server returned the following error code:', 'ninjafirewall') ?> ' + http.status + "\n";
		}
		if (document.liveform.txtlog.value == '') {
			document.liveform.txtlog.value = '<?php
			// translators: quotes ('") must be escaped
			_e('No traffic yet, please wait...', 'ninjafirewall') ?>' + "\n";
		}
		document.getElementById('loading').innerHTML = "<?php _e('Sleeping', 'ninjafirewall') ?> " + liveint/1000 + " <?php _e('seconds', 'ninjafirewall') ?>...";
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
		document.getElementById("loading").innerHTML = "<?php
		// translators: quotes ('") must be escaped
		_e('Sleeping', 'ninjafirewall') ?> " + liveint/1000 + " <?php
		// translators: quotes ('") must be escaped
		_e('seconds', 'ninjafirewall') ?>...";
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
	document.getElementById("loading").innerHTML = "<?php
	// translators: quotes ('") must be escaped
	_e('Sleeping', 'ninjafirewall') ?> " + liveint/1000 + " <?php
	// translators: quotes ('") must be escaped
	_e('seconds', 'ninjafirewall') ?>...";
	myinterval = setInterval(live_fetch, liveint);
	// Add cookie so that we remember the user choice for 365 days:
	create_cookie('nfwintval', intv);
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
	// Add cookie so that we remember the user choice for 365 days:
	create_cookie('nfwscroll', scroll);
}
function create_cookie(name, value) {
	// Add cookie so that we remember the user choice for 365 days:
	var d = new Date();
	d.setTime(d.getTime() + ( 365 * 24 * 60 * 60 * 1000) );
	var expires = "expires=" + d.toUTCString();
	document.cookie = name +'=' + value + "; " + expires;
}
</script>

<div class="wrap">
	<div style="width:54px;height:52px;background-image:url( <?php echo plugins_url() ?>/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2><?php _e('Live Log', 'ninjafirewall') ?></h2>
	<br />
<?php
if ( isset($_POST['lf']) ) {
	$res = nf_sub_liveloge_save();
	if ($res) {
		echo '<div class="error notice is-dismissible"><p>' . $res . '</p></div>';
	} else {
		echo '<div class="updated notice is-dismissible"><p>'. __('Your changes have been saved.', 'ninjafirewall') .'</p></div>';
	}
}
$nfw_options = get_option('nfw_options');
?>
<form name="liveform">
	<table class="form-table">
		<tr>
			<td style="width:100%;text-align:center;">
				<span class="description" id="loading">&nbsp;</span><br />
				<textarea name="txtlog" id="idtxtlog" class="small-text code" style="width:100%;height:325px;" wrap="off"><?php _e('No traffic yet, please wait...', 'ninjafirewall'); echo "\n"; ?></textarea>
				<br />
				<center>
					<p>
					<label><input type="radio" name="liveon" value="1" onclick="on_off(1)" checked="checked"><font style="color:white;background-color:green;padding:3px;border-radius:15px;" id="radioon"><?php _e('On', 'ninjafirewall') ?></font></label>&nbsp;&nbsp;<label><input type="radio" name="liveon" value="0" onclick="on_off(0)" id="radiooff"><?php _e('Off', 'ninjafirewall') ?></label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('Refresh rate:', 'ninjafirewall') ?>
					<select name="liveint" id="liveint" onchange="change_int(this.value);">
						<option value="5000"<?php selected($nfwintval, 5000) ?>><?php _e('5 seconds', 'ninjafirewall') ?></option>
						<option value="10000"<?php selected($nfwintval, 10000) ?>><?php _e('10 seconds', 'ninjafirewall') ?></option>
						<option value="20000"<?php selected($nfwintval, 20000) ?>><?php _e('20 seconds', 'ninjafirewall') ?></option>
						<option value="45000"<?php selected($nfwintval, 45000) ?>><?php _e('45 seconds', 'ninjafirewall') ?></option>
					</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" class="button-secondary" name="livecls" value="<?php _e('Clear screen', 'ninjafirewall') ?>" onClick="cls()"/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label><input type="checkbox" name="livescroll" id="livescroll" value="1" onchange="is_scroll()" <?php checked($nfwscroll, 1)?>><?php _e('Autoscrolling', 'ninjafirewall') ?></label>
				</p>
				</center>
			</td>
		</tr>
	</table>
	<div align="right"><span class="description"><?php _e('Live Log will not include yourself or any other whitelisted users.', 'ninjafirewall') ?></span></div>
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
if ( empty($nfw_options['livetz']) || preg_match('/[^\w\/]/', $nfw_options['livetz']) ) {
	$livetz = 'UTC';
} else {
	$livetz = $nfw_options['livetz'];
}
?>
<form method="post">
	<h3><?php _e('Live Log options', 'ninjafirewall') ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row"><?php _e('Format', 'ninjafirewall') ?></th>
			<td align="left">
				<p><label><input type="radio" name="lf" value="0"<?php checked($lf, 0) ?> onclick="document.getElementById('liveformat').disabled=true"><code>[%time] %name %client &quot;%method %uri&quot; &quot;%referrer&quot; &quot;%ua&quot; &quot;%forward&quot; &quot;%host&quot;</code></label></p>
				<p><label><input type="radio" name="lf" value="1"<?php checked($lf, 1) ?> onclick="document.getElementById('liveformat').disabled=false"><?php _e('Custom', 'ninjafirewall') ?> </label><input id="liveformat" type="text" class="regular-text" name="liveformat" value="<?php echo $liveformat ?>"<?php disabled($lf, 0) ?> autocomplete="off"></p>
				<span class="description"><?php _e('See contextual help for available log format.', 'ninjafirewall') ?></span>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('Display', 'ninjafirewall') ?></th>
			<td align="left">
				<select name="liveport">
					<option value="0"<?php selected($liveport, 0) ?>><?php _e('HTTP and HTTPS traffic (default)', 'ninjafirewall') ?></option>
					<option value="1"<?php selected($liveport, 1) ?>><?php _e('HTTP traffic only', 'ninjafirewall') ?></option>
					<option value="2"<?php selected($liveport, 2) ?>><?php _e('HTTPS traffic only', 'ninjafirewall') ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('Timezone', 'ninjafirewall') ?></th>
			<td align="left">
				<select name="livetz">
				<?php
				$timezone_choice = nfw_timezone_choice();
				foreach ($timezone_choice as $tz_place) {
					echo '<option value ="' . htmlentities( $tz_place ) . '"';
					if ($livetz == $tz_place) { echo ' selected'; }
					echo '>'. htmlentities( $tz_place ) .'</option>';
				}
				?>
				</select>
			</td>
		</tr>
	</table>
	<p><input type="submit" class="button-primary" value="<?php _e('Save Live Log Options', 'ninjafirewall') ?>" /></p>
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
			$nfw_options['liveformat'] = preg_replace('`[^a-z%[\]"\x20]`', '', $tmp);
		}
		if (empty($_POST['liveformat']) ) {
			return __('Error: please enter the custom log format.', 'ninjafirewall');
		}
	}

	if ( empty($_POST['liveport'])  || ! preg_match('/^[1-2]$/', $_POST['liveport']) ) {
		$nfw_options['liveport'] = 0;
	} else {
		$nfw_options['liveport'] = $_POST['liveport'];
	}

	if ( empty($_POST['livetz'])  || preg_match('/[^\w\/]/', $_POST['livetz']) ) {
		$nfw_options['livetz'] = 0;
	} else {
		$nfw_options['livetz'] = $_POST['livetz'];
	}

	$nfw_options = update_option('nfw_options', $nfw_options);
}

/* ------------------------------------------------------------------ */

function nfw_timezone_choice() {
	return array('UTC', 'Africa/Abidjan', 'Africa/Accra', 'Africa/Addis_Ababa', 'Africa/Algiers', 'Africa/Asmara', 'Africa/Asmera', 'Africa/Bamako', 'Africa/Bangui', 'Africa/Banjul', 'Africa/Bissau', 'Africa/Blantyre', 'Africa/Brazzaville', 'Africa/Bujumbura', 'Africa/Cairo', 'Africa/Casablanca', 'Africa/Ceuta', 'Africa/Conakry', 'Africa/Dakar', 'Africa/Dar_es_Salaam', 'Africa/Djibouti', 'Africa/Douala', 'Africa/El_Aaiun', 'Africa/Freetown', 'Africa/Gaborone', 'Africa/Harare', 'Africa/Johannesburg', 'Africa/Kampala', 'Africa/Khartoum', 'Africa/Kigali', 'Africa/Kinshasa', 'Africa/Lagos', 'Africa/Libreville', 'Africa/Lome', 'Africa/Luanda', 'Africa/Lubumbashi', 'Africa/Lusaka', 'Africa/Malabo', 'Africa/Maputo', 'Africa/Maseru', 'Africa/Mbabane', 'Africa/Mogadishu', 'Africa/Monrovia', 'Africa/Nairobi', 'Africa/Ndjamena', 'Africa/Niamey', 'Africa/Nouakchott', 'Africa/Ouagadougou', 'Africa/Porto-Novo', 'Africa/Sao_Tome', 'Africa/Timbuktu', 'Africa/Tripoli', 'Africa/Tunis', 'Africa/Windhoek', 'America/Adak', 'America/Anchorage', 'America/Anguilla', 'America/Antigua', 'America/Araguaina', 'America/Argentina/Buenos_Aires', 'America/Argentina/Catamarca', 'America/Argentina/ComodRivadavia', 'America/Argentina/Cordoba', 'America/Argentina/Jujuy', 'America/Argentina/La_Rioja', 'America/Argentina/Mendoza', 'America/Argentina/Rio_Gallegos', 'America/Argentina/Salta', 'America/Argentina/San_Juan', 'America/Argentina/San_Luis', 'America/Argentina/Tucuman', 'America/Argentina/Ushuaia', 'America/Aruba', 'America/Asuncion', 'America/Atikokan', 'America/Atka', 'America/Bahia', 'America/Barbados', 'America/Belem', 'America/Belize', 'America/Blanc-Sablon', 'America/Boa_Vista', 'America/Bogota', 'America/Boise', 'America/Buenos_Aires', 'America/Cambridge_Bay', 'America/Campo_Grande', 'America/Cancun', 'America/Caracas', 'America/Catamarca', 'America/Cayenne', 'America/Cayman', 'America/Chicago', 'America/Chihuahua', 'America/Coral_Harbour', 'America/Cordoba', 'America/Costa_Rica', 'America/Cuiaba', 'America/Curacao', 'America/Danmarkshavn', 'America/Dawson', 'America/Dawson_Creek', 'America/Denver', 'America/Detroit', 'America/Dominica', 'America/Edmonton', 'America/Eirunepe', 'America/El_Salvador', 'America/Ensenada', 'America/Fort_Wayne', 'America/Fortaleza', 'America/Glace_Bay', 'America/Godthab', 'America/Goose_Bay', 'America/Grand_Turk', 'America/Grenada', 'America/Guadeloupe', 'America/Guatemala', 'America/Guayaquil', 'America/Guyana', 'America/Halifax', 'America/Havana', 'America/Hermosillo', 'America/Indiana/Indianapolis', 'America/Indiana/Knox', 'America/Indiana/Marengo', 'America/Indiana/Petersburg', 'America/Indiana/Tell_City', 'America/Indiana/Vevay', 'America/Indiana/Vincennes', 'America/Indiana/Winamac', 'America/Indianapolis', 'America/Inuvik', 'America/Iqaluit', 'America/Jamaica', 'America/Jujuy', 'America/Juneau', 'America/Kentucky/Louisville', 'America/Kentucky/Monticello', 'America/Knox_IN', 'America/La_Paz', 'America/Lima', 'America/Los_Angeles', 'America/Louisville', 'America/Maceio', 'America/Managua', 'America/Manaus', 'America/Marigot', 'America/Martinique', 'America/Matamoros', 'America/Mazatlan', 'America/Mendoza', 'America/Menominee', 'America/Merida', 'America/Mexico_City', 'America/Miquelon', 'America/Moncton', 'America/Monterrey', 'America/Montevideo', 'America/Montreal', 'America/Montserrat', 'America/Nassau', 'America/New_York', 'America/Nipigon', 'America/Nome', 'America/Noronha', 'America/North_Dakota/Center', 'America/North_Dakota/New_Salem', 'America/Ojinaga', 'America/Panama', 'America/Pangnirtung', 'America/Paramaribo', 'America/Phoenix', 'America/Port-au-Prince', 'America/Port_of_Spain', 'America/Porto_Acre', 'America/Porto_Velho', 'America/Puerto_Rico', 'America/Rainy_River', 'America/Rankin_Inlet', 'America/Recife', 'America/Regina', 'America/Resolute', 'America/Rio_Branco', 'America/Rosario', 'America/Santa_Isabel', 'America/Santarem', 'America/Santiago', 'America/Santo_Domingo', 'America/Sao_Paulo', 'America/Scoresbysund', 'America/Shiprock', 'America/St_Barthelemy', 'America/St_Johns', 'America/St_Kitts', 'America/St_Lucia', 'America/St_Thomas', 'America/St_Vincent', 'America/Swift_Current', 'America/Tegucigalpa', 'America/Thule', 'America/Thunder_Bay', 'America/Tijuana', 'America/Toronto', 'America/Tortola', 'America/Vancouver', 'America/Virgin', 'America/Whitehorse', 'America/Winnipeg', 'America/Yakutat', 'America/Yellowknife', 'Arctic/Longyearbyen', 'Asia/Aden', 'Asia/Almaty', 'Asia/Amman', 'Asia/Anadyr', 'Asia/Aqtau', 'Asia/Aqtobe', 'Asia/Ashgabat', 'Asia/Ashkhabad', 'Asia/Baghdad', 'Asia/Bahrain', 'Asia/Baku', 'Asia/Bangkok', 'Asia/Beirut', 'Asia/Bishkek', 'Asia/Brunei', 'Asia/Calcutta', 'Asia/Choibalsan', 'Asia/Chongqing', 'Asia/Chungking', 'Asia/Colombo', 'Asia/Dacca', 'Asia/Damascus', 'Asia/Dhaka', 'Asia/Dili', 'Asia/Dubai', 'Asia/Dushanbe', 'Asia/Gaza', 'Asia/Harbin', 'Asia/Ho_Chi_Minh', 'Asia/Hong_Kong', 'Asia/Hovd', 'Asia/Irkutsk', 'Asia/Istanbul', 'Asia/Jakarta', 'Asia/Jayapura', 'Asia/Jerusalem', 'Asia/Kabul', 'Asia/Kamchatka', 'Asia/Karachi', 'Asia/Kashgar', 'Asia/Kathmandu', 'Asia/Katmandu', 'Asia/Kolkata', 'Asia/Krasnoyarsk', 'Asia/Kuala_Lumpur', 'Asia/Kuching', 'Asia/Kuwait', 'Asia/Macao', 'Asia/Macau', 'Asia/Magadan', 'Asia/Makassar', 'Asia/Manila', 'Asia/Muscat', 'Asia/Nicosia', 'Asia/Novokuznetsk', 'Asia/Novosibirsk', 'Asia/Omsk', 'Asia/Oral', 'Asia/Phnom_Penh', 'Asia/Pontianak', 'Asia/Pyongyang', 'Asia/Qatar', 'Asia/Qyzylorda', 'Asia/Rangoon', 'Asia/Riyadh', 'Asia/Saigon', 'Asia/Sakhalin', 'Asia/Samarkand', 'Asia/Seoul', 'Asia/Shanghai', 'Asia/Singapore', 'Asia/Taipei', 'Asia/Tashkent', 'Asia/Tbilisi', 'Asia/Tehran', 'Asia/Tel_Aviv', 'Asia/Thimbu', 'Asia/Thimphu', 'Asia/Tokyo', 'Asia/Ujung_Pandang', 'Asia/Ulaanbaatar', 'Asia/Ulan_Bator', 'Asia/Urumqi', 'Asia/Vientiane', 'Asia/Vladivostok', 'Asia/Yakutsk', 'Asia/Yekaterinburg', 'Asia/Yerevan', 'Atlantic/Azores', 'Atlantic/Bermuda', 'Atlantic/Canary', 'Atlantic/Cape_Verde', 'Atlantic/Faeroe', 'Atlantic/Faroe', 'Atlantic/Jan_Mayen', 'Atlantic/Madeira', 'Atlantic/Reykjavik', 'Atlantic/South_Georgia', 'Atlantic/St_Helena', 'Atlantic/Stanley', 'Australia/ACT', 'Australia/Adelaide', 'Australia/Brisbane', 'Australia/Broken_Hill', 'Australia/Canberra', 'Australia/Currie', 'Australia/Darwin', 'Australia/Eucla', 'Australia/Hobart', 'Australia/LHI', 'Australia/Lindeman', 'Australia/Lord_Howe', 'Australia/Melbourne', 'Australia/NSW', 'Australia/North', 'Australia/Perth', 'Australia/Queensland', 'Australia/South', 'Australia/Sydney', 'Australia/Tasmania', 'Australia/Victoria', 'Australia/West', 'Australia/Yancowinna', 'Europe/Amsterdam', 'Europe/Andorra', 'Europe/Athens', 'Europe/Belfast', 'Europe/Belgrade', 'Europe/Berlin', 'Europe/Bratislava', 'Europe/Brussels', 'Europe/Bucharest', 'Europe/Budapest', 'Europe/Chisinau', 'Europe/Copenhagen', 'Europe/Dublin', 'Europe/Gibraltar', 'Europe/Guernsey', 'Europe/Helsinki', 'Europe/Isle_of_Man', 'Europe/Istanbul', 'Europe/Jersey', 'Europe/Kaliningrad', 'Europe/Kiev', 'Europe/Lisbon', 'Europe/Ljubljana', 'Europe/London', 'Europe/Luxembourg', 'Europe/Madrid', 'Europe/Malta', 'Europe/Mariehamn', 'Europe/Minsk', 'Europe/Monaco', 'Europe/Moscow', 'Europe/Nicosia', 'Europe/Oslo', 'Europe/Paris', 'Europe/Podgorica', 'Europe/Prague', 'Europe/Riga', 'Europe/Rome', 'Europe/Samara', 'Europe/San_Marino', 'Europe/Sarajevo', 'Europe/Simferopol', 'Europe/Skopje', 'Europe/Sofia', 'Europe/Stockholm', 'Europe/Tallinn', 'Europe/Tirane', 'Europe/Tiraspol', 'Europe/Uzhgorod', 'Europe/Vaduz', 'Europe/Vatican', 'Europe/Vienna', 'Europe/Vilnius', 'Europe/Volgograd', 'Europe/Warsaw', 'Europe/Zagreb', 'Europe/Zaporozhye', 'Europe/Zurich', 'Indian/Antananarivo', 'Indian/Chagos', 'Indian/Christmas', 'Indian/Cocos', 'Indian/Comoro', 'Indian/Kerguelen', 'Indian/Mahe', 'Indian/Maldives', 'Indian/Mauritius', 'Indian/Mayotte', 'Indian/Reunion', 'Pacific/Apia', 'Pacific/Auckland', 'Pacific/Chatham', 'Pacific/Easter', 'Pacific/Efate', 'Pacific/Enderbury', 'Pacific/Fakaofo', 'Pacific/Fiji', 'Pacific/Funafuti', 'Pacific/Galapagos', 'Pacific/Gambier', 'Pacific/Guadalcanal', 'Pacific/Guam', 'Pacific/Honolulu', 'Pacific/Johnston', 'Pacific/Kiritimati', 'Pacific/Kosrae', 'Pacific/Kwajalein', 'Pacific/Majuro', 'Pacific/Marquesas', 'Pacific/Midway', 'Pacific/Nauru', 'Pacific/Niue', 'Pacific/Norfolk', 'Pacific/Noumea', 'Pacific/Pago_Pago', 'Pacific/Palau', 'Pacific/Pitcairn', 'Pacific/Ponape', 'Pacific/Port_Moresby', 'Pacific/Rarotonga', 'Pacific/Saipan', 'Pacific/Samoa', 'Pacific/Tahiti', 'Pacific/Tarawa', 'Pacific/Tongatapu', 'Pacific/Truk', 'Pacific/Wake', 'Pacific/Wallis', 'Pacific/Yap');
}

/* ------------------------------------------------------------------ */
// EOF
