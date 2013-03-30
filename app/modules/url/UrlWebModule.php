<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/**
  * @package Module
  * @subpackage Fullweb
  */
class UrlWebModule extends WebModule {
  protected $id = 'url';
  
  protected function initializeForPage() {
     if ($url = $this->getModuleVar('url')) {
         $this->logView();
         Kurogo::redirectToURL($url);
     } else {
        throw new KurogoConfigurationException("URL not specified");
     }
  }
}
