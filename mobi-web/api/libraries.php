<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

require_once LIBDIR . '/LibrariesInfo.php';

switch ($_REQUEST['command']) {
    case 'libraries':
        $data = Libraries::getAllLibraries();
        break;

    case 'archives':
        $data = Libraries::getAllArchives();
        break;

    case 'opennow':
        $data = Libraries::getOpenNow();
        break;

    case 'libdetail':
        $libId = urldecode($_REQUEST['id']);
        $libName = urldecode($_REQUEST['name']);
        $data = Libraries::getLibraryDetails($libId, $libName);
        break;

    case 'archivedetail':
        $archiveId = urldecode($_REQUEST['id']);
        $archiveName = urldecode($_REQUEST['name']);
        $data = Libraries::getArchiveDetails($archiveId, $archiveName);
}

    echo json_encode($data);
?>