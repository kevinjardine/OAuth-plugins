<?php
elgg_load_library('elgg:oauth');

define("GOOGLE_CONSUMER_KEY", "anonymous"); // 
define("GOOGLE_CONSUMER_SECRET", "anonymous"); // 

define("GOOGLE_OAUTH_HOST", "https://www.google.com");
define("GOOGLE_REQUEST_TOKEN_URL", GOOGLE_OAUTH_HOST . "/accounts/OAuthGetRequestToken");
define("GOOGLE_AUTHORIZE_URL", GOOGLE_OAUTH_HOST . "/accounts/OAuthAuthorizeToken");
define("GOOGLE_ACCESS_TOKEN_URL", GOOGLE_OAUTH_HOST . "/accounts/OAuthGetAccessToken");
define("GOOGLE_REVOKE_ACCESS_URL",GOOGLE_OAUTH_HOST . "/accounts/AuthSubRevokeToken");

// insert the Google server definition
$user_guid = elgg_get_logged_in_user_guid();

$server_def = array(
	'consumer_key' => GOOGLE_CONSUMER_KEY, 
	'consumer_secret' => GOOGLE_CONSUMER_SECRET,
	'server_uri' => GOOGLE_OAUTH_HOST,
	'request_token_uri' => GOOGLE_REQUEST_TOKEN_URL,
	'authorize_uri' => GOOGLE_AUTHORIZE_URL,
	'signature_methods' => array('HMAC-SHA1'),
	'access_token_uri' => GOOGLE_ACCESS_TOKEN_URL,
	'revoke_access_uri' => GOOGLE_REVOKE_ACCESS_URL,
);
$store = OAuthStore::instance("Elgg");
$store->updateServer($server_def,$user_guid, TRUE);

elgg_set_plugin_setting('google_server_uri', "https://www.google.com",'gcal_sync');
elgg_set_plugin_setting('google_scope','https://www.google.com/calendar/feeds/','gcal_sync');
elgg_set_plugin_setting('google_display_name',elgg_echo('gcal_sync:app_display_name'),'gcal_sync');
