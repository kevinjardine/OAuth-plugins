<?php
$entity = $vars['entity'];
$title = htmlspecialchars($entity->title);
$description = htmlspecialchars($entity->description);
$venue = htmlspecialchars($entity->venue);
$start_time = date('c',$entity->start_date);
$end_time = date('c',$entity->real_end_time);

$event_feed = <<<__FEED
<entry>
    <batch:id>Insert item{$entity->guid}</batch:id>
    <batch:operation type='insert' />
    <category scheme='http://schemas.google.com/g/2005#kind' term='http://schemas.google.com/g/2005#event' />
    <title type='text'>$title</title>
    <content type='text'>$description</content>
    <gd:where valueString='$venue'></gd:where>
  	<gd:when startTime='$start_time' endTime='$end_time'></gd:when>    
  </entry>
__FEED;

echo $event_feed;
