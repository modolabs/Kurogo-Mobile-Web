<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
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
        switch($this->command) {
            case 'notice':
                if ($noticeConfig = $this->getOptionalModuleSection('notice','feeds')) {

                    $modelClass = isset($noticeConfig['MODEL_CLASS']) ? $noticeConfig['MODEL_CLASS'] : 'EmergencyNoticeDataModel';
                    $emergencyNoticeController = EmergencyNoticeDataModel::factory($modelClass, $noticeConfig);

                    $emergencyNotice = $emergencyNoticeController->getFeaturedEmergencyNotice();
                    
                    if (isset($emergencyNotice['text'])) {
                        $emergencyNotice['text'] = Sanitizer::htmlStripTags2UTF8($emergencyNotice['text']);
                    }
                    if (isset($emergencyNotice['body'])) {
                        $emergencyNotice['body'] = Sanitizer::htmlStripTags2UTF8($emergencyNotice['body']);
                    }
                    
                    // if there is no emergency notice and a no-notice section
                    // is set, use it as a feed
                    if (!isset($emergencyNotice) && ($noEmergencyConfig = $this->getOptionalModuleSection('no-notice', 'feeds'))) {
                      $modelClass = isset($noEmergencyConfig['MODEL_CLASS']) ? $noEmergencyConfig['MODEL_CLASS'] : 'EmergencyNoticeDataModel';
                        if (!isset($noEmergencyConfig['NOTICE_EXPIRATION'])) {
                            $noEmergencyConfig['NOTICE_EXPIRATION'] = 0;
                        }
                      $noEmergencyNoticeController = EmergencyNoticeDataModel::factory($modelClass, $noEmergencyConfig);
                      $emergencyNotice = $noEmergencyNoticeController->getFeaturedEmergencyNotice();
                    }
                    
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
                if($contactsConfig = $this->getOptionalModuleSection('contacts', 'feeds')) {
                    $modelClass = isset($contactsConfig['MODEL_CLASS']) ? $contactsConfig['MODEL_CLASS'] : 'EmergencyContactsDataModel';
                    $contactsController = EmergencyContactsDataModel::factory($modelClass, $contactsConfig);

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
            if (!$subtitle) {
                $subtitle = '('.$value.')';
            }

            $formattedContacts[] = array(
                'title' => $contact->getTitle(),
                'subtitle' => $subtitle,
                'type' => 'phone',
                'url' => $contact->getPhoneDialable(),
             );
        }
        return $formattedContacts;
    }
}
