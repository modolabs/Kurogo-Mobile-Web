<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class DateFormatter
{
    const NO_STYLE=0;
    const SHORT_STYLE=1;
    const MEDIUM_STYLE=2;
    const LONG_STYLE=3;
    const FULL_STYLE=4;
    const DEFAULT_STYLE=null;

    public static function formatDateUsingFormat($date, $dateFormat, $timeFormat) {

        if ($date instanceOf DateTime) {
            $date = $date->format('U');
        }
        
        $string = '';
        if ($dateFormat) {

            // Work around lack of %e support in windows
            // http://php.net/manual/en/function.strftime.php
		    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
               $dateFormat = preg_replace('#(?<!%)((?:%%)*)%e#', '\1%#d', $dateFormat);
			}	
			
            $string .= strftime($dateFormat, $date);
            if ($timeFormat) {
                $string .= " ";
            }
        }
        
        if ($timeFormat) {
            // Work around lack of %P support in Mac OS X
            $lowercase = false;
            if (strpos($timeFormat, '%P') !== false) {
                $timeFormat = str_replace('%P', '%p', $timeFormat);
                $lowercase = true;
            }
            $formatted = strftime($timeFormat, $date);
            if ($lowercase) {
                $formatted = strtolower($formatted);
            }
            
            // Work around leading spaces that come from use of %l (but don't exist in date())
            if (strpos($timeFormat, '%l') !== false) {
                $formatted = trim($formatted);
            }
            
            $string .= $formatted;
        }
        
        return $string;
    }
    
    public static function formatDate($date, $dateStyle=self::DEFAULT_STYLE, $timeStyle=self::DEFAULT_STYLE) {
        if ($dateStyle === self::DEFAULT_STYLE) {
            $dateStyle = self::MEDIUM_STYLE;
        }

        if ($timeStyle === self::DEFAULT_STYLE) {
            $timeStyle = self::MEDIUM_STYLE;
        }
        
        $dateStyleConstant = self::getDateConstant($dateStyle);
        $timeStyleConstant = self::getTimeConstant($timeStyle);

        $timestamp = $date instanceOf DateTime ? $date->format('U') : $date;

        $dateFormat = null;
        if ($dateStyleConstant) {
            if (($dateStyleConstant=='SHORT_DATE_FORMAT') && date('Y') != date('Y', $timestamp)) {
                $dateStyleConstant .="_YEAR";
            }
            $dateFormat = Kurogo::getLocalizedString($dateStyleConstant);
        }
        
        $timeFormat = null;
        if ($timeStyleConstant) {
            $timeFormat = Kurogo::getLocalizedString($timeStyleConstant);
        }
        
        return self::formatDateUsingFormat($date, $dateFormat, $timeFormat);
    }

    private static function getTimeConstant($timeStyle) {
        switch ($timeStyle)
        {
            case self::NO_STYLE:
                return '';
            case self::SHORT_STYLE:
                return 'SHORT_TIME_FORMAT';
            case self::MEDIUM_STYLE:
                return 'SHORT_TIME_FORMAT';
            case self::LONG_STYLE:
                return 'LONG_TIME_FORMAT';
            case self::FULL_STYLE:
                return 'FULL_TIME_FORMAT';
        }
    }
    
    private static function getDateConstant($dateStyle) {
        switch ($dateStyle)
        {
            case self::NO_STYLE:
                return '';
            case self::SHORT_STYLE:
                return 'SHORT_DATE_FORMAT';
            case self::MEDIUM_STYLE:
                return 'SHORT_DATE_FORMAT';
            case self::LONG_STYLE:
                return 'LONG_DATE_FORMAT';
            case self::FULL_STYLE:
                return 'FULL_DATE_FORMAT';
        }
    }

    public static function formatDateRange(TimeRange $range, $dateStyle, $timeStyle) {
        $string = '';
        if ($range instanceOf DayRange) {
            $timeStyle = self::NO_STYLE;
        }
        
        $string = self::formatDate($range->get_start(), $dateStyle, $timeStyle);
        if ($range->get_end() && $range->get_end() != $range->get_start()) {
            if (date('Ymd', $range->get_start()) == date('Ymd', $range->get_end())) {
                $dateStyle = self::NO_STYLE;
            }
            
            if ($dateStyle != self::NO_STYLE || $timeStyle != self::NO_STYLE) {
              $string .= ($dateStyle ? ' - ' : '-') .self::formatDate($range->get_end(), $dateStyle, $timeStyle);
            }
        }
        
        return $string;
    }
}
