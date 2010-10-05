<?php
require_once "Home.inc";
require_once "Modules.inc";
require WEBROOT . "/customize/customize_lib.inc";

$page = Page::factory();
$page->module('home');

if ($_SERVER['REQUEST_METHOD'] == 'GET')
   PageViews::increment('home', $page->platform);

$whats_new_count = Home::$whats_new_count;
$top_item = Home::$whats_new->getTopItemName();

Modules::init($page->branch, $page->certs, $page->platform);

$old_modules = getModuleOrder();
$moduleorder = Modules::refreshAll($old_modules);
setModuleOrder($moduleorder);

$modules = getActiveModules();
$modules = Modules::refreshActive($old_modules, $modules);
$modules = Modules::add_required($modules);
setActiveModules($modules);

$page->prevent_caching('Basic');
$page->prevent_caching('Touch');
$page->cache();

ob_start();
  require $page->delta_file('index', 'html');
$html = ob_get_clean();
echo Page::compress_whitespace($html);


?>
