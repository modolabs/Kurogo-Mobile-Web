<?php

Kurogo::includePackage('Emergency');

class EmergencyWebModule extends WebModule 
{
    protected $id='emergency';
    protected $contactsController;
    protected $emergencyNoticeController;

    protected function initialize() {
        $config = $this->loadFeedData();
        
        if(isset($config['contacts'])) {

            try {
                if (isset($config['contacts']['CONTROLLER_CLASS'])) {
                    $modelClass = $config['contacts']['CONTROLLER_CLASS'];
                } else {
                    $modelClass = isset($config['contacts']['MODEL_CLASS']) ? $config['contacts']['MODEL_CLASS'] : 'EmergencyContactsDataModel';
                }
                
                $this->contactsController = EmergencyContactsDataModel::factory($modelClass, $config['contacts']);
            } catch (KurogoException $e) { 
                $this->contactsController = DataController::factory($config['contacts']['CONTROLLER_CLASS'], $config['contacts']);
            }
            
        }
        
        if(isset($config['notice'])) {
            try {
                if (isset($config['notice']['CONTROLLER_CLASS'])) {
                    $modelClass = $config['notice']['CONTROLLER_CLASS'];
                } else {
                    $modelClass = isset($config['notice']['MODEL_CLASS']) ? $config['notice']['MODEL_CLASS'] : 'EmergencyNoticeDataModel';
                }
            
                $this->emergencyNoticeController = EmergencyNoticeDataModel::factory($modelClass, $config['notice']);
            } catch (KurogoException $e) { 
                $this->emergencyNoticeController = DataController::factory($config['notice']['CONTROLLER_CLASS'], $config['notice']);
            }
        }    
                
    }

    protected function initializeForPage() {
        // construct controllers

        switch($this->page) {
            case 'pane':
                $hasEmergencyFeed = ($this->emergencyNoticeController !== NULL);
                $this->assign('hasEmergencyFeed', $hasEmergencyFeed);
                if($hasEmergencyFeed) {
                    $emergencyNotice = $this->emergencyNoticeController->getLatestEmergencyNotice();
                    
                    if($emergencyNotice !== NULL) {
                        $this->assign('emergencyFeedEmpty', FALSE);             
                        $this->assign('title', $emergencyNotice['title']);
                        $this->assign('content', $emergencyNotice['text']);
                        $this->assign('date', $emergencyNotice['date']);
                        $this->assign('dateFormat', $this->getLocalizedString('MEDIUM_DATE_FORMAT'));
                    } else {
                        $this->assign('emergencyFeedEmpty', TRUE);
                    }
                }
                break;
                
            case 'index':
                $contactNavListItems = array();
                if($this->contactsController !== NULL) {
                    foreach($this->contactsController->getPrimaryContacts() as $contact) {
                        $contactNavListItems[] = self::contactNavListItem($contact);
                    }

                    if($this->contactsController->hasSecondaryContacts()) {
                        $contactNavListItems[] = array(
                            'title' => $this->getModuleVar('MORE_CONTACTS'),
                            'url' => $this->buildBreadcrumbURL('contacts', array()),
                        );
                    }
                    $this->assign('contactNavListItems', $contactNavListItems);
                }
                $this->assign('hasContacts', (count($contactNavListItems) > 0));

                $hasEmergencyFeed = ($this->emergencyNoticeController !== NULL);
                $this->assign('hasEmergencyFeed', $hasEmergencyFeed);
                if($hasEmergencyFeed) {
                    $emergencyNotice = $this->emergencyNoticeController->getLatestEmergencyNotice();
                    
                    if($emergencyNotice !== NULL) {
                        $this->assign('emergencyFeedEmpty', FALSE);             
                        $this->assign('title', $emergencyNotice['title']);
                        $this->assign('content', $emergencyNotice['text']);
                        $this->assign('date', $emergencyNotice['date']);
                    } else {
                        $this->assign('emergencyFeedEmpty', TRUE);
                    }
                }

                break;

            case 'contacts':
                $contactNavListItems = array();
                foreach($this->contactsController->getAllContacts() as $contact) {
                    $contactNavListItems[] = self::contactNavListItem($contact);
                }
                $this->assign('contactNavListItems', $contactNavListItems);
                break;
        }
        
    }


    protected static function contactNavListItem($contact) {
        return array(
            'title' => $contact->getTitle(),
            'subtitle' => $contact->getSubtitle() . ' (' . $contact->getPhoneDelimitedByPeriods() . ')',
            'url' => 'tel:' . $contact->getPhoneDialable(),
            'class' => 'phone',
        );
    }
}
    