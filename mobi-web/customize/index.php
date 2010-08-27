<?php


require_once WEBROOT . "/home/Modules.inc";
require_once "customize_lib.inc";

if ($page->branch == 'Webkit') {
  $template = $page->delta_file('index', 'html');
} else {
  $template = "$page->branch/index.html";
}

Modules::init($page->branch, $page->certs, $page->platform);

// iphone can customize without reloading
if($page->delta == 'iphone') {
  $modules = Modules::$primaryModules;

} else {
  $modules = getModuleOrder();
  $activemodules = getActiveModules();

  // Process the various possible actions
  if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'swap') {
    $module_1 = $_REQUEST['module1'];
    $module_2 = $_REQUEST['module2'];
    $position_1 = intval($_REQUEST['position1']);
    $position_2 = intval($_REQUEST['position2']);

    //make sure cookie is consistent with action
    // if so swap them
    if( ($modules[$position_1] == $module_1) && ($modules[$position_2] == $module_2) ) {
      $modules[$position_1] = $module_2;
      $modules[$position_2] = $module_1;
    }
  }

  if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'on') {
    $activemodules[] = $_REQUEST['module'];
  }

  if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'off') {
    $module = $_REQUEST['module'];
    if(in_array($module, $activemodules)) {
      array_splice($activemodules, array_search($module, $activemodules), 1);
    }
  }



  // reorder active modules to be consistent with the module-order
  $old_activemodules = $activemodules;
  $activemodules = array();
  foreach($modules as $module) {
    if(in_array($module, $old_activemodules)) {
      $activemodules[] = $module;
    }
  }
  $activemodules = Modules::add_required($activemodules);

  $old_modules = $modules;
  $modules = Modules::refreshAll($old_modules);
  $activemodules = Modules::refreshActive($old_modules, $activemodules);

  setModuleOrder($modules);
  setActiveModules($activemodules);

  $menu = array();
  $lastindex = count($modules)-1;
  foreach($modules as $index => $module) {
    
    $status = in_array($module, $activemodules) ? "on" : "off";

    // required modules can not be toggled on and off
    $toggle_action = NULL;
    if(!Modules::required($module)) {
      $toggle_action = in_array($module, $activemodules) ? "off" : "on";
    }

    $menu[] = array(
      "name" => $module,
      "status" => $status,
      "toggle_action" => $toggle_action,
      "toggle_url" => toggle_url($module, $toggle_action),
      "swap_up_url" => ($index > 0) ? 
      	swap_url($module, $index, $modules[$index-1], $index-1) : null,
      "swap_down_url" => ($index < $lastindex) ? 
      	swap_url($module, $index, $modules[$index+1], $index+1) : null
    );
  }

  $img_ext = ($page->branch == 'Basic') ? 'gif' : 'jpg';

  $check_imgs = Array(
    'on' => $page->img_tag('check-on', $img_ext, 'On', array('border' => '0')),
    'off' => $page->img_tag('check-off', $img_ext, 'Off', array('border' => '0')),
    );
  $up_img = $page->img_tag('up', $img_ext, 'Up', array('border' => '0'));
  $down_img = $page->img_tag('down', $img_ext, 'Down', array('border' => '0'));
}

require $template;

$page->help_off();
$page->output();

function toggle_url($module, $action) {
  if($action) {
    return "index.php?".http_build_query(array(
        "action" => $action,
        "module" => $module,
    ));
  }
}

function swap_url($module1, $position1, $module2, $position2) {

  if($module1 && $module2) {
    return "index.php?".http_build_query(array(
        "action" => 'swap',
        "module1" => $module1,
        "position1" => $position1,
        "module2" => $module2,
        "position2" => $position2,
    ));
  }
}

?>
