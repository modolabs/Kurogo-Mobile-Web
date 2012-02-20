<?php

includePackage('DataModel');
class CalendarListModel extends DataModel
{
    protected $cacheFolder = 'Calendar';
    protected $RETRIEVER_INTERFACE = 'CalendarListRetriever';
    
    public function getUserCalendars() {
        $this->setOption('action', 'userCalendars');
        return $this->getData();
    }
    
    public function getResources() {
        $this->setOption('action', 'resources');
        return $this->getData();
    }
    
}

interface CalendarListRetriever {
}