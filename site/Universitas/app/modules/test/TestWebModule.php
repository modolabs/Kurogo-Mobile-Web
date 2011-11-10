<?php

class TestWebModule extends NewsWebModule {
    protected $configModule = 'test';
    
    protected function initializeForNativeTemplatePage() {
        // Native template support
        // specify anything that goes into the header or footer here
        // and force the appearance of assets so they get loaded into the template
        switch ($this->page) {
            case 'index':
                // force appearance of section select button
                $this->assign('sections', array(1, 2));
            case 'search':
                $this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
                $this->addOnLoad('setupNewsListing();');
                break;
                
            case 'story':
                if ($this->getOptionalModuleVar('SHARING_ENABLED', 1)) {
                    $this->assign('shareTitle', $this->getLocalizedString('SHARE_THIS_STORY'));
                    $this->assign('shareEmailURL', 'dummy');
                    $this->assign('shareRemark',   'dummy');
                    $this->assign('storyURL',      'dummy');
                }

        }
    }
}
