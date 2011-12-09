<?php

includePackage('Calendar');
class AthleticEventsDataModel extends CalendarDataModel {

    public static function getAthleticScheduleRetrievers() {
        return array(
            'CSTVDataRetriever'=>'CSTV',
            'URLDataRetriever'=>'Basic URL'
        );
    }

    protected function init($args)
    {
        parent::init($args);
    }

        
}