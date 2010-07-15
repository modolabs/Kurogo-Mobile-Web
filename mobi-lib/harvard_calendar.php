<?php

$categories = array();

class Harvard_Event_Category {

private static $name;
private static $id;
private static $urlLink;

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


class Harvard_Calendar {

    public function get_categories($pathToFile) {

        $filename = fopen($pathToFile, "r");

       
        while(!feof($filename)) {
            
            $event_cat = new Harvard_Event_Category();
            
            $cat_name = fgets($filename);
            $cat_uid = fgets($filename);
            $cat_url = fgets($filename);
            
            $event_cat->set_name(str_replace("\n", "", $cat_name));
            $event_cat->set_cat_id(str_replace("\n", "", $cat_uid));
            $event_cat->set_url(str_replace("\n", "", $cat_url));

            $categories[] = $event_cat;
        }
    
        return $categories;

        
    }

}

?>
