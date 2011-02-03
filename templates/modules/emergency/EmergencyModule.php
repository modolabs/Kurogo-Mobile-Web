<?php

class EmergencyModule extends Module 
{
    protected $id='emergency';
    
    protected function initializeForPage() {
        // construct controllers

        $config = $this->loadFeedData();
        if(isset($config['contacts'])) {
          $contactsController = ContactsListDataController::factory($config);
        } else {
          $contactsController = NULL;
        }
        $emergencyNoticeController = EmergencyNoticeDataController::factory($config);
        
        switch($this->page) {
            case 'index':
                $contactNavListItems = array();
                if($contactsController !== NULL) {
                    foreach($contactsController->getPrimaryContacts() as $contact) {
                        $contactNavListItems[] = self::contactNavListItem($contact);
                    }

                    if($contactsController->hasSecondaryContacts()) {
                        $moduleStrings = $this->getModuleSection('strings');
                        $contactNavListItems[] = array(
                            'title' => $moduleStrings['MORE_CONTACTS'],
                            'url' => $this->buildBreadcrumbURL('contacts', array()),
                        );
                    }
                    $this->assign('contactNavListItems', $contactNavListItems);
                }
                $this->assign('hasContacts', (count($contactNavListItems) > 0));

                $emergencyNotice = $emergencyNoticeController->getLatestEmergencyNotice();
                $this->assign('title', $emergencyNotice['title']);
                $this->assign('content', $emergencyNotice['text']);
                $this->assign('date', $emergencyNotice['date']);

                break;

            case 'contacts':
                $contactNavListItems = array();
                foreach($contactsController->getAllContacts() as $contact) {
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
    