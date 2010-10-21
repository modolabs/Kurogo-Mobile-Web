<?
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_lib_constants.php";
require_once(LIBDIR . "StellarData.php");

StellarData::init();

//print_r(StellarData::get_courses());
//print_r(StellarData::get_subjects(7));
//StellarData::get_subjects(99);
//print_r(StellarData::get_announcements_xml(7.012));
//var_dump(StellarData::get_subject_id('7.012'));
//print_r(StellarData::get_subject_info('7.012'));
StellarData::push_subscribe('99.999', 'dummy');
print_r(StellarData::$subscriptions);
//print_r(StellarData::get_announcements('7.012'));
StellarData::push_unsubscribe('99.999', 'dummy');
print_r(StellarData::$subscriptions);


''

?>