<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

define("MAX_ITEMS", 2);
require_once 'Modules.inc';

// some modules clean up search terms in the backend 
// (i.e. mobi-lib portion) so we pass different sets
$raw_search_terms = $_REQUEST['search_terms'];
$search_terms = urlencode(stripslashes($raw_search_terms));

// people search
require_once LIBDIR . "/LdapWrapper.php";
$ldapWrapper = new LdapWrapper();
$ldapWrapper->buildQuery($raw_search_terms);
$people = $ldapWrapper->doQuery();

$people_result_items = array();
foreach($people as $person) {
    $people_result_items[] = array(
        "link" => "/people/index.php?username=" . urlencode($person->getId()),
        "title" => $person->getFieldSingle('cn'),
        "subtitle" => $person->getFieldSingle('title'),
    );
}


// map search
require_once LIBDIR . '/MapSearch.php';

// ArcGISServer.php does urlencode on the search terms
$resultObj = searchCampusMap($raw_search_terms);
$map_result_items = array();
foreach ($resultObj->results as $result) {
    $attributes = $result->attributes;
    $title = $attributes->{'Building Name'};
    if (!$title)
        $title = $result->value;
    $params = array(
        'selectvalues' => $title,
        'info' => $attributes,
        );

    $map_result_items[] = array(
        'link' => '/map/detail.php?' . http_build_query($params),
        'title' => $title,
        'subtitle' => $attributes->address,
        );
}


// calendar search
require_once LIBDIR . "/harvard_calendar.php";
// retrieve data for the week

$url = HARVARD_EVENTS_ICS_BASE_URL ."?days=7" ."&search=" . $search_terms;
$events = makeIcalSearchEvents($url, $search_terms);

$event_result_items = array();
foreach ($events as $event) {
    $event_result_items[] = array(
        "link" => "/calendar/detail.php?id=" . $event->get_uid(),
        "title" => $event->get_summary(),
        "subtitle" => date('n/j/y g:i A', $event->get_start()) . '-' . date('g:i A', $event->get_end()),
    );
}





// Course search
require_once LIBDIR . '/courses.php';
$class_results = CourseData::search_subjects($search_terms, '', '');
$class_result_items = array();
foreach($class_results['classes'] as $class) {
    $class_result_items[] = array(
        "link" => "/courses/detail.php?id=" . $class['masterId'],
        "title" => $class['name'],
        "subtitle" => $class['title'],
    );
}






// News search
require_once LIBDIR . '/GazetteRSS.php';
$stories = GazetteRSS::searchArticlesArray($raw_search_terms, NULL);
$news_result_items = array();
foreach($stories as $story) {
    $news_result_items[] = array(
        "link" => "/news/story.php?search_terms=" . urlencode($search_terms) . "&story_id=" . $story['story_id'],
        "title" => $story['title'],
        "subtitle" => date('Y-m-d G:i', $story['unixtime']),
    );
}


$federated_results = array(
    "people" => array(
        "results" => $people_result_items,
        "search-link" => "/people/index.php?filter=" . urlencode($search_terms),
     ),

    'map' => array(
        'results' => $map_result_items,
        'search-link' => '/map/search.php?filter=' . rawurlencode($search_terms),
     ),

    "calendar" => array(
        "results" => $event_result_items,
        "search-link" => "/calendar/search.php?timeframe=0&filter=" . urlencode($search_terms),
     ),

    "courses" => array(
        "results" => $class_result_items,
        "search-link" => "/courses/search.php?filter=" . urlencode($search_terms),
    ),

    "news" => array(
        "results" => $news_result_items,
        "search-link" => "/news/?search_terms=" . urlencode($search_terms),
    ),
);

require "$page->branch/search.html";
$page->output();


?>
