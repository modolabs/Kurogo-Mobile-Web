"""

/****************************************************************
 *
 *  Copyright 2010 The President and Fellows of Harvard College
 *  Copyright 2010 Modo Labs Inc.
 *
 ****************************************************************/

These tests require:

- Twill (http://twill.idyll.org/)
- Python 2.7 (http://www.python.org/download/releases/2.7/)

Suggested usage: python check_modules.py 2> error.txt
That way, messages from Twill are not interspersed with error reporting.
"""


"""Set this to the server you want to use for these tests."""
#BASE_URL = "http://localhost:8888"
BASE_URL = "http://mobile-dev.harvard.edu"
#BASE_URL = "http://mobile-staging.harvard.edu"
#BASE_URL = "http://m.harvard.edu"

MOBILE_SAFARI_USER_AGENT = "Mozilla/5.0 (iPhone; U; CPU iPhone OS 3_1_3 like Mac OS X; en-us) AppleWebKit/528.18 (KHTML, like Gecko) Version/4.0 Mobile/7E18 Safari/528.16"
# This is the BlackBerry Storm.
TOUCH_PHONE_USER_AGENT = "BlackBerry9530/4.7.0.167 Profile/MIDP-2.0 Configuration/CLDC-1.1 VendorID/102 UP.Link/6.3.1.20.0 BlackBerry9530/5.0.0.328 Profile/MIDP-2.1 Configuration/CLDC-1.1 VendorID/105"
BLACKBERRY_PLUS_USER_AGENT = "BlackBerry9630/4.7.1.40 Profile/MIDP-2.0 Configuration/CLDC-1.1 VendorID/104"
BASIC_PHONE_USER_AGENT = "LG U880: LG/U880/v1.0"

from sys import exit
import unittest
import re
import urllib
import httplib
from urlparse import urljoin, urlsplit, urlunsplit
from twill import get_browser
from twill.commands import *


class TestModuleBase(unittest.TestCase):
    """The 'abstract' base class for the test cases."""
    def __init__(self, module_name, 
                 user_agent=BASIC_PHONE_USER_AGENT, branch='Basic', platform='',
                 methodName='runTest'):
        unittest.TestCase.__init__(self, methodName)
        self.base_url = BASE_URL
        self.module_name = module_name
        self.user_agent = user_agent
        self.branch = branch
        self.platform = platform
                
    def setUp(self):
        self.browser = get_browser()
        self.browser.set_agent_string(self.user_agent)
        self.browser.clear_cookies()


class TestModuleAPI(TestModuleBase):    
    """Class for testing module apis.
    
    module_name: e.g. people, dining. Is used as the module argument in the 
    API query.
    api_argument_dict: Arguments for the API query other than module.
    apiResultCheckRegex: The test case will run this regex against the API 
    output to determine whether or not the call was successful.
    """
    
    def __init__(self, module_name, 
                 api_argument_dict = {}, api_result_check_regex = '*'):
        TestModuleBase.__init__(self, module_name)
        self.api_argument_dict = api_argument_dict
        self.api_result_check_regex = api_result_check_regex

    def runTest(self):
        self.test_api()
        
    def test_api(self):
        self.hit_api_with_arguments(self.api_argument_dict)
        self.assertEqual(self.browser.get_code(), 200)
        self.verify_api_results()
        
    def hit_api_with_arguments(self, argumentDict):
        if 'module' not in argumentDict:
            argumentDict['module'] = self.module_name

        queryString = urllib.urlencode(argumentDict)
        #echo("Query: {}".format(end_with_slash(self.base_url) + 'api/?' + queryString))
        self.browser.go(self.base_url + '/api/?' + queryString)
            
    def verify_api_results(self):
        self.assertRegexpMatches(self.browser.get_html(), self.api_result_check_regex,
            'Could not find API result matching "{}".'.format(self.api_result_check_regex))


