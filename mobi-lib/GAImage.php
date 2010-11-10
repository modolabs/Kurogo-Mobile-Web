<?php

define('GA_COOKIE_TIMEOUT', 15724800);
define('GA_COOKIE_PATH', '/');
define('GA_SESSION_TIMEOUT', 1800);

class GAImage {
  private $searchEngines = array(
    'daum' => 'q',
    'eniro' => 'search_word',
    'naver' => 'query',
    'images.google' => 'q',
    'google' => 'q',
    'yahoo' => 'p',
    'msn' => 'q',
    'bing' => 'q',
    'aol' => 'query',
    'aol' => 'encquery',
    'lycos' => 'query',
    'ask' => 'q',
    'altavista' => 'q',
    'netscape' => 'query',
    'cnn' => 'query',
    'about' => 'terms',
    'mamma' => 'query',
    'alltheweb' => 'q',
    'voila' => 'rdata',
    'virgilio' => 'qs',
    'live' => 'q',
    'baidu' => 'wd',
    'alice' => 'qs',
    'yandex' => 'text',
    'najdi' => 'q',
    'aol' => 'q',
    'mama' => 'query',
    'seznam' => 'q',
    'search' => 'q',
    'wp' => 'szukaj',
    'onet' => 'qt',
    'szukacz' => 'q',
    'yam' => 'k',
    'pchome' => 'q',
    'kvasir' => 'q',
    'sesam' => 'q',
    'ozu' => 'q',
    'terra' => 'query',
    'mynet' => 'q',
    'ekolay' => 'q',
    'rambler' => 'words',
  );
  private $hostname = '-';
  private $UTMA = array();
  private $UTMZ = array();

  private function fieldVal($array, $key, $default='-') {
    if (isset($array[$key])) {
      return $array[$key];
    } else {
      return $default;
    }
  }
  
  private function firstFieldVal($array, $key, $default='-') {
    if (isset($array[$key])) {
      return reset(explode(',', reset(explode(';', $array[$key]))));
    } else {
      return $default;
    }
  }
  
  // Based on code in Galvanize http://sourceforge.net/projects/galvanize/
  public function __construct() {
    $timestamp = time();
    $this->hostname = reset(explode(':', $_SERVER['HTTP_HOST']));
    
    if (isset($_COOKIE['__utma'])) {
      // If UTMA is set, get the UTMA value
      $this->UTMA = explode('.', $_COOKIE['__utma']);
      if (!isset($_COOKIE['__utmb'], $_COOKIE['__utmc'], $_COOKIE['__utmz'])) {
        $this->UTMA[5]++; // Increase the UTMA visit number
        $this->UTMA[3] = $this->UTMA[4]; // Bump the previous visit timestamp left
        $this->UTMA[4] = $timestamp; // Insert the new visit timestamp
        $UTMB = array($this->UTMA[0], 1, 10, $timestamp); // Create UTMB from UTMA
        $UTMC = array($this->UTMA[0]); // Create UTMC from UTMA
        // Recreate the UTMZ value here
      } else {
        // Just read the UTMB, UTMC and UTMZ cookies and then increase the impression count in UTMB
        $UTMB = explode('.', $_COOKIE['__utmb']);
        $UTMB[1]++;
        $UTMC = explode('.', $_COOKIE['__utmc']);
        $UTMZ = explode('.', $_COOKIE['__utmz']);
      }
    } else {
      // if the UTMA isn't set, set it and the UTMB and UTMC. Get the the UTMZ referer and write that as well
      $this->UTMA = array(rand(10000000,99999999), rand(1000000000,2147483647), $timestamp, $timestamp, $timestamp, 1);
      $UTMB = array($this->UTMA[0], 1, 10, $timestamp);
      $UTMC = array($this->UTMA[0]);
      
      // Get the traffic source and build the UTMZ variable
      $utmcsr = '(direct)';
      $utmccn = '(direct)';
      $utmcmd = '(none)';
      $utmctr = NULL;
      $utmcct = NULL;
      
      if (isset($_SERVER['HTTP_REFERER'])) {
        // Parse the referring URL
        $parsedURL = parse_url($_SERVER['HTTP_REFERER']);
          
        // Check for organic search engine referrals
        // Need to add code for ignored organics in here as well
        foreach ($searchEngines as $engineDomain => $engineParam) {
          if (strstr($parsedURL['host'], $engineDomain) && strstr($parsedURL['query'], $engineParam.'=')) {
            $utmcsr = $engineDomain;
            $utmccn = '(organic)';
            $utmcmd = 'organic';
  
            $query = explode('&', $parsedURL['query']);
            foreach ($query as $queryParam) {
              if (substr($queryParam, 0, strlen($engineParam) + 1) == $engineParam."=") {
                $utmctr = substr($queryParam, strlen($engineParam) + 1);
              }
            }
          }
        }
        
        // CHECK FOR REFERRALS
        // See if we can match the tracking domain to the referring host. If
        // we can then return a (direct), if not return a (referral).
        if (!preg_match('/'.$this->hostname.'$/', $parsedURL['host'])) {
          // Finally, return (referral) if nothing else has returned so far
          $utmcsr = $parsedURL['host'];
          $utmccn = '(referral)';
          $utmcmd = 'referral';
          $utmcct = $parsedURL['path'];
        }
      }
      
      $this->UTMZ = array($this->UTMA[0], $timestamp, 1, 1, 
          "utmcsr=$utmcsr|utmccn=$utmccn|utmcmd=$utmcmd".
          (isset($utmctr) ? "|utmctr=$utmctr" : '').
          (isset($utmcct) ? "|utmcct=$utmcct" : ''));
    }
    
    // If we can save the cookies, then let's do so...
    if (!headers_sent()) {
      setcookie('__utma', implode('.', $this->UTMA), $timestamp + 63072000,           GA_COOKIE_PATH, $this->hostname);
      setcookie('__utmb', implode('.', $UTMB),       $timestamp + GA_SESSION_TIMEOUT, GA_COOKIE_PATH, $this->hostname);
      setcookie('__utmc', implode('.', $UTMC),       0,                               GA_COOKIE_PATH, $this->hostname);
      setcookie('__utmz', implode('.', $this->UTMZ), $timestamp + GA_COOKIE_TIMEOUT,  GA_COOKIE_PATH, $this->hostname);
    }
  }
  
