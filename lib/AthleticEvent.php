<?php

class AthleticEvent extends XMLElement {

    protected $name = 'event';
    protected $eventID;
    protected $sport;
    protected $sportFullName;
    protected $datetime;
    protected $opponent;
    protected $homeAway;
    protected $location;
    protected $score;
    protected $recap;
    
    protected $allday = false; //follow the time attrib
    protected $tba = false; //follow the time attrib
    
    public function getID() {
        return $this->getEventID();
    }
    
    public function getEventID() {
        return $this->eventID;
    }
    public function getSport() {
        return $this->sport;
    }
    
    public function getSportFullName() {
        return $this->sportFullName;
    }
    
    public function getDateTime() {
        return $this->datetime;
    }
    
    public function setDateTime(DateTime $time) {
        $this->datetime = $time;
    }
    
    public function getOpponent() {
        return $this->opponent;
    }
    
    public function getHomeAway() {
        return $this->homeAway;
    }
    
    public function getLocation() {
        return $this->location;
    }
    
    public function getScore() {
        return $this->score;
    }
    
    public function getLinkToRecap() {
        return $this->linkToRecap;
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
    
    protected function elementMap() {
        return array(
            'SPORT'=>'sport',
            'SPORT_FULLNAME'=>'sportFullName',
            'OPPONENT'=>'opponent',
            'HOME_VISITOR'=>'homeAway',
            'LOCATION'=>'location',
            'OUTCOME_SCORE'=>'score',
            'RECAP'=>'recap'
        );
    }
    
    function __construct($attribs) {
        if (isset($attribs['ID']) && $attribs['ID']) {
            $this->eventID = $attribs['ID'];
        }
        $this->setAttribs($attribs);
    }
}