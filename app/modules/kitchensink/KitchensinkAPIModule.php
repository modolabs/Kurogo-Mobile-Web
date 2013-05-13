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
  */

/**
  * @package Module
  */
class KitchenSinkAPIModule extends APIModule {
    protected $id = 'kitchensink';
    protected $vmin = 1;
    protected $vmax = 1;

    public function initializeForCommand() {
        switch ($this->command) {
            case 'test':
                $this->setResponse('This is the Javascript API response.  Success!');
                $this->setResponseVersion(1);
                break;

            default:
                $this->invalidCommand();
                break;
        }
    }
}