class TestModulePage(TestModuleBase):
    """Class for testing a page in a module. 
    
    Checks status, branch, platform, images, and looks for whatever content you 
    specify.
    
    module_name: people, dining. Is used to build the URLs for the page in the 
    module.
    
    user_agent: The user agent string to pass. Some user agent constants are 
    defined at the top of the file.
    
    branch: e.g. Basic, Touch. The branch the test case should expect to be 
    shown when it uses the user_agent to browse the module.
    
    platform: The platform the test case should expect to be shown. e.g. bbplus.
    
    content_check_dict: A dictionary whose keys are regexes that the 
    test case should run against the html returned by the module page. The 
    values are error messages to log when the regexes fail to match anything.
    
    location_within_module: The location of the page to test relative to 
    host/module_name/. Leave as '' to test the index page.
    """
    
    def __init__(self, module_name, 
                 user_agent=BASIC_PHONE_USER_AGENT, branch='Basic', platform='',
                 content_check_dict = {}, location_within_module = ''):
        TestModuleBase.__init__(self, module_name, user_agent, branch, platform)
        self.location_within_module = location_within_module
        self.content_check_dict = content_check_dict

    def runTest(self):
        self.browser.go(self.get_page_url())
        self.assertEqual(self.browser.get_code(), 200, 
            '{}: The page at {} is not OK. It returned HTTP code: {}'.format(
                self.module_name, self.get_page_url(), self.browser.get_code()))
        self.verify_branch()
        self.verify_platform()
        self.verify_contents()
        self.verify_images()
        
    # Verification methods
    def verify_branch(self):
        self.assertRegexpMatches(self.browser.get_html(), 
            '<!--\ Branch:\ "{}"'.format(self.branch),
            "{} is not displaying the {} branch for user agent {}.".format(
            self.browser.get_url(), self.branch, self.user_agent))
    
    def verify_platform(self):
        if self.platform:
            self.assertRegexpMatches(self.browser.get_html(), 
                '<!--\ Platform:\ "{}"'.format(self.platform),
                "{} is not displaying the {} platform for user agent {}.".format(
                self.browser.get_url(), self.platform, self.user_agent))
        
    def verify_contents(self):
        for regex, message in self.content_check_dict.iteritems():
            self.assertRegexpMatches(self.browser.get_html(), regex, 
                '{}, {}/{}: Content check for the page at {} failed. {}\n'.format(
                    self.module_name, self.branch, self.platform, 
                    self.get_page_url(), message))
        
    def verify_images(self):
        # Just checks the images to see if they return 200.
        #echo("Searching this html for images: " + self.browser.get_html())
        imageMatches = re.findall('<img .*?src="([^"]*)"', self.browser.get_html())
        if imageMatches:
            baseURL = self.browser.get_url()
            imageSet = frozenset(imageMatches)
            echo(baseURL)
            #echo(imageSet)
            for imageSrc in imageSet:
                self.verify_image(baseURL, imageSrc)

    def verify_image(self, baseURL, imageURL):
        fullImageURL = imageURL
        if not urlsplit(imageURL).scheme:
            # Resolve relative path
            fullImageURL = urljoin(baseURL, imageURL)

        echo("Checking image: {}".format(fullImageURL))
        urlparts = urlsplit(fullImageURL)
        escapedparts = self.get_escaped_address_parts_minus_host(urlparts)
        
        if urlparts.netloc and urlparts.path:
            try:                    
                conn = httplib.HTTPConnection(urlparts.netloc)
                conn.request("HEAD", urlunsplit(escapedparts))
                echo("Going to path: {}\n".format(urlunsplit(escapedparts)))
                res = conn.getresponse()
            except Exception as inst:
                self.fail("While checking image {}, encountered exception: {}".format(
                    fullImageURL, inst))
                    
            self.assertEqual(res.status, 200, 
                'The image at {} is not OK. Looking for it resulted in HTTP code: {}'.format(
                    urlunsplit([urlparts.scheme, urlparts.netloc, escapedparts[2], 
                        escapedparts[3], escapedparts[4]]), 
                    res.status))
        else:
            self.fail("The URL for this image is invalid: {}".format(fullImageURL))
        
    # Test helper methods
    def get_page_url(self):
        return self.base_url + '/' + self.module_name + self.location_within_module

    def get_escaped_address_parts_minus_host(self, urlsplitparts):
        """
        Takes the result of urlsplitting something like 'http://hostname.com/path/?querysting=args 
        and returns the parts corresponding to just path/?querysting=args, with appropriate parts safely 
        escaped.
        
        urlpartslist: urlparse.SplitResult.
        """
        urlpartscopy = list(urlsplitparts)
        for i in range(5):
            if i < 2:
                # Drop the scheme and netloc from the copy.
                urlpartscopy[i] = ''
            else:
                if i == 2:
                    # Make sure this part is escaped properly.
                    urlpartscopy[i] = urllib.quote(urlpartscopy[i]) 
        return urlpartscopy
        
# Test suite functions

