<?php
/**
 * BaseObject
 * BaseObject implemented magic __call function and camelDown function
 * 
 * @package 
 * @version $id$
 * @copyright 2011 Symbio
 * @author Jeffery You <jianfeng.you@symbio.com> 
 * @license 
 */
class BaseObject {
    protected $id;
    protected $title;
    
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
}
