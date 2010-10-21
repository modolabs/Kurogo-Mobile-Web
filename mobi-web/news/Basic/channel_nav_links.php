<?php
foreach(channels() as $index => $title) { 
  $page->nav_link("./?channel_id=$index", $title);
}
?>