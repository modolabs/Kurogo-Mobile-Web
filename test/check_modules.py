"""
These tests require:

- Twill (http://twill.idyll.org/)
- Python 2.7 (http://www.python.org/download/releases/2.7/)

"""

"""
Set this to the server you want to use for these tests.
"""
#g_base_url = "http://localhost:8888"
#g_base_url = "http://mobile-dev.harvard.edu"
#g_base_url = "http://mobile-staging.harvard.edu/"
g_base_url = "http://m.harvard.edu"

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

    def __init__(self, methodName='runTest', branch='Basic'):
        unittest.TestCase.__init__(self, methodName)
        self.baseUrl = g_base_url
        self.moduleName = '' # Should be overridden.
        self.branch = branch
                
    def setUp(self):
        self.browser = get_browser()

    # Tests
    def test_index(self):
        self.goToModulePage()
        self.assertEqual(self.browser.get_code(), 200, 
            'The ' + self.moduleName + ' module index page is not OK. It returned HTTP code: ' 
            + str(self.browser.get_code()))
        self.verifyPageContents()

    def test_api(self):
        self.hitAPIWithArguments({ 'q': 'roger+brockett', 'command': 'search'})
        self.assertEqual(self.browser.get_code(), 200)
        self.verifyAPIResults()
        
    # Verification methods
    def verifyPageContents(self):
        # Override this in subclasses.
        self.assertTrue(True)

    def verifyAPIResults(self):
        # Override this in subclasses.
        self.assertTrue(True)
        
    # Test helper methods
    def goToModulePage(self):
        self.browser.go(self.appendBranchQueryArg(
            endWithSlash(endWithSlash(self.baseUrl) + self.moduleName)))
            
    def appendBranchQueryArg(self, url):
        if self.branch:
            connector = '?'
            if (url.find('?') > -1):
                connector = '&'            
            return url + connector + 'branch=' + self.branch
        else:
            return url
        
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
    
    def __init__(self, methodName='runTest', branch='Basic'):
        TestModule.__init__(self, methodName, branch)
        self.moduleName = 'people'
    
    def verifyPageContents(self):
        # browser.get_title() doesn't seem to work.
        self.assertRegexpMatches(self.browser.get_html(), '<title>People</title>', 
            'Could not verify index title.')
        #echo(self.browser.get_html())
                
    def verifyAPIResults(self):
        # TODO: A little more precision.
        self.assertRegexpMatches(self.browser.get_html(), 'Brockett',
            'Could not find Brockett result.')

class TestMapModule(TestModule):

    def __init__(self, methodName='runTest', branch='Basic'):
        TestModule.__init__(self, methodName, branch)
        self.moduleName = 'map'

    def verifyPageContents(self):
        # browser.get_title() doesn't seem to work.
        self.assertRegexpMatches(self.browser.get_html(), '<title>Map</title>', 
            'Could not verify index title.')
        #echo(self.browser.get_html())

    def verifyAPIResults(self):
        self.assertTrue(True)
        #self.assertRegexpMatches(self.browser.get_html(), 'Brockett',
        #    'Could not find Brockett result.')

class TestCalendarModule(TestModule):

    def __init__(self, methodName='runTest', branch='Basic'):
        TestModule.__init__(self, methodName, branch)
        self.moduleName = 'calendar'

    def verifyPageContents(self):
        # browser.get_title() doesn't seem to work.
        self.assertRegexpMatches(self.browser.get_html(), '<title>Events</title>', 
            'Could not verify index title.')
        #echo(self.browser.get_html())

    def verifyAPIResults(self):
        self.assertTrue(True)
        #self.assertRegexpMatches(self.browser.get_html(), 'Brockett',
        #    'Could not find Brockett result.')

class TestCoursesModule(TestModule):

    def __init__(self, methodName='runTest', branch='Basic'):
        TestModule.__init__(self, methodName, branch)
        self.moduleName = 'courses'

    def verifyPageContents(self):
        # browser.get_title() doesn't seem to work.
        self.assertRegexpMatches(self.browser.get_html(), '<title>Courses</title>', 
            'Could not verify index title.')
        #echo(self.browser.get_html())

    def verifyAPIResults(self):
        self.assertTrue(True)
        #self.assertRegexpMatches(self.browser.get_html(), 'Brockett',
        #    'Could not find Brockett result.')

class TestNewsModule(TestModule):

    def __init__(self, methodName='runTest', branch='Basic'):
        TestModule.__init__(self, methodName, branch)
        self.moduleName = 'news'

    def verifyPageContents(self):
        # browser.get_title() doesn't seem to work.
        self.assertRegexpMatches(self.browser.get_html(), '<title>News</title>', 
            'Could not verify index title.')
        #echo(self.browser.get_html())

    def verifyAPIResults(self):
        self.assertTrue(True)
        #self.assertRegexpMatches(self.browser.get_html(), 'Brockett',
        #    'Could not find Brockett result.')

