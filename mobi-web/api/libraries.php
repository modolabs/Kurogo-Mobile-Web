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
    
    }
    echo json_encode($data);
?>