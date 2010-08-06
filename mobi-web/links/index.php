<?php


class Links {
  public static $links = array(
    'Faculty of Arts and Sciences' => 'http://www.fas.harvard.edu/home/',
    'Harvard Business School' => 'http://www.hbs.edu/',
    'Harvard College' => 'http://www.college.harvard.edu/',
    'Harvard University Division of Continuing Education' => 'http://www.dce.harvard.edu/',
    'Harvard School of Dental Medicine' => 'http://www.hsdm.harvard.edu/asp-html/',
    'Graduate School of Design' => 'http://www.gsd.harvard.edu/',
    'Harvard Divinity School' => 'http://www.hds.harvard.edu/',
    'Harvard Graduate School of Education' => 'http://www.gse.harvard.edu/',	
    'Harvard School of Engineering and Applied Sciences' => 'http://www.seas.harvard.edu/',
    'Harvard Kennedy School' => 'http://www.ksg.harvard.edu',
    'Graduate School of Arts and Sciences' => 'http://www.gsas.harvard.edu',
    'Harvard Law School' => 'http://www.law.harvard.edu',
    'Harvard Medical School' => 'http://www.hms.harvard.edu/',
    'Harvard School of Public Health' => 'http://www.hsph.harvard.edu',
    'Radcliffe Institute for Advanced Study' => 'http://www.radcliffe.edu',
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
