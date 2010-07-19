<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

define('PATH_TO_DINING_HRS', '/Users/muhammadamjad/Documents/work/Harvard/Harvard-Mobile/opt/mitmobile/static/DiningHours');

class MealRestrictions {
    
    public $day;
    public $time;
    public $message;

    public function get_day() {
        return $this->day;
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

        $restrictions = split(";", )


        
    }
}





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
    printf("%s\n", $line1);
    printf("%s\n", $line2);
    printf("%s\n", $line3);
    printf("%s\n", $line4);
    printf("%s\n", $line5);
    printf("%s\n", $line6);
    printf("\n");


    $line7 = str_replace("\n", "", fgets($handle));
    $line8 = str_replace("\n", "", fgets($handle));
    $line9 = str_replace("\n", "", fgets($handle));
    $line10 = str_replace("\n", "", fgets($handle));  // empty line
    

    $dining_halls[] = $hall;

}
fclose($handle);

?>