def add_page_tests_for_module(module_name, suite, pagesAndContentChecksDict, 
    configurations = {
        BASIC_PHONE_USER_AGENT: ['Basic', ''], 
        TOUCH_PHONE_USER_AGENT: ['Touch', ''], 
        MOBILE_SAFARI_USER_AGENT: ['Webkit', ''], 
        BLACKBERRY_PLUS_USER_AGENT: ['Basic', 'bbplus']}
    ):
    """Adds a standard group of tests to the test suite for the module. 
    
    pagesAndContentChecksDict: A dictionary:
    
        Key: Addresses local to the module. (e.g. 'help.php' in the module people 
        would point to http://m.harvard.edu/people/help.php)
        
        Value: A dictionary that details what to check for in the output:
        
            Key: A regex to run against the html from the loaded page.
            
            Value: A message to display in the failture log if that regex 
                doesn't match anything.
    
    configurations: A dictionary mapping branch/platform pairs to user agents 
    that should be used for the added tests. Use the default value if you 
    want to add tests for all of the configurations.
    
    If you need content checks specific to each branch (Basic, Touch, etc.) 
    of a module index, create the TestModulePage objects directly.        
    """
    
    for page, contentCheckDict in pagesAndContentChecksDict.iteritems():
        for useragent, branch_platform_pair in configurations.iteritems():
            suite.addTest(TestModulePage(module_name, useragent, 
                branch_platform_pair[0], branch_platform_pair[1], 
                contentCheckDict, page))

