<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class EmergencyWebModule extends WebModule implements HomeAlertInterface
{
    protected $id='emergency';
    protected $contactsController;
    protected $emergencyNoticeController;
    protected $nonEmergencyNoticeController;
    
    public function getHomeScreenAlert() {
        return $this->emergencyNoticeController ? $this->emergencyNoticeController->getFeaturedEmergencyNotice() : null;
    }

    protected function initialize() {
        $config = $this->loadFeedData();
        
        if(isset($config['contacts'])) {
            $modelClass = isset($config['contacts']['MODEL_CLASS']) ? $config['contacts']['MODEL_CLASS'] : 'EmergencyContactsDataModel';
            $this->contactsController = EmergencyContactsDataModel::factory($modelClass, $config['contacts']);
        }
        
        if(isset($config['notice'])) {
            $modelClass = isset($config['notice']['MODEL_CLASS']) ? $config['notice']['MODEL_CLASS'] : 'EmergencyNoticeDataModel';
            $this->emergencyNoticeController = EmergencyNoticeDataModel::factory($modelClass, $config['notice']);
        }
        
        if(isset($config['no-notice'])){
            $modelClass = isset($config['no-notice']['MODEL_CLASS']) ? $config['no-notice']['MODEL_CLASS'] : 'EmergencyNoticeDataModel';
            if (!isset($config['no-notice']['NOTICE_EXPIRATION'])) {
                $config['no-notice']['NOTICE_EXPIRATION'] = 0;
            }
            $this->nonEmergencyNoticeController = EmergencyNoticeDataModel::factory($modelClass, $config['no-notice']);
        }
                
    }

    protected function assignPaneNotice($notice){
        $this->assign('hasNotice', true);
        $this->assign('title', $notice['title']);
        $this->assign('text', $notice['text']);
        $this->assign('date', $notice['date']);
        $this->assign('timeFormat', $this->getLocalizedString('MEDIUM_TIME_FORMAT'));
        $this->assign('dateFormat', $this->getLocalizedString('MEDIUM_DATE_FORMAT'));
    }

    protected function initializeForPage() {
        $this->assign('subTitleNewline', $this->getOptionalModuleVar('CONTACTS_SUBTITLE_NEWLINE', false));
        // construct controllers

        switch($this->page) {
            case 'pane':
                if ($this->ajaxContentLoad) {
                    if ($this->emergencyNoticeController && ($notice = $this->emergencyNoticeController->getFeaturedEmergencyNotice())){
                        $this->assignPaneNotice($notice);
                    }elseif ($this->nonEmergencyNoticeController && ($notice = $this->nonEmergencyNoticeController->getFeaturedEmergencyNotice())){
                        $this->assignPaneNotice($notice);
                    }else{
                        $this->assign('hasNotice', false);
                    }
                    $this->assign('emergencyModuleURL', $this->buildURL('index'));
                }
                $this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
                break;
                
            case 'index':
                $contactNavListItems = array();
                if($this->contactsController !== NULL) {
                    foreach ($this->contactsController->getPrimaryContacts() as $contact) {
                        $contactNavListItems[] = self::contactNavListItem($contact);
                    }

                    if ($this->contactsController->hasSecondaryContacts()) {
                        $contactNavListItems[] = array(
                            'title' => $this->getModuleVar('MORE_CONTACTS'),
                            'url' => $this->buildBreadcrumbURL('contacts', array()),
                        );
                    }
                    $this->assign('contactNavListItems', $contactNavListItems);
                }
                $this->assign('hasContacts', (count($contactNavListItems) > 0));
                
                $hasEmergencyFeed = ($this->emergencyNoticeController !== NULL);
                $hasNonEmergencyFeed = ($this->nonEmergencyNoticeController !== NULL);
                $this->assign('hasEmergencyFeed', $hasEmergencyFeed);
                $this->assign('hasNonEmergencyFeed', $hasNonEmergencyFeed);
                if ($hasEmergencyFeed) {
                    $emergencyFeedEmpty = TRUE;
                    
                    $emergencyNotices = $this->emergencyNoticeController->getAllEmergencyNotices();
                    if ($emergencyNotices) {
                        foreach ($emergencyNotices as &$notice) {
                            if ($notice['body']) {
                                $notice['url'] = $this->buildBreadcrumbURL('notice', array());
                            } elseif ($notice['link']) {
                                $notice['url'] = $this->buildExternalURL($notice['link']);
                                $notice['external'] = true;
                            }
                        }
                        
                        $emergencyFeedEmpty = FALSE;
                        $this->assign('emergencyNotices', $emergencyNotices);
                        $this->assign('timeFormat', $this->getLocalizedString('MEDIUM_TIME_FORMAT'));
                        $this->assign('dateFormat', $this->getLocalizedString('MEDIUM_DATE_FORMAT'));
                    } elseif ($this->nonEmergencyNoticeController) {
                        $nonEmergencyNotices = $this->nonEmergencyNoticeController->getAllEmergencyNotices();
                        if ($nonEmergencyNotices) {
                            foreach ($nonEmergencyNotices as &$notice) {
                                if ($notice['body']) {
                                    $notice['url'] = $this->buildBreadcrumbURL('notice', array());
                                } elseif ($notice['link']) {
                                    $notice['url'] = $this->buildExternalURL($notice['link']);
                                    $notice['external'] = true;
                                }
                            }
                            $this->assign('emergencyNotices', $nonEmergencyNotices);
                            $this->assign('timeFormat', $this->getLocalizedString('MEDIUM_TIME_FORMAT'));
                            $this->assign('dateFormat', $this->getLocalizedString('MEDIUM_DATE_FORMAT'));
                        }
                    }
                    $this->assign('emergencyFeedEmpty', $emergencyFeedEmpty);
                }
                
                break;
            case 'notice':
                $hasEmergencyFeed = ($this->emergencyNoticeController !== NULL);
                $hasNonEmergencyFeed = ($this->nonEmergencyNoticeController !== NULL);
                $this->assign('hasEmergencyFeed', $hasEmergencyFeed);
                $this->assign('hasNonEmergencyFeed', $hasNonEmergencyFeed);
                if ($hasEmergencyFeed) {
                    $emergencyFeedEmpty = TRUE;

                    if ($emergencyNotice = $this->emergencyNoticeController->getFeaturedEmergencyNotice()) {
                        $this->assign('emergencyNotice', $emergencyNotice);
                        $this->assign('timeFormat', $this->getLocalizedString('MEDIUM_TIME_FORMAT'));
                        $this->assign('dateFormat', $this->getLocalizedString('MEDIUM_DATE_FORMAT'));
                    
                    } else {
                        $nonEmergencyNotice = $this->nonEmergencyNoticeController->getFeaturedEmergencyNotice();
                        if ($nonEmergencyNotice) {
                            $this->assign('emergencyNotice', $nonEmergencyNotice);
                            $this->assign('timeFormat', $this->getLocalizedString('MEDIUM_TIME_FORMAT'));
                            $this->assign('dateFormat', $this->getLocalizedString('MEDIUM_DATE_FORMAT'));
                        }
                    }
                    $this->assign('emergencyFeedEmpty', $emergencyFeedEmpty);
                }
                break;            

            case 'contacts':
                $contactNavListItems = array();
                foreach ($this->contactsController->getAllContacts() as $contact) {
                    $contactNavListItems[] = self::contactNavListItem($contact);
                }
                $this->assign('contactNavListItems', $contactNavListItems);
                break;
        }
        
    }


    protected static function contactNavListItem($contact) {
        $subtitle = $contact->getSubtitle() ? $contact->getSubtitle() : '('.$contact->getPhoneDelimitedByPeriods().')';
        return array(
            'title' => $contact->getTitle(),
            'subtitle' => $subtitle,
            'url' => $contact->getPhoneDialable(),
            'class' => 'phone',
        );
    }
}
