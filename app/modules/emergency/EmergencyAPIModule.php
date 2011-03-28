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
        }
    }
}