<?php

require_once realpath(LIB_DIR.'/Module.php');
require_once realpath(LIB_DIR.'/feeds/LdapWrapper.php');

class PeopleModule extends Module {
  protected $id = 'people';
  
  private function formatPersonDetails($person) {
    $this->loadThemeConfigFile('peopleDetails');
    
    $detailFields = $this->getTemplateVars('peopleDetails');
    $details = array();
    //error_log(print_r($detailFields, true));
    
    $strtrValue = array(
      '-', '-&shy;',
    );
    
    foreach($detailFields as $key => $info) {
      $section = array();
      
      if (isset($info['format'])) {
        $formatFunction = create_function('$value', $info['format']);
      } else {
        $formatFunction = null;
      }
      
      foreach($person->getField($info['keys']) as $value) {
        $detail = array(
          'label' => $info['label'],
          'title' => $value,
        );
        
        if (isset($formatFunction)) {
          $detail['title'] = $formatFunction($detail['title']);
        }
        
        switch(isset($info['type']) ? $info['type'] : 'text') {
          case 'email':
            $detail['title'] = str_replace('@', '@&shy;', $detail['title']);
            
            $detail['url'] = "mailto:$value";
            $detail['class'] = 'email';
            break;
            
          case 'phone':
            $detail['title'] = str_replace('-', '-&shy;', $detail['title']);
            
            if (strpos($value, '+1') !== 0) { $value = "+1$value"; }
            $detail['url'] = 'tel:'.strtr($value, '-', '');
            $detail['class'] = 'phone';
            break;
            
          case 'map':
            // Only send the next-to-last line of the address to the map module
            $lines = explode('$', $value);
            $count = count($lines);
            $linkAddress = ($count > 1) ? $lines[$count - 2] : $value;
            $detail['url'] = '/map/search.php?'.http_build_query(array(
              'filter' => $linkAddress
            ));
            $detail['class'] = 'map';
            break;
        }
        
        // $ is the LDAP return character for multiline fields
        $detail['title'] = str_replace('$', '<br />', $detail['title']);
        
        $section[] = $detail;
      }
      
      if (count($section)) {
        if (isset($info['section'])) {
          if (!isset($details[$info['section']])) {
            $details[$info['section']] = $section;
          } else {
            $details[$info['section']] = array_merge($details[$info['section']], $section);
          }
        } else {
          $details[] = $section;
        }
      }
    }
    
    return $details;
  }

  protected function initializeForPage() {
    switch ($this->page) {
      case 'help':
        break;
        
      case 'detail':
        $this->setPageTitle('Detail');

        if (isset($this->args['username'])) {
          $ldapWrapper = new LdapWrapper();
          $person = $ldapWrapper->lookupUser($this->args['username']);
          
          if ($person) {
            $this->assign('personDetails', $this->formatPersonDetails($person));
          } else {
            $this->assign('searchError', $ldapWrapper->getError());
          }          
        } else {
          $this->assign('searchError', 'No username specified');
        }
        break;
        
      case 'search':
        $this->setPageTitle('Search');
      
        if (isset($this->args['filter'])) {
          $searchTerms = trim($this->args['filter']);
          $ldapWrapper = new LdapWrapper();
          
          $this->assign('searchTerms', $searchTerms);
          
          if ($ldapWrapper->buildQuery($searchTerms)
              && ($people = $ldapWrapper->doQuery()) !== FALSE) {
            $resultCount = count($people);
            
            switch ($resultCount) {
              case 0:
                $this->redirectTo('index');
                exit;
              
              case 1:
                $this->assign('personDetails', $this->formatPersonDetails($people[0]));
                break;
                
              default:
                $results = array();
                foreach ($people as $person) {
                  $results[] = array(
                    'url' => $this->buildBreadcrumbURL('detail', array(
                       'username' => $person->getId(),
                       'filter'   => $this->args['filter'],
                    )),
                    'title' => htmlentities(
                        $person->getFieldSingle('sn').', '.
                        $person->getFieldSingle('givenname')
                    ),
                  );
                }//error_log(print_r($results, true));
                $this->assign('resultCount', $resultCount);
                $this->assign('results', $results);
                break;
            }
          
          } else {
            $this->assign('searchError', $ldapWrapper->getError());
          }
        } else {
          $this->redirectTo('index');
        }
        break;
        
      case 'index':
      default:
        // Redirect for old bookmarks
        if (isset($this->args['username'])) {
          $this->redirectTo('detail');
    
        } else if (isset($this->args['filter'])) {
          $this->redirectTo('search');
        }
        
        $this->loadThemeConfigFile('peopleContacts');
        break;
    }  
  }
}
