<?php

class Modules {

  public static $default_order = array();

  /*
   * for modules with no subtitle, just enter the name of the module
   * for mobules with a subtitle, provide Array("MODULE_NAME", "SUBITTLE")
   */
  private static $regular_modules = array(
    "people"          => "People Directory",
    "map"             => "Campus Map",
    "shuttleschedule" => "Shuttle Schedule",
    "calendar"        => "Events Calendar",
    "stellar"         => array("Stellar", "class info"),
    "careers"         => "Student Careers",
    "emergency"       => "Emergency Info",
    "3down"           => array("3DOWN", "service status"),
    "techcash"        => "TechCASH (BETA)",
    "libraries"       => "Libraries (BETA)",
    "links"           => "Useful Links",
  );

  /* these modules are to be shown in the extras sections 
   * for touch and basic pages
   * they will be treated like ordinary modules for webkit phones
   */
  private static $extras = array(
    "sms"             => "MIT SMS (BETA)",
    "certificates"    => "MIT Cerfiticates",
    "webmitedu"       => "Full MIT Website",
    "mobile-about"    => "About this Site",
    "customize"       => "Customize Home",
    "download"        => "Download",
  );

  /*
   * default urls are the folder by the same name as the module
   */
  private static $non_default_urls = array(
    "certificates"    => "http://ca.mit.edu/",
    "webmitedu"       => "http://web.mit.edu/",
    //"about"           => "../mobile-about/",
    ///"preferences"     => "../customize/",
  );

  // main function that initializes the module list for the phone
  public static function init($branch, $certs, $platform=NULL) {
    if (!count(self::$default_order)) {
      foreach(self::full_list($branch) as $module => $name) {
	// modules are shown by default, turned off if 1/2 conditions fail
	$show_module = TRUE;

	// condition 1: platform dependent modules require platform
	if (!self::is_right_platform($platform, $module)) {
	  $show_module = FALSE;
	}

	// condition 2: restricted modules require certs
	if (in_array($module, self::$restricted)) {
	  if (!$certs) {
	    $show_module = FALSE;
	  }
	}

	if ($show_module) {
	  self::$default_order[] = $module;
	}
      }
    }
    return self::$default_order;
  }

  /* required modules */
  // these cannot be turned on/off in preferences
  // however, according to this code nothing is required
  // even though 'mobile-about' and 'customize' should be required
  // they are hard coded instead
  private static $required = array('mobile-about', 'customize'); 

  public static function required($module) {
    return in_array($module, self::$required);
  }

  public static function add_required($modules) {
    foreach(self::$default_order as $module) {
      if(self::required($module) && !in_array($module, $modules)) {
        $modules[] = $module;
      }
    }
    return $modules;
  }
  /* end of required modules section */

  /* restricted modules */
  // restricted by phone's certificate support
  private static $restricted = array("certificates", "techcash");
  private static $certificate_required = array("techcash");

  public static function certificate_required($module) {
    return in_array($module, self::$certificate_required);
  }

  // restricted by platform
  private static $platform_dependent = Array(
    'certificates' => Array('iphone'),
    'techcash' => Array('iphone', 'computer'), // should be shown for all platforms that support certs, but winmo/blackberry support not enough yet
    'download' => Array('blackberry'),
    'libraries' => Array('iphone'),
    'webmitedu' => Array('iphone', 'android', 'webos', 'winmo', 'blackberry', 'palm', 'symbian', 'computer'),
    );

  public static function is_right_platform($platform, $module) {
    if (array_key_exists($module, self::$platform_dependent)) {
      if (!in_array($platform, self::$platform_dependent[$module])) {
	return FALSE;
      }
    }
    return TRUE;
  }
  /* end of restricted modules section */

  // new apps will be highlighted on the iphone
  private static $new = array();

  public static function new_apps() {
    return self::$new;
  }

  public static function new_apps_count() {
    return count(self::$new);
  }
  // end of new apps section

  public static function title($module) {
    $regular_modules = self::full_list();
    $name = $regular_modules[$module];
    return self::make_title($name, True);
  }

  // for touch homescreen, make long titles appear on two lines
  public static function wrap_title($module) {
    $max_chars = 10;
    $title_words = explode(' ', self::title($module));
    $title_lines = Array();
    while (count($title_words) > 0) {
      $title_lines[] = array_shift($title_words);
      while (count($title_words) 
	     && self::approx_length(end($title_lines)) + self::approx_length($title_words[0]) < $max_chars) {
	$title_lines[count($title_lines) - 1] .= ' ' . array_shift($title_words);
	if (! count($title_words)) break 2;
      }
    }
    return implode('<br/>', $title_lines);
  }

  private static function approx_length($str) {
    return strlen(preg_replace('/[A-HKM-QUW-Z]/', '..', $str));
  }

  public static function subtitle($module) {
    $regular_modules = self::full_list();
    $name = $regular_modules[$module];
    return self::make_title($name, False);
  }

  public static function make_title($title_data, $title_mode=True) {
    if(is_array($title_data)) {
      $index = $title_mode ? 0 : 1;
      return $title_data[$index];
    } else {
      return $title_mode ? $title_data : NULL;
    }
  }

  public static function full_list($branch=NULL) {
    if($branch !== "Basic") {
      return array_merge(self::$regular_modules, self::$extras);
    }
    return self::$regular_modules;
  }
  
  public static function url($module) {
    if(isset(self::$non_default_urls[$module])) {
      return self::$non_default_urls[$module];
    } else {
      return "../$module/";
    }
  }

  public static function filterExists($modules) {
    $filtered = array();

    foreach($modules as $module) {
      if(in_array($module, self::$default_order)) {
	$filtered[] = $module;
      }
    }
    return $filtered;
  }

  private static function newModules($modules) {
    $new = array();    

    // add any modules not already in the list
    foreach(self::$default_order as $module) {
      if(!in_array($module, $modules)) {
        $new[] = $module;
      }
    }
    return $new;
  }

  // update the module list, if the users cookies 
  // are inconsistent with the services module list
  public static function refreshAll($all) {
    $refreshed = self::filterExists($all);
    return self::add_new_items($refreshed, self::newModules($all));
  }

  // update the module list, if the users cookies 
  // are inconsistent with the services module list
  public static function refreshActive($all, $active) {
    $refreshed = self::filterExists($active);
    return self::add_new_items($refreshed, self::newModules($all));
  }


  private static function add_new_items($old, $new) {
    foreach($new as $item) {
      if(!in_array($item, $old)) {
        $old[] = $item;
      }
    }
    return $old;
  }
}

?>