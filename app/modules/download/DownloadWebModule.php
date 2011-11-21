<?php

class DownloadWebModule extends WebModule {
    protected $id = 'download';
    
    public static function appDownloadText($platform) {
        if ($config = ModuleConfigFile::factory('download', 'apps')) {
            return $config->getOptionalVar('downloadText', null, $platform);
        }
    }
    
    protected function initializeForPage() {
        $this->assign('deviceName',   Kurogo::getOptionalSiteVar($this->platform, null, 'deviceNames'));
        $this->assign('introduction', $this->getOptionalModuleVar('introduction', null, $this->platform, 'apps'));
        $this->assign('instructions', $this->getOptionalModuleVar('instructions', null, $this->platform, 'apps'));
        $this->assign('downloadUrl',  $this->getOptionalModuleVar('url', null, $this->platform, 'apps'));
    }
}
