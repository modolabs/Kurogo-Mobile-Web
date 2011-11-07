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
            return array(
                'primary' => $items[0]->getCCKField('primary-contacts'),
                'secondary' => $items[0]->getCCKField('secondary-contacts'),
            );
        }
    }
}
