#!/usr/bin/php
<?php

/**** Splits complete menu data into individual sorted files containing
 *    menus for each day.
 */
define('DINING_MENU_DIRECTORY', '/Users/muhammadamjad/Desktop/dining_example/');
define('DINING_MENU_FLAT_FILE', '/Users/muhammadamjad/Desktop/dining_example/sample_dining_menu/');
define('DINING_LIFESPAN', 60*60*24);

class MenuItem {
  public $mealDate;
  public $id;
  public $name;
  public $meal;
  public $hall;
  public $foodType;
  public $servingSize;
  public $servingUnit;

  public function __construct($data) {
      date_default_timezone_set('America/New_York');
    $this->mealDate = strtotime($data[0]);
    $this->id = $data[1];
    $this->name = $data[2];
    $this->meal = $data[3];
    $this->hall = $data[4];
    $this->foodType = $data[5];
    $this->servingSize = $data[6];
    $this->servingUnit = $data[7];
  }

  /****
   * Function to convert menu item food type codes into human-readable string
   */
  public function getFoodTypeAsName() {
    $foodTypeName;
    switch ($this->foodType) {
      case "01":
        $foodTypeName = "Breakfast Meats";
        break;
      case "02":
        $foodTypeName = "Breakfast Entrees";
        break;
      case "03":
        $foodTypeName = "Breakfast Bakery";
        break;
      case "04":
        $foodTypeName = "Breakfast Misc";
        break;
      case "05":
        $foodTypeName = "Breakfast Breads";
        break;
      case "06":
        $foodTypeName = "Seasonal";
        break;
      case "07":
        $foodTypeName = "Today's Soup";
        break;
      case "08":
        $foodTypeName = "Made to Order Bar";
        break;
      case "09":
        $foodTypeName = "Brunch";
        break;
      case "10":
        $foodTypeName = "Salad Bar";
        break;
      case "11":
        $foodTypeName = "Sandwich Bar";
        break;
      case "12":
        $foodTypeName = "Entrees";
        break;
      case "13":
        $foodTypeName = "Accompaniments";
        break;
      case "14":
        $foodTypeName = "Starch & Potatoes";
        break;
      case "15":
        $foodTypeName = "Vegetables";
        break;
      case "16":
        $foodTypeName = "Fruit, Fresh, Caned & Frozen";
        break;
      case "17":
        $foodTypeName = "Desserts";
        break;
      case "18":
        $foodTypeName = "Bread, Rolls, Misc Bakery";
        break;
      case "19":
        $foodTypeName = "From the Grille";
        break;
      case "20":
        $foodTypeName = "Bean, Whole Grain";
        break;
      case "21":
        $foodTypeName = "Basic Food Table";
        break;
      case "22":
        $foodTypeName = "Brown Rice Station";
        break;
      case "23":
        $foodTypeName = "Make or Build Your Own";
        break;
      case "24":
        $foodTypeName = "Special Bars - Board Menu";
        break;
      case "25":
        $foodTypeName = "Culinary Display";
        break;
      case "27":
        $foodTypeName = "In Addition at Annenberg";
        break;
      case "28":
        $foodTypeName = "Bag Lunches";
        break;
      case "29":
        $foodTypeName = "Production Salads";
        break;
      case "30":
        $foodTypeName = "A C I";
        break;
      case "31":
        $foodTypeName = "Chef's Choice";
        break;
      case "40":
        $foodTypeName = "Festive Meals";
        break;
      case "41":
        $foodTypeName = "Kosher Table";
        break;
      case "42":
        $foodTypeName = "Fly-By";
        break;
      case "43":
        $foodTypeName = "Continental Breakfast";
        break;
      case "44":
        $foodTypeName = "Vegetarian Station";
        break;
      case "45":
        $foodTypeName = "Pasta a la Carte";
        break;
      case "46":
        $foodTypeName = "Love Your Heart Menu";
        break;
      case "90":
        $foodTypeName = "Brain Break";
        break;
      case "99":
        $foodTypeName = "Misc. Supplies";
        break;
      default:
       $foodTypeName = "Other";
    }
    return $foodTypeName;
  }

