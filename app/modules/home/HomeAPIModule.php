<?php

class HomeAPIModule extends APIModule
{
    protected $id = 'home';
    protected $vmin = 1;
    protected $vmax = 1;

    public function initializeForCommand() {
        switch ($this->command)
        {
            case 'notice':
                $response = null;
                $responseVersion = 1;
                $notice = $this->getOptionalModuleSection('notice');
                if ($notice) {
                    $bannerNotice = null;
                    // notice can either take a module or data model class or retriever class. The section is passed on. It must implement the HomeAlertInterface interface
                    if (isset($notice['MODULE'])) {
                        $moduleID = $notice['MODULE'];
                        $controller = WebModule::factory($moduleID);
                    } elseif (isset($notice['MODEL_CLASS'])) {
                        $controller = DataModel::factory($notice['MODEL_CLASS'], $notice);
                    } elseif (isset($notice['RETRIEVER_CLASS'])) {
                        $controller = DataRetriever::factory($notice['RETRIEVER_CLASS'], $notice);
                    }
    
                    if (!$controller instanceOf HomeAlertInterface) {
                        throw new KurogoConfigurationException("Module $moduleID does not implement HomeAlertModule interface");
                    } 
    
                    if ($bannerNotice = $controller->getHomeScreenAlert()) {
                        $response = $bannerNotice;
                    }
                }

                $this->setResponse($response);
                $this->setResponseVersion($responseVersion);
                break;
            default:
                $this->invalidCommand();
        }
    }
}


