<?php
function gcal_sync_do_sync($user_guid,$store,$server) {
	$test_update = <<<__XML
<feed xmlns='http://www.w3.org/2005/Atom'
      xmlns:app='http://www.w3.org/2007/app'
      xmlns:batch='http://schemas.google.com/gdata/batch'
      xmlns:gCal='http://schemas.google.com/gCal/2005'
      xmlns:gd='http://schemas.google.com/g/2005'>
  <category scheme='http://schemas.google.com/g/2005#kind' term='http://schemas.google.com/g/2005#event' />
  <entry>
    <batch:id>Insert itemA</batch:id>
    <batch:operation type='insert' />
    <category scheme='http://schemas.google.com/g/2005#kind' term='http://schemas.google.com/g/2005#event' />
    <title type='text'>Event inserted via batch</title>
    <content type='text'>I think that this is the description.</content>
    <gd:where valueString='Alpha Centauri'></gd:where>
  	<gd:when startTime='2011-10-17T15:00:00.000Z' endTime='2011-10-17T17:00:00.000Z'></gd:when>    
  </entry>
</feed>
__XML;
	// Get the list of the owned calendars
	$url = "https://www.google.com/calendar/feeds/default/owncalendars/full?alt=jsonc";
	$result = oauth_handle_request_with_redirects($url,$user_guid,$store,$server);
	
	if (($result !== FALSE) && ($result['code'] == 200)) {
		$r = json_decode($result['body']);
		// assuming that first calendar returned is the primary calendar
		$cal = $r->data->items[0];			
		$feed_link = $cal->eventFeedLink.'/batch';
		
		$result = oauth_handle_post_with_redirects($feed_link,array(),$test_update,$user_guid,$store,$server);
		
		// todo, add new events or update events with the same gcal_id.
		
		if (($result !== FALSE) && ($result['code'] == 200)) {
			return TRUE;
		}
	}
	return FALSE;
}
