<?php
/*
 +---------------------------------------------------------------------+
 | NinjaFirewall (WP edition)                                          |
 |                                                                     |
 | (c) NinTechNet - http://nintechnet.com/                             |
 +---------------------------------------------------------------------+
 | REVISION: 2015-04-16 21:59:04                                       |
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

$update_log = NFW_LOG_DIR . '/nfwlog/updates.php';
$update_url = array(
	'http://plugins.svn.wordpress.org/ninjafirewall/trunk/updates/',
	'version.php',
	'rules.php'
);

// Scheduled updates ?
if (defined('NFUPDATESDO') ) {
	nf_sub_do_updates($update_url, $update_log);
	return;
}

// Block immediately if user is not allowed :
nf_not_allowed( 'block', __LINE__ );

echo '<div class="wrap">
	<div style="width:54px;height:52px;background-image:url( ' . plugins_url() . '/ninjafirewall/images/ninjafirewall_50.png);background-repeat:no-repeat;background-position:0 0;margin:7px 5px 0 0;float:left;"></div>
	<h2>' . __('Updates') . '</h2>
	<br />';

//Saved options ?
if (! empty($_POST['nfw_act']) ) {
	if ( empty($_POST['nfwnonce']) || ! wp_verify_nonce($_POST['nfwnonce'], 'updates_save') ) {
		wp_nonce_ays('updates_save');
	}
	if ($_POST['nfw_act'] == 1) {
		nf_sub_updates_save();
	} elseif ($_POST['nfw_act'] == 2) {
		nf_sub_updates_clearlog($update_log);
	}
	echo '<div class="updated settings-error"><p><strong>' . __('Your changes have been saved.') . '</strong></p></div>';
}

$nfw_options = get_option('nfw_options');

if ( empty($nfw_options['enable_updates']) ) {
	$enable_updates = 0;
} else {
	$enable_updates = 1;
}
if ( empty($nfw_options['sched_updates']) || ! preg_match('/^[2-3]$/', $nfw_options['sched_updates']) ) {
	$sched_updates = 1;
} else {
	$sched_updates = $nfw_options['sched_updates'];
}
if ( empty($nfw_options['notify_updates']) && isset($nfw_options['notify_updates']) ) {
	$notify_updates = 0;
} else {
	// Defaut if not set yet:
	$notify_updates = 1;
}
?>

<script type="text/javascript">
function toogle_table(off) {
	if ( off == 1 ) {
		document.getElementById('upd_table').style.display = '';
	} else if ( off == 2 ) {
		document.getElementById('upd_table').style.display = 'none';
	}
	return;
}
</script>
<br />
<form method="post">
	<?php wp_nonce_field('updates_save', 'nfwnonce', 0); ?>
	<table class="form-table">
		<tr style="background-color:#F9F9F9;border: solid 1px #DFDFDF;">
			<th scope="row"><?php _e('Automatically update NinjaFirewall security rules') ?></th>
			<td align="left">
			<label><input type="radio" name="enable_updates" value="1"<?php checked($enable_updates, 1) ?> onclick="toogle_table(1);">&nbsp;<?php _e('Yes') ?></label>
			</td>
			<td align="left">
			<label><input type="radio" name="enable_updates" value="0"<?php checked($enable_updates, 0) ?> onclick="toogle_table(2);">&nbsp;<?php _e('No (default)') ?></label>
			</td>
		</tr>
	</table>

	<?php
	// If WP cron is disabled, we simply warn the user :
	if ( defined('DISABLE_WP_CRON') ) {
	?>
		<p><img src="<?php echo plugins_url() ?>/ninjafirewall/images/icon_warn_16.png" height="16" border="0" width="16">&nbsp;<span class="description"><?php printf( __('It seems that %s is enabled. Ensure you have another way to run WP-Cron, otherwise NinjaFirewall automatic updates will not work.'), '<code>DISABLE_WP_CRON</code>' ) ?></span></p>
	<?php
	}
	?>

	<table class="form-table" id="upd_table"<?php echo $enable_updates == 1 ? '' : ' style="display:none"' ?>>
		<tr>
			<th scope="row"><?php _e('Check for updates') ?></th>
				<td align="left">
					<p><label><input type="radio" name="sched_updates" value="1"<?php checked($sched_updates, 1) ?> /><?php _e('Hourly') ?></label></p>
					<p><label><input type="radio" name="sched_updates" value="2"<?php checked($sched_updates, 2) ?> /><?php _e('Twicedaily') ?></label></p>
					<p><label><input type="radio" name="sched_updates" value="3"<?php checked($sched_updates, 3) ?> /><?php _e('Daily') ?></label></p>
					<?php
					if ( $nextcron = wp_next_scheduled('nfsecupdates') ) {
						$sched = new DateTime( date('M d, Y H:i:s', $nextcron) );
						$now = new DateTime( date('M d, Y H:i:s', time() ) );
						$diff = $now->diff($sched);
					?>
						<p><span class="description"><?php printf( __('Next scheduled update will start in approximately %s day, %s hour(s), %s minute(s) and %s seconds.'), $diff->format('%a') % 7, $diff->format('%h'), $diff->format('%i'), $diff->format('%s') ) ?></span></p>
					<?php
						// Ensure that the scheduled scan time is in the future,
						// not in the past, otherwise send a warning because wp-cron
						// is obviously not working as expected :
						if ( $nextcron < time() ) {
						?>
							<p><img src="<?php echo plugins_url() ?>/ninjafirewall/images/icon_warn_16.png" height="16" border="0" width="16">&nbsp;<span class="description"><?php _e('The next scheduled date is in the past! WordPress wp-cron may not be working or may have been disabled.'); ?></span>
						<?php
						}
					}
					?>
				</td>
			</tr>
		<tr>
			<th scope="row"><?php _e('Notification') ?></th>
			<td align="left">
				<p><label><input type="checkbox" name="notify_updates" value="1"<?php checked($notify_updates, 1) ?> /><?php _e('Send me a report by email when security rules have been updated.') ?></label></p>
				<span class="description"><?php _e('Reports will be sent to the contact email address defined in the Event Notifications menu.') ?></span>
			</td>
		</tr>
	</table>

	<input type="hidden" name="nfw_act" value="1" />
	<p><input type="submit" class="button-primary" value="<?php _e('Save Updates Options') ?>" /></p>
	</form>

	<?php
	if (! empty($nfw_options['enable_updates']) ) {
		if ( file_exists($update_log) ) {
			$log_data = file_get_contents($update_log);
		} else {
			$log_data = __('The updates log is currently empty.');
		}
	?>
	<br />
	<form method="post">
		<?php wp_nonce_field('updates_save', 'nfwnonce', 0); ?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php _e('Updates Log') ?></th>
				<td align="left">
					<textarea class="small-text code" style="width:100%;height:150px;" wrap="off"><?php
						echo htmlentities($log_data); ?></textarea>
						<p>
						<?php
						echo '<input type="submit" name="clear_updates_log" value="' . __('Delete Log') . '" class="button-secondary"';
						if (file_exists($update_log) ) {
							echo ' />';
						} else {
							echo ' disabled="disabled" />';
						}
						echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="description">' . __('Log is flushed automatically.') . '</span>';
						?>
				</td>
			</tr>
		</table>
		<input type="hidden" name="nfw_act" value="2" />
	</form>
	<?php
	}
	?>
</div>
<?php

/* ------------------------------------------------------------------ */

