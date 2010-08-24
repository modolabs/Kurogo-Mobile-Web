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

g_mobileSafariUserAgent = "Mozilla/5.0 (iPhone; U; CPU iPhone OS 3_1_3 like Mac OS X; en-us) AppleWebKit/528.18 (KHTML, like Gecko) Version/4.0 Mobile/7E18 Safari/528.16"
g_touchPhoneUserAgent = "BlackBerry9530/4.7.0.167 Profile/MIDP-2.0 Configuration/CLDC-1.1 VendorID/102 UP.Link/6.3.1.20.0 BlackBerry9530/5.0.0.328 Profile/MIDP-2.1 Configuration/CLDC-1.1 VendorID/105"
g_blackberryPlusUserAgent = "BlackBerry9630/4.7.1.40 Profile/MIDP-2.0 Configuration/CLDC-1.1 VendorID/104"
g_basicPhoneUserAgent = "LG U880: LG/U880/v1.0"

import unittest
from twill import get_browser
from twill.commands import *
import re


""" Utility functions. """
def endWithSlash(string):
    if string[-1] != '/':
        string += '/'
    return string


""" 
Base test case. Its tests go to the module pages and check for 200, but 
do not define how the contents should be checked. Subclasses should do that.
"""

class TestModule(unittest.TestCase):

    def __init__(self, methodName='runTest', userAgent=g_basicPhoneUserAgent, branch='Basic', platform=''):
        unittest.TestCase.__init__(self, methodName)
        self.baseUrl = g_base_url
        self.moduleName = '' # Should be overridden.
        self.userAgent = userAgent
        self.branch = branch
        self.platform = platform
                
    def setUp(self):
        self.browser = get_browser()
        self.browser.set_agent_string(self.userAgent)
        self.browser.clear_cookies()

    # Tests
    def test_index(self):
        self.goToModulePage()
        self.assertEqual(self.browser.get_code(), 200, 
            'The ' + self.moduleName + ' module index page is not OK. It returned HTTP code: ' 
            + str(self.browser.get_code()))
        self.verifyBranch()
        self.verifyPlatform()
        self.verifyPageContents()
        self.verifyImages()

    def test_api(self):
        # Override in subclass.
        self.assertTrue(True)
        
    # Verification methods
    def verifyBranch(self):
        self.assertRegexpMatches(self.browser.get_html(), '<!--\ Branch:\ "' + self.branch + '"',
            self.browser.get_url() + " is not displaying the " + self.branch + " branch for user agent " 
            + self.userAgent)
    
    def verifyPlatform(self):
        if len(self.platform) > 0:
            self.assertRegexpMatches(self.browser.get_html(), '<!--\ Platform:\ "' + self.platform + '"',
                self.browser.get_url() + " is not displaying the " + self.platform 
                + " platform for user agent " + self.userAgent)
        
    def verifyPageContents(self):
        # Override this in subclasses.
        self.assertTrue(True)

    def verifyAPIResults(self):
        # Override this in subclasses.
        self.assertTrue(True)
        
    def verifyImages(self):
        # Just checks the images to see if they return 200.
        echo("Searching this html for images: " + self.browser.get_html())
        imageMatches = re.findall('<img src="([^"]*)"', self.browser.get_html())
        if not imageMatches is None:
            baseURL = self.browser.get_url()
            imageSet = set(imageMatches)
            echo(imageSet)
            for imageSrc in imageSet:
                echo("Checking image: " + imageSrc)
                try:
                    # Before going the image, go to the baseURL first, in case the image is using a relative path in its URL.
                    self.browser.go(baseURL) 
                    self.browser.go(imageSrc)
                    self.assertEqual(self.browser.get_code(), 200, 
                        'The image at ' + imageSrc + ' is not OK. Looking for it resulted in HTTP code: ' 
                        + str(self.browser.get_code()))
                except Exception as inst:
                    self.fail("While checking image " + imageSrc + ", encountered exception: " + str(inst))
                    
    # Test helper methods
    def goToModulePage(self):
        self.browser.go(endWithSlash(endWithSlash(self.baseUrl) + self.moduleName))
        
    def hitAPIWithArguments(self, argumentDict):
        if not 'module' in argumentDict:
            argumentDict['module'] = self.moduleName
        
        queryString = ''
        for arg, val in argumentDict.iteritems():
            if len(queryString) > 0:
                queryString += '&'
            queryString += (arg + '=' + val)
            
        self.browser.go(endWithSlash(self.baseUrl) + 'api/?' + queryString)


