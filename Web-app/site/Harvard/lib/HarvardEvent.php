<?php

require_once(LIB_DIR . '/TrumbaCalendarDataController.php');

class HarvardEvent extends TrumbaEvent
{
	public $HarvardCategories=array();
	
    public function get_category_from_name($name)
    {
    	$categories = HarvardEvent::get_all_categories();
    	foreach ($categories as $category) {
    		if ($name==$category->get_name()) {
    			return $category;
    		}
    	}
    	
    	return false;
    }

    public function get_all_categories() {
    
        static $categories=array();
        if ($categories) {
        	return $categories;
        }
    
        $filename = fopen($GLOBALS['siteConfig']->getVar('PATH_TO_EVENTS_CAT'), "r");

       
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

  public function get_attribute($attr)
  {
  	switch ($attr)
  	{
  		case 'description':
  			$fields = array(
  				$this->description
			);
			$other_fields = array('Event Template', 'Organization/Sponsor', 'Cost', 'Ticket Info');
			foreach ($other_fields as $field) {
				if ($value = $this->get_attribute($field)) {
					$fields[] = sprintf("%s: %s", $field, $value);
				}
  			}
  			return implode("\n\n", $fields);
  			break;
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
