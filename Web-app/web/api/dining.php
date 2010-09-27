<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

 require_once LIBDIR . "/harvard_dining.php";
 require_once LIBDIR ."/diningHrs.php";

 //$urlBase = 'http://food.cs50.net/api/1.1/items?date=';

 $hours_indicator = 0;

 switch ($_REQUEST['command']) {
 
	case 'breakfast':
	    $mealTime = 'BRK';
            $day = isset($_REQUEST['date']) ? $_REQUEST['date'] : date('Y-m-d', time());
            echo json_encode(DINING_DATA::getDiningData($day, $mealTime));
            //$hours_indicator = 0;
            break;

	case 'lunch':
	    $mealTime = 'LUN';
            $day = isset($_REQUEST['date']) ? $_REQUEST['date'] : date('Y-m-d', time());
            echo json_encode(DINING_DATA::getDiningData($day, $mealTime));
            //$hours_indicator = 0;
            break;

	case 'dinner':
	     $mealTime = 'DIN';
            $day = isset($_REQUEST['date']) ? $_REQUEST['date'] : date('Y-m-d', time());
            echo json_encode(DINING_DATA::getDiningData($day, $mealTime));
            //$hours_indicator = 0;
            break;

        case 'hours':
            echo json_encode(DINING_HOURS::getDiningHours());
            break;

 }

// if ($hours_indicator == 0)
 //echo(HARVARD_DINING::getMealData($urlBase, $_REQUEST['date'], $mealTime));
/*
switch ($_REQEUST['command']) {

    // Only one case in the switch
    case 'dining':
        $day = isset($_REQUEST['date']) ? $_REQUEST['date'] : date('Y-m-d', time());
        $diningData = DINING_DATA::getDiningData($day);

        echo json_encode($diningData);
}*/
 
?>