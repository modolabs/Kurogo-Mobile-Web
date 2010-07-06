<?php

require_once '../page_builder/url_decoder.inc';

$decimal_id = expand_id($_REQUEST['short_id']);

header("Location: " . NEWSOFFICE_STORY_URL . $decimal_id);

?>
