<?php
/*
 +---------------------------------------------------------------------+
 | NinjaFirewall (WP edition)                                          |
 |                                                                     |
 | (c) NinTechNet - http://nintechnet.com/                             |
 +---------------------------------------------------------------------+
 | REVISION: 2015-06-06 13:57:29                                       |
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

// Block immediately if user is not allowed :
nf_not_allowed( 'block', __LINE__ );

$nfw_options = get_option( 'nfw_options' );

echo '<script>
function ac_radio_toogle(on_off, rbutton) {
	var what = "nfw_options["+rbutton+"]";
	if (on_off) {
		document.nfwalerts.elements[what].disabled = false;
		document.nfwalerts.elements[what].focus();
	} else {
		document.nfwalerts.elements[what].disabled = true;
	}
}
</script>
<div class="wrap">
	<div style="width:54px;height:52px;background-image:url( ' . plugins_url() . '/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2>' . __('Event Notifications') . '</h2>
	<br />';

// Saved ?
if ( isset( $_POST['nfw_options']) ) {
	if ( empty($_POST['nfwnonce']) || ! wp_verify_nonce($_POST['nfwnonce'], 'events_save') ) {
		wp_nonce_ays('events_save');
	}
	nf_sub_event_save();
	echo '<div class="updated settings-error"><p><strong>' . __('Your changes have been saved.') . '</strong></p></div>';
	$nfw_options = get_option( 'nfw_options' );
}

if (! isset( $nfw_options['a_0'] ) ) {
	$nfw_options['a_0'] = 1;
}
?>
	<form method="post" name="nfwalerts">
	<?php wp_nonce_field('events_save', 'nfwnonce', 0); ?>
	<h3><?php _e('WordPress admin dashboard') ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row"><?php _e('Send me an alert whenever') ?></th>
			<td align="left">
			<p><label><input type="radio" name="nfw_options[a_0]" value="1"<?php checked( $nfw_options['a_0'], 1) ?>>&nbsp;<?php _e('An administrator logs in (default)') ?></label></p>
			<p><label><input type="radio" name="nfw_options[a_0]" value="2"<?php checked( $nfw_options['a_0'], 2) ?>>&nbsp;<?php _e('Someone - user, admin, editor, etc - logs in') ?></label></p>
			<p><label><input type="radio" name="nfw_options[a_0]" value="0"<?php checked( $nfw_options['a_0'], 0) ?>>&nbsp;<?php _e('No, thanks (not recommended)') ?></label></p>
			</td>
		</tr>
	</table>

	<br />

	<h3><?php _e('Plugins') ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row"><?php _e('Send me an alert whenever someone') ?></th>
			<td align="left">
			<p><label><input type="checkbox" name="nfw_options[a_11]" value="1"<?php checked( $nfw_options['a_11'], 1) ?>>&nbsp;<?php _e('Uploads a plugin (default)') ?></label></p>
			<p><label><input type="checkbox" name="nfw_options[a_12]" value="1"<?php checked( $nfw_options['a_12'], 1) ?>>&nbsp;<?php _e('Installs a plugin (default)') ?></label></p>
			<p><label><input type="checkbox" name="nfw_options[a_13]" value="1"<?php checked( $nfw_options['a_13'], 1) ?>>&nbsp;<?php _e('Activates a plugin') ?></label></p>
			<p><label><input type="checkbox" name="nfw_options[a_14]" value="1"<?php checked( $nfw_options['a_14'], 1) ?>>&nbsp;<?php _e('Updates a plugin') ?></label></p>
			<p><label><input type="checkbox" name="nfw_options[a_15]" value="1"<?php checked( $nfw_options['a_15'], 1) ?>>&nbsp;<?php _e('Deactivates a plugin (default)') ?></label></p>
			<p><label><input type="checkbox" name="nfw_options[a_16]" value="1"<?php checked( $nfw_options['a_16'], 1) ?>>&nbsp;<?php _e('Deletes a plugin') ?></label></p>
			</td>
		</tr>
	</table>

	<br />

	<h3><?php _e('Themes') ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row"><?php _e('Send me an alert whenever someone') ?></th>
			<td align="left">
			<p><label><input type="checkbox" name="nfw_options[a_21]" value="1"<?php checked( $nfw_options['a_21'], 1) ?>>&nbsp;<?php _e('Uploads a theme (default)') ?></label></p>
			<p><label><input type="checkbox" name="nfw_options[a_22]" value="1"<?php checked( $nfw_options['a_22'], 1) ?>>&nbsp;<?php _e('Installs a theme (default)') ?></label></p>
			<p><label><input type="checkbox" name="nfw_options[a_23]" value="1"<?php checked( $nfw_options['a_23'], 1) ?>>&nbsp;<?php _e('Activates a theme') ?></label></p>
			<p><label><input type="checkbox" name="nfw_options[a_24]" value="1"<?php checked( $nfw_options['a_24'], 1) ?>>&nbsp;<?php _e('Deletes a theme') ?></label></p>
			</td>
		</tr>
	</table>

	<br />

	<h3><?php _e('Core') ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row"><?php _e('Send me an alert whenever someone') ?></th>
			<td align="left">
			<p><label><input type="checkbox" name="nfw_options[a_31]" value="1"<?php checked( $nfw_options['a_31'], 1) ?>>&nbsp;<?php _e('Updates WordPress (default)') ?></label></p>
			</td>
		</tr>
	</table>

	<br />

	<?php
	if (! isset( $nfw_options['a_51']) ) {
		$nfw_options['a_51'] = 1;
	}
	?>
	<h3><?php _e('Database') ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row"><?php _e('Send me an alert whenever') ?></th>
			<td align="left">
				<p><label><input type="checkbox" name="nfw_options[a_51]" value="1"<?php checked( $nfw_options['a_51'], 1) ?>>&nbsp;<?php _e('An administrator account is created, modified or deleted in the database (default)') ?></label></p>
			</td>
		</tr>
	</table>

	<br />

	<h3><?php _e('Log') ?></h3>
	<table class="form-table">
		<tr>
			<th scope="row"><?php _e('Write all events to the firewall log') ?></th>
			<td align="left">
			<p><label><input type="checkbox" name="nfw_options[a_41]" value="1"<?php checked( $nfw_options['a_41'], 1) ?>>&nbsp;<?php _e('Yes (default)') ?></label></p>
			</td>
		</tr>
	</table>

	<br />

<?php
if (! is_multisite() ) {
?>
	<h3><?php _e('Contact email') ?></h3>
	<table class="form-table">
		<tr style="background-color:#F9F9F9;border: solid 1px #DFDFDF;">
			<th scope="row"><?php _e('Alerts should be sent to') ?></th>
			<td align="left">
			<input class="regular-text" type="text" name="nfw_options[alert_email]" size="45" maxlength="250" value="<?php
			if ( empty( $nfw_options['alert_email'])) {
				echo htmlspecialchars( get_option('admin_email') );
			} else {
				echo htmlspecialchars( $nfw_options['alert_email'] );
			}
			?>">
			<br /><span class="description">Multiple recipients must be comma-separated (e.g., <code>joe@example.org,alice@example.org</code>).</span>
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
	if ($nfw_options['alert_sa_only'] == 3) {
		$tmp_email = htmlspecialchars( $nfw_options['alert_email'] );
	} else {
		$tmp_email = '';
	}
?>
	<h3><?php _e('Contact email') ?></h3>
	<table class="form-table">
		<tr style="background-color:#F9F9F9;border: solid 1px #DFDFDF;">
			<th scope="row"><?php _e('Alerts should be sent to') ?></th>
			<td align="left">
			<p><label><input type="radio" name="nfw_options[alert_sa_only]" value="1"<?php checked( $nfw_options['alert_sa_only'], 1 ) ?> onclick="ac_radio_toogle(0,'alert_multirec');" />&nbsp;<?php _e('Only to me, the Super Admin') ?> (<?php echo htmlspecialchars(get_option('admin_email')); ?>)</label></p>
			<p><label><input type="radio" name="nfw_options[alert_sa_only]" value="2"<?php checked( $nfw_options['alert_sa_only'], 2) ?> onclick="ac_radio_toogle(0,'alert_multirec');" />&nbsp;<?php _e('To the administrator of the site where originated the alert (default)') ?></label></p>
			<p><label><input type="radio" name="nfw_options[alert_sa_only]" value="3"<?php checked( $nfw_options['alert_sa_only'], 3) ?> onclick="ac_radio_toogle(1,'alert_multirec');" />&nbsp;<?php _e('Other(s)') ?>: </label><input class="regular-text" type="text" name="nfw_options[alert_multirec]" size="45" maxlength="250" value="<?php echo $tmp_email ?>" <?php disabled($tmp_email, '') ?>></p>
			<span class="description">Multiple recipients must be comma-separated (e.g., <code>joe@example.org,alice@example.org</code>).</span>
			<input type="hidden" name="nfw_options[alert_email]" value="<?php echo htmlspecialchars(get_option('admin_email')); ?>">
			</td>
		</tr>
	</table>
<?php
}
?>

	<br />
	<br />
	<input class="button-primary" type="submit" name="Save" value="<?php _e('Save Event Notifications') ?>" />

	</form>

</div>
<?php

/* ------------------------------------------------------------------ */