"""
Module-specfic test cases.
"""        

class TestPeopleModule(TestModule):
    
    def __init__(self, methodName='runTest', userAgent=g_basicPhoneUserAgent, branch='Basic', platform=''):        
        TestModule.__init__(self, methodName, userAgent, branch, platform)
        self.moduleName = 'people'
    
    def verifyPageContents(self):
        # browser.get_title() doesn't seem to work.
        self.assertRegexpMatches(self.browser.get_html(), '<title>People</title>', 
            'Could not verify index title.')
        #echo(self.browser.get_html())

    def test_api(self):
        self.hitAPIWithArguments({ 'q': 'roger+brockett', 'command': 'search'})
        self.assertEqual(self.browser.get_code(), 200)
        self.verifyAPIResults()
                
    def verifyAPIResults(self):
        # TODO: A little more precision.
        self.assertRegexpMatches(self.browser.get_html(), 'Brockett',
            'Could not find Brockett result.')

class TestMapModule(TestModule):

    def __init__(self, methodName='runTest', userAgent=g_basicPhoneUserAgent, branch='Basic', platform=''):
        TestModule.__init__(self, methodName, userAgent, branch, platform)
        self.moduleName = 'map'

    def verifyPageContents(self):
        # browser.get_title() doesn't seem to work.
        self.assertRegexpMatches(self.browser.get_html(), '<title>Map</title>', 
            'Could not verify index title.')
        #echo(self.browser.get_html())

    def test_api(self):
        self.hitAPIWithArguments({ 'q': '1737', 'command': 'search'})
        self.assertEqual(self.browser.get_code(), 200)
        self.verifyAPIResults()
            
    def verifyAPIResults(self):
        self.assertRegexpMatches(self.browser.get_html(), '\"Building\ Name\":\"KNAFEL\ BUILDING\"',
            'Could not find Knafel building.')

class TestCalendarModule(TestModule):

    def __init__(self, methodName='runTest', userAgent=g_basicPhoneUserAgent, branch='Basic', platform=''):
        TestModule.__init__(self, methodName, userAgent, branch, platform)
        self.moduleName = 'calendar'

    def verifyPageContents(self):
        # browser.get_title() doesn't seem to work.
        self.assertRegexpMatches(self.browser.get_html(), '<title>Events</title>', 
            'Could not verify index title.')
        #echo(self.browser.get_html())

    def test_api(self):
        self.hitAPIWithArguments({'command': 'categories'})
        self.assertEqual(self.browser.get_code(), 200)
        self.verifyAPIResults()
            
    def verifyAPIResults(self):
        self.assertRegexpMatches(self.browser.get_html(), 'Special\ Events',
            'Could not find Special Event category.')

class TestCoursesModule(TestModule):

    def __init__(self, methodName='runTest', userAgent=g_basicPhoneUserAgent, branch='Basic', platform=''):
        TestModule.__init__(self, methodName, userAgent, branch, platform)
        self.moduleName = 'courses'

    def verifyPageContents(self):
        # browser.get_title() doesn't seem to work.
        self.assertRegexpMatches(self.browser.get_html(), '<title>Courses</title>', 
            'Could not verify index title.')
        #echo(self.browser.get_html())

    def test_api(self):
        self.hitAPIWithArguments({'command': 'courses'})
        self.assertEqual(self.browser.get_code(), 200)
        self.verifyAPIResults()

    def verifyAPIResults(self):
        self.assertRegexpMatches(self.browser.get_html(), '\"school_name\":\"Harvard\ Business\ School\ -\ MBA\ Program\"',
            'Could not find Harvard Business School Doctoral Program school in JSON results.')

