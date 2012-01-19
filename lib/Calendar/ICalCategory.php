<?php

class ICalCategory implements CalendarCategory {
    protected $id;
    protected $name;

    public function filterItem($filters) {
        foreach ($filters as $filter=>$value) {
            switch ($filter)
            {
                case 'search': //case insensitive
                    return  (stripos($this->getTitle(), $value)!==FALSE) ||
                            (stripos($this->getDescription(), $value)!==FALSE);
                    break;
            }
        }   
        
        return true;
    }

    public function getName() {
        return $this->name;
    }
    
    public function setName($name) {
        $this->name = $name;
    }

    public function setID($id) {
        $this->id = $id;
    }

    public function getID() {
        return $this->id;
    }
}
