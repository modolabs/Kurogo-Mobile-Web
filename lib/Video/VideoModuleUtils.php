<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

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
    
    public static function getListItemForVideo(VideoObject $video, $section) {

        $desc = $video->getDescription();

        return array(
            'title'=>$video->getTitle(),
            'subtitle'=> "(" . VideoModuleUtils::getDuration($video->getDuration()) . ") " . $desc,
            'imgWidth'=>120,  
            'imgHeight'=>100,  
            'img'=>$video->getImage()
            );
    }
    
    // Valid formats are:
    //    long  - a long format for a detail view
    //           ("1 hour 32 minutes", "5 minutes 36 seconds", "12 seconds")
    //    short - a short format for left justified lists
    //            ("1h 32m", "5m 36s", "12s")
    //    time  - a time-like format for right-justified lists
    //            ("1:32:16", "5:36", "0:12")
    public static function getDuration($prop_length, $format='time') {
        if (!$prop_length) { $prop_length = 0; }
        $hours = intval($prop_length / 3600);
        $minutes = intval($prop_length / 60) % 60;
        $seconds = $prop_length % 60;
        
        $duration = '';
        switch ($format) {
            case 'short':
            case 'long':
                $suffixes = array(
                    'long' => array(
                        'h' => ' hour',
                        'm' => ' minute',
                        's' => ' second',
                        'plural' => 's',
                    ),
                    'short' => array(
                        'h' => 'h',
                        'm' => 'm',
                        's' => 's',
                        'plural' => '',
                    ),
                );
                $suffix = $suffixes[$format];
                
                $parts = array();
                if ($hours > 0) {
                    $parts[] = $hours.$suffix['h'].($hours > 1 ? $suffix['plural']: '');
                }
                if ($minutes > 0) {
                    $parts[] = $minutes.$suffix['m'].($minutes > 1 ? $suffix['plural']: '');
                }
                if ($seconds > 0 && $hours <= 0) {
                    $parts[] = $seconds.$suffix['s'].($seconds > 1 ? $suffix['plural']: '');
                }
                $duration = implode(' ', $parts);
                break;
            
            case 'time':
            default:
                if ($hours > 0) {
                    $duration = sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
                } else {
                    $duration = sprintf('%d:%02d', $minutes, $seconds);
                }
                break;
        }
        
        return $duration;
    }
}
