<?php
// require all vendor libraries
$plugin_path = dirname(dirname(__FILE__)) . '/vendors/oauth-php/library';
require_once "$plugin_path/OAuthDiscovery.php";
require_once "$plugin_path/OAuthException2.php";
require_once "$plugin_path/OAuthRequest.php";
require_once "$plugin_path/OAuthRequester.php";
require_once "$plugin_path/OAuthRequestLogger.php";
require_once "$plugin_path/OAuthRequestSigner.php";
require_once "$plugin_path/OAuthRequestVerifier.php";
require_once "$plugin_path/OAuthServer.php";
require_once "$plugin_path/OAuthSession.php";
require_once "$plugin_path/OAuthStore.php";

require_once "$plugin_path/body/OAuthBodyMultipartFormdata.php";

require_once "$plugin_path/store/OAuthStoreAbstract.class.php";

require_once "$plugin_path/signature_method/OAuthSignatureMethod_HMAC_SHA1.php";
require_once "$plugin_path/signature_method/OAuthSignatureMethod_MD5.php";
require_once "$plugin_path/signature_method/OAuthSignatureMethod_PLAINTEXT.php";
require_once "$plugin_path/signature_method/OAuthSignatureMethod_RSA_SHA1.php";

// handle potential redirects
// need to do it this way because each redirect needs to be re-signed

function oauth_handle_request_with_redirects($url,$user_guid,$store,$server,$reauthorize_forward='') {
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
			$secrets_array = $store->getServerTokenSecrets($server->consumer_key, '', 'access', $user_guid);
			$store->deleteServerToken($server->consumer_key,$secrets_array['token'],$user_guid);
			if($reauthorize_forward) {
				forward($reauthorize_forward);
				exit;
			} else {
				return FALSE;
			}
		}
	}
	
	return $result;
}

// handle potential redirects
// need to do it this way because each redirect needs to be re-signed

function oauth_handle_post_with_redirects($url,$params,$body,$user_guid,$store,$server,$reauthorize_forward='') {
	for ($i=0; $i<5;$i++) {
		$request = new OAuthRequester($url, 'POST', $params, $body);
		try {
			$result = $request->doRequest($user_guid);
			if ($result['code'] == 302) {
				$url = $result['headers']['location'];
			} else {
				break;
			}
		} catch(OAuthException2 $e) {
			// so far as I can see, if an exception is triggered at this stage, 
			// the user must have revoked the access token, so delete the one we have and 
			// optionally ask for another one
			$secrets_array = $store->getServerTokenSecrets($server->consumer_key, '', 'access', $user_guid);
			$store->deleteServerToken($server->consumer_key,$secrets_array['token'],$user_guid);
			if($reauthorize_forward) {
				forward($reauthorize_forward);
				exit;
			} else {
				return FALSE;
			}
			exit;
		}
	}
	
	return $result;
}
