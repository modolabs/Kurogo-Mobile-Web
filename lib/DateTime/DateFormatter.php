<?php

class DateFormatter
{
    const NO_STYLE=0;
    const SHORT_STYLE=1;
    const MEDIUM_STYLE=2;
    const LONG_STYLE=3;
    const FULL_STYLE=4;

    public static function formatDate($date, $dateStyle, $timeStyle) {
        $dateStyleConstant = self::getDateConstant($dateStyle);
        $timeStyleConstant = self::getTimeConstant($timeStyle);
        
        if ($date instanceOf DateTime) {
            $date = $date->format('U');
        }
        
        $string = '';
        if ($dateStyleConstant) {
            $string .= strftime(Kurogo::getLocalizedString($dateStyleConstant), $date);
            if ($timeStyleConstant) {
                $string .= " ";
            }
        }
        
        if ($timeStyleConstant) {
            // Work around lack of %P support in Mac OS X
            $format = Kurogo::getLocalizedString($timeStyleConstant);
            $lowercase = false;
            if (strpos($format, '%P') !== false) {
                $format = str_replace('%P', '%p', $format);
                $lowercase = true;
            }
            $formatted = strftime($format, $date);
            if ($lowercase) {
                $formatted = strtolower($formatted);
            }
            
            // Work around leading spaces that come from use of %l (but don't exist in date())
            if (strpos($format, '%l') !== false) {
                $formatted = trim($formatted);
            }
            
            $string .= $formatted;
        }
        
        return $string;
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
