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
                
                try {
                    if (isset($noticeConfig['CONTROLLER_CLASS'])) {
                        $modelClass = $noticeConfig['CONTROLLER_CLASS'];
                    } else {
                        $modelClass = isset($noticeConfig['MODEL_CLASS']) ? $noticeConfig['MODEL_CLASS'] : 'EmergencyNoticeDataModel';
                    }
                
                    $emergencyNoticeController = EmergencyNoticeDataModel::factory($modelClass, $noticeConfig);
                } catch (KurogoException $e) { 
                    $emergencyNoticeController = DataController::factory($noticeConfig['CONTROLLER_CLASS'], $noticeConfig);
                }
                
                
                $emergencyNotice = $emergencyNoticeController->getFeaturedEmergencyNotice();
                $response = array('notice' => $emergencyNotice);
                $this->setResponse($response);
                $this->setResponseVersion(1);
                break;

            case 'contacts':
                $contactsConfig = $config->getSection('contacts');

                try {
                    if (isset($contactsConfig['CONTROLLER_CLASS'])) {
                        $modelClass = $contactsConfig['CONTROLLER_CLASS'];
                    } else {
                        $modelClass = isset($contactsConfig['MODEL_CLASS']) ? $contactsConfig['MODEL_CLASS'] : 'EmergencyContactsDataModel';
                    }
                    
                    $contactsController = EmergencyContactsDataModel::factory($modelClass, $contactsConfig);
                } catch (KurogoException $e) { 
                    $contactsController = DataController::factory($contactsConfig['CONTROLLER_CLASS'], $contactsConfig);
                }

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