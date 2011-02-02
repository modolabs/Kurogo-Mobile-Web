<?php

require_once(LIB_DIR . '/ContactsList.php');

class DrupalContactsDataParser extends DrupalCCKDataParser
{

    protected function parseFieldEmergencyContact($fieldValueNode) {
        $fields = array();
        foreach($fieldValueNode->getElementsByTagName('div') as $divNode) {
            $fields[$divNode->getAttribute('class')] = $divNode->nodeValue;
        }

        return new ContactsListItem($fields['title'], $fields['subtitle'], $fields['phone']);
    }

    public function parseData($data) {
        $items = parent::parseData($data);
        return array(
            'primary' => $items[0]->getCCKField('primary-contacts'),
            'secondary' => $items[0]->getCCKField('secondary-contacts'),
        );
    }
}