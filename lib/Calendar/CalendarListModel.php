<?php

includePackage('DataModel');
class CalendarListModel extends DataModel
{
    protected $cacheFolder = 'Calendar';
    protected $RETRIEVER_INTERFACE = 'CalendarListRetriever';
    
    public function getUserCalendars() {
        $this->setOption('action', 'userCalendars');
        return $this->getParsedData();
    }
    
    public function getResources() {
        $this->setOption('action', 'resources');
        return $this->getParsedData();
    }
    
}

interface CalendarListRetriever {
}