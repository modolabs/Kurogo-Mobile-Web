<?php

require_once 'lib_constants.inc';
require_once 'html2text.php';

class Libraries{

    public static function getAllLibrariesOrArchives($librariesOrArchives) {

        $institutes = array();


        $xmlURLPath = URL_LIBRARIES_INFO;
        $xml = file_get_contents($xmlURLPath);

        if ($xml == "") {
            // if failed to grab xml feed, then run the generic error handler
            throw new DataServerException('COULD NOT GET XML');
        }

        $xml_obj = simplexml_load_string($xml);
        
        $count = 0;
         foreach ($xml_obj->institution as $institution) {

             $timeOpen = $institution->hoursofoperation[0]->dailyhours->hours[0];

             if (strlen($timeOpen) <= 0) {
                 $timeOpen = $institution->hoursofoperation->hoursofoperation->dailyhours->hours[0];
             }

             if (strlen($timeOpen) <= 0) {
                 $timeOpen = "N/A";
             }

             
             $isOpen = self::isOpenNow($timeOpen);
            
             $type = explode(":",$institution->type[0]);
             $type = $type[0];
             
             if ($type == $librariesOrArchives){
                 $institute = array();

                 $name = explode(":", $institution->name[0]);
                 $id = explode(":", $institution->id[0]);
                 $address = explode(":", $institution->location->address[0]);
                 $longitude = explode(":",$institution->location->longitude);
                 $latitude = explode(":", $institution->location->latitude);
                 $hrsOpenToday = explode(":", $timeOpen);
                 
                 $institute['name'] = $name[0];
                 $institute['id'] = $id[0];
                 $institute['type'] = $type;
                 $institute['address'] = $address[0];
                 $institute['latitude'] = $latitude[0];
                 $institute['longitude'] = $longitude[0];
                 $institute['hrsOpenToday'] = $hrsOpenToday[0];
                 $institute['isOpenNow'] = $isOpen;

                 $institutes[] = $institute;
             }

        }
           return $institutes;
       }



       public static function isOpenNow($timeString) {

           if ($timeString == 'closed')
               return "NO";

           else{

               $timeArray= explode('-', $timeString);
               $startString = $timeArray[0];
               $endString = $timeArray[1];

               $isStartAM = self::isAM($startString);
               $isStartPM = self::isPM($startString);

               $isEndAM = self::isAM($endString);
               $isEndPM = self::isPM($endString);

               if ($isStartAM){
                   $sPos = strpos($startString, 'am');
                   $startTime = substr($startString, 0, $sPos);

               }
               else if ($isStartPM){
                   $sPos = strpos($startString, 'pm');
                   $startTime = substr($startString, 0, $sPos);

               }

               if ($isEndAM){
                   $ePos = strpos($endString, 'am');
                   $endTime = substr($endString, 0, $sPos);

               }
               else if ($isEndPM){
                   $ePos = strpos($endString, 'pm');
                   $endTime = substr($endString, 0, $ePos);
               }


               if ((!isStartAM) && (!isStartPM) && (($isEndAM) || ($isEndPM))){
                   if ($isEndAM)
                       $isStartAM = true;
                   else
                       $isStartPM = true;
               }

               else  if ((!$isEndAM) && (!$isEndPM) && (($isStartAM) || ($isStartPM))){
                   if ($isStartAM)
                       $isEndAM = true;
                   else
                       $isEndPM = true;
               }


               $startTimeArray = explode(':',$startTime);
               $startHrs = (int)$startTimeArray[0];
               $startMins = 0;
               if(count($startTimeArray) > 1){
                   $startMins = (int)$startTimeArray[1];
               }

               $endTimeArray = explode(':',$endTime);
               $endHrs = (int)$endTimeArray[0];
               $endMins = 0;
               if(count($endTimeArray) > 1){
                   $endMins = (int)$endTimeArray[1];
               }

               if ($isStartPM)
                   $startHrs += 12;

               if ($isEndPM)
                   $endHrs += 12;


               $start = 60*((int)$startHrs) +((int)$startMins);
               $end = 60*((int)$endHrs) + ((int)$endMins);

               $nowHrs = date('G');
               $nowMins = date('i');

               $now = 60*$nowHrs + $nowMins;
              /* print("satrtHrs = ");
               print($startHrs);
               print("startMins = ");
               print($startMins);
               print('<br>');
               print("endHrs = ");
               print($endHrs);
               print("endMins = ");
               print($endMins);
               print('<br>');
               */


               if (($now > $start) && ($now < $end)){
                   return "YES";
               }
               else
                   return "NO";
           }
       
       }


       public static function isPM ($timeString) {

        $posPM = strpos($timeString,'pm');

                if($posPM === false) {
                    return false;
                }
                else {
                    return true;
                }
       }

       public static function isAM ($timeString) {

        $posAM = strpos($timeString,'am');

                if($posAM === false) {
                    return false;
                }
                else {
                    return true;
                }
       }


}

?>
