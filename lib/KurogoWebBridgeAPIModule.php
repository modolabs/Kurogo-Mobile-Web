<?php

class KurogoWebBridgeAPIModule extends APIModule {
    public function getWebBridgeConfig() {
        $bridgeConfig = null;
        
        $mediaInfo = KurogoWebBridge::getAvailableMediaInfo($this->configModule);
        if ($mediaInfo) {
            $bridgeConfig = array();
            
            foreach ($mediaInfo as $key => $mediaItem) {
                $bridgeConfig[$key] = array(
                    'md5' => $mediaItem['md5'],
                    'url' => $mediaItem['url'],
                );
            }
        }
        
        return $bridgeConfig;
    }
    
    protected function initializeForCommand() {
        switch ($this->command) {
            default:
                $this->invalidCommand();
                break;
        }
    }
}
