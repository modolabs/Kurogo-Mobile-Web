<?php

require_once 'lib_constants.inc';
require_once 'html2text.php';

class Libraries{


    public static function getAllLibraries() {

       $xmlURLPath = URL_LIBRARIES_INFO;

      $filenm = LIB_DIR .'/librariesInfo.xml';
      if (file_exists($filenm) && ((time() - filemtime($filenm)) < LIB_DIR_CACHE_TIMEOUT)) {
      }
      else {
          $handle = fopen($filenm, "w");
          fwrite($handle, file_get_contents($xmlURLPath));
          //$urlString = $filenm;
      }

      $xml = file_get_contents($filenm);

        if ($xml == "") {
            // if failed to grab xml feed, then run the generic error handler
            throw new DataServerException('COULD NOT GET XML');
        }

        $xml_obj = simplexml_load_string($xml);
        return self::getAllLibrariesOrArchives('Library', $xml_obj);
    }


    public static function getAllArchives() {

        $xmlURLPath = URL_LIBRARIES_INFO;

      $filenm = LIB_DIR .'/librariesInfo.xml';
      if (file_exists($filenm) && ((time() - filemtime($filenm)) < LIB_DIR_CACHE_TIMEOUT)) {
      }
      else {
          $handle = fopen($filenm, "w");
          fwrite($handle, file_get_contents($xmlURLPath));
          //$urlString = $filenm;
      }

      $xml = file_get_contents($filenm);

        if ($xml == "") {
            // if failed to grab xml feed, then run the generic error handler
            throw new DataServerException('COULD NOT GET XML');
        }

        $xml_obj = simplexml_load_string($xml);
        return self::getAllLibrariesOrArchives('archive', $xml_obj);
    }


