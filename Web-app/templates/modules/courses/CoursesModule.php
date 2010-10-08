<?php

require_once realpath(LIB_DIR.'/Module.php');
require_once realpath(LIB_DIR.'/feeds/courses.php');

define('MYCOURSES_EXPIRE_TIME', 160 * 24 * 60 * 60); // Maybe use customize expire time?
define('MY_CLASSES_COOKIE', 'myclasses');


class CoursesModule extends Module {
  protected $id = 'courses';

  private function getMyClasses() {
    // read the cookie, and create three groups                                                                                                                                                           
    // first group all the classes in the cookie                                                                                                                                                          
    // second group is the classes for the current semester                                                                                                                                               
    // third group is the classes from previous semesters 
    
    $term = CourseData::get_term();
    if(isset($_COOKIE[MY_CLASSES_COOKIE])) {
      $allTags = explode(",", $_COOKIE[MY_CLASSES_COOKIE]);
      natsort($allTags);
    } else {
      $allTags = array();
    }

    $currentTags = array();
    $currentIds = array();
    $oldIds = array();
    foreach($allTags as $classTag) {
      $parts = explode(" ", $classTag, 2);
      if(count($parts) > 1 && $parts[1] == $term) {
        $currentTags[] = $classTag;
        $currentIds[] = $parts[0];
      } else {
        $oldIds[] = $parts[0];
      }
    }
  
    return array(
      "allTags"     => $allTags,
      "currentTags" => $currentTags,
      "currentIds"  => $currentIds,
      "oldIds"      => $oldIds,
    );
  }
  
  private function removeOldMyClasses() {
    $myClasses = $this->getMyClasses();
    $this->setMyClasses($myClasses['currentTags']);
  }
  
  private function setMyClasses($classes) {
    setcookie(MY_CLASSES_COOKIE, implode(",", $classes), time() + MYCOURSES_EXPIRE_TIME);
  }
  
  private function coursesURL($school, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('courses', array(
      "school" => $school,
    ), $addBreadcrumb);
  }
  
  private function getClassListItem($classes, $i) {
    $class = $classes[$i];
  
    // Multiple classes with the same name in a row, show instructors to differentiate     
    $staffNamesIfNeeded = '';    
    if (($i > 0                   && $className == $classes[$i-1]['name']) || 
        ($i < (count($classes)-1) && $className == $classes[$i+1]['name'])) {
      $staffNamesIfNeeded = implode(', ', $class['staff']['instructors']);
      if (strlen($staffName)) {
        $staffNamesIfNeeded = ' ('.$staffNamesIfNeeded.')';
      }
    }
    
    return array(
      'title' => $class['name'].': '.$class['title'].$staffNamesIfNeeded,
      'url'   => $this->detailURL($class['masterId']),
    );
  }

  private function courseURL($course, $courseShort, $school, $schoolShort, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('course', array(
      "course"      => $course,
      "courseShort" => $courseShort,
      "school"      => $school,
      "schoolShort" => $schoolShort,
    ), $addBreadcrumb);
  }
  
  private function searchSchoolURL($filter, $school, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('search', array(
      'filter' => $filter,
      "school" => $school,
    ), $addBreadcrumb);
  }
  
  private function searchCourseURL($filter, $school, $course, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('search', array(
      'filter' => $filter,
      "course" => $course,
      "school" => $school,
    ), $addBreadcrumb);
  }
  
  private function detailURL($class, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('detail', array(
      'class' => $class,
    ), $addBreadcrumb);
  }
  
  private function personURL($person) {
    return '../people/search.php?'.http_build_query(array(
      'filter' => str_replace('.', '', preg_replace('/\s+/', ' ', $person)),
    ));
  }
  
  private function mapURLForClassTime($location) {
    return '../map/search.php?'.http_build_query(array(
      'loc'    => 'courses',
      'filter' => $location,
    ));
  }

