<?php

class AthleticEvent implements KurogoObject {

    protected $ID;
    protected $sport;
    protected $sportFullName;
    protected $gender;
    protected $datetime;
    protected $opponent;
    protected $homeAway;
    protected $location;
    protected $score;
    protected $recap;
    
    protected $allday = false; //follow the time attrib
    protected $tba = false; //follow the time attrib
    
    public function filterItem($filters) {
        foreach ($filters as $filter=>$value) {
            switch ($filter)
            {
                case 'search': //case insensitive
                    return  (stripos($this->getSportFullName(), $value)!==FALSE) ||
                    (stripos($this->getOpponent(), $value)!==FALSE);
                    break;
            }
        }   
        
        return true;     
    }
        
    public function setID($id) {
        $this->ID = $id;
    }
    
    public function getID() {
        return $this->ID;
    }
    
    public function setSport($sport) {
        $this->sport = $sport;
    }
    
    public function getSport() {
        return $this->sport;
    }
    
    public function setSportFullName($fullName) {
        $this->sportFullName = $fullName;
    }
    
    public function getSportFullName() {
        return $this->sportFullName;
    }
    
    public function setGender($gender) {
        $this->gender = $gender;
    }
    
    public function getGender() {
        return $this->gender;
    }
    
    public function getDateTime() {
        return $this->datetime;
    }
    
    public function setDateTime(DateTime $time) {
        $this->datetime = $time;
    }
    
    public function setOpponent($opponent) {
        $this->opponent = $opponent;
    }
    
    public function getOpponent() {
        return $this->opponent;
    }
    
    public function setHomeAway($home_away) {
        $this->homeAway = $home_away;
    }
    
    public function getHomeAway() {
        return $this->homeAway;
    }
    
    public function setLocation($location) {
        $this->location = $location;
    }
    
    public function getLocation() {
        return $this->location;
    }
    
    public function setScore($score) {
        $this->score = $score;
    }
    
    public function getScore() {
        return $this->score;
    }
    
    public function setLinkToRecap($recap) {
        $this->recap = $recap;
    }
    
    public function getLinkToRecap() {
        return $this->recap;
    }
    
    public function setAllDay($result) {
        $this->allday = $result;
    }
    
    public function getAllDay() {
        return $this->allday;
    }
    
    public function setTBA($result) {
        $this->tba = $result;
    }
    
    public function getTBA() {
        return $this->tba;
    }
}