    public static function getAllLibrariesOrArchives($librariesOrArchives, $xml_obj) {

        $institutes = array();
       
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
             /*print($institution->id[0]);
             print("     ");
             print($timeOpenToSend);
             print("<br>");*/
            
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
                 $institute['address'] = HTML2TEXT($address[0]);
                 $institute['latitude'] = $latitude[0];
                 $institute['longitude'] = $longitude[0];
                 $institute['hrsOpenToday'] = $hrsOpenToday[0];
                 $institute['isOpenNow'] = $isOpen;

                 $institutes[] = $institute;
             }

        }
           return $institutes;
       }



       public static function getOpenNow() {

        $xmlURLPath = URL_LIBRARIES_INFO;

        $filenm = LIB_DIR . '/librariesInfo.xml';
        if (file_exists($filenm) && ((time() - filemtime($filenm)) < LIB_DIR_CACHE_TIMEOUT)) {

        } else {
            $handle = fopen($filenm, "w");
            fwrite($handle, file_get_contents($xmlURLPath));
            //$urlString = $filenm;
        }

        $xml = file_get_contents($filenm);

        if ($xml == "") {
            // if failed to grab xml feed, then run the generic error handler
            throw new DataServerException('COULD NOT GET XML');
        }

        $xml_obj = simplexml_load_string($xml);

        $institutes = array();

         foreach ($xml_obj->institution as $institution) {

             $timeOpen = $institution->hoursofoperation[0]->dailyhours->hours[0];

             if (strlen($timeOpen) <= 0) {
                 $timeOpen = $institution->hoursofoperation->hoursofoperation->dailyhours->hours[0];
             }

             if (strlen($timeOpen) <= 0) {
                 $timeOpen = "N/A";
             }

             $isOpen = self::isOpenNow($timeOpen);

                 $institute = array();

                 $name = explode(":", $institution->name[0]);
                 $id = explode(":", $institution->id[0]);
                 $type = explode(":",$institution->type[0]);

                 $institute['name'] = $name[0];
                 $institute['id'] = $id[0];
                 $institute['type'] = $type[0];
                 $institute['isOpenNow'] = $isOpen;

                 $institutes[] = $institute;
             }
           return $institutes;     
       }



       public static function getLibraryDetails($libId, $libName){

        $xmlURLPath = URL_LIB_DETAIL_BASE . $libId;

        $filenm = LIB_DIR . '/lib-' .$libId. $libName . '.xml';
        if (file_exists($filenm) && ((time() - filemtime($filenm)) < LIB_DIR_CACHE_TIMEOUT*24)) {

        } else {
            $handle = fopen($filenm, "w");
            fwrite($handle, file_get_contents($xmlURLPath));
            //$urlString = $filenm;
        }

        $xml = file_get_contents($filenm);

        //$xml = file_get_contents($xmlURLPath);

        if ($xml == "") {
            // if failed to grab xml feed, then run the generic error handler
            throw new DataServerException('COULD NOT GET XML');
        }

        $xml_obj = simplexml_load_string($xml);

           return self::getLibOrArchiveDetails($xml_obj, $libName);
       }



       public static function getArchiveDetails($archiveId, $archiveName){

        $xmlURLPath = URL_ARCHIVE_DETAIL_BASE . $archiveId;

        $filenm = LIB_DIR . '/archive-' .$archiveId. $archiveName . '.xml';
        if (file_exists($filenm) && ((time() - filemtime($filenm)) < LIB_DIR_CACHE_TIMEOUT*24)) {

        } else {
            $handle = fopen($filenm, "w");
            fwrite($handle, file_get_contents($xmlURLPath));
            //$urlString = $filenm;
        }

        $xml = file_get_contents($filenm);
         
        //$xml = file_get_contents($xmlURLPath);

        if ($xml == "") {
            // if failed to grab xml feed, then run the generic error handler
            throw new DataServerException('COULD NOT GET XML');
        }

        $xml_obj = simplexml_load_string($xml);

           return self::getLibOrArchiveDetails($xml_obj, $archiveName);
       }

       

       public static function getLibOrArchiveDetails($xml_obj, $name){

           $details = array();

           $institution = $xml_obj;

                $primaryName = explode(":",$institution->names->primaryname[0]);
                $primaryName = $primaryName[0];

                $nameToReturn = $name;
                
                if ($primaryName == $name)
                    $nameToReturn;

                else{

                    foreach($institution->names->alternatename as $possibleName){

                        $possibleName = explode(":", $possibleName[0]);
                        $possibleName = $possibleName[0];

                        if ($possibleName == $name){
                            $nameToReturn = $possibleName;
                            break;
                        }
                    }
                }

                 $type = explode(":",$institution->type[0]);
                 $type = $type[0];
                 $id = explode(":", $institution->id[0]);
                 $id = $id[0];

                 $directionArray = explode(":",$institution->location->directions[0]);
                 $direction = $directionArray[0];
                 for ($j=1; $j < count($directionArray); $j++){
                    $direction = $direction .":" .$directionArray[$j];
                 }
                    
                 $direction = HTML2TEXT($direction);
                    
                 $ur = explode(":", $institution->url);
                  if (count($ur) > 1)
                    $url = $ur[0].':'.$ur[1];
                  else
                      $url = $ur[0];


                  $email = explode(":", $institution->emailaddresses[0]->email->emailaddress[0]);
                  $email = $email[0];

                  $phoneNumberArray = array();

                  foreach ($institution->phonenumbers->phonenumber as $phone) {
                      $phoneNumberEntry = array();

                      $phoneNumber = explode(":",$phone->number[0]);
                      $phoneNumber = $phoneNumber[0];

                      $description = explode(":",$phone->description);
                      $description = $description[0];

                      if (strlen($description) == 0)
                          $description = '';

                      $phoneNumberEntry['description'] = HTML2TEXT($description);
                      $phoneNumberEntry['number'] = $phoneNumber;

                      $phoneNumberArray[] = $phoneNumberEntry;
                  }


                  $weeklyHours = array();

                    foreach ($institution->dailyhours as $hours) {
                        $openHours = array();

                        $date = explode(":",$hours->date[0]);
                        $date = $date[0];

                        $day = date("l", mktime(0, 0, 0, substr($date, 4, 2), substr($date, 6, 2), substr($date, 0, 4)));

                        $dayHours = "";
                        $dayHrs = explode(":",$hours->hours[0]);
                        $dayHours = $dayHrs[0];
                            for ($i=1; $i < count($dayHrs); $i++)
                                $dayHours = $dayHours . ":" .$dayHrs[$i];
                        //$dayHours = $dayHours[0];

                        $openHours['date'] = $date;
                        $openHours['day'] = $day;
                        $openHours['hours'] = $dayHours;

                        $weeklyHours[] = $openHours;
                  }


                  $details['name'] = $nameToReturn;
                  $details['id'] = $id;
                  $details['type'] = $type;
                  $details['directions'] = $direction;
                  $details['website'] = $url;
                  $details['email'] = $email;
                  $details['phone'] = $phoneNumberArray;
                  $details['weeklyHours'] = $weeklyHours;

            return $details;
           
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

               $startTime = $startString;
               $endTime = $endString;

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

               if (($isStartPM) && ($startHrs != 12))
                   $startHrs += 12;

               if (($isEndPM) && ($startHrs != 12))
                   $endHrs += 12;


               $posNOON_start = strpos($startString, "noon");

               if ($posNOON_start > 0)
                   $startHrs = 12;

               $posMIDNIGHT_start = strpos($startString, "midnight");

               if ($posMIDNIGHT_start > 0)
                   $startHrs = 0;

               $posNOON_end = strpos($endString, "noon");

               if ($posNOON_end > 0)
                   $endHrs = 12;

               $posMIDNIGHT_end= strpos($endString, "midnight");

               if ($posMIDNIGHT_start > 0)
                   $endHrs = 24;


               $start = 60*((int)$startHrs) +((int)$startMins);
               $end = 60*((int)$endHrs) + ((int)$endMins);

               $nowHrs = date('G');
               $nowMins = date('i');

               $now = 60*$nowHrs + $nowMins;

               /*print("startTime = ");
               print($startTime);
               print('<br>');               
               print("startTimeArray = ");
               print_r($startTimeArray);
               print('<br>');

               print("endTime = ");
               print($endTime);
               print('<br>');
               print("endTimeArray = ");
               print_r($endTimeArray);
               print('<br>');

               print("satrtHrs = ");
               print($startHrs);
               print("startMins = ");
               print($startMins);
               print('<br>');
               print("endHrs = ");
               print($endHrs);
               print("endMins = ");
               print($endMins);
               print('<br>');
               print("nowHrs = ");
               print($nowHrs);
               print("nowMins = ");
               print($nowMins);
               print('<br>');

               if ($isStartPM)
               print("isStartPM = YES");
               
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
