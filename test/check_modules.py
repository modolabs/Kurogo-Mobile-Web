"""
These tests require:

- Twill (http://twill.idyll.org/)
- Python 2.7 (http://www.python.org/download/releases/2.7/)

"""

"""
Set this to the server you want to use for these tests.
"""
g_base_url = "http://localhost:8888/"


import unittest
from twill import get_browser
from twill.commands import *
import re



""" 
Base test case. Its tests go to the module pages and check for 200, but 
do not define how the contents should be checked. Subclasses should do that.
"""

class TestModule(unittest.TestCase):

    def __init__(self, methodName='runTest', branch='Basic'):
        unittest.TestCase.__init__(self, methodName)
        self.base_url = g_base_url
        self.module_name = '' # Should be overridden.
        self.branch = branch
                
    def setUp(self):
        self.browser = get_browser()

    # Tests
    def test_index(self):
        self.goToModulePage()
        self.assertEqual(self.browser.get_code(), 200)
        self.verifyPageContents()

    def test_api(self):
        self.hitAPIWithArguments([])
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
        self.browser.go(self.appendBranchQueryArg(self.base_url + self.module_name))
    
    def appendBranchQueryArg(self, url):
        if self.branch:
            connector = '?'
            if (url.find('?') > -1):
                connector = '&'            
            return url + connector + 'branch=' + self.branch
        else:
            return url
        
    def hitAPIWithArguments(self, argumentDict):
        # TODO
        self.browser.go(self.base_url + 'api/?')
        

class TestPeopleModule(TestModule):
    
    def __init__(self, methodName='runTest', branch='Basic'):
        TestModule.__init__(self, methodName, branch)
        self.module_name = 'people'
    
    def setUp(self):
        TestModule.setUp(self)
        
    def verifyPageContents(self):
        # browser.get_title() doesn't seem to work.
        self.assertRegexpMatches(self.browser.get_html(), '<title>People</title>')
        #echo(self.browser.get_html())


def suite():
    # Builds the test suite.
    testSuite = unittest.TestSuite()
    testSuite.addTest(TestPeopleModule('test_index', 'Basic'))
    testSuite.addTest(TestPeopleModule('test_index', 'Touch'))
    testSuite.addTest(TestPeopleModule('test_index', 'Webkit'))
    testSuite.addTest(TestPeopleModule('test_index', 'Basic&Platform=bbplus'))
    #testSuite.addTest(TestPeopleModule('test_api'))
    return testSuite

if __name__ == '__main__':
    suite = suite()
    unittest.TextTestRunner(verbosity=2).run(suite)
    