class TestDiningModule(TestModule):

    def __init__(self, methodName='runTest', branch='Basic'):
        TestModule.__init__(self, methodName, branch)
        self.moduleName = 'dining'

    def verifyPageContents(self):
        # browser.get_title() doesn't seem to work.
        self.assertRegexpMatches(self.browser.get_html(), '<title>Student Dining</title>', 
            'Could not verify index title.')
        #echo(self.browser.get_html())

    def verifyAPIResults(self):
        self.assertTrue(True)
        #self.assertRegexpMatches(self.browser.get_html(), 'Brockett',
        #    'Could not find Brockett result.')

class TestLinksModule(TestModule):

    def __init__(self, methodName='runTest', branch='Basic'):
        TestModule.__init__(self, methodName, branch)
        self.moduleName = 'links'

    def verifyPageContents(self):
        # browser.get_title() doesn't seem to work.
        self.assertRegexpMatches(self.browser.get_html(), '<title>Schools</title>', 
            'Could not verify index title.')
        #echo(self.browser.get_html())

class TestCustomizeModule(TestModule):

    def __init__(self, methodName='runTest', branch='Basic'):
        TestModule.__init__(self, methodName, branch)
        self.moduleName = 'customize'

    def verifyPageContents(self):
        # browser.get_title() doesn't seem to work.
        self.assertRegexpMatches(self.browser.get_html(), '<title>Customize Home</title>', 
            'Could not verify index title.')
        #echo(self.browser.get_html())

class TestAboutModule(TestModule):

    def __init__(self, methodName='runTest', branch='Basic'):
        TestModule.__init__(self, methodName, branch)
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
    testSuite.addTest(TestPeopleModule('test_index', 'Basic'))
    testSuite.addTest(TestPeopleModule('test_index', 'Touch'))
    testSuite.addTest(TestPeopleModule('test_index', 'Webkit'))
    testSuite.addTest(TestPeopleModule('test_index', 'Basic&Platform=bbplus'))
    testSuite.addTest(TestPeopleModule('test_api'))

    # Map
    testSuite.addTest(TestMapModule('test_index', 'Basic'))
    testSuite.addTest(TestMapModule('test_index', 'Touch'))
    testSuite.addTest(TestMapModule('test_index', 'Webkit'))
    testSuite.addTest(TestMapModule('test_index', 'Basic&Platform=bbplus'))
    testSuite.addTest(TestMapModule('test_api'))
    
    # Calendar
    testSuite.addTest(TestCalendarModule('test_index', 'Basic'))
    testSuite.addTest(TestCalendarModule('test_index', 'Touch'))
    testSuite.addTest(TestCalendarModule('test_index', 'Webkit'))
    testSuite.addTest(TestCalendarModule('test_index', 'Basic&Platform=bbplus'))
    testSuite.addTest(TestCalendarModule('test_api'))
    
    # Courses
    testSuite.addTest(TestCoursesModule('test_index', 'Basic'))
    testSuite.addTest(TestCoursesModule('test_index', 'Touch'))
    testSuite.addTest(TestCoursesModule('test_index', 'Webkit'))
    testSuite.addTest(TestCoursesModule('test_index', 'Basic&Platform=bbplus'))
    testSuite.addTest(TestCoursesModule('test_api'))
        
    # News
    testSuite.addTest(TestNewsModule('test_index', 'Basic'))
    testSuite.addTest(TestNewsModule('test_index', 'Touch'))
    testSuite.addTest(TestNewsModule('test_index', 'Webkit'))
    testSuite.addTest(TestNewsModule('test_index', 'Basic&Platform=bbplus'))
    testSuite.addTest(TestNewsModule('test_api'))

    # Dining
    testSuite.addTest(TestDiningModule('test_index', 'Basic'))
    testSuite.addTest(TestDiningModule('test_index', 'Touch'))
    testSuite.addTest(TestDiningModule('test_index', 'Webkit'))
    testSuite.addTest(TestDiningModule('test_index', 'Basic&Platform=bbplus'))
    testSuite.addTest(TestDiningModule('test_api'))

    # Links
    testSuite.addTest(TestLinksModule('test_index', 'Basic'))
    testSuite.addTest(TestLinksModule('test_index', 'Touch'))
    testSuite.addTest(TestLinksModule('test_index', 'Webkit'))
    testSuite.addTest(TestLinksModule('test_index', 'Basic&Platform=bbplus'))

    # Customize
    testSuite.addTest(TestCustomizeModule('test_index', 'Basic'))
    testSuite.addTest(TestCustomizeModule('test_index', 'Touch'))
    testSuite.addTest(TestCustomizeModule('test_index', 'Webkit'))
    testSuite.addTest(TestCustomizeModule('test_index', 'Basic&Platform=bbplus'))

    # About
    testSuite.addTest(TestAboutModule('test_index', 'Basic'))
    testSuite.addTest(TestAboutModule('test_index', 'Touch'))
    testSuite.addTest(TestAboutModule('test_index', 'Webkit'))
    testSuite.addTest(TestAboutModule('test_index', 'Basic&Platform=bbplus'))
                
    #testSuite.addTest(TestPeopleModule('test_api'))
    return testSuite

if __name__ == '__main__':
    suite = suite()
    unittest.TextTestRunner(verbosity=2).run(suite)