class TestNewsModule(TestModule):

    def __init__(self, methodName='runTest', userAgent=g_basicPhoneUserAgent, branch='Basic', platform=''):
        TestModule.__init__(self, methodName, userAgent, branch, platform)
        self.moduleName = 'news'

    def verifyPageContents(self):
        # browser.get_title() doesn't seem to work.
        self.assertRegexpMatches(self.browser.get_html(), '<title>News</title>', 
            'Could not verify index title.')
        #echo(self.browser.get_html())

    def test_api(self):
        self.hitAPIWithArguments({})
        self.assertEqual(self.browser.get_code(), 200)
        self.verifyAPIResults()
            
    def verifyAPIResults(self):
        # Look for part of RSS header.        
        self.assertRegexpMatches(self.browser.get_html(), 'xmlns:harvard="http://news.harvard.edu/gazette/',
            'Could not find namespace in RSS header.')

class TestDiningModule(TestModule):

    def __init__(self, methodName='runTest', userAgent=g_basicPhoneUserAgent, branch='Basic', platform=''):
        TestModule.__init__(self, methodName, userAgent, branch, platform)
        self.moduleName = 'dining'

    def verifyPageContents(self):
        # browser.get_title() doesn't seem to work.
        self.assertRegexpMatches(self.browser.get_html(), '<title>Student Dining</title>', 
            'Could not verify index title.')
        #echo(self.browser.get_html())

    def test_api(self):
        self.hitAPIWithArguments({'command': 'hours'})
        self.assertEqual(self.browser.get_code(), 200)
        self.verifyAPIResults()

    def verifyAPIResults(self):
        self.assertTrue(True)
        self.assertRegexpMatches(self.browser.get_html(), 'lunch_restrictions',
            'Could not find lunch_restrictions in dining hours JSON.')

class TestLinksModule(TestModule):

    def __init__(self, methodName='runTest', userAgent=g_basicPhoneUserAgent, branch='Basic', platform=''):
        TestModule.__init__(self, methodName, userAgent, branch, platform)
        self.moduleName = 'links'

    def verifyPageContents(self):
        # browser.get_title() doesn't seem to work.
        self.assertRegexpMatches(self.browser.get_html(), '<title>Schools</title>', 
            'Could not verify index title.')
        #echo(self.browser.get_html())

class TestCustomizeModule(TestModule):

    def __init__(self, methodName='runTest', userAgent=g_basicPhoneUserAgent, branch='Basic', platform=''):
        TestModule.__init__(self, methodName, userAgent, branch, platform)
        self.moduleName = 'customize'

    def verifyPageContents(self):
        # browser.get_title() doesn't seem to work.
        self.assertRegexpMatches(self.browser.get_html(), '<title>Customize Home</title>', 
            'Could not verify index title.')
        #echo(self.browser.get_html())

class TestAboutModule(TestModule):

    def __init__(self, methodName='runTest', userAgent=g_basicPhoneUserAgent, branch='Basic', platform=''):
        TestModule.__init__(self, methodName, userAgent, branch, platform)
        self.moduleName = 'mobile-about'

    def verifyPageContents(self):
        # browser.get_title() doesn't seem to work.
        self.assertRegexpMatches(self.browser.get_html(), '<title>About</title>', 
            'Could not verify index title.')
        #echo(self.browser.get_html())


