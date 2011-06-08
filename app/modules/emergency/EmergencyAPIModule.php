<?php

includePackage('Emergency');

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


    protected static function formatContacts($contacts) {
        $formattedContacts = array();
        foreach($contacts as $contact) {
             $formattedContacts[] = array(
                'title' => $contact->getTitle(),
                'subtitle' => $contact->getSubtitle(),
                'formattedPhone' => $contact->getPhoneDelimitedByPeriods(),
                'dialablePhone' => $contact->getPhoneDialable(),
             );
        }
        return $formattedContacts;
    }
}