  protected function initializeForPage() {
    switch ($this->page) {
      case 'help':
        break;
        
      case 'index':
        // List of bookmarked courses and schools
        $myClassesInfo = $this->getMyClasses();
        $myClasses = array();
        foreach ($myClassesInfo['currentIds'] as $id) {
          $class = CourseData::get_subject_details($id);
          
          $myClasses[] = array(
            'title' => htmlentities($class['name']).': '.$class['title'],
            'url'   => $this->detailURL($id),
          );
        }
        $this->assign('myClasses',        $myClasses);
        $this->assign('myRemovedCourses', $myClassesInfo['oldIds']);
        
        $schoolsInfo = CourseData::get_schoolsAndCourses();
        $schools = array();
        foreach ($schoolsInfo as $schoolInfo) {
          $courses   = $schoolInfo->courses;
          $name      = $schoolInfo->school_name;
          $shortName = $schoolInfo->school_name_short;
        
          $school = array(
            'title' => $schoolInfo->school_name_short
          );
          if (count($courses) == 1 && $courses[0]->name == "") {
            $school['url'] = $this->courseURL($name, $shortName, $name, $shortName);
              
          } else if (count($courses) == 1) {
            $school['url'] = $this->courseURL($shortName, $shortName, $name, $shortName);
            
          } else {
            $school['url'] = $this->coursesURL($name);
          }
          $schools[] = $school;
        }        
        $this->assign('schools', $schools);
        break;
        
      case 'courses':
        // A list of all the departments in a school
        $schoolName = $this->args['school'];
        
        $schoolsInfo = CourseData::get_schoolsAndCourses();
        
        $schoolNameShort = '';
        $coursesInfo = array();
        foreach($schoolsInfo as $schoolInfo) {
          if ($schoolInfo->school_name == $schoolName) {
            $coursesInfo = $schoolInfo->courses;
            $schoolNameShort = $schoolInfo->school_name_short;
            break;
          }
        }
        
        $courses = array();
        foreach ($coursesInfo as $courseInfo) {
          $courseName = $courseInfo->name;
          if (!strlen($courseName) {
            $courseName = $schoolNameShort.'-other';
          }
          
          $courses[] = array(
            'title' => $courseName,
            'url'   => $this->courseURL($courseName, $courseName, $schoolName, $schoolNameShort),
          );
        }
        
        $this->assign('schoolNameShort', $schoolNameShort);
        $this->assign('courses', $courses);
        $this->assign('extraSearchArgs', array(
          'school' => $this->args['school'],
        ));
        break;

      case 'course':
        // A list of classes in a department
        $courseName      = $this->getArg('course');
        $courseNameShort = $this->getArg('courseShort');
        $schoolName      = $this->getArg('school');
        $schoolNameShort = $this->getArg('schoolShort');
        
        $classesInfo = CourseData::get_subjectsForCourse(str_replace('-other', '', $courseName), $schoolName);

        $classes = array();
        foreach ($classesInfo as $i => $classInfo) {
          $classes[] = $this->getClassListItem($classesInfo, $i);
        }        

        $this->assign('classes',         $classes);
        $this->assign('courseNameShort', $courseNameShort);
        $this->assign('extraSearchArgs', array(
          'school'      => $schoolName,
          'schoolShort' => $schoolNameShort,
          'course'      => $courseName,
          'courseShort' => $courseNameShort,
        ));
        break;
        
      case 'searchCourses':
        // A list of departments with search results
        $searchTerms = $this->getArg('filter');

        $data = CourseData::search_subjects($searchTerms, '', '');
        $count = $data['count'];
        $classes = isset($data["classes"]) ? $data["classes"] : NULL;
    
      
        $schools = array();
        if ($data['count'] <= 100) {
          foreach ($data["classes"] as $class) {
            if (!in_array($class['school'], array_keys($schools))) {
              $schools[$class['school']] = array(
                'title' => "{$class['short_name']} (1)",
                'url'   => $this->searchSchoolURL($searchTerms, $class['school']),
                'count' => 1,
              );
            } else {
              $schools[$class['school']]['count']++;
              $schools[$class['school']]['title'] = "{$class['short_name']} ({$schools[$class['school']]['count']})";
            }
          }
        } else {
          // schoolData will only be available for searches 
          // from the top-level view where search results are > 100
          $schoolData = isset($data['schools']) ? $data['schools'] : NULL;
          foreach ($schoolData as $school) {
            $schools[$class['school']] = array(
              'title' => "{$class['short_name']} ({$school['count']})",
              'url'   => $this->searchSchoolURL($searchTerms, $class['school']),
              'count' => $school['count'],
            );
          }
        }
        $this->assign('searchTerms', $searchTerms);
        $this->assign('schools',     array_values($schools));
        break;
        
      case 'search':
        // search results for a department
        $searchTerms = $this->getArg('filter');
        $schoolName  = $this->getArg('school');
        $courseName  = $this->getArg('course');

        $data = CourseData::search_subjects($searchTerms, $schoolName, $courseName);
        $count = $data['count'];
        $classes = isset($data["classes"]) ? $data["classes"] : NULL;
    
        $classes = array();
        foreach ($data["classes"] as $i => $classInfo) {
          $classes[] = $this->getClassListItem($data["classes"], $i);
        }        

        $this->assign('classes', $classes);
        break;
        
      case 'detail':
        $classId = $this->args['class'];
        
        $classInfo = CourseData::get_subject_details($classId);
        $termId = CourseData::get_term();
        
        if (!$classInfo) {
          $this->assign('errorText', "Sorry, class '$courseID' not found for the $term term");
          break;
        }
        
        $myClasses = $this->getMyClasses();
        $myClassTags = $myClasses['allTags'];
        $classTag = "$classId $termId";
        $isInMyClasses = in_array($classTag, $myClassTags);

        // Add or remove from the myClasses list
        if (isset($this->args['action'])) {
          if ($this->args['action'] == 'add' && !$isInMyClasses) {
            $myClassTags[] = $classTag;
            
          } else if ($this->args['action'] == 'remove') {
            if ($isInMyClasses) {
              array_splice($myClassTags, array_search($classTag, $myClassTags), 1);
            } else {
              foreach ($myClassTags as $item) {
                if (strpos($item, $classId) !== false) {
                  array_splice($myClassTags, array_search($item, $myClassTags), 1);
                }
              }
            }
          }
          $this->setMyClasses($myClassTags);
          $this->redirectTo($this->page, $this->args);
        }
        
        // Info
        $meetingTimes = $classInfo['meeting_times'];
        
        $times = array();
        if ($meetingTimes->parseSucceeded()) {
          foreach ($meetingTimes->all() as $meetingTime) {
            $time = array(
              'days' => $meetingTime->daysText(),
              'time' => $meetingTime->timeText(),
            );
            
            if ($meetingTime->isLocationKnown()) {
              $time['location'] = $meetingTime->locationText();
              $time['url'] = $this->mapURLForClassTime($meetingTime->locationText());
            }
            $times[] = $time;
          }
        } else {
          $times[] = array(
            'days' => $meetingTimes->rawTimesText(),
            'time' => $meetingTimes->rawLocationsText(),
          );
        }
        
        // Staff
        $staff = array();
        foreach ($classInfo['staff'] as $type => $staffList) {
          $staff[$type] = array();
          foreach ($classInfo['staff'][$type] as $person) {
            $staff[$type][] = array(
              'title' => $person,
              'url'   => $this->personURL($person),
            );
          }
        }
        
        $this->assign('term',          $termId);
        $this->assign('classId',       $classId);
        $this->assign('className',     $classInfo['name']);
        $this->assign('classTitle',    $classInfo['title']);
        $this->assign('times',         $times);
        $this->assign('description',   $classInfo['description']);
        $this->assign('staff',         $staff);
        $this->assign('isInMyClasses', $isInMyClasses);
        
        $this->enableTabs(array('info', 'staff'));
        
        $this->addInlineJavascript('var MY_CLASSES_COOKIE = "'.MY_CLASSES_COOKIE.'";');
        break;
    }
  }
}