  /**
   * Returns an array of values in the original order for writing out as CSV
   */
  public function toArray() {
    $values = array();
    $values[] = date("m/d/Y", $this->mealDate);
    $values[] = $this->id;
    $values[] = $this->name;
    $values[] = $this->meal;
    $values[] = $this->hall;
    $values[] = $this->getFoodTypeAsName();
    $values[] = $this->servingSize;
    $values[] = $this->servingUnit;

    return $values;
  }

  /**
   * Order of meals
   */
  public function getMealOrder() {
    $mealOrder = 0;
    switch ($this->meal) {
      case "BRK":
        $mealOrder = 0;
        break;
      case "LUN":
        $mealOrder = 1;
        break;
      case "DIN":
        $mealOrder = 2;
        break;
      default:
        $mealOrder = 3;
    }
    return $mealOrder;
  }

  /**
   * Comparator function used for sorting
   */
  static function compare($obj1, $obj2) {

    if ($obj1->getMealOrder() != $obj2->getMealOrder()) {
      return ($obj1->getMealOrder() < $obj2->getMealOrder()) ? -1 : 1;
    }

    if ($obj1->foodType != $obj2->foodType) {
      return ($obj1->foodType < $obj2->foodType) ? -1 : 1;
    }

    return strcmp($obj1->name, $obj2->name);
  }
}



/**** MAIN ****/

/****
 * TODO: Get main CSV menu file from somewhere
 * and download locally
 *
 */
//echo "Retrieving main menu CSV file\n";
$local_file = DINING_MENU_FLAT_FILE;


/****
 * Parse CSV into an associative array of
 * arrays of MenuItem objects, keyed by date
 *
 */
//echo "Parsing main menu CSV file\n";
$menus = array();
// only read if this is the first time or if the file is more than a day old
 if (!file_exists($localFile) ||
                filemtime($localFile) < time() - DINING_LIFESPAN) {

     $handle = fopen($local_file, "r");

     while (($data = fgetcsv($handle)) !== FALSE) {
         $menu_item = new MenuItem($data);
         $menu_key = $menu_item->mealDate;

         if (! array_key_exists($menu_key, $menus)) {
                $menus[$menu_key] = array();
            }
        $menus[$menu_key][] = $menu_item;
     }

     fclose($handle);

    /****
    * Write out a file for each date in appropriate sorted order.
    *
    */
    //echo "Writing daily sorted files\n";

     foreach ($menus as $menuDate => $menuItemList) {
      // Format as YYYY-MM-DD for file name
        $filename = "/Users/muhammadamjad/Desktop/dining_example/" .date("Y-m-d", $menuDate).".csv";
        $handle = fopen($filename, "w");
        usort($menuItemList, array("MenuItem", "compare"));
        foreach ($menuItemList as $menuItem) {
            fputcsv($handle, $menuItem->toArray());
        }

        fclose($handle);
     }
  }


  $menu = array();
  $day = isset($_REQUEST['date']) ? $_REQUEST['date'] : date('Y-m-d', time());  
  $filename = DINING_MENU_DIRECTORY .$day .".csv";
  $handle = fopen($filename, "r");

   
    while (($data = fgetcsv($handle)) !== FALSE) {
        $menu_item = new MenuItem($data);

        $menu_item_array = array();
        $menu_item_array['name'] = $menu_item->name;
        $menu_item_array['meal'] = $menu_item->meal;
        $menu_item_array['id'] = $menu_item->id;
        $menu_item_array['category'] = $menu_item->foodType;
        $menu_item_array['servingSize'] = $menu_item->servingSize;
        $menu_item_array['servingUnit'] = $menu_item->servingUnit;

        $menu[] = $menu_item_array;
    }

echo json_encode($menu);

exit(0);

?>