function nf_sub_updates_save() {

	$nfw_options = get_option('nfw_options');

	if ( empty($_POST['sched_updates']) || ! preg_match('/^[2-3]$/', $_POST['sched_updates']) ) {
		$nfw_options['sched_updates'] = 1;
		$schedtype = 'hourly';
	} else {
		$nfw_options['sched_updates'] = $_POST['sched_updates'];
		if ($nfw_options['sched_updates'] == 2) {
			$schedtype = 'twicedaily';
		} else {
			$schedtype = 'daily';
		}
	}

	if ( empty($_POST['enable_updates']) ) {
		$nfw_options['enable_updates'] = 0;
		// Clear scheduled scan (if any) and its options :
		if ( wp_next_scheduled('nfsecupdates') ) {
			wp_clear_scheduled_hook('nfsecupdates');
		}
	} else {
		$nfw_options['enable_updates'] = 1;
		// Create a new scheduled scan :
		if ( wp_next_scheduled('nfsecupdates') ) {
			wp_clear_scheduled_hook('nfsecupdates');
		}
		// Start next cron in 90 seconds:
		wp_schedule_event( time() + 90, $schedtype, 'nfsecupdates');
	}

	if ( empty($_POST['notify_updates']) ) {
		$nfw_options['notify_updates'] = 0;
	} else {
		$nfw_options['notify_updates'] = 1;
	}

	update_option('nfw_options', $nfw_options);

}

