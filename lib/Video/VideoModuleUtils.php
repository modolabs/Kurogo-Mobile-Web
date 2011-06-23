<?php

class VideoModuleUtils 
{
    public static function getSectionsFromFeeds($feeds) {        
         $sections = array();
         foreach ($feeds as $index => $feedData) {
              $sections[] = array(
                'value'    => $index,
                'title'    => $feedData['TITLE']
              );
         }         
         return $sections;
    }
    
    public static function getDuration($prop_length) {
        if (!$prop_length) {
            return "";
        } elseif ($prop_length<60) {
            return "0:". $prop_length;
        } else {
            $mins = intval($prop_length / 60);
            $secs = $prop_length % 60;
            if($secs<10) {
				return $mins . ":0" . $secs;
			} else { 
				return $mins . ":" . $secs;
        	}
		}
    }
    
}
