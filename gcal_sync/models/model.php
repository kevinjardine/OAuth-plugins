<?php
elgg_load_library('elgg:event_calendar');
function gcal_sync_do_sync($user_guid,$store,$server) {
	$events = event_calendar_get_personal_events_for_user($user_guid,500);
	$event_list = '';
	foreach ($events as $event) {
		$event_list .= elgg_view('gcal_sync/event_feed',array('entity'=>$event));
	}
	$feed = <<<__XML
<feed xmlns='http://www.w3.org/2005/Atom'
      xmlns:app='http://www.w3.org/2007/app'
      xmlns:batch='http://schemas.google.com/gdata/batch'
      xmlns:gCal='http://schemas.google.com/gCal/2005'
      xmlns:gd='http://schemas.google.com/g/2005'>
  <category scheme='http://schemas.google.com/g/2005#kind' term='http://schemas.google.com/g/2005#event' />
  $event_list
</feed>
__XML;
	// Get the list of the owned calendars
	$url = "https://www.google.com/calendar/feeds/default/owncalendars/full?alt=jsonc";
	$result = oauth_handle_request_with_redirects($url,$user_guid,$store,$server,'',TRUE);
	
	if (($result !== FALSE) && ($result['code'] == 200)) {
		$r = json_decode($result['body']);
		// assuming that first calendar returned is the primary calendar
		$cal = $r->data->items[0];			
		$feed_link = $cal->eventFeedLink.'/batch';
		
		$result = oauth_handle_post_with_redirects($feed_link,array(),$feed,$user_guid,$store,$server);
		
		// todo, add new events or update events with the same gcal_id.
		
		if (($result !== FALSE) && ($result['code'] == 200)) {
			error_log('calendar sync feed sent to Google Calendar: '.print_r($feed,TRUE));
			error_log('calendar sync response returned by Google Calendar: '.print_r($result,TRUE));
			return TRUE;
		}
	}
	return FALSE;
}
