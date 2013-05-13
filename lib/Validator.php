<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/**
  * @package Core
  */

/**
  * @package Core
  */
class Validator
{
    public static function isValidMonth($value) {
        return preg_match("/^\d+$/", $value) && $value >= 1 && $value <=12;
    }

    public static function isValidDay($value, $month=1, $year=null) {
        $year = is_null($year) ? date('Y') : $year;
        $time = @mktime(0,0,0, $month, $value, $year);
        return $time === false ? false : true;
    }

    public static function isValidEmail($value) 
    {
        if (!is_string($value)) {
            return false;
        }
        $pattern = "/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/";
        return preg_match($pattern, $value);
    }

    public static function isValidPhone($value, &$bits=null)
    {
        // Validates phone numbers that contain +, -, ., (, ) characters only (digits too)
        //      any other delimeters will return false
        //      + symbol can only be in first position specifying international phone numbers
        // after stripping delimeters, phone numbers must be 7-15 digits in length and, if international, contain a 1-3 digit country code


        if (!is_scalar($value)) {
            return false;
        }

        // Pattern match for incorrect delimeters. return false if any match is found
        if (preg_match('/[^\s\-\.\+\(\)0-9]/', $value)) {
            return false; // value is using invalid delimeters
        }

        // be sure + symbol does not appear anywhere other than the 0 index
        if( strpos($value, '+', 1)) {
            return false; // value contains '+' symbol in invalid position
        }
        
        $phone = preg_replace('/[^0-9\+]/', '', $value); // strip any unwanted characters from value in order to validate raw digits

        $pattern = '/^(\+\d{1,3})?\d{7,15}$/';
        // pattern matches international or domestic phone numbers
        // country code needs '+' prefix and contains 1-3 digits. phone number must contain between 7 and 15 digits
    
        return preg_match($pattern, $phone, $bits);
    }

    public static function isValidURL($value)
    {
        if (!is_scalar($value)) {
            return false;
        }

        $pattern = "@^((https?://)?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.]*(\?\S+)?)?)*)$@"; // From http://snipplr.com/view/36992/improvement-of-url-interpretation-with-regex/
        return preg_match($pattern, $value);
    }
}

