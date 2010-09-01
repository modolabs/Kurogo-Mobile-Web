<?php

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 *****************************************************************/

/**** Splits the "contact info" field of events out into individual "email", "phone", "link", and "text"
 * fields for ease of display in mobile app.
 */

class ContactInfo {
  public $email = array();  // Array: standalone email address(es) embedded in the contact info
  public $phone = array();  // Array: standalone phone number(s)
  public $url = array();    // Array: standalone web URL(s) embedded in the contact info
  public $text = array();   // Array: text strings in the contact info (e.g. "RSVP to foo@bar.com") -- may contain phone numbers, email addresses,
                            // or anything else that's not standalone

  public $full;   // String: original full string of the contact info for reference

  // Regular expressions for email, phone, and URL
  const emailPattern = "@^[A-Z0-9._%+-]+\@[A-Z0-9.-]+\.[A-Z]{2,6}$@i"; // From http://www.regular-expressions.info/email.html, should identify most email addresses
  const phonePattern = "@^\(?([0-9]{3}[\)\.\-])? ?[0-9]{3}[\.\-][0-9]{4}$@"; // assuming no international, and either "." or "-" as delimiters
  const urlPattern = "@^((https?://)?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.]*(\?\S+)?)?)*)$@"; // From http://snipplr.com/view/36992/improvement-of-url-interpretation-with-regex/

  public function __construct($contactInfo) {
    // Split on commas; they seem to separate multiple addresses
    // They're probably escaped, but just in case they're not, make the backslash optional.
    $data = preg_split("/(\\\)?,/", $contactInfo);

    // For each value, check to see what it's most likely to be
    foreach ($data as $datum) {
      $datum = trim($datum);
      if (preg_match(self::emailPattern, $datum) > 0) {
        $this->email[] = $datum;
      } elseif (preg_match(self::phonePattern, $datum) > 0) {
        $this->phone[] = preg_replace("/[ \(]/", "", preg_replace("/[\-\)]/", ".", $datum)); // Normalize things into "."s while we're in here
      } elseif (preg_match(self::urlPattern, $datum) > 0) {
        $this->url[] = $datum;
      } else {
        $this->text[] = $datum;
      }
    }

    $this->full = $contactInfo;
  }

}



/**** MAIN ****/

/****
 * MAIN FOR TESTING - this class should be used
 * with the real events data feed.
 *
 */
/*
echo "Testing...\n";

$testdata = array();
$testdata[] = "jean_gauthier@harvard.edu, dmorley@fas.harvard.edu\, linda_schneider@harvard.edu";
$testdata[] = "www.realcolegiocomplutense.harvard.edu";
$testdata[] = "617-495-8576\, susannah_hutchison@harvard.edu";
$testdata[] = "617-495-8576\, 617.714.7992";
$testdata[] = "617-495-8576, 617.714.7992";
$testdata[] = "617.714.7992";
$testdata[] = "(617) 714.7992";
$testdata[] = "(617)714.7992";
$testdata[] = "paul_massari@harvard.edu";
$testdata[] = "RSVP to melissa_danello@hks.harvard.edu";
$testdata[] = "617.495.4544\, artmuseum_membership@harvard.edu to register";
$testdata[] = "No reservations required";
$testdata[] = "http://www.harvard.edu, https://www.google.com\,foo@bar.com,617-555.1212\,Use this phone number: 617.555.1212\,";


foreach ($testdata as $testdatum) {
  echo "Original text: \n  ".$testdatum."\n";
  $contact = new ContactInfo($testdatum);
  echo "\nParsed contact info:\n";
  echo "  email: ".implode("\n         ", $contact->email)."\n";
  echo "  phone: ".implode("\n         ", $contact->phone)."\n";
  echo "  url: ".implode("\n       ", $contact->url)."\n";
  echo "  text: ".implode("\n           ", $contact->text)."\n";
  echo "\n";
  echo "  full: ".$contact->full."\n";
  echo "-----\n";
}

exit(0);
*/
?>
