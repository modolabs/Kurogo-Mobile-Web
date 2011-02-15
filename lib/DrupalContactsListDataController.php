<?php

class DrupalContactsListDataController extends ContactsListDataController
{
    protected $DEFAULT_PARSER_CLASS = 'DrupalContactsDataParser';
    protected $cacheFileSuffix = "rss";
    protected $contactsLoaded = FALSE;

    protected function loadContacts() {
        if(!$this->contactsLoaded) {
            $data = $this->getData();
            $contacts = $this->parseData($data);
            $this->primaryContacts = $contacts['primary'];
            $this->secondaryContacts = $contacts['secondary'];
            $this->contactsLoaded = TRUE;
        }
    }

    protected function init($args) {
        $args['BASE_URL'] = $args['DRUPAL_SERVER_URL'] .
            "/emergency-contacts-v" . $args['FEED_VERSION'];
        parent::init($args);
    }
}