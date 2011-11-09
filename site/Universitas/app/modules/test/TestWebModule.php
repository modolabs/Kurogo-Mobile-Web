<?php

class TestWebModule extends NewsWebModule {
    protected $configModule = 'test';
    
    protected function initializeForPage() {
        if ($this->ajaxPagetype && !$this->ajaxContentLoad) {
            // Native template support
            // specify anything that goes into the header or footer here
            switch ($this->page) {
                case 'index':
                case 'search':
                    $this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
                    $this->addOnLoad('setupNewsListing();');
                    break;
            }
        } else {
            parent::initializeForPage();
        }
    }
}
