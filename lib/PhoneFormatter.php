<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class PhoneFormatter
{
    public static function formatPhone($value) {

        // add the local area code if missing
        if (preg_match('/^\d{3}-\d{4}/', $value)) {
          $phone = Kurogo::getSiteVar('LOCAL_AREA_CODE').$value;
        }
        $phone = str_replace('-', '-&shy;', str_replace('.', '-', $value));
        
        return $phone;
    }

    public static function getPhoneURL($value) {

        // add the local area code if missing
        if (preg_match('/^\d{3}-?\d{4}/', $value)) {
          $phone = Kurogo::getSiteVar('LOCAL_AREA_CODE').$value;
        }
    
        // remove all non-word characters from the number
        $phone = 'tel:'.preg_replace('/\W/', '', $value);
        
        return $phone;
    }
}
