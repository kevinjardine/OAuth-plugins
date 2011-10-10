<?php

$server_uri = elgg_get_plugin_setting('google_server_uri','gcal_sync');

define("OAUTH_LOG_REQUEST",TRUE);

$user_guid = elgg_get_logged_in_user_guid();

//  Init the OAuthStore and get the server definition
$store = OAuthStore::instance("Elgg");
$store->setCurrentServerURI($server_uri);
$server = $store->getServerForUri($server_uri,$user_guid);

$authorized = FALSE;

try {

	// see if we have an access token already
	// is there a better way to do this?
	$secrets_array = $store->getServerTokenSecrets($server->consumer_key, '', 'access', $user_guid);
	if ($secrets_array) {
		$authorized = TRUE;
	} else {
		// no access token, so go through the dance
		//  STEP 1:  If we do not have an OAuth token yet, go get one
		$oauth_token = get_input('oauth_token');
		if (!$oauth_token)
		{
			$getAuthTokenParams = array(
				'scope' => elgg_get_plugin_setting('google_scope','gcal_sync'),
				'xoauth_displayname' => elgg_get_plugin_setting('google_display_name','gcal_sync'),
				'oauth_callback' => elgg_get_site_url().'gcal_sync/test'
			);
	
			// get a request token
			$tokenResultParams = OAuthRequester::requestRequestToken($server->consumer_key, $user_guid, $getAuthTokenParams);
			header("Location: " . $server->authorize_uri . "?oauth_token=" . $tokenResultParams['token']);
		} else {
			//  STEP 2:  Get an access token
			$tokenResultParams = $_GET;
			try {			
				OAuthRequester::requestAccessToken($server->consumer_key, $oauth_token, $user_guid, 'POST', $_GET);
			} catch (OAuthException2 $e) {
				register_error(elgg_echo('gcal_sync:authorization_denied'));
				forward();
			    exit;
			}
			$authorized = TRUE;
		}
	}
	if ($authorized) {
		// Get the list of all calendars
		$url = "https://www.google.com/calendar/feeds/default/allcalendars/full?alt=jsonc";
		$result = oauth_handle_request_with_redirects($url,$user_guid,$store,$server,'gcal_sync/test');
		
		if ($result['code'] == 200) {
			$r = json_decode($result['body']);
			echo "<h3>Your calendar events</h3>";
			// assuming that first calendar returned is the primary calendar
			$cal = $r->data->items[0];			
			echo $cal->title;
			echo '<br />';
			echo $cal->eventFeedLink;
			echo '<br />';
			$url = $cal->eventFeedLink."?alt=jsonc";
	
			$result = oauth_handle_request_with_redirects($url,$user_guid,$store,$server,'gcal_sync/test');
			
			// todo, add new events or update events with the same gcal_id.
			
			if ($result['code'] == 200) {
				echo "<br />Events:<br />";
				$r = json_decode($result['body']);
				foreach($r->data->items as $e) {
					$title = $e->title;
					$gcal_id = $e->id;
					echo $title;
					$ts = array();
					foreach($e->when as $t) {
						$ts[] = $t->start. " - ".$t->end;
					}
					echo " (".implode(", ",$ts).")";
					echo '<br />';
				}
			} else {
				echo 'Error';
				print_r($result);
			}
		} else {
			echo 'Error';
			print_r($result);
		}
	}
} catch(OAuthException2 $e) {
	echo "OAuthException:  " . $e->getMessage();
	var_dump($e);
}
