<?php

Kurogo::includePackage('Emergency');

class EmergencyAPIModule extends APIModule {

    protected $id = 'emergency';
    protected $vmin = 1;
    protected $vmax = 1;
    
    protected function initializeForCommand() {
        $config = $this->getConfig('feeds');

        switch($this->command) {
            case 'notice':
                $noticeConfig = $config->getSection('notice');
                $emergencyNoticeController = DataController::factory('EmergencyNoticeDataController', $noticeConfig);
                $emergencyNotice = $emergencyNoticeController->getLatestEmergencyNotice();
                $response = array('notice' => $emergencyNotice);
                $this->setResponse($response);
                $this->setResponseVersion(1);
                break;

            case 'contacts':
                $contactsConfig = $config->getSection('contacts');
                $contactsController = DataController::factory($contactsConfig['CONTROLLER_CLASS'], $contactsConfig);

                $response = array(
                    'primary' => self::formatContacts($contactsController->getPrimaryContacts()),
                    'secondary' => self::formatContacts($contactsController->getSecondaryContacts()),
                );
                $this->setResponse($response);
                $this->setResponseVersion(1);
                break;

        }
    }

    // TODO: support other types of contacts besides phone
    protected static function formatContacts($contacts) {
        $formattedContacts = array();
        foreach($contacts as $contact) {
            $value = $contact->getPhoneDelimitedByPeriods();
            $subtitle = $contact->getSubtitle();
            if ($subtitle) {
                $subtitle = $contact->getSubtitle().' ('.$value.')';
            } else {
                $subtitle = '('.$value.')';
            }

            $formattedContacts[] = array(
                'title' => $contact->getTitle(),
                'subtitle' => $subtitle,
                'type' => 'phone',
                'url' => 'tel:'.$contact->getPhoneDialable(),
             );
        }
        return $formattedContacts;
    }
}