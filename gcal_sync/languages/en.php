<?php
/**
 * English language strings
 *
 */

$english = array(
	'gcal_sync:authorization_denied' => 'Cannot carry out task because authorization was denied.',
	'gcal_sync:app_display_name' => "Elgg / Google Calendar sync",
	'gcal_sync:usersettings:description' => "Synchronize your %s personal calendar with Google Calendar.",
	'gcal_sync:usersettings:request' => "You must first <a href=\"%s\">authorize</a> %s to upload your personal calendar to Google Calendar.",
	'gcal_sync:authorize:error' => 'Unable to get authorization from Google Calendar.',
	'gcal_sync:authorize:success' => 'Google Calendar access has been authorized and your personal calendar has been uploaded.',

	'gcal_sync:usersettings:authorized' => "You have authorized %s to access your Google Calendar.",
	'gcal_sync:usersettings:revoke' => 'Click <a href="%s">here</a> to revoke access.',
	'gcal_sync:revoke:success' => 'Google Calendar access has been revoked.',
	'gcal_sync:revoke:error' => 'Unable to revoke access to Google Calendar.',
	'gcal_sync:revoke:weird' => 'Strange error: Unable to revoke access to Google Calendar.',
);

add_translation("en", $english);