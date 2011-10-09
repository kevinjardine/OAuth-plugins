<?php

/**
 * Will be converted into OAuthStoreElgg in due course.
 * 
 * OAuthSession is a really *dirty* storage. It's useful for testing and may 
 * be enough for some very simple applications, but it's not recommended for
 * production use.
 * 
 * @version $Id: OAuthStoreSession.php 153 2010-08-30 21:25:58Z brunobg@corollarium.com $
 * @author BBG
 * 
 * The MIT License
 * 
 * Copyright (c) 2007-2008 Mediamatic Lab
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

require_once dirname(__FILE__) . '/OAuthStoreAbstract.class.php';

class OAuthStoreElgg extends OAuthStoreAbstract
{
	
	protected $current_server_uri = '';
	
	// an Elgg store needs nothing to set up
	public function __construct( $current_server_uri = '' )	{
		if ($current_server_uri) {
			$this->current_server_uri;
		}
		/*if (!session_id()) {
			session_start();
		}
		if(isset($options['consumer_key']) && isset($options['consumer_secret']))
		{
			$this->session = &$_SESSION['oauth_' . $options['consumer_key']];
			$this->session['consumer_key'] = $options['consumer_key'];
			$this->session['consumer_secret'] = $options['consumer_secret'];
			$this->session['signature_methods'] = array('HMAC-SHA1');
			$this->session['server_uri'] = $options['server_uri']; 
			$this->session['request_token_uri'] = $options['request_token_uri'];
			$this->session['authorize_uri'] = $options['authorize_uri'];
			$this->session['access_token_uri'] = $options['access_token_uri']; 
			
		}
		else
		{
			throw new OAuthException2("OAuthStoreSession needs consumer_token and consumer_secret");
		}*/
	}

	public function getSecretsForVerify ( $consumer_key, $token, $token_type = 'access' ) { throw new OAuthException2("OAuthStoreElgg doesn't support " . __METHOD__); }
	
	/**
	 * Find the server details for signing a request, always looks for an access token.
	 * The returned credentials depend on which local user is making the request.
	 * 
	 * The consumer_key must belong to the user or be public (user id is null)
	 * 
	 * For signing we need all of the following:
	 * 
	 * consumer_key			consumer key associated with the server
	 * consumer_secret		consumer secret associated with this server
	 * token				access token associated with this server
	 * token_secret			secret for the access token
	 * signature_methods	signing methods supported by the server (array)
	 * 
	 * @todo filter on token type (we should know how and with what to sign this request, and there might be old access tokens)
	 * @param string uri	uri of the server
	 * @param int user_id	id of the logged on user
	 * @param string name	(optional) name of the token (case sensitive)
	 * @exception OAuthException2 when no credentials found
	 * @return array
	 */
	public function getSecretsForSignature ( $uri, $user_id ) {
		// always returns the most recent access token for this uri and user_id,
		// if available
		// but should it?
		if ($this->current_server_uri) {
			$server = $this->getServerForUri($this->current_server_uri,$user_id);
			if (is_array($server->signature_methods)) {
				$signature_methods = $server->signature_methods;
			} else {
				$signature_methods =  array($server->signature_methods);
			}
			$options = array (
				'type' => 'object',
				'subtype' => 'oauth_server_token',
				'owner_guid' => $user_id,
				'metadata_name_value_pairs' => array(
					array('name'=>'server_uri','value'=>sanitize_string($this->current_server_uri)),
					//array('name'=>'token_ttl','value'=>time(),'operand'=>'>='),
					array('name'=>'token_type','value'=>'access')
				),
				'order_by' => 'e.guid DESC',
				'limit' => 1,			
			);
			$entities = elgg_get_entities_from_metadata($options);
			if ($entities) {
				$token = $entities[0];
				return array(
					'consumer_key' 		=> 	$server->consumer_key,
					'consumer_secret' 	=> 	$server->consumer_secret,
					'signature_methods' => 	$signature_methods,
					'token'				=>	$token->token,
					'token_secret'		=>	$token->token_secret,
				);
			} else {
				return array(
					'consumer_key' 		=> 	$server->consumer_key,
					'consumer_secret' 	=> 	$server->consumer_secret,
					'signature_methods' => 	$signature_methods,
				);
			}
		} else {
			throw new OAuthException2('No server URI defined');
		}
		//return $this->session;
	}

	public function getServerTokenSecrets ( $consumer_key, $token, $token_type, $user_id, $name = '') { 
		/*if ($consumer_key != $this->session['consumer_key']) {
			return array();
		} 
		return array(
			'consumer_key' => $consumer_key,
			'consumer_secret' => $this->session['consumer_secret'],
			'token' => $token,
			'token_secret' => $this->session['token_secret'],
			'token_name' => $name,
			'signature_methods' => $this->session['signature_methods'],
			'server_uri' => $this->session['server_uri'],
			'request_token_uri' => $this->session['request_token_uri'],
			'authorize_uri' => $this->session['authorize_uri'],
			'access_token_uri' => $this->session['access_token_uri'],
			'token_ttl' => 3600,
		);*/
		if ($this->current_server_uri) {
			$options = array (
				'type' => 'object',
				'subtype' => 'oauth_server_token',
				'owner_guid' => $user_id,
				'metadata_name_value_pairs' => array(
					array('name'=>'server_uri','value'=>sanitize_string($this->current_server_uri)),
					//array('name'=>'token_ttl','value'=>time(),'operand'=>'>='),
					array('name'=>'token_type','value'=>sanitize_string($token_type)),
				),
				'order_by' => 'e.guid DESC',
				'limit' => 1,			
			);
			$entities = elgg_get_entities_from_metadata($options);
			if ($entities) {
				$token = $entities[0];
				$server = $this->getServerForUri ( $this->current_server_uri, $user_id );
				if ($server) {
					if (is_array($server->signature_methods)) {
						$signature_methods = $server->signature_methods;
					} else {
						$signature_methods =  array($server->signature_methods);
					}
					$result = array(
						'consumer_key' => $server->consumer_key,
						'consumer_secret' => $server->consumer_secret,
						'token' => $token->token,
						'token_secret' => $token->token_secret,
						'token_name' => $token->name,
						'signature_methods' => $signature_methods,
						'server_uri' => $this->current_server_uri,
						'request_token_uri' => $server->request_token_uri,
						'authorize_uri' => $server->authorize_uri,
						'access_token_uri' => $server->access_token_uri,
						'token_ttl' => $token->token_ttl-time(),
					);
					//print_r($result);
					return $result;
				}
			}
		}
		return array();
	}
	
	public function addServerToken ( $consumer_key, $token_type, $token, $token_secret, $user_id, $options = array() ) {
		$token_obj = new ElggObject();
		$token_obj->subtype = 'oauth_server_token';
		$token_obj->owner_guid = $user_id;
		$token_obj->container_guid = $user_id;
		$token_obj->access_id = ACCESS_PRIVATE;
		$token_obj->token_type = $token_type;
		$token_obj->token = $token;
		$token_obj->token_secret = $token_secret;
		if (isset($options['server_uri'])) {
			$token_obj->server_uri = $options['server_uri'];
		} else {
			$token_obj->server_uri = $this->current_server_uri;
		}
		if (isset($options['token_ttl']) && is_numeric($options['token_ttl'])) {
			$token_obj->token_ttl = time()+$options['token_ttl'];
		} else {
			// make it last forever
			$token_obj->token_ttl = 1000000000;			
		}
		if (isset($options['name'])) {
			$token_obj->name = $options['name'];
		}
		if (isset($options['token_ttl']) && is_numeric($options['token_ttl'])) {
			$token_obj->token_ttl = time()+$options['token_ttl'];
		}
		
		$token_obj->save();
	}

	public function deleteServer ( $consumer_key, $user_id, $user_is_admin = false ) { throw new OAuthException2("OAuthStoreElgg doesn't support " . __METHOD__); }
	public function getServer( $consumer_key, $user_id, $user_is_admin = false ) {
		if ($this->current_server_uri) {
			$server = $this->getServerForUri ( $this->current_server_uri, $user_id );
			if ($server) {
				if (is_array($server->signature_methods)) {
					$signature_methods = $server->signature_methods;
				} else {
					$signature_methods = array($server->signature_methods);
				}
				return array( 
					'id' => $server->guid,
					'user_id' => $user_id,
					'consumer_key' => $server->consumer_key,
					'consumer_secret' => $server->consumer_secret,
					'signature_methods' => $signature_methods,
					'server_uri' => $this->current_server_uri,
					'request_token_uri' => $server->request_token_uri,
					'authorize_uri' => $server->authorize_uri,
					'access_token_uri' => $server->access_token_uri,
				);
			}
		}
		return array();
	}
	
	public function getServerForUri ( $uri, $user_id ) {
		// currently $user_id is ignored
		$options = array(
			'type' => 'object',
			'subtype' => 'oauth_server',
			'metadata_name' => 'server_uri',
			'metadata_value' => sanitize_string($uri),
			'limit' => 0,
		);
		$entities = elgg_get_entities_from_metadata($options);
		if ($entities) {
			return $entities[0];
		} else {
			return FALSE;
		}
	}
	public function listServerTokens ( $user_id ) { throw new OAuthException2("OAuthStoreElgg doesn't support " . __METHOD__); }
	public function countServerTokens ( $consumer_key ) { throw new OAuthException2("OAuthStoreElgg doesn't support " . __METHOD__); }
	public function getServerToken ( $consumer_key, $token, $user_id ) { throw new OAuthException2("OAuthStoreElgg doesn't support " . __METHOD__); }
	public function deleteServerToken ( $consumer_key, $token, $user_id, $user_is_admin = false ) {
		$options = array (
				'type' => 'object',
				'subtype' => 'oauth_server_token',
				'metadata_name_value_pairs' => array(
					array('name'=>'server_uri','value'=>sanitize_string($this->current_server_uri)),
					array('name'=>'token','value'=>sanitize_string($token)),
				),	
		);
		if ($user_guid) {
			$options['owner_guid'] = $user_id;
		}
		error_log("Attempting to delete token $token");
		$entities = elgg_get_entities_from_metadata($options);
		foreach($entities as $e) {
			$e->delete();
		}
	}

	public function setServerTokenTtl ( $consumer_key, $token, $token_ttl )
	{
		//This method just needs to exist. It doesn't have to do anything!
	}
	
	public function listServers ( $q = '', $user_id ) { throw new OAuthException2("OAuthStoreElgg doesn't support " . __METHOD__); }
	public function updateServer ( $server, $user_id, $user_is_admin = false ) {
		// register or update a server using the data in the server array
		// add or modify this definition only if the user is an admin
		if (!$user_is_admin) {
			throw new OAuthException2("Only admins can create or update server definitions"); 
		} else {
			$server_uri = $server['server_uri'];
			$server_obj = $this->getServerForUri($server_uri,$user_id);
			
			if (!$server_obj) {
				$server_obj = new ElggObject();
				$server_obj->subtype = 'oauth_server';
				$server_obj->owner_guid = $user_id;
				$server_obj->container_guid = $user_id;
				$server_obj->access_id = ACCESS_PUBLIC;
				$server_obj->server_uri = $server['server_uri'];
			}
			
			$server_obj->consumer_key = $server['consumer_key'];
			$server_obj->consumer_secret = $server['consumer_secret'];
			$server_obj->signature_methods = $server['signature_methods'];
			
			$server_obj->request_token_uri = $server['request_token_uri'];
			$server_obj->authorize_uri = $server['authorize_uri'];
			$server_obj->access_token_uri = $server['access_token_uri'];
			$server_obj->save();
		}		
	}

	public function updateConsumer ( $consumer, $user_id, $user_is_admin = false ) { throw new OAuthException2("OAuthStoreElgg doesn't support " . __METHOD__); }
	public function deleteConsumer ( $consumer_key, $user_id, $user_is_admin = false ) { throw new OAuthException2("OAuthStoreElgg doesn't support " . __METHOD__); }
	public function getConsumer ( $consumer_key, $user_id, $user_is_admin = false ) { throw new OAuthException2("OAuthStoreElgg doesn't support " . __METHOD__); }
	public function getConsumerStatic () { throw new OAuthException2("OAuthStoreElgg doesn't support " . __METHOD__); }

	public function addConsumerRequestToken ( $consumer_key, $options = array() ) { throw new OAuthException2("OAuthStoreElgg doesn't support " . __METHOD__); }
	public function getConsumerRequestToken ( $token ) { throw new OAuthException2("OAuthStoreElgg doesn't support " . __METHOD__); }
	public function deleteConsumerRequestToken ( $token ) { throw new OAuthException2("OAuthStoreElgg doesn't support " . __METHOD__); }
	public function authorizeConsumerRequestToken ( $token, $user_id, $referrer_host = '' ) { throw new OAuthException2("OAuthStoreElgg doesn't support " . __METHOD__); }
	public function countConsumerAccessTokens ( $consumer_key ) { throw new OAuthException2("OAuthStoreElgg doesn't support " . __METHOD__); }
	public function exchangeConsumerRequestForAccessToken ( $token, $options = array() ) { throw new OAuthException2("OAuthStoreElgg doesn't support " . __METHOD__); }
	public function getConsumerAccessToken ( $token, $user_id ) { throw new OAuthException2("OAuthStoreElgg doesn't support " . __METHOD__); }
	public function deleteConsumerAccessToken ( $token, $user_id, $user_is_admin = false ) { throw new OAuthException2("OAuthStoreElgg doesn't support " . __METHOD__); }
	public function setConsumerAccessTokenTtl ( $token, $ttl ) { throw new OAuthException2("OAuthStoreElgg doesn't support " . __METHOD__); }
	
	public function listConsumers ( $user_id ) { throw new OAuthException2("OAuthStoreElgg doesn't support " . __METHOD__); }
	public function listConsumerApplications( $begin = 0, $total = 25 )  { throw new OAuthException2("OAuthStoreElgg doesn't support " . __METHOD__); }
	public function listConsumerTokens ( $user_id ) { throw new OAuthException2("OAuthStoreElgg doesn't support " . __METHOD__); }

	public function checkServerNonce ( $consumer_key, $token, $timestamp, $nonce ) { throw new OAuthException2("OAuthStoreElgg doesn't support " . __METHOD__); }
	
	public function addLog ( $keys, $received, $sent, $base_string, $notes, $user_id = null ) { error_log(print_r(array($keys, $received, $sent, $base_string, $notes, $user_id),TRUE)); }
	public function listLog ( $options, $user_id ) { throw new OAuthException2("OAuthStoreElgg doesn't support " . __METHOD__); }
	
	public function install () { throw new OAuthException2("OAuthStoreElgg doesn't support " . __METHOD__); }

	// extra getter and setter for current_server_uri
	
	public function getCurrentServerURI() {
		return $this->current_server_uri;
	}
	
	public function setCurrentServerURI($current_server_uri) {
		$this->current_server_uri = $current_server_uri;
	}
}

?>