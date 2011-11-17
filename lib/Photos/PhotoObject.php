<?php

class PhotoObject implements KurogoObject {
    protected $id;
    protected $title;
    /**
     * url 
     * alternate photo url
     * 
     * @var mixed
     * @access protected
     */
    protected $url;
    protected $description;
    /**
     * type 
     * privider type name
     * eg: flickr, picasa
     * 
     * @var string
     * @access protected
     */
    protected $type;
    protected $mime_type;
    /**
     * m_url 
     * middle image url
     * 
     * @var string
     * @access protected
     */
    protected $m_url;
    /**
     * t_url 
     * small/thumb image url
     * 
     * @var string
     * @access protected
     */
    protected $t_url;
    /**
     * l_url 
     * large image url
     * 
     * @var string
     * @access protected
     */
    protected $l_url;
    /**
     * photo_url 
     * origin image url
     *
     * @var string
     * @access protected
     */
    protected $photo_url;
    /**
     * date_taken 
     * photo taken datetime
     * 
     * @var datetime
     * @access protected
     */
    protected $date_taken;
    protected $author_name;
    protected $author_url;
    protected $author_id;
    protected $author_icon;
    /**
     * published 
     * publish photo datetime
     * 
     * @var datetime
     * @access protected
     */
    protected $published;
    protected $width;
    protected $height;
    protected $tags;
    
    /**
     * __call 
     * handle all standard set/get functions
     * 
     * @param mixed $name 
     * @param mixed $args 
     * @access public
     * @return mixed
     */
    public function __call($name, $args) {
        $action = substr($name, 0, 3);
        $do = $this->camelDown(substr($name, 3));
        if(!$do) {
            return false;
        }
        if($action == 'get') {
            if($this->$do === null) {
                return '';
            }
            return $this->$do;
        }
        if($action == 'set') {
            $this->$do = $args[0];
        }
        return false;
    }

    protected function camelDown($name) {
        $lowercase = strtolower($name);
        if(property_exists($this, $lowercase)) {
            return $lowercase;
        }
        $words = array();
        $word = '';
        for($i = 0;$i < strlen($name);$i ++) {
            // if upper case, previous is a word
            if(ord($name[$i]) < 97) {
                if(strlen($word)) {
                    $words[] = strtolower($word);
                }
                $word = $name[$i];
            }else {
                // new word starts
                $word .= $name[$i];
            }
            // if this is the last character, it is a word too
            if($i == (strlen($name) - 1)) {
                $words[] = strtolower($word);
            }
        }
        return implode($words, '_');
    }

    public function getID() {
        return $this->id;
    }

    public function setDateTaken(DateTime $date) {
        $this->date_taken = $date;
    }

    public function setPublished(DateTime $date) {
        $this->published = $date;
    }

    public function filterItem($filters) {
        foreach ($filters as $filter=>$value) {
            switch ($filter) {
                case 'search':
                    return (stripos($this->getTitle(), $value)!==FALSE) ||
                        (stripos($this->getDescription(), $value)!==FALSE);
                    break;
            }
        }

        return true;
    }
}
