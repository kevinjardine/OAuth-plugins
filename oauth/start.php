<?php
/**
 * OAuth
 * @package oauth
 */

// added outside init function to avoid plugin ordering problems
elgg_register_library('elgg:oauth', elgg_get_plugins_path() . 'oauth/models/model.php');
