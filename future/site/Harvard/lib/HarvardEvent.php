<?php

require_once(LIB_DIR . '/TrumbaCalendarDataController.php');

class HarvardEvent extends TrumbaEvent {
    protected $HarvardCategories = array();
    
    const emailPattern = "@^[A-Z0-9._%+-]+\@[A-Z0-9.-]+\.[A-Z]{2,6}$@i"; // From http://www.regular-expressions.info/email.html, should identify most email addresses
    const phonePattern = "@^\(?([0-9]{3}[\)\.\-])? ?[0-9]{3}[\.\-][0-9]{4}$@"; // assuming no international, and either "." or "-" as delimiters
    const urlPattern = "@^((https?://)?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.]*(\?\S+)?)?)*)$@"; // From http://snipplr.com/view/36992/improvement-of-url-interpretation-with-regex/

    private function getContactInfoArray()
    {
    	$ContactInfo = isset($this->TrumbaCustomFields['Contact Info']) ? $this->TrumbaCustomFields['Contact Info'] : '';
    	$info = array('email'=>array(), 'phone'=>array(), 'url'=>array(), 'text'=>array(), 'full'=>$ContactInfo);
		$data = preg_split("/(\\\)?,/", $ContactInfo);
		
		// For each value, check to see what it's most likely to be
		foreach ($data as $datum) {
		  $datum = trim($datum);
		  if (preg_match(self::emailPattern, $datum) > 0) {
			$info['email'][] = $datum;
		  } elseif (preg_match(self::phonePattern, $datum) > 0) {
			$info['phone'][] = preg_replace("/[ \(]/", "", preg_replace("/[\-\)]/", ".", $datum)); // Normalize things into "."s while we're in here
		  } elseif (preg_match(self::urlPattern, $datum) > 0) {
			$info['url'][] = $datum;
		  } else {
			$info['text'][] = $datum;
		  }
		}

		return $info;
    }

    public function apiArray()
    {
    
	 $arr= array (
	 	'id'=>crc32($this->get_uid()) >>1,
	 	'title'=>$this->get_summary(),
	 	'start'=>$this->get_start(),
	 	'end'=>$this->get_end()
	 );

    if ($urlLink = $this->get_url()) {
        $arr['url'] = $urlLink;
    }
    if ($location = $this->get_location()) {
        $arr['location'] = $location;
    }
    if ($description = $this->get_description()) {
        $arr['description'] = $description;
    }
    
    if ($custom = $this->TrumbaCustomFields) {
 		$custom['Contact Info'] = $this->getContactInfoArray();
 		$arr['custom'] = $custom;
	}
	 return $arr;
    
	}


    public function get_category_from_name($name) {
    	$categories = HarvardEvent::getEventCategories();
    	foreach ($categories as $category) {
    		if ($name == $category->get_name()) {
    			return $category;
    		}
    	}
    	
    	return false;
    }

    public function getEventCategories() {
    
        static $categories=array();
        if ($categories) {
            return $categories;
        }
    
        $filename = fopen($GLOBALS['siteConfig']->getVar('HARVARD_CALENDAR_CATEGORY_FILE'), "r");

       
        while(!feof($filename)) {
            
            $event_cat = new Harvard_Event_Category();
            
            $cat_name = fgets($filename);
            $cat_uid = fgets($filename);
            $cat_url = fgets($filename);
            
            $event_cat->set_name(trim(str_replace("\n", "", $cat_name)));
            $event_cat->set_cat_id(trim(str_replace("\n", "", $cat_uid)));
            $event_cat->set_url(trim(str_replace("\n", "", $cat_url)));

            $categories[] = $event_cat;
        }
    
        return $categories;
    }


  public function set_attribute($attr, $value, $params=null) {
    switch ($attr) {
        case 'Contact Info':
            $values = explode(',', iCalendar::ical_unescape_text($value));
            foreach ($values as $_value) {
                $_value = trim($_value);
                if (Validator::isValidEmail($_value)) {
                    $this->set_attribute('email', $_value);
                } elseif (Validator::isValidPhone($_value)) {
                    $this->set_attribute('phone', $_value);
                } elseif (Validator::isValidURL($_value)) {
                }
            }
            break;
        case 'Gazette Classification':
            $this->set_attribute('CATEGORIES', $value);
            $values = explode(',', $value);
            foreach ($values as $category_name) {
                $category_name = trim($category_name);
                if ($category = self::get_category_from_name($category_name)) {
                    $this->HarvardCategories[] = $category;
                } else {
                    error_log("HarvardEvent->set_attribute(): Cannot find category for $category_name");
                }
            }
            break;
        default:
            parent::set_attribute($attr, $value, $params);
            break;
    }
  }

  public function get_attribute($attr) {
  	switch ($attr) {
  		case 'Gazette Classification':
  			return $this->HarvardCategories;
  		default:
		  	return parent::get_attribute($attr);
  	}
  }

}

class Harvard_Event_Category {

  private static $name;
  private static $id;
  private static $urlLink;
  
  public function __toString() {
    return $this->get_name();
  }
  
  public function set_name($nm) {
    $this->name = $nm;
  }
  
  public function set_cat_id($uid) {
    $this->id = $uid;
  }
  
  public function set_url($url) {
    $this->urlLink = $url;
  }
  
  public function get_name() {
    return $this->name;
  }
  
  public function get_cat_id() {
    return $this->id;
  }
  
  public function get_url() {
    return $this->urlLink;
  }
}
