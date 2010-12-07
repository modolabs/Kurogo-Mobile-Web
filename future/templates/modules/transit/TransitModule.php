<?php

require_once realpath(LIB_DIR.'/Module.php');
require_once realpath(LIB_DIR.'/TransitDataParser.php');

class TransitModule extends Module {
  protected $id = 'transit';
  
  protected function initialize() {

  }

  private function timesURL($routeID, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('times', array(
      'route' => $routeID,      
    ), $addBreadcrumb);
  }
  
  private static function routeSort($a, $b) {
    return strcmp($a['title'], $b['title']);
  }

  protected function initializeForPage() {
    $view = new TransitDataView();
  
    switch ($this->page) {
      case 'index':
        $indexConfig = $this->loadWebAppConfigFile('transit-index', 'indexConfig');        
        
        //
        // Running, Offline and News Panes
        //
        $activeRouteConfigs = $view->getRoutes();
        $inactiveRouteConfigs = $view->getInactiveRoutes();

        $activeRoutes = array();
        $inactiveRoutes = array();

        foreach ($activeRouteConfigs as $routeID => $routeConfig) {
          $agencyID = $routeConfig['agency'];
        
          if (!isset($activeRoutes[$agencyID])) {
            $heading = isset($indexConfig['agencies'][$agencyID]) ? 
              $indexConfig['agencies'][$agencyID] : $agencyID;
          
            $activeRoutes[$agencyID] = array(
              'heading' => $heading,
              'items' => array(),
            );
          }
          $activeRoutes[$agencyID]['items'][$routeID] = array(
            'title' => $routeConfig['name'],
            'url'   => $this->timesURL($routeID),
          );
        }
        foreach ($activeRoutes as $agencyID => $section) {
          uasort($activeRoutes[$agencyID]['items'], array(get_class($this), 'routeSort'));
        }
        
        foreach ($inactiveRouteConfigs as $routeID => $routeConfig) {
          $agencyID = $routeConfig['agency'];
        
          if (!isset($inactiveRoutes[$agencyID])) {
            $heading = isset($indexConfig['agencies'][$agencyID]) ? 
              $indexConfig['agencies'][$agencyID] : $agencyID;
          
            $inactiveRoutes[$agencyID] = array(
              'heading' => $heading,
              'items' => array(),
            );
          }
          $inactiveRoutes[$agencyID]['items'][$routeID] = array(
            'title' => $routeConfig['name'],
            'url'   => $this->timesURL($routeID),
          );
        }
        foreach ($inactiveRoutes as $agencyID => $section) {
          uasort($inactiveRoutes[$agencyID]['items'], array(get_class($this), 'routeSort'));
        }
        
        //
        // Info Pane
        //
        $infosections = array();
        foreach ($indexConfig['infosections'] as $key => $heading) {
          $infosection = array(
            'heading' => $heading,
            'items'   => array(),
          );
          foreach ($indexConfig[$key]['titles'] as $index => $title) {
            $infosection['items'][] = array(
              'title'    => $title,
              'url'      => isset($indexConfig[$key]['urls'])      ? $indexConfig[$key]['urls'][$index]      : null,
              'subtitle' => isset($indexConfig[$key]['subtitles']) ? $indexConfig[$key]['subtitles'][$index] : null,
              'class'    => isset($indexConfig[$key]['classes'])   ? $indexConfig[$key]['classes'][$index]   : null,
            );
          }
          if (count($infosection['items'])) {
            $infosections[] = $infosection;
          }
        }
        
        $this->enableTabs(array(
          'running',
          'offline',
          'news',
          'info',
        ));
        
        $this->assign('activeRoutes',   $activeRoutes);
        $this->assign('inactiveRoutes', $inactiveRoutes);
        $this->assign('infosections',   $infosections);
        break;
        
      case 'times':
        $routeID = $this->getArg('route');
        
        $routeInfo = $view->getRouteInfo($routeID);
        
        $this->assign('routeInfo', $routeInfo);
        break;
    }
  }
}
