<?

function getModuleOrder() {
  return explode(",", $_COOKIE["moduleorder"]);
}

function getActiveModules() {
  if(!isset($_COOKIE["activemodules"])) {
    return Modules::$default_order;
  } elseif($_COOKIE["activemodules"]=="NONE") {
    return array();
  } else {
    return explode(",", $_COOKIE["activemodules"]);
  }
}

function setModuleOrder($modules) {
  setcookie("moduleorder", implode(",", $modules), time() + MODULE_ORDER_COOKIE_LIFESPAN, HTTPROOT);
}

function setActiveModules($modules) {
  if(count($modules) > 0) {
    setcookie("activemodules", implode(",", $modules), time() + MODULE_ORDER_COOKIE_LIFESPAN, HTTPROOT);
  } else {
    setcookie("activemodules", "NONE", time() + MODULE_ORDER_COOKIE_LIFESPAN, HTTPROOT);
  }
}

?>