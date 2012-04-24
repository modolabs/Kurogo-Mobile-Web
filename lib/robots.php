<?php

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
