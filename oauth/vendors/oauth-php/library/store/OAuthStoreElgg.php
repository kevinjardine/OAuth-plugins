<?php

/**
 * OAuth Elgg store
 *
 * @package oauth
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Kevin Jardine <kevin@radagast.biz>
 * @copyright Radagast Solutions 2011
 * @link http://radagast.biz/
 *
 */

require_once dirname(__FILE__) . '/OAuthStoreAbstract.class.php';

class OAuthStoreElgg extends OAuthStoreAbstract
{
	protected $current_server_uri = '';
	protected $current_scope = array();
	
	// an Elgg store needs nothing to set up
	// but optionally can take a server_uri and scope array
	// these can also be added later using the setter functions
	public function __construct( $current_server_uri = null, $current_scope = null )	{
		if (isset($current_server_uri)) {
			$this->current_server_uri;
		}
		if (isset($current_scope)) {
			$this->current_scope = $current_scope;
		}
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
		// always returns the most recent unexpired access token for this uri and user_id,
		// if available, but should it?
		if ($this->current_server_uri) {
			$server = $this->getServerForUri($this->current_server_uri,$user_id);
			if (is_array($server->signature_methods)) {
				$signature_methods = $server->signature_methods;
			} else {
				$signature_methods =  array($server->signature_methods);
			}
			
			$tokens = $this->getServerTokens($this->current_server_uri,'access',$user_id,$this->current_scope);
			if ($tokens) {
				$token = $tokens[0];
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
	}
	
	// currently ignores the $token and $name parameters but should perhaps use them if defined

	public function getServerTokenSecrets ( $consumer_key, $token, $token_type, $user_id, $name = '') { 
		if ($this->current_server_uri) {
			$tokens = $this->getServerTokens($this->current_server_uri,$token_type,$user_id,$this->current_scope);
			if ($tokens) {
				$token = $tokens[0];
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
					return $result;
				}
			}
		}
		return array();
	}
	
	// TODO consider making the server description the parent object to the tokens
	// rather than using server_uri
	
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
			// make it last for ten years
			$token_obj->token_ttl = time()+60*60*24*365*10;			
		}
		if (isset($options['name'])) {
			$token_obj->name = $options['name'];
		}
		
		if (isset($this->current_scope)) {
			$token_obj->scope = $this->current_scope;
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
	
	public function addLog ( $keys, $received, $sent, $base_string, $notes, $user_id = null ) {
		// crude method to get this done for now
		// TODO: should there be oauth_log_entry objects?
		error_log(print_r(array(
			'keys'			=>	$keys,
			'received'		=>	$received, 
			'sent'			=>	$sent, 
			'base_string'	=>	$base_string, 
			'notes'			=>	$notes, 
			'user_guid'		=>	$user_id
		),TRUE)); 
	}
	public function listLog ( $options, $user_id ) { throw new OAuthException2("OAuthStoreElgg doesn't support " . __METHOD__); }
	
	public function install () { throw new OAuthException2("OAuthStoreElgg doesn't support " . __METHOD__); }

	// extra getters and setters for current_server_uri and current_scope
	
	public function getCurrentServerURI() {
		return $this->current_server_uri;
	}
	
	public function setCurrentServerURI($current_server_uri) {
		$this->current_server_uri = $current_server_uri;
	}
	
	public function getCurrentScope() {
		return $this->current_scope;
	}
	
	public function setCurrentScope($current_scope) {
		$this->current_scope = $current_scope;
	}
	
	// TODO: putting four metadata constraints on this query is really inefficient
	// try to make this more efficient (eg. make tokens contained by a server parent object)
	
	public function getServerTokens($server_uri,$token_type,$user_id,$scope = null,$limit=1) {
		$options = array (
			'type' => 'object',
			'subtype' => 'oauth_server_token',
			'owner_guid' => $user_id,
			'metadata_name_value_pairs' => array(
				array('name'=>'server_uri','value'=>sanitize_string($server_uri)),
				array('name'=>'token_ttl','value'=>time(),'operand'=>'>='),
				array('name'=>'token_type','value'=>sanitize_string($token_type)),
			),
			'order_by' => 'e.guid DESC',
			'limit' => $limit,			
		);
		if ($scope) {
			$scopes = array();
			foreach($this->current_scope as $s) {
				$scopes[] = sanitize_string($s);
			}
			if ($scopes) {
				$options['metadata_name_value_pairs'][] = array('name'=>'scope','value'=>$scopes,'operand'=>'IN');
			}
		}
		return elgg_get_entities_from_metadata($options);
	}
}

?>