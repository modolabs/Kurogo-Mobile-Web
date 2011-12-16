<?php

class KurogoWebBridgeAPIModule extends APIModule {

    protected $vmin = 1;
    protected $vmax = 1;

    public function getWebBridgeConfig() {
        return KurogoWebBridge::getAssetsConfiguration($this->configModule);
    }
    
    protected function initializeForCommand() {
        switch ($this->command) {
            default:
                $this->invalidCommand();
                break;
        }
    }
}
