<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

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
