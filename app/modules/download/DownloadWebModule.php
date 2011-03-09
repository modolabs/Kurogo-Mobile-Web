<?php

class DownloadWebModule extends WebModule {
  protected $id = 'download';
  
  protected function initializeForPage() {
    $downloadInfo = $this->loadWebAppConfigFile('download-index', 'download');

    $this->assign('deviceName',   self::argVal($downloadInfo['deviceNames'],        $this->platform, null));
    $this->assign('instructions', self::argVal($downloadInfo['deviceInstructions'], $this->platform, null));
    $this->assign('downloadUrl',  self::argVal($downloadInfo['deviceApps'],         $this->platform, null));
  }
}
