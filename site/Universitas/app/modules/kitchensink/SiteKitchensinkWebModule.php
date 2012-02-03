<?php

class SiteKitchensinkWebModule extends KitchensinkWebModule {
    
    protected function initializeForNativeTemplatePage() {
        // Native template support
        // specify anything that goes into the header or footer here
        // and force the appearance of assets so they get loaded into the template
        
        // All the data in this module is static!
        $this->initializeForPage();
    }
}
