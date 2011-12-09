<?php

class KurogoWebBridgeAPIModule extends APIModule {
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
