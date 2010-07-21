<?php

 require_once LIBDIR . "/harvard_dining.php";
 require_once LIBDIR ."/diningHrs.php";

 $urlBase = 'http://food.cs50.net/api/1.1/items?date=';

 $hours_indicator = 0;

 switch ($_REQUEST['command']) {
 
	case 'breakfast':
	     $mealTime = '&meal=Breakfast&output=json';
            $hours_indicator = 0;
            break;

	case 'lunch':
	     $mealTime = '&meal=Lunch&output=json';
            $hours_indicator = 0;
            break;

	case 'dinner':
	     $mealTime = '&meal=Dinner&output=json';
            $hours_indicator = 0;
            break;

        case 'hours':
            $hours_indicator = 1;
            echo json_encode(DINING_HOURS::getDiningHours());
            break;

 }

 if ($hours_indicator == 0)
 echo(HARVARD_DINING::getMealData($urlBase, $_REQUEST['date'], $mealTime)); 
/*
switch ($_REQEUST['command']) {

    // Only one case in the switch
    case 'dining':
        $day = isset($_REQUEST['date']) ? $_REQUEST['date'] : date('Y-m-d', time());
        $diningData = DINING_DATA::getDiningData($day);

        echo json_encode($diningData);
}*/
 
?>