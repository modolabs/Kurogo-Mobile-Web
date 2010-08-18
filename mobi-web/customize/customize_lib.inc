<?php

function getModuleOrder() {
  if(!isset($_COOKIE["moduleorder"])) {
    return Modules::$primaryModules;
  } else {
    return explode(",", $_COOKIE["moduleorder"]);
  }
}

function getActiveModules() {
  if(!isset($_COOKIE["activemodules"])) {
    return Modules::$primaryModules;
  } elseif($_COOKIE["activemodules"]=="NONE") {
    return array();
  } else {
    return explode(",", $_COOKIE["activemodules"]);
  }
}

function setModuleOrder($modules) {
  global $ini;
  setcookie("moduleorder", implode(",", $modules), time() + $ini['MODULE_ORDER_COOKIE_LIFESPAN'], HTTPROOT);
}

function setActiveModules($modules) {
  global $ini;
  if(count($modules) > 0) {
    setcookie("activemodules", implode(",", $modules), time() + $ini['MODULE_ORDER_COOKIE_LIFESPAN'], HTTPROOT);
  } else {
    setcookie("activemodules", "NONE", time() + $ini['MODULE_ORDER_COOKIE_LIFESPAN'], HTTPROOT);
  }
}

?>
