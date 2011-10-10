<?php
elgg_register_event_handler('init', 'system', 'gcal_sync_init');

function gcal_sync_init() {

	elgg_register_page_handler('gcal_sync', 'gcal_sync_page_handler');
	elgg_register_library('elgg:gcal_sync', elgg_get_plugins_path() . 'gcal_sync/models/model.php');
}
/**
 * gcal_sync page handler
 *
 * URLs take the form of
 *  test page:    gcal_sync/test
 *
 * @param array $page Array of url segments for routing
 */
function gcal_sync_page_handler($page) {

	elgg_load_library('elgg:oauth');
	
	$page_dir = elgg_get_plugins_path() . 'gcal_sync/pages/';

	switch ($page[0]) {
		case 'test':
			require_once($page_dir.'test.php');
			break;
		case 'auth_and_sync':
			require_once($page_dir.'auth_and_sync.php');
			break;
		case 'revoke':
			require_once($page_dir.'revoke.php');
			break;
		case 'delete_access_token':
			require_once($page_dir.'delete_access_token.php');
			break;
	}
}