  // Fake up an image link for GA
  public function imageHTML($pageTitle) {
    $gaProtocol = $this->fieldVal($_SERVER, 'HTTPS', 'off') != 'off' ? 'https' : 'http';
    
    $gaArgs = array(
      'utmwv' => '4.5.7', // Tracking code version
      'utmac' => GA_ACCOUNT,
      'utmn'  => rand(1000000000, 9999999999), // Id to prevent caching
      'utmhn' => $this->hostname, // Host name
      'utmp'  => $_SERVER['REQUEST_URI'], // Page request
      'utmr'  => $this->fieldVal($_SERVER, 'HTTP_REFERER'), // Referral URL
      'utmdt' => $pageTitle, // Page title
      'utmcs' => $this->firstFieldVal($_SERVER, 'HTTP_ACCEPT_CHARSET'), // Language charset
      'utmul' => $this->firstFieldVal($_SERVER, 'HTTP_ACCEPT_LANGUAGE', 'en'), // Browser language
      'utmfl' => $this->fieldVal($_COOKIE, '__utmfl'), // Flash Version
      'utmje' => $this->fieldVal($_COOKIE, '__utmje', '0'), // Java-enabled
      'utmsc' => $this->fieldVal($_COOKIE, '__utmsc'), // Screen color depth
      'utmsr' => $this->fieldVal($_COOKIE, '__utmsr'), // Screen resolution
      'utmcc' => '__utma%3D'.implode('.', $this->UTMA).
           '%3B%2B__utmz%3D'.implode('.', $this->UTMZ).'%3B', // cookies
    
      /* Ignore for now
      // Ecommerce and campaign variables
      'utme'   => '-', // Extensible parameter
      'utmcn'  => '-', // start new campaign session
      'utmcr'  => '-', // Repeat campaign visit?
      'utmt'   => '-', // Request type (event, transaction, item, page, custom)
      'utmipc' => '-', // Product code
      'utmipn' => '-', // Product name
      'utmipr' => '-', // Unit Price
      'utmiqt' => '-', // Quantity
      'utmiva' => '-', // Item variation
      'utmtci' => '-', // Billing city
      'utmtco' => '-', // Billing country
      'utmtid' => '-', // Order ID
      'utmtrg' => '-', // Billing region
      'utmtsp' => '-', // Shipping cost
      'utmtst' => '-', // Affiliation (ecommerce)
      'utmtto' => '-', // Total cost
      'utmttx' => '-', // Tax 
      */
    );
    
    $gaSrc = $gaProtocol.'://www.google-analytics.com/__utm.gif?'.http_build_query($gaArgs);
    
    return '<img src="'.$gaSrc.'" />';
  }
}