/* ------------------------------------------------------------------ */

function nf_sub_updates_clearlog($update_log) {

	if (file_exists($update_log) ) {
		unlink($update_log);
	}

}

/* ------------------------------------------------------------------ */

function nf_sub_do_updates($update_url, $update_log) {

	$nfw_options = get_option('nfw_options');

	// Don't do anything if NinjaFirewall is disabled :
	if ( empty( $nfw_options['enabled'] ) ) { return 0; }

	if (! $new_rules_version = nf_sub_updates_getversion($update_url, $nfw_options['rules_version'], $update_log) ) {
		// Error or nothing to update :
		return;
	}

	// There is a new version, let's fetch it:
	if (! $data = nf_sub_updates_download($update_url, $update_log, $new_rules_version) ) {
		// Error :
		return;
	}

	// Unserialize the new rules :
	if (! $new_rules = @unserialize(preg_replace('/eeee/', 'e', $data)) ) {
		nf_sub_updates_log(
			$update_log,
			__('Error: Unable to unserialize the new rules.')
		);
		return 0;
	}
	// One more check...:
	if (! is_array($new_rules) || empty($new_rules[1]['where']) ) {
		nf_sub_updates_log(
			$update_log,
			__('Error: Unserialized rules seem corrupted.')
		);
		return 0;
	}

	$nfw_rules = get_option('nfw_rules');

	foreach ( $new_rules as $new_key => $new_value ) {
		foreach ( $new_value as $key => $value ) {
			// If that rule exists already, we keep its 'on' flag value
			// as it may have been changed by the user with the rules editor :
			if ( ( isset( $nfw_rules[$new_key]['on'] ) ) && ( $key == 'on' ) ) {
				$new_rules[$new_key]['on'] = $nfw_rules[$new_key]['on'];
			}
		}
	}
	$new_rules[NFW_DOC_ROOT]['what']= $nfw_rules[NFW_DOC_ROOT]['what'];
	$new_rules[NFW_DOC_ROOT]['on']	= $nfw_rules[NFW_DOC_ROOT]['on'];

	// Update rules in the DB :
	update_option('nfw_rules', $new_rules);

	// Update rules version in the options table :
	$nfw_options['rules_version'] = $new_rules_version;
	update_option('nfw_options', $nfw_options);

	nf_sub_updates_log(
		$update_log,
		sprintf( __('Security rules updated to version %s.'),
		preg_replace('/(\d{4})(\d\d)(\d\d)/', '$1-$2-$3', $new_rules_version) )
	);

	// Email the admin ?
	if (! empty($nfw_options['notify_updates']) ) {
		nf_sub_updates_notification($new_rules_version);
	}
}

/* ------------------------------------------------------------------ */

