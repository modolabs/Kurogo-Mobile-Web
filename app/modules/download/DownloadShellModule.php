<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class DownloadShellModule extends ShellModule
{
    protected $id = 'download';

    public function getStaticNotificationContexts() {
        $contexts = array();

        $appData = Kurogo::getAppData();
        foreach ($appData as $platform => $data) {
            $version = $data['version'];
            $contexts[] = $platform . ':' . $version;
        }

        return $contexts;
    }

    public function getUpdatesForStaticContext($context, $platform, $lastCheckTime) {
        $messages = array();

        // only check each version once
        if (strlen($platform) && strpos($context, $platform) === 0 && $lastCheckTime === null) {
            $version = substr($context, strlen($platform) + 1);
            // FIXME - don't hard code this string
            $messages[] = new KurogoMessage("Version $version of the $platform app is available", 'kurogo', $this->getConfigModule());
        }
        return $messages;
    }

    protected function initializeForCommand() {

    }

}
