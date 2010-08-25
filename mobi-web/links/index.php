<?php


class Links {
  public static $links = array(
    'Business' => 'http://www.hbs.edu/',
    'College' => 'http://www.college.harvard.edu/',
    'Continuing Education' => 'http://www.dce.harvard.edu/',
    'Dental' => 'http://www.hsdm.harvard.edu/asp-html/',
    'Design' => 'http://www.gsd.harvard.edu/',
    'Divinity' => 'http://www.hds.harvard.edu/',
    'Education' => 'http://www.gse.harvard.edu/',	
    'Engineering' => 'http://www.seas.harvard.edu/',
    'Government' => 'http://www.ksg.harvard.edu',
    'Graduate School' => 'http://www.gsas.harvard.edu',
    'Law' => 'http://www.law.harvard.edu',
    'Medical' => 'http://www.hms.harvard.edu/',
    'Public Health' => 'http://www.hsph.harvard.edu',
    'Radcliffe' => 'http://www.radcliffe.edu',
    'FAS' => 'http://www.fas.harvard.edu/home/',
  );
}

$links = array();
foreach(Links::$links as $name => $link) {
  $links[] = array(
    "name" => htmlentities($name),
    "link" => $link,
  );
}

require "$page->branch/index.html";

$page->cache();
$page->output();
    
?>
