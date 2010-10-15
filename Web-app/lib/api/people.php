<?php

$peopleController = $GLOBALS['siteConfig']->getVar('PEOPLE_CONTROLLER_CLASS');
$personClass      = $GLOBALS['siteConfig']->getVar('PEOPLE_PERSON_CLASS');

$displayFields = $GLOBALS['siteConfig']->getAPIVar($_REQUEST['module'], 'displayFields');

switch ($_REQUEST['command']) {
  case 'details':
    if (isset($_REQUEST['uid'])) {

      $PeopleController = new $peopleController();
      $PeopleController->setPersonClass($personClass);
      $PeopleController->setAttributes(array_keys($displayFields));
      if ($person = $PeopleController->lookupUser($_REQUEST['uid'])) {
            $result = array(
                'uid'=>$person->getId()
            );
            foreach ($displayFields as $field=>$display) {
                if ($value = $person->getField($field)) {
                    $result[$field] = $value;
                }
            }
            $content = json_encode($result);      
      } else {
        $result = array('error' => $ldap->gerError());
        $content = json_encode($result);
      }

    }
    break;
  case 'search':
    if (isset($_REQUEST['q']) && strlen((trim($_REQUEST['q'])))) {
          $searchText = trim(stripslashes($_REQUEST['q']));
          $PeopleController = new $peopleController();
          $PeopleController->setPersonClass($personClass);
          $PeopleController->setAttributes(array_keys($displayFields));
          
          $people = $PeopleController->search($searchText);
          if (!is_array($people)) {
            $result = array('error' => 'Nothing Found');
            $content = json_encode($result);
          } elseif ($PeopleController->getError()) {
            $result = array('error' => $PeopleController->getError());
            $content = json_encode($result);
        } elseif (count($people)==0) {
            $result = array('error' => 'Nothing Found');
            $content = json_encode($result);
        } else {
            
            $results = array();
            foreach ($people as $person) {
                $result = array(
                    'uid'=>$person->getId()
                );
                foreach ($displayFields as $field=>$display) {
                    if ($value = $person->getField($field)) {
                        $result[$field] = $value;
                    }
                }
                
                $results[] = $result;
            }
            
            $content = json_encode($results);
        
        }
    }
    break;
  case 'displayFields':
    $content = json_encode($displayFields);
    break;
  default:
    break;
}

header('Content-Length: ' . strlen($content));
echo $content;
