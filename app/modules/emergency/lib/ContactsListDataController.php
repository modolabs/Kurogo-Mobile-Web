<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/********* RETAINED FOR COMPATIBILITY *******/

abstract class ContactsListDataController extends DataController
{
    protected $DEFAULT_PARSER_CLASS = 'DrupalContactsDataParser';

    protected $cacheFolder = 'ContactsList';
    protected $primaryContacts = NULL;
    protected $secondaryContects = NULL;

    public static function getContactsListDataControllers() {
        return array(
            'DrupalContactsListDataController'=>'Drupal Module',
            'INIFileContactsListDataController'=>'INI File'
        );
    }

    public function getItem($id)
    {
        $this->loadContacts();
        foreach ($this->primaryContacts as $contact) {
            if($contact->getTitle() == $id) {
                return $contact;
            }
        }

        foreach ($this->secondaryContacts as $contact) {
            if($contact->getTitle() == $id) {
                return $contact;
            }
        }

        return NULL;
    }

    public function getPrimaryContacts() {
        $this->loadContacts();
        return $this->primaryContacts;
    }

    public function getSecondaryContacts() {
        $this->loadContacts();
        return $this->secondaryContacts;
    }

    public function hasSecondaryContacts() {
        $this->loadContacts();
        return (count($this->secondaryContacts) > 0);
    }

    public function getAllContacts() {
        $this->loadContacts();
        return array_merge($this->primaryContacts, $this->secondaryContacts);
    }

    abstract protected function loadContacts();

}
