<?php

includePackage('DataController');
class CalendarListController extends ExternalDataController
{
    protected $cacheFolder = 'Calendar';
    protected $RETRIEVER_INTERFACE = 'CalendarListRetriever';
    
    public function getUserCalendars() {
        $this->setAction('userCalendars');
        return $this->getParsedData();
    }
    
    public function getResources() {
        $this->setAction('resources');
        return $this->getParsedData();
    }
    
}

interface CalendarListRetriever {
}