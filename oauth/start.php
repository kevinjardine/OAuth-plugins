<?php
/**
 * OAuth
 * @package oauth
 */

elgg_register_event_handler('init', 'system', 'oauth_init');
function oauth_init() {
	elgg_register_library('elgg:oauth', elgg_get_plugins_path() . 'oauth/models/model.php');
}
