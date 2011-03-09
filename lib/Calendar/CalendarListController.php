<?php

abstract class CalendarListController
{
    abstract public function getUserCalendars();
    abstract public function getResources(); 
    protected $user;
    
    public static function factory($controllerClass, $args=array()) {
        $args = is_array($args) ? $args : array();

        if (!class_exists($controllerClass)) {
            throw new Exception("Class $controllerClass not defined");
        }
        
        $controller = new $controllerClass;
        
        if (!$controller instanceOf CalendarListController) {
            throw new Exception("$controllerClass is not a subclass of CalendarListController");
        }
        
        $controller->init($args);
        
        return $controller;
    }
    
    protected function setUser(User $user) {
        $this->user = $user;
    }

    protected function setSession(Session $session) {
        $this->setUser($session->getUser());
    }
    
    protected function init($args) {
        if (isset($args['USER'])) {
            $this->setUser($args['USER']);
        }
        
        if (isset($args['SESSION'])) {
            $this->setSession($args['SESSION']);
        }
        
    }
}

