<?php

class AboutAPIModule extends APIModule {

    protected $id = 'about';
    protected $vmin = 1;
    protected $vmax = 1;
    public function availableVersions() {
        return array(1);
    }

    protected function getSiteAboutHTML() {
        $paragraphs = $this->getOptionalModuleVar('SITE_ABOUT_HTML', false, 'strings', 'api-about_site');
        if (!$paragraphs) {
            $paragraphs = $this->getModuleVar('SITE_ABOUT_HTML', 'strings');
        }
        return $paragraphs;
    }

    protected function getAboutHTML() {
        $paragraphs = $this->getOptionalModuleVar('ABOUT_HTML', false, 'strings', 'api-about_site');
        if (!$paragraphs) {
            $paragraphs = $this->getModuleVar('ABOUT_HTML', 'strings');
        }
        return $paragraphs;
    }

    protected function getCreditsHTML() {
        $module = WebModule::factory($this->configModule, 'credits_html');
        return $module->fetchPage();
    }

    protected function initializeForCommand()  {

        // retrieve all Data for the About screen
        $textArray = array(
            'orgtext'       => $this->getSiteAboutHTML(),
            'abouttext'     => $this->getAboutHTML(),
            'orgname'       => Kurogo::getSiteString('ORGANIZATION_NAME'),
            'email'         => Kurogo::getSiteString('FEEDBACK_EMAIL'),
            'website'       => Kurogo::getSiteString('COPYRIGHT_LINK'),
            'copyright'     => Kurogo::getSiteString('COPYRIGHT_NOTICE'),
        );
         
        switch ($this->command) {

            case 'index':
                $dictionaryOfSections = $this->getModuleSections('api-index');
                $response = array();
                foreach ($dictionaryOfSections as $key => $value){
                    $response[] = $value;
                }
                $this->setResponse($response);
                $this->setResponseVersion(1);
                break;

            case 'about_site':
                $response = '<p>' . implode('</p><p>', $textArray['orgtext']) . '</p>';
                $this->setResponse($response);
                $this->setResponseVersion(1);
                break;

            case 'about':
                $response = '<p>' . implode('</p><p>', $textArray['abouttext']) . '</p>';
                $this->setResponse($response);
                $this->setResponseVersion(1);
                break;

            case 'credits':
                $response = $this->getCreditsHTML();
                $this->setResponse($response);
                $this->setResponseVersion(1);
                break;

            case 'orgname':
            case 'orgtext':
            case 'abouttext':
            case 'copyright':
            case 'email':
            case 'website':
                $response = array($this->command => $textArray[$this->command]);
                $this->setResponse($response);
                $this->setResponseVersion(1);
                break;

            // Use 'alldata' to get everything in one API-CALL
            case 'alldata':
                $textArray['credits'] = $this->getCreditsHTML();
                $response = $textArray;
                $this->setResponse($response);
                $this->setResponseVersion(1);
                break;

            default:
                $this->invalidCommand();
                $this->setResponseVersion(1);
                break;
        }
     }
}
