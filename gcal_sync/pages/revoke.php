<?php
elgg_load_library('elgg:oauth');

$user_guid = elgg_get_logged_in_user_guid();
$server_uri = elgg_get_plugin_setting('google_server_uri','gcal_sync');
//  Init the OAuthStore and get the server definition
$store = OAuthStore::instance("Elgg");
$store->setCurrentServerURI($server_uri);

$server = $store->getServerForUri($server_uri,$user_guid);

$rejectTokenParams = array(
	'scope' => elgg_get_plugin_setting('google_scope','gcal_sync'),
	'xoauth_displayname' => elgg_get_plugin_setting('google_display_name','gcal_sync'),
);

$url = $server->revoke_access_uri;

$curl_options = array(CURLOPT_HTTPHEADER => array("Content-Type: application/atom+xml","GData-Version: 2.0"));

$request = new OAuthRequester($url, 'GET',$rejectTokenParams);
try {
	$result = $request->doRequest($user_guid,$curl_options);
} catch(OAuthException2 $e) {
	error_log('error in revoke, error was: '.print_r($e,TRUE));
	$result = FALSE;
}

if (($result == FALSE) || ($result['code'] != 200)) {
	error_log('error in revoke, result was: '.print_r($result,TRUE));
	register_error(elgg_echo('gcal_sync:revoke:error'));
	forward(REFERER);
} else {
	forward('gcal_sync/delete_access_token');
}

exit;
