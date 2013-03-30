<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class KurogoWebModule extends WebModule
{
    protected $id = 'kurogo';
    protected $canBeAddedToHomeScreen = false;
    protected $canBeRemoved = false;
    protected $canBeDisabled = false;
    protected $canAllowRobots = false;

    protected function getError($code) {
        static $errors = array(
            'server' => array(
              'status'    => '504 Gateway Timeout'
            ),
            'data' => array(
              'status'    => '500 Internal Server Error'
            ),
            'user'=> array(
              'status'    => '500 Internal Server Error'
            ),
            'config'=> array(
              'status'    => '500 Internal Server Error'
            ),
            'internal' => array(
              'status'  => '500 Internal Server Error',
            ),
            'notfound' => array(
              'status'  => '404 Not Found',
            ),
            'forbidden' => array(
              'status'  => '403 Forbidden',
            ),
            'disabled'  => array(
            ),
            'protected' => array(
            ),
            'default' => array(
              'status'  => '500 Internal Server Error',
            )
          );

        $code =   isset($errors[$code]) ? $code : 'default';
        $error = $errors[$code];
        $error['message'] = $this->getLocalizedString(strtoupper('ERROR_' . $code));
        return $error;
    }

    protected function getModuleIcon() {
        if ($this->page == 'error') {
            return 'error';
        } else {
            return $this->getOptionalModuleVar('icon', $this->configModule, 'module');
        }
    }

    protected function init($page='', $args=array()) {
        if ($page == 'error') {
          if(!Kurogo::getSiteVar('PRODUCTION_ERROR_HANDLER_ENABLED')) {
            set_exception_handler("exceptionHandlerForError");
          }
          $this->pagetype = Kurogo::deviceClassifier()->getPagetype();
          $this->platform = Kurogo::deviceClassifier()->getPlatform();
          $this->browser  = Kurogo::deviceClassifier()->getBrowser();
          $this->page = 'error';
          $this->setTemplatePage($this->page, $this->id);
          $this->setArgs($args);
          $this->ajaxContentLoad = $this->getArg(self::AJAX_PARAMETER) ? true : false;
          $this->logView = Kurogo::getOptionalSiteVar('STATS_ENABLED', true) ? true : false;
          try {
              $this->moduleName = $this->getOptionalModuleVar('title', 'Error', 'module');
          } catch (KurogoConfigurationException $e) {
          }
      } else {
          $this->redirectToModule($this->getHomeModuleID(), 'index');
      }
      return;
  }

  protected function getAccessControlLists($type) {
    return array(AccessControlList::allAccess());
  }

  protected function initializeForPage() {
    $code = $this->getArg('code', 'default');
    $url = $this->buildURLFromArray($this->args);

    $error = $this->getError($code);

    if (isset($error['status'])) {
      header('Status: '.$error['status']);
    }

    $linkText = isset($error['linkText']) ? $error['linkText'] : $this->getLocalizedString('DEFAULT_LINK_TEXT');
    $this->assign('linkText', $linkText);

    if($this->devError() === false){
      $this->assign('message', $error['message']);
    } else {
      $this->assign('message', nl2br($this->devError()));
    }
    $this->assign('url', $url);
  }

  protected function devError() {

    // production
    if(Kurogo::getSiteVar('PRODUCTION_ERROR_HANDLER_ENABLED')) {
      return false;
    }

    // check for development errors
    if(isset($_GET['error'])){
      $path = explode('/', $_GET['error']);
      $sanitizedFileName = end($path);
      $file = $path =  CACHE_DIR . "/errors/" . $sanitizedFileName . ".log";
      if(file_exists($file) && $handle = fopen($file, "r")) {
        $msg = fread($handle, filesize($file));
        fclose($handle);
        return $msg;
      }
    }

    return false;
  }
    
}