<?

require_once realpath(LIB_DIR.'/Module.php');
require_once realpath(SITE_LIB_DIR.'/HarvardDining.php');
require_once realpath(SITE_LIB_DIR.'/HarvardDiningHalls.php');

  
class DiningModule extends Module {
  protected $id = 'dining';
  private $activeTab = '';
  
  
  private function dayURL($time, $addBreadcrumb=true) {
    $args = array('time' => $time);
    if($this->activeTab) {
      $args['tab'] = $this->activeTab;
    }
    return $this->buildBreadcrumbURL('index', $args, $addBreadcrumb);
  }  

  private function detailURL($statusDetails, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('detail', array(
      'location' => $statusDetails['name'],      
    ), $addBreadcrumb);
  }

  protected function initializeForPage() {
    switch ($this->page) {
      case 'help':
        break;
        
      case 'index':
        $time  = isset($this->args['time']) ? $this->args['time'] : time();
        $today = time();
        $next  = $time + 24*60*60;
        $prev  = $time - 24*60*60;
        
        $this->assign('current', $time);

        // limit how far into the past/future we can see
        if ((($next - $today)/(24*60*60)) < 7) {
          $this->assign('next', array(
            'timestamp' => $next,
            'url'       => $this->dayURL($next, false),
          ));
        }
        if ((($today - $prev)/(24*60*60)) < 7) {
          $this->assign('prev', array(
            'timestamp' => $prev,
            'url'       => $this->dayURL($prev, false),
          ));
        }
        
        $day = date('Y-m-d', $time);
        $foodItems = array(
          "breakfast" => DiningData::getDiningData($day, "BRK"),
          "lunch"     => DiningData::getDiningData($day, "LUN"),
          "dinner"    => DiningData::getDiningData($day, "DIN"),
        );
        
        $hour = intval(date('G'));
        if($hour < 12) {
            $currentMeal = "breakfast";
        } else if ($hour < 15) {
            $currentMeal = "lunch";
        } else {
            $currentMeal = "dinner";
        }
        
        $diningStatuses = DiningHalls::getDiningHallStatuses();

        error_log(print_r($foodItems, true));
        //error_log(print_r($diningHours, true));
        //error_log(print_r($diningStatuses, true));
        
        $this->assign('currentMeal',    $currentMeal);
        $this->assign('foodItems',      $foodItems);
        $this->assign('diningStatuses', $diningStatuses);

        $this->addInlineJavascriptFooter("showTab('{$currentMeal}tab');");

        break;
        
      case 'detail':
        break;
    }
  }
}