function nf_sub_event_save() {

	// Save Event Notifications :

	// Block immediately if user is not allowed :
	nf_not_allowed( 'block', __LINE__ );

	$nfw_options = get_option( 'nfw_options' );

	if (! preg_match('/^[012]$/', $_POST['nfw_options']['a_0']) ) {
		$nfw_options['a_0'] = 1;
	} else {
		$nfw_options['a_0'] = $_POST['nfw_options']['a_0'];
	}

	if (! preg_match('/^[123]$/', $_POST['nfw_options']['alert_sa_only']) ) {
		$nfw_options['alert_sa_only'] = 2;
	} else {
		$nfw_options['alert_sa_only'] = $_POST['nfw_options']['alert_sa_only'];
	}

	if ( empty( $_POST['nfw_options']['a_11']) ) {
		$nfw_options['a_11'] = 0;
	} else {
		$nfw_options['a_11'] = 1;
	}
	if ( empty( $_POST['nfw_options']['a_12']) ) {
		$nfw_options['a_12'] = 0;
	} else {
		$nfw_options['a_12'] = 1;
	}
	if ( empty( $_POST['nfw_options']['a_13']) ) {
		$nfw_options['a_13'] = 0;
	} else {
		$nfw_options['a_13'] = 1;
	}
	if ( empty( $_POST['nfw_options']['a_14']) ) {
		$nfw_options['a_14'] = 0;
	} else {
		$nfw_options['a_14'] = 1;
	}
	if ( empty( $_POST['nfw_options']['a_15']) ) {
		$nfw_options['a_15'] = 0;
	} else {
		$nfw_options['a_15'] = 1;
	}
	if ( empty( $_POST['nfw_options']['a_16']) ) {
		$nfw_options['a_16'] = 0;
	} else {
		$nfw_options['a_16'] = 1;
	}

	if ( empty( $_POST['nfw_options']['a_21']) ) {
		$nfw_options['a_21'] = 0;
	} else {
		$nfw_options['a_21'] = 1;
	}
	if ( empty( $_POST['nfw_options']['a_22']) ) {
		$nfw_options['a_22'] = 0;
	} else {
		$nfw_options['a_22'] = 1;
	}
	if ( empty( $_POST['nfw_options']['a_23']) ) {
		$nfw_options['a_23'] = 0;
	} else {
		$nfw_options['a_23'] = 1;
	}
	if ( empty( $_POST['nfw_options']['a_24']) ) {
		$nfw_options['a_24'] = 0;
	} else {
		$nfw_options['a_24'] = 1;
	}

	if ( empty( $_POST['nfw_options']['a_31']) ) {
		$nfw_options['a_31'] = 0;
	} else {
		$nfw_options['a_31'] = 1;
	}

	if ( empty( $_POST['nfw_options']['a_41']) ) {
		$nfw_options['a_41'] = 0;
	} else {
		$nfw_options['a_41'] = 1;
	}

	if ( empty( $_POST['nfw_options']['a_51']) ) {
		$nfw_options['a_51'] = 0;
	} else {
		$nfw_options['a_51'] = 1;
	}

	// Multiple recipients (WPMU only) ?
	if (! empty( $_POST['nfw_options']['alert_multirec']) ) {
		$_POST['nfw_options']['alert_email'] = $_POST['nfw_options']['alert_multirec'];
	}

	if (! empty( $_POST['nfw_options']['alert_email']) ) {
		$nfw_options['alert_email'] = '';
		$tmp_email = explode(',', preg_replace('/\s/', '', $_POST['nfw_options']['alert_email']) );
		foreach ($tmp_email as $notif_email) {
			$nfw_options['alert_email'] .= sanitize_email($notif_email) . ', ';
		}
		$nfw_options['alert_email'] = rtrim($nfw_options['alert_email'], ', ' );
	}
	if ( empty( $nfw_options['alert_email'] ) ) {
		$nfw_options['alert_email'] = get_option('admin_email');
	}

	// Update options :
	update_option( 'nfw_options', $nfw_options );

}

/* ------------------------------------------------------------------ */
// EOF
