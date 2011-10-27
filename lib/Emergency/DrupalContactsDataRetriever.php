<?php

class DrupalContactsDataRetriever extends URLDataRetriever
{
    protected $DEFAULT_PARSER_CLASS = 'DrupalContactsDataParser';

    
    protected function loadContacts() {
        if(!$this->contactsLoaded) {
            $contacts = $this->getParsedData();
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

class DrupalContactsDataParser extends DrupalCCKDataParser
{
    protected function parseFieldEmergencyContact($fieldValueNode) {
        $fields = array();
        foreach($fieldValueNode->getElementsByTagName('div') as $divNode) {
            $fields[$divNode->getAttribute('class')] = $divNode->nodeValue;
        }

        return new EmergencyContactsListItem($fields['title'], $fields['subtitle'], $fields['phone']);
    }

    public function parseData($data) {
        if ($items = parent::parseData($data)) {
            return array(
                'primary' => $items[0]->getCCKField('primary-contacts'),
                'secondary' => $items[0]->getCCKField('secondary-contacts'),
            );
        }
    }
}