# Test suite
def suite():
    # Builds the test suite.
    testSuite = unittest.TestSuite()

    # People
    testSuite.addTest(TestPeopleModule('test_index', g_basicPhoneUserAgent, 'Basic'))
    testSuite.addTest(TestPeopleModule('test_index', g_touchPhoneUserAgent, 'Touch'))
    testSuite.addTest(TestPeopleModule('test_index', g_mobileSafariUserAgent, 'Webkit'))
    testSuite.addTest(TestPeopleModule('test_index', g_blackberryPlusUserAgent, 'Basic', 'bbplus'))
    #testSuite.addTest(TestPeopleModule('test_api'))
    
    # Map
    testSuite.addTest(TestMapModule('test_index', g_basicPhoneUserAgent, 'Basic'))
    testSuite.addTest(TestMapModule('test_index', g_touchPhoneUserAgent, 'Touch'))
    testSuite.addTest(TestMapModule('test_index', g_mobileSafariUserAgent, 'Webkit'))
    testSuite.addTest(TestMapModule('test_index', g_blackberryPlusUserAgent, 'Basic', 'bbplus'))
    testSuite.addTest(TestMapModule('test_api'))

    # Calendar
    testSuite.addTest(TestCalendarModule('test_index', g_basicPhoneUserAgent, 'Basic'))
    testSuite.addTest(TestCalendarModule('test_index', g_touchPhoneUserAgent, 'Touch'))
    testSuite.addTest(TestCalendarModule('test_index', g_mobileSafariUserAgent, 'Webkit'))
    testSuite.addTest(TestCalendarModule('test_index', g_blackberryPlusUserAgent, 'Basic', 'bbplus'))
    testSuite.addTest(TestCalendarModule('test_api'))
    
    # Courses
    testSuite.addTest(TestCoursesModule('test_index', g_basicPhoneUserAgent, 'Basic'))
    testSuite.addTest(TestCoursesModule('test_index', g_touchPhoneUserAgent, 'Touch'))
    testSuite.addTest(TestCoursesModule('test_index', g_mobileSafariUserAgent, 'Webkit'))
    testSuite.addTest(TestCoursesModule('test_index', g_blackberryPlusUserAgent, 'Basic', 'bbplus'))
    testSuite.addTest(TestCoursesModule('test_api'))
        
    # News
    testSuite.addTest(TestNewsModule('test_index', g_basicPhoneUserAgent, 'Basic'))
    testSuite.addTest(TestNewsModule('test_index', g_touchPhoneUserAgent, 'Touch'))
    testSuite.addTest(TestNewsModule('test_index', g_mobileSafariUserAgent, 'Webkit'))
    testSuite.addTest(TestNewsModule('test_index', g_blackberryPlusUserAgent, 'Basic', 'bbplus'))
    testSuite.addTest(TestNewsModule('test_api'))

    # Dining
    testSuite.addTest(TestDiningModule('test_index', g_basicPhoneUserAgent, 'Basic'))
    testSuite.addTest(TestDiningModule('test_index', g_touchPhoneUserAgent, 'Touch'))
    testSuite.addTest(TestDiningModule('test_index', g_mobileSafariUserAgent, 'Webkit'))
    testSuite.addTest(TestDiningModule('test_index', g_blackberryPlusUserAgent, 'Basic', 'bbplus'))
    testSuite.addTest(TestDiningModule('test_api'))

    # Links
    testSuite.addTest(TestLinksModule('test_index', g_basicPhoneUserAgent, 'Basic'))
    testSuite.addTest(TestLinksModule('test_index', g_touchPhoneUserAgent, 'Touch'))
    testSuite.addTest(TestLinksModule('test_index', g_mobileSafariUserAgent, 'Webkit'))
    testSuite.addTest(TestLinksModule('test_index', g_blackberryPlusUserAgent, 'Basic', 'bbplus'))

    # Customize
    testSuite.addTest(TestCustomizeModule('test_index', g_basicPhoneUserAgent, 'Basic'))
    testSuite.addTest(TestCustomizeModule('test_index', g_touchPhoneUserAgent, 'Touch'))
    testSuite.addTest(TestCustomizeModule('test_index', g_mobileSafariUserAgent, 'Webkit'))
    testSuite.addTest(TestCustomizeModule('test_index', g_blackberryPlusUserAgent, 'Basic', 'bbplus'))
    
    # About
    testSuite.addTest(TestAboutModule('test_index', g_basicPhoneUserAgent, 'Basic'))
    testSuite.addTest(TestAboutModule('test_index', g_touchPhoneUserAgent, 'Touch'))
    testSuite.addTest(TestAboutModule('test_index', g_mobileSafariUserAgent, 'Webkit'))
    testSuite.addTest(TestAboutModule('test_index', g_blackberryPlusUserAgent, 'Basic', 'bbplus'))

    return testSuite

if __name__ == '__main__':
    suite = suite()
    unittest.TextTestRunner(verbosity=2).run(suite)

