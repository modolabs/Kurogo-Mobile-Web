<?php

require_once LIB_DIR . "/ContactsList.php";

class INIFileContactsListDataController extends ContactsListDataController
{
    protected $DEFAULT_PARSER_CLASS = 'INIFileParser';
    protected $primarySection;
    protected $secondarySection;

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

    private $contactsLoaded = FALSE;

    protected function loadContacts() {
        if(!$this->contactsLoaded) {
            $iniData = $this->getParsedData();
            $this->primaryContacts = isset($iniData['primary']) ? self::createContactsList($iniData['primary']) : array();
            $this->secondaryContacts = isset($iniData['secondary']) ? self::createContactsList($iniData['secondary']) : array();
            $this->contactsLoaded = TRUE;
        }
    }
}