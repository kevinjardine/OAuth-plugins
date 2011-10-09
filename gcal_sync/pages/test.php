<?php

// currently this only works for admins

admin_gatekeeper();

define("OAUTH_LOG_REQUEST",TRUE);

define("GOOGLE_CONSUMER_KEY", "anonymous"); // 
define("GOOGLE_CONSUMER_SECRET", "anonymous"); // 

define("GOOGLE_OAUTH_HOST", "https://www.google.com");
define("GOOGLE_REQUEST_TOKEN_URL", GOOGLE_OAUTH_HOST . "/accounts/OAuthGetRequestToken");
define("GOOGLE_AUTHORIZE_URL", GOOGLE_OAUTH_HOST . "/accounts/OAuthAuthorizeToken");
define("GOOGLE_ACCESS_TOKEN_URL", GOOGLE_OAUTH_HOST . "/accounts/OAuthGetAccessToken");

define('OAUTH_TMP_DIR', function_exists('sys_get_temp_dir') ? sys_get_temp_dir() : realpath($_ENV["TMP"]));

//  Init the OAuthStore

$store = OAuthStore::instance("Elgg");
$store->setCurrentServerURI(GOOGLE_OAUTH_HOST);

$user_guid = elgg_get_logged_in_user_guid();

// TODO: move the server update into something else
// (activation routine, admin interface?)

$server_def = array(
	'consumer_key' => GOOGLE_CONSUMER_KEY, 
	'consumer_secret' => GOOGLE_CONSUMER_SECRET,
	'server_uri' => GOOGLE_OAUTH_HOST,
	'request_token_uri' => GOOGLE_REQUEST_TOKEN_URL,
	'authorize_uri' => GOOGLE_AUTHORIZE_URL,
	'signature_methods' => array('HMAC-SHA1'),
	'access_token_uri' => GOOGLE_ACCESS_TOKEN_URL
);

$authorized = FALSE;

try {

	$store->updateServer($server_def,$user_guid, TRUE);
	// see if we have an access token already
	// is there a better way to do this?
	$secrets_array = $store->getServerTokenSecrets(GOOGLE_CONSUMER_KEY, '', 'access', $user_guid);
	if ($secrets_array) {
		$authorized = TRUE;
	} else {
		// no access token, so go through the dance
		//  STEP 1:  If we do not have an OAuth token yet, go get one
		$oauth_token = get_input('oauth_token');
		if (!$oauth_token)
		{
			$getAuthTokenParams = array('scope' => 
				'https://www.google.com/calendar/feeds/',
				'xoauth_displayname' => 'OAuth test',
				'oauth_callback' => elgg_get_site_url().'gcal_sync/test');
	
			// get a request token
			$tokenResultParams = OAuthRequester::requestRequestToken(GOOGLE_CONSUMER_KEY, $user_guid, $getAuthTokenParams);
			header("Location: " . GOOGLE_AUTHORIZE_URL . "?oauth_token=" . $tokenResultParams['token']);
		} else {
			//  STEP 2:  Get an access token
			$tokenResultParams = $_GET;
			try {			
				OAuthRequester::requestAccessToken(GOOGLE_CONSUMER_KEY, $oauth_token, $user_guid, 'POST', $_GET);
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
		$result = handle_request_with_redirects($url,$user_guid,$store);
		
		if ($result['code'] == 200) {
			$r = json_decode($result['body']);
			echo "<h3>Your calendars</h3>";
			// assuming that first calendar returned is the primary calendar
			$cal = $r->data->items[0];			
			echo $cal->title;
			echo '<br />';
			echo $cal->eventFeedLink;
			echo '<br />';
			$url = $cal->eventFeedLink."?alt=jsonc";
	
			$result = handle_request_with_redirects($url,$user_guid,$store);
			
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

// handle potential redirects
// need to do it this way because each redirect needs to be re-signed

function handle_request_with_redirects($url,$user_guid,$store) {
	for ($i=0; $i<5;$i++) {
		$request = new OAuthRequester($url, 'GET');
		try {
			$result = $request->doRequest($user_guid);
			if ($result['code'] == 302) {
				$url = $result['headers']['location'];
			} else {
				break;
			}
		} catch(OAuthException2 $e) {
			// so far as I can see, if an exception is triggered at this stage, 
			// the user must have revoked the access token, so delete the one we have and ask 
			// for another one
			$secrets_array = $store->getServerTokenSecrets(GOOGLE_CONSUMER_KEY, '', 'access', $user_guid);
			$store->deleteServerToken(GOOGLE_CONSUMER_KEY,$secrets_array['token'],$user_guid);
			forward('gcal_sync/test');
			exit;
		}
	}
	
	return $result;
}