def suite():
    """Builds the test suite to be run by this script."""
    
    testSuite = unittest.TestSuite()
    
    # People    
    testSuite.addTest(TestModuleAPI('people', 
        {'q': 'roger brockett', 'command': 'search'}, 'Brockett'))
        # TODO: More API tests.
    add_page_tests_for_module('people', testSuite, {
        '/': 
            {'<title>People</title>': 
            'Could not verify index title.'},
        '/index.php?filter=brockett&sch_btn=Search': 
            {'<(span|div) class=\"value\">An Wang Professor of Electrical Engineering and Computer Science</(span|div)>': 
            'Search for brockett failed.'},
        '/help.php':
            {'If you are a member of the Harvard community concerned about your privacy settings':
            'Help page privacy clause not found.'}
    })
    
    # Map    
    testSuite.addTest(TestModuleAPI('map', {'q': '1737', 'command': 'search'}, 
        '\"Building\ Name\":\"KNAFEL\ BUILDING\"'))    
    add_page_tests_for_module('map', testSuite, {
        '/':
            {'<title>Map</title>': 
            'Could not verify index title.'},
        '/search.php?filter=knafel&x=0&y=0':
            {'1737&shy; CAMBRIDGE&shy; ST':
            'Could not find the Knafel building.'},
        '/?category=Libraries.0':
            {'UNIVERSITY HERBARIA':
            'Could not find the Herbaria in the libraries category.'},
        '/?category=Dining.0':
            {'Barker Rotunda':
            'Could not find the Barker Rotunda in the dining category.'},
        '/detail.php?selectvalues=CRONKHITE+CENTER&category=Housing.2&info%5BBuilding+Name%5D=CRONKHITE+CENTER&info%5BAddress%5D=84-86+BRATTLE+ST&info%5BCity%5D=Cambridge&info%5BEligibility%5D=GSE%2C+GSD%2C+GSAS%2C+HDS%2C+KSG&info%5BMore+Info%5D=http%3A%2F%2Fradcliffe.harvard.edu%2Fabout%2Fhousing.aspx&info%5BHousing+Type%5D=Radcliffe+Institute&info%5BPhoto%5D=03022+CRONKHITE+GRADUATE+CENTER+NW+obl+020607.png&back=Browse':
            {'84-86(&shy;)* BRATTLE(&shy;)* ST':
            'Could not find the map of CRONKHITE CENTER.'},
        '/help.php':
            {'Search for buildings by short or long name':
            'Help text could not be found.'}
    })

    # Calendar
    testSuite.addTest(TestModuleAPI('calendar', {'command': 'categories'}, 
        'Special\ Events'))    
    add_page_tests_for_module('calendar', testSuite, {
        '/':
            {'<title>Events</title>': 
            'Could not verify index title.',
            # TODO: Academic calendar link, really all the links.
            },
        '/day.php?time=\d+&type=events':
            {'<a href="day.php\?time=\d+&type=events">.* | <a href="day.php\?time=\d+&type=events">': 
            'Could not find next day and previous day links.'},
        '/categorys.php':
            {'<a href="category.php\?id=41150&name=Special\+Events">Special Events</a>': 
            'Could not find Special Events category link.'},
        '/category.php?id=41150&name=Special+Events':
            {}, # Nothing we can count on being in the page, so just make sure the page is there.
        '/academic.php?year=2010':
            {'Martin Luther King Day': 
            'Could not find Martin Luther King Day in academic calendar.'}
    })
    
    # Courses 
    testSuite.addTest(TestModuleAPI('courses', {'command': 'courses'}, 
        '\"school_name\":\"Harvard\ Business\ School\ -\ MBA\ Program\"'))    
    add_page_tests_for_module('courses', testSuite, {
        '/':
            {'<title>Courses</title>': 
            'Could not verify index title.'},
        '/searchMain.php?filter=differential+equations&courseGroup=&courseName=&sch_btn=Search':
            {}, # Nothing we can count on being in the page, so just make sure the page is there.
        '/detail.php?back=Search&id=d_dce_1011_1_MATH_E-15_10436&courseGroup=Harvard+Extension+School&courseGroupShort=Continuing+Education&courseName=&courseNameShort=&filter=calculus':
            {'<h2>MATH E-15: Introduction to the Calculus A</h2>': 
            'Could not find the name of the course.'},
        '/course.php?back=&id=Law&idShort=Law&courseGroup=Harvard+Law+School&courseGroupShort=Law':
            {'<a href="detail.php': 
            'Could not find a link to a law course.'},
        '/detail.php?back=School%7CListing&id=d_fas_1011_1_COMPSCI_50_1&courseGroup=Faculty+of+Arts+and+Sciences&courseGroupShort=Faculty+of+Arts+and+Sciences&courseName=Computer+Science&courseNameShort=Computer+Science&filter=':
            {'<a href=".*?>Course Website</a>':
            'Could not find the course website link in detail page for COMPSCI 50'},
        '/detail.php?back=School%7CListing&id=d_fas_1011_1_APMTH_21a_1&courseGroup=Faculty+of+Arts+and+Sciences&courseGroupShort=Faculty+of+Arts+and+Sciences&courseName=Applied+Mathematics&courseNameShort=Applied+Mathematics&filter=':
            {'<a href=".*?>Course Website</a>':
            'Could not find the course website link in detail page for APMTH 21A'}
    })
    
    # News
    testSuite.addTest(TestModuleAPI('news', {}, 
        'xmlns:harvard="http://news.harvard.edu/gazette/'))    
    add_page_tests_for_module('news', testSuite, {
        '/':
            {'<title>News</title>': 
            'Could not verify index title.'},
        '/index.php?category_id=0&category_seek_id=52703&category_seek_direction=forward':
            {},
            #{'(>Previous (S|s)tories<)':
            #'Could not find Previous Stories link.'},
        '/index.php?category_id=5':
            {'>Athletics</':
            'Could not find the Athletics category header.'},
        '/?search_terms=harvard&category_id=0&category_seek_direction=forward':
            {'href="story\.php':
            'Could not find a single story link after searching for "Harvard".'}
        })
        # TODO: Check a story itself.
    # Dining
    testSuite.addTest(TestModuleAPI('dining', {'command': 'hours'}, 
        'lunch_restrictions'))    
    add_page_tests_for_module('dining', testSuite, {
        '/':
            {'<title>Student Dining</title>': 
            'Could not verify index title.',
            '<a href="index.php\?time=\d+(&tab=locations)*">.*\w|(&nbsp;)|\w|(&nbsp;)<a href="index.php\?time=\d+(&tab=locations)*">': 
            'Could not find next day and previous day links.'},
        '/detail.php?location=Adams':
            {'<h3>Interhouse Restrictions</h3>':
            'Could not find Interhouse Restrictions.'},
        '/help.php':
            {'The student dining home screen shows you the menu for the current or upcoming meal available in Harvard&apos;s dining halls.':
            'Could not find help text.'}
    })

    # Links
    add_page_tests_for_module('links', testSuite, {
        '/':
            {'<title>Schools</title>': 'Could not verify index title.'}
        })
    # Customize
    add_page_tests_for_module('customize', testSuite, {
        '/':
            {'<title>Customize Home</title>': 'Could not verify index title.'}
        })
        
    # About
    add_page_tests_for_module('mobile-about', testSuite, {
        '/':
            {'<title>About</title>': 
            'Could not verify index title.'},
        '/?page=about_site':
            {'The Harvard mobile web application is part of a broader initiative':
            'Could not find about site text.'},
        '/?page=about':
            {'Established in 1636, Harvard is the oldest institution':
            'Could not find about Harvard text.'}
        })

    # Home
    add_page_tests_for_module('home', testSuite, {
        '/':
            {'<title>Harvard Mobile Web</title>': 
            'Could not verify index title.'},
        })
    # Check for BlackBerry shortcut link only for BlackBerry platforms.
    add_page_tests_for_module('home', testSuite, {
        '/':
            {'Add the BlackBerry shortcut to your home screen': 
            'Could not find BlackBerry shortcut link.'}},
        { 
            TOUCH_PHONE_USER_AGENT: ['Touch', ''], 
            BLACKBERRY_PLUS_USER_AGENT: ['Basic', 'bbplus']})
    
    return testSuite


if __name__ == '__main__':
    suite = suite()
    result = unittest.TextTestRunner(verbosity=2).run(suite)
    if result.wasSuccessful():
        exit(0)
    else:
        exit(1)

