<?php

require_once LIB_DIR . "/ContactsList.php";

class INIFileContactsListDataController extends ContactsListDataController
{
    protected $primarySection;
    protected $secondarySection;

    protected function init($args) {
        $this->primaryContacts = isset($args['primary']) ? self::createContactsList($args['primary']) : array();

        $this->secondaryContacts = isset($args['secondary']) ? self::createContactsList($args['secondary']) : array();
    }

    private static function createContactsList($iniData) {
        $contactsList = array();
        if(isset($iniData['title'])) {
            foreach($iniData['title'] as $index => $title) {
                $contactsList[] = new ContactsListItem(
                    $iniData['title'][$index],
                    $iniData['subtitle'][$index],
                    $iniData['phone'][$index]
            	 );
            }
        }
        return $contactsList;
    }

    protected function loadContacts() {
        // contacts are loaded in init form the $args
        // nothing to do
    }
}