function nf_sub_updates_getversion($update_url, $rules_version, $update_log) {

	global $wp_version;
	$res = wp_remote_get(
		$update_url[0] . $update_url[1],
		array(
			'timeout' => 20,
			'httpversion' => '1.1' ,
			'user-agent' => 'WordPress/' . $wp_version,
		)
	);
	if (! is_wp_error($res) ) {
		if ( $res['response']['code'] == 200 ) {
			// Get the rules version :
			$new_version =  explode('|', rtrim($res['body']), 2);
			if (! preg_match('/^\d{8}\.\d+$/', $new_version[1]) ) {
				// Not what we were expecting:
				nf_sub_updates_log(
					$update_log,
					__('Error: Unable to retrieve the new rules version.')
				);
				return 0;
			}
			// Compare versions:
			if ( version_compare($rules_version, $new_version[1], '<') ) {
				return $new_version[1];

			} else {
				nf_sub_updates_log(
				$update_log,
				__('Security rules are up-to-date.')
				);
			}
		// Not a 200 OK ret code :
		} else {
			nf_sub_updates_log(
				$update_log,
				sprintf( __('Error: Server returned a %s HTTP error code (#1).'), htmlspecialchars($res['response']['code']))
			);
		}
	// Connection error :
	} else {
		nf_sub_updates_log(
			$update_log,
			__('Error: Unable to connect to WordPress server') . htmlspecialchars(" ({$result->get_error_message()})")
		);
	}
	return 0;
}

/* ------------------------------------------------------------------ */

function nf_sub_updates_download($update_url, $update_log, $new_rules_version) {

	global $wp_version;
	$res = wp_remote_get(
		$update_url[0] . $update_url[2],
		array(
			'timeout' => 20,
			'httpversion' => '1.1' ,
			'user-agent' => 'WordPress/' . $wp_version,
		)
	);
	if (! is_wp_error($res) ) {
		if ( $res['response']['code'] == 200 ) {
			$data = explode('|', rtrim($res['body']), 3);

			// Rules version should match the one we just fetched :
			if ( $new_rules_version != $data[1]) {
				nf_sub_updates_log(
					$update_log,
					sprintf( __('Error: The new rules versions do not match (%s != %s).'), $new_rules_version, htmlspecialchars($data[1]) )
				);
				return 0;
			}

			return $data[2];

		// Not a 200 OK ret code :
		} else {
			nf_sub_updates_log(
				$update_log,
				sprintf( __('Error: Server returned a %s HTTP error code (#2).'), htmlspecialchars($res['response']['code']))
			);
		}
	// Connection error :
	} else {
		nf_sub_updates_log(
			$update_log,
			__('Error: Unable to connect to WordPress server') . htmlspecialchars(" ({$result->get_error_message()})")
		);
	}
	return 0;
}

/* ------------------------------------------------------------------ */

function nf_sub_updates_log($update_log, $msg) {

	nfw_get_blogtimezone();

	// If the log is bigger than 50Kb (+/- one month old), we flush it :
	if ( file_exists($update_log) ) {
		$log_stat = stat($update_log);
		if ( $log_stat['size'] > 51200 ) {
			@unlink($update_log);
		}
	}
	@file_put_contents($update_log, date_i18n('[d/M/y:H:i:s O]') . " $msg\n", FILE_APPEND | LOCK_EX);

}

/* ------------------------------------------------------------------ */

function nf_sub_updates_notification($new_rules_version) {

	$nfw_options = get_option('nfw_options');

	if ( ( is_multisite() ) && ( $nfw_options['alert_sa_only'] == 2 ) ) {
		$recipient = get_option('admin_email');
	} else {
		$recipient = $nfw_options['alert_email'];
	}

	nfw_get_blogtimezone();

	$subject = __('[NinjaFirewall] Security rules update');
	$msg = __('NinjaFirewall security rules have been updated:') . "\n\n";
	if ( is_multisite() ) {
		$msg .=__('Blog: ') . network_home_url('/') . "\n";
	} else {
		$msg .=__('Blog: ') . home_url('/') . "\n";
	}
	$msg .=__('Rules version: ') . preg_replace('/(\d{4})(\d\d)(\d\d)/', '$1-$2-$3', $new_rules_version) . "\n";
	$msg .= sprintf( __('Date: %s'), date_i18n('M d, Y @ H:i:s O') ) . "\n\n" .
			'NinjaFirewall (WP edition) - http://ninjafirewall.com/' . "\n" .
			'Support forum: http://wordpress.org/support/plugin/ninjafirewall' . "\n";
	wp_mail( $recipient, $subject, $msg );

}

/* ------------------------------------------------------------------ */
// EOF
