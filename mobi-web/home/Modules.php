<?php

class Modules {

  public static $default_order = array();

  public static $module_data = Array(
    'people' => Array(
      'title' => 'People Directory',
      ),
    'map' => Array(
      'title' => 'Campus Map',
      ),
    'shuttleschedule' => Array(
      'title' => 'Shuttle Schedule',
      ),
    'news' => Array(
      'title' => 'MIT News',
      ),
    'calendar' => Array(
      'title' => 'Calendar',
      ),
    'stellar' => Array(
      'title' => 'Stellar',
      'subtitle' => 'class info',
      ),
    'emergency' => Array(
      'title' => 'Emergency Info',
      ),
    '3down' => Array(
      'title' => '3DOWN',
      'subtitle' => 'service status',
      ),
    'techcash' => Array(
      'title' => 'TechCASH (BETA)',
      'restricted' => Array('iphone', 'computer'),
      'certs_required' => TRUE,
      ),
    'libraries' => Array(
      'title' => 'Libraries (BETA)',
      'restricted' => Array('iphone'),
      ),
    'links' => Array(
      'title' => 'Useful Links',
      ),
    'certificates' => Array(
      'title' => 'MIT Certificates',
      'restricted' => Array('iphone'),
      'extra' => TRUE,
      'url' => 'http://ca.mit.edu',
     ),
    'webmitedu' => Array(
      'title' => 'Full MIT Website',
      'extra' => TRUE,
      'url' => 'http://web.mit.edu',
      'restricted' => Array('iphone', 'android', 'webos', 'winmo', 'blackberry', 'palm', 'symbian', 'computer'),
      ),
    'mobile-about' => Array(
      'title' => 'About this site',
      'extra' => TRUE,
      'required' => TRUE,
      ),
    'customize' => Array(
      'title' => 'Customize Home',
      'extra' => TRUE,
      'required' => TRUE,
      ),
    'download' => Array(
      'title' => 'Download',
      'extra' => TRUE,
      'restricted' => Array('blackberry'),
      ),
    );

  // main function that initializes the module list for the phone
  public static function init($branch, $certs, $platform=NULL) {
    if (!count(self::$default_order)) {

      foreach (self::$module_data as $module => $data) {
	// include if pass 3 criteria:
	// if restricted, we have correct platform
	// if require certs, we have certs
	// if not regular module, we have grid layout
	if ((!$data['restricted'] || in_array($platform, $data['restricted']))
	    && (!$data['certs_required'] || $certs)
	    && (!$data['extra'] || ($branch !== 'Basic'))) {
	  self::$default_order[] = $module;
	}
      }

    }
    return self::$default_order;
  }

  public static function required($module) {
    return array_key_exists('required', self::$module_data[$module]);
  }
  
  public static function add_required($modules) {
    foreach(self::$default_order as $module) {
      if(self::required($module) && !in_array($module, $modules)) {
        $modules[] = $module;
      }
    }
    return $modules;
  }

  public static function is_right_platform($platform, $module) {
    $data = self::$module_data[$module];

    return (!$data['restricted'] || in_array($platform, $data['restricted']));
  }

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
    return self::$module_data[$module]['title'];
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
    return self::$module_data[$module]['subtitle'];
  }

  public static function url($module, $certs=FALSE) {
    $url = "../$module/";
    if (array_key_exists('url', self::$module_data[$module])) {
      $url = self::$module_data[$module]['url'];
    }

    if (self::$module_data[$module]['certs_required']) {
      if (!$certs) {
	$url = "";
      } elseif ($_COOKIE['mitcertificate'] != 'yes') {
	$url = "./certcheck.php?ref=" . urlencode($url) . "&name=" . urlencode(Modules::title($module)) . "&image=" . $module;
      }
    }

    return $url;
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


  private static function add_new_items($moduleList, $newModules) {
    foreach($newModules as $newModule) {
      if(!in_array($newModule, $moduleList)) {
	$index = array_search($newModule, self::$default_order);
	if (is_int($index) && $index > 0) {
	  array_splice($moduleList, $index, 0, $newModule);
	} else {
	  // we don't know where to add the module so add at the end
	  $moduleList[] = $newModule;
        }
      }
    }
    return $moduleList;
  }
}

?>