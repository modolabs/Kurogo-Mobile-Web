<?php

function init_logging()
{
    if ((LOG_ALL_ERRORS == FALSE) && (LOG_PEOPLE_DIRECTORY_ERRORS == FALSE))
    {
        return;
    }
    ini_set('track_errors', 1);
    ini_set('log_errors', 1);
    $today = date("m.d.y");
    if (LOG_ALL_ERRORS)
        $logfile = ERROR_LOGFILE . "." . $today;
    else if (LOG_PEOPLE_DIRECTORY_ERRORS == TRUE)
        $logfile = ERROR_LOGFILE . ".PEOPLE_DIRECTORY." . $today;
    ini_set('error_log', $logfile);

}

function log_error($errorMessage)
{
    if ((LOG_ALL_ERRORS == FALSE) && (LOG_PEOPLE_DIRECTORY_ERRORS == FALSE))
    {
        return;
    }
    $error_log = ini_get('error_log');
    error_log($errorMessage);

}
?>