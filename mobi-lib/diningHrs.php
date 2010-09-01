<?php
/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

class MealRestrictions {
    
    public $days = array();
    public $time;
    public $message;

    public function get_days() {
        return $this->days;
    }

    public function get_time() {
        return $this->time;
    }

    public function get_message() {
        return $this->message;
    }
}

class DiningHall {

    public $name;
    public $breakfast_hours;
    public $lunch_hours;
    public $dinner_hours;
    public $bb_hours;
    public $brunch_hours;
    public $lunch_restrictions = array();
    public $dinner_restrictions = array();
    public $brunch_restrictions = array();

    public function __construct($n, $b, $l, $d, $bb, $br) {

        $this->name = $n;
        $this->breakfast_hours = $b;
        $this->lunch_hours = $l;
        $this->dinner_hours = $d;
        $this->bb_hours = $bb;
        $this->brunch_hours = $br;
    }

    public function process_restrictions($line) {

        $restrictions = preg_split("/;/", $line);

        $meal_restrictions = array();

        foreach ($restrictions as $rest) {

            $components = preg_split('/,/', $rest);

            $days = preg_split("/\//", $components[0]);

            $time = $components[1];
            $msg = str_replace("$", ",", $components[2]);

            $meal_rest = new MealRestrictions();
            $meal_rest->days = $days;
            $meal_rest->time = $time;
            $meal_rest->message = $msg;

            $meal_restrictions[] = $meal_rest;
        }


        foreach($meal_restrictions as $lr){

            //print_r($lr->get_days());
            //printf("%s\n", $lr->get_time());
            //printf("%s\n", $lr->get_message());
            //printf("\n");
        }

        return $meal_restrictions;
    }
}


class DINING_HOURS {

    public function getDiningHours() {
        $filename = PATH_TO_DINING_HRS;
        $handle = fopen($filename, 'r');

        $dining_halls = array();

        while(!feof($handle)) {

            $line1 = str_replace("\n", "", fgets($handle)); // name
            $line2 = str_replace("\n", "", fgets($handle)); // breakfast hrs
            $line3 = str_replace("\n", "", fgets($handle)); // lunch hrs
            $line4 = str_replace("\n", "", fgets($handle)); // dinner hrs
            $line5 = str_replace("\n", "", fgets($handle)); // brain-break hrs
            $line6 = str_replace("\n", "", fgets($handle)); // nrunch hrs

            $hall = new DiningHall($line1, $line2, $line3, $line4, $line5, $line6);
            //printf("%s\n", $line1);
            //printf("%s\n", $line2);
            //printf("%s\n", $line3);
            //printf("%s\n", $line4);
            //printf("%s\n", $line5);
            //printf("%s\n", $line6);
            //printf("\n");


            $line7 = str_replace("\n", "", fgets($handle));
            $hall->lunch_restrictions = $hall->process_restrictions($line7);

            $line8 = str_replace("\n", "", fgets($handle));
            $hall->dinner_restrictions = $hall->process_restrictions($line8);

            $line9 = str_replace("\n", "", fgets($handle));
            $hall->brunch_restrictions = $hall->process_restrictions($line9);

            $line10 = str_replace("\n", "", fgets($handle));  // empty line

            $dining_halls[] = $hall;
        }
        fclose($handle);

        return $dining_halls;
    }
}
?>
