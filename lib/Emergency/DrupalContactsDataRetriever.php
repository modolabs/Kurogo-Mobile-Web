<?php

class DrupalContactsDataRetriever extends URLDataRetriever
{
    protected $DEFAULT_PARSER_CLASS = 'DrupalContactsDataParser';

    protected function loadContacts() {
        if(!$this->contactsLoaded) {
            $contacts = $this->getData();
            $this->primaryContacts = $contacts['primary'];
            $this->secondaryContacts = $contacts['secondary'];
            $this->contactsLoaded = TRUE;
        }
    }

    protected function init($args) {
        if (!isset($args['DRUPAL_SERVER_URL'])) {
            throw new KurogoConfigurationException("DRUPAL_SERVER_URL not set");
        }

        if (!isset($args['FEED_VERSION'])) {
            throw new KurogoConfigurationException("FEED_VERSION not set");
        }
        
        $args['BASE_URL'] = $args['DRUPAL_SERVER_URL'] .
            "/emergency-contacts-v" . $args['FEED_VERSION'];
        parent::init($args);
    }
}
