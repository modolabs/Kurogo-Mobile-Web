<?php

require_once realpath(LIB_DIR.'/Module.php');
require_once realpath(LIB_DIR.'/feeds/LdapWrapper.php');

class PeopleModule extends Module {
  protected $id = 'people';
  
  private function displayField() {
    $details = array(
      'name' => array('key' => 'cn',),
    );
    
    
    
    
    return $details;
  }
  
  protected function initializeForPage($page, $args) {
    if (isset($args['username'])) {
      $ldapWrapper = new LdapWrapper();
      $person = $ldapWrapper->lookupUser($args['username']);
      
      if ($person) {
        $personDetails = $this->formatPersonDetails($person);
        $this->assign('personDetails', $personDetails);
      } else {
        $this->assign('searchError', $ldapWrapper->getError());
      }
    } else if (isset($args['filter'])) {
      $searchTerms = stripslashes(trim($args['filter']));
      $ldapWrapper = new LdapWrapper();
      
      if ($ldapWrapper->buildQuery($searchTerms)
          && ($people = $ldapWrapper->doQuery()) !== FALSE) {
        $resultCount = count($people);
        
        switch ($resultCount) {
          case 0:
            $this->assign('searchError', 'No matches found');
            break;
          case 1:
            $this->assign('person', $people[0]);
            break;
          default:
            $results = array();
            foreach ($people as $person) {
              $results = array(
                'url' => 'index.php?'.http_build_query(array(
                   'username' => $person->getId(),
                   'filter'   => $args['filter'],
                )),
                'title' => htmlentities(
                    $person->getFieldSingle('sn').', '.
                    $person->getFieldSingle('givenname')
                ),
              );
            }
            $this->assign('resultCount', $resultCount);
            $this->assign('results', $results);
            break;
        }
      
      } else {
        $this->assign('searchError', $ldapWrapper->getError());
      }
    }
  
    $this->loadThemeConfigFile('peopleContacts');
  }
}
/*  <? $item->display('name', 'cn', NULL, NULL, FALSE, TRUE); ?>
  
  <? $item->display('title', 'title', NULL, NULL, FALSE); ?>
  
  <? $item->display('email', 'mail', 'mailHREF', 'email', FALSE); ?>
  
  <? if(has_phone($person)) { ?>
  <ul class="nav">
  <? $item->display('phone', 'telephonenumber', 'phoneHREF', 'phone', TRUE); ?>
  <? $item->display('home', 'homephone', 'phoneHREF', 'phone', TRUE); ?>
  <? $item->display('fax', 'facsimiletelephonenumber', 'phoneHREF', 'phone', TRUE); ?>
  </ul>
  <? } ?>
  
  <? $item->display('address', 'street', NULL, NULL, FALSE); ?>
  <? $item->display('office', 'postaladdress', 'officeURL', 'map', FALSE); ?>
  <? $item->display('unit', 'ou', NULL, NULL, FALSE); ?>

<?
  public function display($label, $field, $href=NULL, $class=NULL, $group=False, $flat=False) {
    $displayString = "";

    foreach($this->person->getField($field) as $value) {
      $formatted_value = $value;
      if (strcmp($field, 'ou') == 0) {
        $formatted_value = str_replace('^', ' / ', $formatted_value);
      }
      $formatted_value = htmlspecialchars($formatted_value);
	  if (strcmp($field, 'mail') != 0) {
		// We now don't want hyphens to display in an email address, but it's OK elsewhere.
      	$formatted_value = str_replace('@', '@&shy;', $formatted_value);
	  }
      // prevent phones from creating links from things that look like phone/email
      $formatted_value = str_replace('-', '-&shy;', $formatted_value);
      $formatted_value = str_replace('$', '<br />', $formatted_value);
      
      if ($flat) {
        $displayString = '<h2>'.$formatted_value.'</h2>';
        
        if (!$group) {
          $displayString = '<div class="nonfocal">'.$displayString.'</div>';
        }
        
        
      } else {
        $innerContents = "
                 <div class=\"label\">$label</div>
                 <div class=\"value\">$formatted_value</div>";
        if ($href !== NULL) {
		  $linkAddress = $value;
	
          $innerContents = '<a href="' . $href($linkAddress) . '" class="' . $class . '">'
                       . $innerContents . '</a>';
        }

        $innerContents = '<li>' . $innerContents . '</li>';

        if (!$group) {
          $innerContents = '<ul class="nav">' . $innerContents . '</ul>';
        }

        $displayString .= $innerContents;
      }
    }
    echo $displayString;
  }
}
*/