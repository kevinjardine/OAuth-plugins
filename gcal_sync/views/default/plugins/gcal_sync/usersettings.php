<?php
/**
 * User settings for gcal sync
 */

elgg_load_library('elgg:oauth');

$user_guid = elgg_get_logged_in_user_guid();
$server_uri = elgg_get_plugin_setting('google_server_uri','gcal_sync');
//  Init the OAuthStore and get the server definition
$store = OAuthStore::instance("Elgg");
$store->setCurrentServerURI($server_uri);
$server = $store->getServerForUri($server_uri,$user_guid);
print $server->server_uri.",".$server->consumer_key;

$secrets_array = $store->getServerTokenSecrets($server->consumer_key, '', 'access', $user_guid);

$site_name = elgg_get_site_entity()->name;
echo '<div>' . elgg_echo('gcal_sync:usersettings:description', array($site_name)) . '</div>';

if (!$secrets_array) {
	// send user off to authorize Google Calendar access
	$request_link = $vars['url'].'gcal_sync/auth_and_sync';
	echo '<div>' . elgg_echo('gcal_sync:usersettings:request', array($request_link, $site_name)) . '</div>';
} else {
	$url = $vars['url'] . "gcal_sync/revoke";
	echo '<div class="twitter_anywhere">' . elgg_echo('gcal_sync:usersettings:authorized', array($site_name)) . '</div>';
	echo '<div>' . sprintf(elgg_echo('gcal_sync:usersettings:revoke'), $url) . '</div>';
}
