<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

$lines = array(
    'User-agent: *',
    'Allow: '.URL_BASE,
    'Disallow: '.URL_BASE.'rest/',
);

foreach (WebModule::getAllModules() as $module) {
    if (!$module->allowRobots()) {
        $lines[] = 'Disallow: '.URL_BASE.$module->getConfigModule().'/';
    }
}

$output = implode("\n", $lines)."\n";

header('Content-type: text/plain');
print $output;
