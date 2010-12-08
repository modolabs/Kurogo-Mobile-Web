<?php

require_once realpath(LIB_DIR.'/Module.php');
require_once realpath(LIB_DIR.'/TransitDataParser.php');

class TransitModule extends Module {
  protected $id = 'transit';
  
  protected function initialize() {

  }

  private function timesURL($routeID, $addBreadcrumb=true, $noBreadcrumb=false) {
    if ($noBreadcrumb) {
      return $this->buildURL('times', array(
        'id' => $routeID,      
      ));
    } else {
      return $this->buildBreadcrumbURL('times', array(
        'id' => $routeID,      
      ), $addBreadcrumb);
    }
  }

  private function newsURL($newsID, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('announcement', array(
      'id' => $newsID,      
    ), $addBreadcrumb);
  }
  
  private function stopURL($stopID, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('detail', array(
      'id' => $stopID,      
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
        // Running and Offline Panes
        //
        $routeConfigs = $view->getRoutes();
        $runningRoutes = array();
        $offlineRoutes = array();

        foreach ($routeConfigs as $routeID => $routeConfig) {
          $agencyID = $routeConfig['agency'];
          $entry = array(
            'title' => $routeConfig['name'],
            'url'   => $this->timesURL($routeID),
          );
          
          if ($routeConfig['running']) {
            if (!isset($runningRoutes[$agencyID])) {
              $heading = isset($indexConfig['agencies'][$agencyID]) ? 
                $indexConfig['agencies'][$agencyID] : $agencyID;
            
              $runningRoutes[$agencyID] = array(
                'heading' => $heading,
                'items' => array(),
              );
            }
            $runningRoutes[$agencyID]['items'][$routeID] = $entry;
          } else {
            if (!isset($offlineRoutes[$agencyID])) {
              $heading = isset($indexConfig['agencies'][$agencyID]) ? 
                $indexConfig['agencies'][$agencyID] : $agencyID;
            
              $offlineRoutes[$agencyID] = array(
                'heading' => $heading,
                'items' => array(),
              );
            }
            $offlineRoutes[$agencyID]['items'][$routeID] = $entry;
          }
        }
        foreach ($runningRoutes as $agencyID => $section) {
          uasort($runningRoutes[$agencyID]['items'], array(get_class($this), 'routeSort'));
        }
        foreach ($offlineRoutes as $agencyID => $section) {
          uasort($offlineRoutes[$agencyID]['items'], array(get_class($this), 'routeSort'));
        }
        
        //
        // News Pane
        //
        $newsConfigs = $view->getNews();
        
        $news = array();
        foreach ($newsConfigs as $newsID => $newsConfig) {
          $agencyID = $newsConfig['agency'];
        
          if (!isset($news[$agencyID])) {
            $heading = isset($indexConfig['agencies'][$agencyID]) ? 
              $indexConfig['agencies'][$agencyID] : $agencyID;
          
            $news[$agencyID] = array(
              'heading' => $heading,
              'items' => array(),
            );
          }
          $news[$agencyID]['items'][$newsID] = array(
            'title' => $newsConfig['title'],
            'date'  => $newsConfig['date'],
            'url'   => $this->newsURL($newsID),
          );
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
        
        $this->assign('runningRoutes', $runningRoutes);
        $this->assign('offlineRoutes', $offlineRoutes);
        $this->assign('news',          $news);
        $this->assign('infosections',  $infosections);
        break;
        
      case 'times':
        $routeID = $this->getArg('id');
        
        $timesConfig = $this->loadWebAppConfigFile('transit-times', 'timesConfig');
        
        $routeInfo = $view->getRouteInfo($routeID);
        foreach ($routeInfo['stops'] as $stopID => $stop) {
          $routeInfo['stops'][$stopID]['url']   = $this->stopURL($stopID);
          $routeInfo['stops'][$stopID]['title'] = $stop['name'];
          
          if ($stop['upcoming']) {
            $routeInfo['stops'][$stopID]['title'] = "<strong>{$stop['name']}</strong>";
            $routeInfo['stops'][$stopID]['imgAlt'] = $timesConfig['busImageAltText'];
         }
          
          $routeInfo['stops'][$stopID]['img'] = '/common/images/';
          if ($this->pagetype == 'basic') {
            $routeInfo['stops'][$stopID]['img'] .= $stop['upcoming'] ? 'bus.gif' : 'bus-spacer.gif';
          } else {
            $routeInfo['stops'][$stopID]['img'] .= $stop['upcoming'] ? 'shuttle.png' : 'shuttle-spacer.png';
          }
        }

        $this->enableTabs(array('map', 'stops'));
        
        $mapImageSize = 270;
        if ($this->pagetype == 'basic') {
          $mapImageSize = 200;
        }

        $this->assign('mapImageSrc',  $view->getMapImageForRoute($routeID, $mapImageSize, $mapImageSize));
        $this->assign('mapImageSize', $mapImageSize);
        $this->assign('lastRefresh',  time());
        $this->assign('routeInfo',    $routeInfo);
        break;
      
      case 'detail':
        $stopID = $this->getArg('id');
        
        $stopInfo = $view->getStopInfo($stopID);
        
        $runningRoutes = array();
        $offlineRoutes = array();
        foreach ($stopInfo['routes'] as $routeID => $routeInfo) {
          $entry = array(
            'title' => $routeInfo['name'],
            'url'   => $this->timesURL($routeID, false, true), // no breadcrumbs
          );
          if ($routeInfo['running']) {
            $runningRoutes[$routeID] = $entry;
          } else {
            $offlineRoutes[$routeID] = $entry;
          }
        }
        
        $mapImageWidth = 270;
        if ($this->pagetype == 'basic') {
          $mapImageWidth = 200;
        }
        $mapImageHeight = floor($mapImageWidth/2);
        
        $this->assign('mapImageSrc',    $view->getMapImageForStop($stopID, $mapImageWidth, $mapImageHeight));
        $this->assign('mapImageWidth',  $mapImageWidth);
        $this->assign('mapImageHeight', $mapImageHeight);
        $this->assign('stopName',       $stopInfo['name']);
        $this->assign('runningRoutes',  $runningRoutes);
        $this->assign('offlineRoutes',  $offlineRoutes);
        break;
      
      case 'info':
        $infoConfig = $this->loadWebAppConfigFile('transit-info', 'infoConfig');
        $infoType = $this->getArg('id');
        
        if (!isset($infoConfig[$infoType], $infoConfig[$infoType]['content']) || 
            !count($infoConfig[$infoType]['content'])) {
          $this->redirectTo('index', array());
        }
        $this->assign('infoType', $infoType);        
        break;
        
      case 'announcement':
        $newsConfigs = $view->getNews();
        $newsID = $this->getArg('id');
        
        if (!isset($newsConfigs[$newsID])) {
          $this->redirectTo('index', array());
        }

        $this->assign('title',   $newsConfigs[$newsID]['title']);        
        $this->assign('date',    $newsConfigs[$newsID]['date']);        
        $this->assign('content', $newsConfigs[$newsID]['html']);        
        break;
    }
  }
}
