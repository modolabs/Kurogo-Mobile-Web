<?php
/*
 * WEBROOT specifies the root directory of the Mobile Web
 * in relation to THIS COMPUTER.
 * on red hat machines this is usually somewhere in /var/www/
 */
define('WEBROOT', dirname(__FILE__) . '/../');
define('PAGE_HEADER', WEBROOT . 'page_builder/page_header.php');
define('HELP_HEADER', WEBROOT . 'page_builder/help.php');
require_once WEBROOT . "../mobi-config/web_constants.php";

?>
