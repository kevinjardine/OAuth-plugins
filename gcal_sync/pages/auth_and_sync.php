<?php

elgg_load_library('elgg:gcal_sync');

$server_uri = elgg_get_plugin_setting('google_server_uri','gcal_sync');

$user = elgg_get_logged_in_user_entity();

//  Init the OAuthStore and get the server definition
$store = OAuthStore::instance("Elgg");
$store->setCurrentServerURI($server_uri);
$server = $store->getServerForUri($server_uri,$user->guid);

$authorized = FALSE;

try {
	//  STEP 1:  If we do not have an OAuth token yet, go get one
	$oauth_token = get_input('oauth_token');
	if (!$oauth_token)
	{
		$getAuthTokenParams = array(
			'scope' => elgg_get_plugin_setting('google_scope','gcal_sync'),
			'xoauth_displayname' => elgg_get_plugin_setting('google_display_name','gcal_sync'),
			'oauth_callback' => elgg_get_site_url().'gcal_sync/auth_and_sync'
		);

		// get a request token
		$tokenResultParams = OAuthRequester::requestRequestToken($server->consumer_key, $user->guid, $getAuthTokenParams);
		header("Location: " . $server->authorize_uri . "?oauth_token=" . $tokenResultParams['token']);
		exit;
	} else {
		//  STEP 2:  Get an access token
		$tokenResultParams = $_GET;
		try {			
			OAuthRequester::requestAccessToken($server->consumer_key, $oauth_token, $user->guid, 'POST', $_GET);
			$authorized = TRUE;
		} catch (OAuthException2 $e) {
			register_error(elgg_echo('gcal_sync:authorization_denied'));
		}
	}
	if ($authorized) {
		if (gcal_sync_do_sync($user->guid,$store,$server)) {
			system_message(elgg_echo('gcal_sync:authorize:success'));
		} else {
			register_error(elgg_echo('gcal_sync:authorize:error'));
		}
	}
} catch(OAuthException2 $e) {
	register_error(elgg_echo('gcal_sync:authorize:error').$e->message);
}

forward('settings/plugins/'.$user->username);
