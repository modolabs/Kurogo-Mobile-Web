<?php

abstract class CalendarListController
{
    abstract public function getUserCalendars();
    abstract public function getResources(); 
    
    public static function factory($controllerClass, $args=array()) {
        $args = is_array($args) ? $args : array();

        if (!class_exists($controllerClass)) {
            throw new KurogoConfigurationException("Class $controllerClass not defined");
        }
        
        $controller = new $controllerClass;
        
        if (!$controller instanceOf CalendarListController) {
            throw new KurogoConfigurationException("$controllerClass is not a subclass of CalendarListController");
        }
        
        $controller->init($args);
        
        return $controller;
    }
    
    protected function init($args) {
    }
}

