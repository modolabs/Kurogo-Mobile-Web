<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require_once WEBROOT . "home/Home.php";
require_once WEBROOT . "home/Modules.php";
require_once WEBROOT . "page_builder/Page.php";
//require WEBROOT . "page_builder/counter.php";
require WEBROOT . "page_builder/page_tools.php";
require WEBROOT . "customize/customize_lib.php";

$page = Page::factory();
$page->module('home');

PageViews::increment('home', $page->platform);

$whats_new_count = Home::$whats_new_count;
$top_item = Home::$whats_new->getTopItemName();

Modules::init($page->branch, $page->certs, $page->platform);

$old_modules = getModuleOrder();
$moduleorder = Modules::refreshAll($old_modules, $page->branch);
setModuleOrder($moduleorder);

$modules = getActiveModules($page->branch);
$modules = Modules::refreshActive($old_modules, $modules, $page->branch);
$modules = Modules::add_required($modules, $page->branch);
setActiveModules($modules);

$all_modules = Modules::$default_order;

//$fh = fopen('/tmp/headers-' . time() . '.txt', 'w');
//fwrite($fh, str_replace('",', "\",\n", json_encode($_SERVER)) . '\n');
//fclose($fh);

$page->prevent_caching('Basic');
$page->prevent_caching('Touch');
$page->cache();

/*
function url($module) {
  // we rewrite urls for modules which require certificates
  // to make sure the user at least once sees the get certificates page
  $url = Modules::url($module);
  if( $_COOKIE['mitcertificate'] != 'yes' && Modules::certificate_required($module) ) {
    $url = "./certcheck.php?ref=" . urlencode($url) . "&name=" . urlencode(Modules::title($module)) . "&image=" . $module;
  }

  return $url;
}
*/

ob_start();
  require $page->branch . '/index.html';
$html = ob_get_clean();
echo Page::compress_whitespace($html);


?>
