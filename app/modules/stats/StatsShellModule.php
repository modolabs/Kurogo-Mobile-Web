<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class StatsShellModule extends ShellModule {

    protected $id = 'stats';

    public function migrateData($updateSummaryOnly = false){
        $this->out("Beginning Migration");
        if ($table = Kurogo::getOptionalSiteVar("KUROGO_STATS_TABLE", "")) {
            $this->out("Using table $table");
            $this->out("Analyzing data.",false);
            $day = KurogoStats::getStartDate($table);
            $this->out(".",false);
            $endDay = KurogoStats::getLastDate($table);
            $this->out(".",false);
            $totalRows = KurogoStats::getTotalRows($table);
            $totalNumberOfDays = floor((strtotime($endDay) - strtotime($day)) / (60 * 60 *24));
            $this->out(".",false);
            $this->out("Done.");
            $this->out("Migrating data from $day to $endDay");
            $this->out("Total Number of Days: $totalNumberOfDays");
            $this->out("Total Number of Rows: " . number_format($totalRows));
            $this->out("");
            $startTime = microtime(true);
            $totalRowsProcessed = 0;
            $dayCount = 0;
            while (strtotime($day) <= strtotime($endDay)) {
                $this->out("Migrating data for $day....",false);
                $rowsProcessed = KurogoStats::migrateData($table, $day, 50000, $updateSummaryOnly);
                $totalRowsProcessed += $rowsProcessed;
                $dayCount++;
                $this->out("rows found: $rowsProcessed",false);

                $timeTaken = microtime(true) - $startTime;
                $clockTimeTaken = $this->secondsToTime($timeTaken);
                $rowsRemaining = $totalRows - $totalRowsProcessed;
                $daysRemaining = $totalNumberOfDays - $dayCount;
                $pctDone = floor(($totalRowsProcessed * 100) / $totalRows);

                $this->out("\t Elapsed: $clockTimeTaken \t $daysRemaining days remaining. " . number_format($rowsRemaining) . " rows remaining. $pctDone% complete.");

                $dayTimestamp = strtotime($day);
                $nextDayTimestamp = strtotime("+1 day", $dayTimestamp);
                $day = date("Y-m-d", $nextDayTimestamp);
            }
            $this->out('All data has been migrated successfully.');
           
        } else {
            $this->out('The stats table is not configured.');
        }
    }

    private function secondsToTime($seconds){
        $hours = floor($seconds / (60 * 60));
        $divisor_for_minutes = $seconds % (60 * 60);
        $minutes = floor($divisor_for_minutes / 60);
        $divisor_for_seconds = $divisor_for_minutes % 60;
        $seconds = ceil($divisor_for_seconds);
        if($hours){
            $minutes = $this->padString($minutes);
            $seconds = $this->padString($seconds);
            return "$hours:$minutes:{$seconds}h";
        }elseif($minutes){
            $seconds = $this->padString($seconds);
            return "$minutes:{$seconds}m";
        }else{
            return ":{$seconds}s";
        }
    }

    private function padString($string){
        return str_pad($string, 2, "0", STR_PAD_LEFT);
    }
    
    protected function initializeForCommand() {

        switch($this->command) {
            case 'migrate':
                $this->migrateData();
                
                return 0;
                break;
            case 'summary':
                $this->migrateData(true);

                return 0;
                break;
            case 'update':
                KurogoStats::exportStatsData();
                return 0;
                break;
            case 'updateFromShards':
                if($timestamp = $this->getArg('timestamp')){
                    KurogoStats::updateSummaryFromShards($timestamp);
                    return 0;
                }
                $this->out('timestamp parameter is required');
                return 10;
                break;
                
            default:
                 $this->invalidCommand();
                 break;
        }
    }
}
