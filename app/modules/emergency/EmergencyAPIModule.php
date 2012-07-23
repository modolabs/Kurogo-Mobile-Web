<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class EmergencyAPIModule extends APIModule {

    protected $id = 'emergency';
    protected $vmin = 1;
    protected $vmax = 2;
    
    protected function initializeForCommand() {
        $config = $this->getConfig('feeds');

        switch($this->command) {
            case 'notice':
                if ($noticeConfig = $config->getOptionalSection('notice')) {

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
                    $noticeEnabled = true;
                } else {
                    // Config section 'notice' not set, there is not notice
                    $emergencyNotice = null;
                    $noticeEnabled = false;
                }
                $response = array('notice' => $emergencyNotice, 'noticeEnabled' => $noticeEnabled);
                $this->setResponse($response);
                $this->setResponseVersion(2);
                break;

            case 'contacts':
                if($contactsConfig = $config->getOptionalSection('contacts')) {
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
                }else {
                    $response = new stdClass();
                }
                $this->setResponse($response);
                $this->setResponseVersion(2);
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
