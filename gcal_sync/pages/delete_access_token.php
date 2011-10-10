<?php
elgg_load_library('elgg:oauth');

$user = elgg_get_logged_in_user_entity();
if ($user) {
	$server_uri = elgg_get_plugin_setting('google_server_uri','gcal_sync');
	//  Init the OAuthStore and get the server definition
	$store = OAuthStore::instance("Elgg");
	$store->setCurrentServerURI($server_uri);
	$server = $store->getServerForUri($server_uri,$user_guid);
	
	// delete the access token
	$secrets_array = $store->getServerTokenSecrets($server->consumer_key, '', 'access', $user->guid);
	$store->deleteServerToken($server->consumer_key,$secrets_array['token'],$user->guid);
	system_message(elgg_echo('gcal_sync:revoke:success'));
	
	forward('settings/plugins/'.$user->username);
}
