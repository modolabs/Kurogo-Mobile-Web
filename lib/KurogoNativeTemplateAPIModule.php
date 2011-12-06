<?php

class KurogoNativeTemplateAPIModule extends APIModule {
    public function getPayload() {
        return KurogoNativeTemplates::getNativeTemplateInfo($this->configModule);
    }
    
    protected function initializeForCommand() {
        switch ($this->command) {
            default:
                $this->invalidCommand();
                break;
        }
    }
}
