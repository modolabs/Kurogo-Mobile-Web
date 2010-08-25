"""
These tests require:

- Twill (http://twill.idyll.org/)
- Python 2.7 (http://www.python.org/download/releases/2.7/)

Suggested usage: python check_modules.py 2> error.txt
That way, messages from Twill are not interspersed with error reporting.

"""

"""
Set this to the server you want to use for these tests.
"""
BASE_URL = "http://localhost:8888"
#BASE_URL = "http://mobile-dev.harvard.edu"
#BASE_URL = "http://mobile-staging.harvard.edu"
#BASE_URL = "http://m.harvard.edu"

MOBILE_SAFARI_USER_AGENT = "Mozilla/5.0 (iPhone; U; CPU iPhone OS 3_1_3 like Mac OS X; en-us) AppleWebKit/528.18 (KHTML, like Gecko) Version/4.0 Mobile/7E18 Safari/528.16"
TOUCH_PHONE_USER_AGENT = "BlackBerry9530/4.7.0.167 Profile/MIDP-2.0 Configuration/CLDC-1.1 VendorID/102 UP.Link/6.3.1.20.0 BlackBerry9530/5.0.0.328 Profile/MIDP-2.1 Configuration/CLDC-1.1 VendorID/105"
BLACKBERRY_PLUS_USER_AGENT = "BlackBerry9630/4.7.1.40 Profile/MIDP-2.0 Configuration/CLDC-1.1 VendorID/104"
BASIC_PHONE_USER_AGENT = "LG U880: LG/U880/v1.0"

import unittest
import re
import urllib
from twill import get_browser
from twill.commands import *


""" 
The 'abstract' base class for the test cases.
"""
class TestModuleBase(unittest.TestCase):
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

"""
Class for testing module apis.
"""
class TestModuleAPI(TestModuleBase):
    
    """
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

    """
    module_name: people, dining. Is used to build the URLs for the page in the module.
    user_agent: The user agent string to pass. Some user agent constants are defined 
    at the top of the file.
    branch: e.g. Basic, Touch. The branch the test case should expect to be shown 
    when it uses the user_agent to browse the module.
    platform: The platform the test case should expect to be shown. e.g. bbplus.
    content_check_dict: A dictionary whose keys are regexes that the 
    test case should run against the html returned by the module page. The values 
    are error messages to log when the regexes fail to match anything.
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
            self.assertRegexpMatches(self.browser.get_html(), regex, message)
        
    def verify_images(self):
        # Just checks the images to see if they return 200.
        #echo("Searching this html for images: " + self.browser.get_html())
        imageMatches = re.findall('<img src="([^"]*)"', self.browser.get_html())
        if imageMatches:
            baseURL = self.browser.get_url()
            imageSet = frozenset(imageMatches)
            #echo(imageSet)
            for imageSrc in imageSet:
                imageSrc = urllib.quote(imageSrc)
                echo("Checking image: {}".format(imageSrc))
                try:
                    # Before going the image, go to the baseURL first, in case 
                    # the image is using a relative path in its URL.
                    self.browser.go(baseURL) 
                    self.browser.go(imageSrc)
                    self.assertEqual(self.browser.get_code(), 200, 
                        'The image at {} is not OK. Looking for it resulted in HTTP code: {}'.format(
                        imageSrc, self.browser.get_code()))
                except Exception as inst:
                    self.fail("While checking image {}, encountered exception: {}".format(
                        [imageSrc, inst]))
                    
    # Test helper methods
    def get_page_url(self):
        return self.base_url + '/' + self.module_name + self.location_within_module


# Test suite functions

# Adds a standard group of tests to the test suite for the module. If 
# you need different tests for each branch (Basic, Touch, etc.), create 
# the TestModulePage objects directly.
def add_page_tests_for_module(module_name, suite, contentCheckDict):
    suite.addTest(TestModulePage(module_name, BASIC_PHONE_USER_AGENT, 
        'Basic', '', contentCheckDict))
    suite.addTest(TestModulePage(module_name, TOUCH_PHONE_USER_AGENT, 
        'Touch', '', contentCheckDict))
    suite.addTest(TestModulePage(module_name, MOBILE_SAFARI_USER_AGENT, 
        'Webkit', '', contentCheckDict))
    suite.addTest(TestModulePage(module_name, BLACKBERRY_PLUS_USER_AGENT, 
        'Basic', 'bbplus', contentCheckDict))

# Builds the test suite.
def suite():
    testSuite = unittest.TestSuite()

    # People    
    testSuite.addTest(TestModuleAPI('people', 
        {'q': 'roger brockett', 'command': 'search'}, 'Brockett'))        
    add_page_tests_for_module('people', testSuite, 
        {'<title>People</title>': 'Could not verify index title.'})

    # Map    
    testSuite.addTest(TestModuleAPI('map', {'q': '1737', 'command': 'search'}, 
        '\"Building\ Name\":\"KNAFEL\ BUILDING\"'))    
    add_page_tests_for_module('map', testSuite, 
        {'<title>Map</title>': 'Could not verify index title.'})

    # Calendar
    testSuite.addTest(TestModuleAPI('calendar', {'command': 'categories'}, 
        'Special\ Events'))    
    add_page_tests_for_module('calendar', testSuite, 
        {'<title>Events</title>': 'Could not verify index title.'})
    
    # Courses 
    testSuite.addTest(TestModuleAPI('courses', {'command': 'courses'}, 
        '\"school_name\":\"Harvard\ Business\ School\ -\ MBA\ Program\"'))    
    add_page_tests_for_module('courses', testSuite, 
        {'<title>Courses</title>': 'Could not verify index title.'})
        
    # News
    testSuite.addTest(TestModuleAPI('news', {}, 
        'xmlns:harvard="http://news.harvard.edu/gazette/'))    
    add_page_tests_for_module('news', testSuite, 
        {'<title>News</title>': 'Could not verify index title.'})
        
    # Dining
    testSuite.addTest(TestModuleAPI('dining', {'command': 'hours'}, 
        'lunch_restrictions'))    
    add_page_tests_for_module('dining', testSuite, 
        {'<title>Student Dining</title>': 'Could not verify index title.'})
    
    # Links
    add_page_tests_for_module('links', testSuite, 
        {'<title>Schools</title>': 'Could not verify index title.'})
        
    # Customize
    add_page_tests_for_module('customize', testSuite, 
        {'<title>Customize Home</title>': 'Could not verify index title.'})
    
    # About
    add_page_tests_for_module('mobile-about', testSuite, 
        {'<title>About</title>': 'Could not verify index title.'})
    
    return testSuite

if __name__ == '__main__':
    suite = suite()
    unittest.TextTestRunner(verbosity=2).run(suite)

