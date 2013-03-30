<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class EmergencyContactsListItem 
{

    protected $title;
    protected $subtitle;
    protected $phone;

    public function __construct($title, $subtitle, $phone) {
        $this->title = $title;
        $this->subtitle = !empty($subtitle) ? $subtitle : NULL;
        $this->phone = $phone;
    }

    public function getTitle() {
        return $this->title;
    } 

    public function getSubtitle() {
        return $this->subtitle;
    } 

    public function getPhone() {
        return $this->phone;
    } 

    /*
     * For now we only handle North American numbers
     * for the following two methods
     */
    public function getPhoneDelimitedByPeriods() {
        $phone = $this->phone;
        if(strlen($phone) == 10) {  // 10 digits in a north american number
            return substr($phone, 0, 3) . '.' . substr($phone, 3, 3) . '.' . substr($phone, 6, 4);
        } else {
            return $phone;
        }
    }

    public function getPhoneDialable() {
        return PhoneFormatter::getPhoneURL($this->phone);
    }
}
