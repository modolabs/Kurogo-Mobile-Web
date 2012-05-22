<?php

class TestWebModule extends NewsWebModule {
    protected $configModule = 'test';
    
    protected function initializeForNativeTemplatePage() {
        // Native template support
        // force the appearance of assets so they get loaded into the template
        switch ($this->page) {
            case 'index':
                // section selection control with search button:
                $this->assign('sections', array(1, 2));
            case 'search':
                // ellipsizer javascript on search and index pages:
                $this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
                break;
                
            case 'story':
                // conditional share button image:
                $this->assign('shareTitle', $this->getLocalizedString('SHARE_THIS_STORY'));
                $this->assign('shareEmailURL', 'dummy');
                $this->assign('shareRemark',   'dummy');
                $this->assign('storyURL',      'dummy');
        }
    }
}
