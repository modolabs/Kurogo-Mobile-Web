<?php

class Modules {

  public static $primaryModules = array();

  // non-customizable
  public static $secondaryModules = array();

  // TODO: create a Module class so we don't
  // have to bother with so many array functions

  public static $module_data = Array(
    'people' => Array(
      'title' => 'People',
      ),
    'map' => Array(
      'title' => 'Map',
      ),
    'news' => Array(
      'title' => 'News',
      ),
    'calendar' => Array(
      'title' => 'Events',
      ),
    'courses' => Array(
      'title' => 'Courses',
      ),
    'dining' => Array(
      'title' => 'Student Dining',
      ),
    'shuttleschedule' => Array(
      'title' => 'Shuttle Tracker',
      ),
    'links' => Array(
      'title' => 'Schools',
      'extra' => TRUE,
      ),
    'fullweb' => Array(
      'title' => 'Full Website',
      'extra' => TRUE,
      'url' => 'http://www.harvard.edu/?fullsite=yes'
     ),
    'customize' => Array(
      'title' => 'Customize Home',
      'extra' => TRUE,
      ),
    'mobile-about' => Array(
      'title' => 'About',
      'extra' => TRUE,
      ),
  );

  // main function that initializes the module list for the phone
  public static function init($branch, $certs, $platform=NULL) {
    if (!count(self::$primaryModules)) {

      foreach (self::$module_data as $module => $data) {

	// if restricted, make sure we have correct platform
	// if require certs, make sure we have certs

	if ((!array_key_exists('restricted', $data) || in_array($platform, $data['restricted']))
	    && (!array_key_exists('certs_required', $data) || $certs))
        {
          if (!array_key_exists('extra', $data)) {
            self::$primaryModules[] = $module;
          } else {
            self::$secondaryModules[] = $module;
          }
	}

      }
    }
    return self::$primaryModules;
  }

  public static function required($module) {
    return array_key_exists('required', self::$module_data[$module]);
  }
  
  public static function add_required($modules) {
    foreach(self::$primaryModules as $module) {
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
    if (array_key_exists('subtitle', self::$module_data[$module])) {
      return self::$module_data[$module]['subtitle'];
    }
  }
  
  public static function url($module, $certs=FALSE) {
    $url = "../$module/";
    $module_data = self::$module_data[$module];
    if (array_key_exists('url', $module_data)) {
      $url = $module_data['url'];
    }

    if (array_key_exists('certs_required', $module_data)) {
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
      if(in_array($module, self::$primaryModules)) {
	$filtered[] = $module;
      }
    }
    return $filtered;
  }

  private static function newModules($modules) {
    $new = array();    

    // add any modules not already in the list
    foreach(self::$primaryModules as $module) {
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
