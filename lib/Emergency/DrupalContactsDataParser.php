<?php

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
            $item = $items[0];
            $primaryContacts = $item->getCCKField('primary-contacts');
            $secondaryContacts = $item->getCCKField('secondary-contacts');
            return array(
                'primary' => $primaryContacts ? $primaryContacts : array(),
                'secondary' => $secondaryContacts ? $secondaryContacts : array()
            );
        }
    }
}
