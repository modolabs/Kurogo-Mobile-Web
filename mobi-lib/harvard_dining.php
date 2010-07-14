<?php

class HARVARD_DINING {

      public function getMealData($baseUrl, $dateToday, $mealExtension)
     {
	$urlLink = $baseUrl.$dateToday.$mealExtension;
        $contents = file_get_contents($urlLink);

        return $contents;
     }


}

# echo(HARVARD_DINING::getMealData());

?>
