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
g_base_url = "http://localhost:8888"
#g_base_url = "http://mobile-dev.harvard.edu"
#g_base_url = "http://mobile-staging.harvard.edu/"
#g_base_url = "http://m.harvard.edu"

# TODO: ALL CAPS
g_mobileSafariUserAgent = "Mozilla/5.0 (iPhone; U; CPU iPhone OS 3_1_3 like Mac OS X; en-us) AppleWebKit/528.18 (KHTML, like Gecko) Version/4.0 Mobile/7E18 Safari/528.16"
g_touchPhoneUserAgent = "BlackBerry9530/4.7.0.167 Profile/MIDP-2.0 Configuration/CLDC-1.1 VendorID/102 UP.Link/6.3.1.20.0 BlackBerry9530/5.0.0.328 Profile/MIDP-2.1 Configuration/CLDC-1.1 VendorID/105"
g_blackberryPlusUserAgent = "BlackBerry9630/4.7.1.40 Profile/MIDP-2.0 Configuration/CLDC-1.1 VendorID/104"
g_basicPhoneUserAgent = "LG U880: LG/U880/v1.0"

import unittest
from twill import get_browser
from twill.commands import *
import re
import urllib

""" Utility functions. """
def endWithSlash(string):
    if not string.endswith('/'):
        string += '/'
    return string

""" 
The 'abstract' base class for the test cases.
"""
class TestModuleBase(unittest.TestCase):
    def __init__(self, moduleName, 
                 userAgent=g_basicPhoneUserAgent, branch='Basic', platform='',
                 methodName='runTest'):
        unittest.TestCase.__init__(self, methodName)
        self.baseUrl = g_base_url
        self.moduleName = moduleName
        self.userAgent = userAgent
        self.branch = branch
        self.platform = platform
                
    def setUp(self):
        self.browser = get_browser()
        self.browser.set_agent_string(self.userAgent)
        self.browser.clear_cookies()

"""
Class for testing module apis.
"""
class TestModuleAPI(TestModuleBase):
    
    """
    moduleName: e.g. people, dining. Is used as the module argument in the 
    API query.
    apiArgumentDict: Arguments for the API query other than module.
    apiResultCheckRegex: The test case will run this regex against the API 
    output to determine whether or not the call was successful.
    """
    def __init__(self, moduleName, 
                 apiArgumentDict = {}, apiResultCheckRegex = '*'):
        TestModuleBase.__init__(self, moduleName)
        self.apiArgumentDict = apiArgumentDict
        self.apiResultCheckRegex = apiResultCheckRegex

    def runTest(self):
        self.test_api()
        
    def test_api(self):
        self.hit_api_with_arguments(self.apiArgumentDict)
        self.assertEqual(self.browser.get_code(), 200)
        self.verify_api_results()
        
    def hit_api_with_arguments(self, argumentDict):
        if 'module' not in argumentDict:
            argumentDict['module'] = self.moduleName

        queryString = '' # todo: Look for urllib method for this.
        for arg, val in argumentDict.iteritems():
            if len(queryString) > 0:
                queryString += '&'
            queryString += (arg + '=' + val)

        echo("Query: {}".format(endWithSlash(self.baseUrl) + 'api/?' + queryString))
        self.browser.go(endWithSlash(self.baseUrl) + 'api/?' + queryString)
            
    def verify_api_results(self):
        self.assertRegexpMatches(self.browser.get_html(), self.apiResultCheckRegex,
            'Could not find API result matching "{}".'.format(self.apiResultCheckRegex))

class TestModulePage(TestModuleBase):

    """
    moduleName: people, dining. Is used to build the URLs for the page in the module.
    userAgent: The user agent string to pass. Some user agent constants are defined 
    at the top of the file.
    branch: e.g. Basic, Touch. The branch the test case should expect to be shown 
    when it uses the userAgent to browse the module.
    platform: The platform the test case should expect to be shown. e.g. bbplus.
    contentCheckRegExesAndMessageDict: A dictionary whose keys are regexes that the 
    test case should run against the html returned by the module page. The values 
    are error messages to log when the regexes fail to match anything.
    locationWithinModule: The location of the page to test relative to 
    host/moduleName/. Leave as '' to test the index page.
    """
    def __init__(self, moduleName, 
                 userAgent=g_basicPhoneUserAgent, branch='Basic', platform='',
                 contentCheckRegExesAndMessageDict = {}, locationWithinModule = ''):
        TestModuleBase.__init__(self, moduleName, userAgent, branch, platform)
        self.locationWithinModule = locationWithinModule
        self.contentCheckRegExesAndMessageDict = contentCheckRegExesAndMessageDict

    def runTest(self):
        self.browser.go(self.get_page_url())
        self.assertEqual(self.browser.get_code(), 200, 
            '{}: The page at {} is not OK. It returned HTTP code: {}'.format(
                self.moduleName, self.get_page_url(), self.browser.get_code()))
        self.verify_branch()
        self.verify_platform()
        self.verify_contents()
        self.verify_images()
        
    # Verification methods
    def verify_branch(self):
        self.assertRegexpMatches(self.browser.get_html(), 
            '<!--\ Branch:\ "{}"'.format(self.branch),
            "{} is not displaying the {} branch for user agent {}.".format(
            self.browser.get_url(), self.branch, self.userAgent))
    
    def verify_platform(self):
        if self.platform:
            self.assertRegexpMatches(self.browser.get_html(), 
                '<!--\ Platform:\ "{}"'.format(self.platform),
                "{} is not displaying the {} platform for user agent {}.".format(
                self.browser.get_url(), self.platform, self.userAgent))
        
    def verify_contents(self):
        for regex, message in self.contentCheckRegExesAndMessageDict.iteritems():
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
        moduleURL = endWithSlash(endWithSlash(self.baseUrl) + self.moduleName)
        return moduleURL + self.locationWithinModule


# Test suite functions

# Adds a standard group of tests to the test suite for the module. If 
# you need different tests for each branch (Basic, Touch, etc.), create 
# the TestModulePage objects directly.
def add_page_tests_for_module(moduleName, suite, contentCheckDict):
    suite.addTest(TestModulePage(moduleName, g_basicPhoneUserAgent, 
        'Basic', '', contentCheckDict))
    suite.addTest(TestModulePage(moduleName, g_touchPhoneUserAgent, 
        'Touch', '', contentCheckDict))
    suite.addTest(TestModulePage(moduleName, g_mobileSafariUserAgent, 
        'Webkit', '', contentCheckDict))
    suite.addTest(TestModulePage(moduleName, g_blackberryPlusUserAgent, 
        'Basic', 'bbplus', contentCheckDict))

# Builds the test suite.
def suite():
    testSuite = unittest.TestSuite()

    # People    
    testSuite.addTest(TestModuleAPI('people', 
        {'q': 'roger+brockett', 'command': 'search'}, 'Brockett'))        
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

