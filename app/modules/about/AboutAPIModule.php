<?php

class AboutAPIModule extends APIModule {

    protected $id = 'about';
    protected $vmin = 1;
    protected $vmax = 1;
    public function availableVersions() {
        return array(1);
    }

     protected function initializeForCommand()  {

        // retrieve all Data for the About screen
         $stringArray = ($this->getModuleArray('strings'));
         $textArray['siteAboutHTML'] = $stringArray[0]['SITE_ABOUT_HTML'];
         $textArray['aboutHTML'] = $stringArray[0]['ABOUT_HTML'];
         $textArray['orgName'] = Kurogo::getSiteString('ORGANIZATION_NAME');
         $textArray['email'] = Kurogo::getSiteString('FEEDBACK_EMAIL');
         $textArray['website'] = Kurogo::getSiteString('COPYRIGHT_LINK');
         $textArray['copyright'] = Kurogo::getSiteString('COPYRIGHT_NOTICE');
         $textArray['credits'] = file_get_contents(MODULES_DIR . "/{$this->id}/templates/credits_html.tpl");
         
        switch ($this->command) {

            case 'index':
                $response = $this->getModuleSections('api-index');
                $this->setResponse($response);
                $this->setResponseVersion(1);
                break;

            case 'about_site':
                $response = "<p>" . implode("</p><p>", $this->getModuleVar('SITE_ABOUT_HTML', 'strings')) . "</p>";
                $this->setResponse($response);
                $this->setResponseVersion(1);
                break;

            case 'about':
                $response = "<p>" . implode("</p><p>", $this->getModuleVar('ABOUT_HTML', 'strings')) . "</p>";
                $this->setResponse($response);
                $this->setResponseVersion(1);
                break;

            case 'credits':
                $response = file_get_contents(MODULES_DIR . "/{$this->id}/templates/credits_html.tpl");
                $this->setResponse($response);
                $this->setResponseVersion(1);
                break;

            case 'orgname':
                $response = array('orgName' => $textArray['orgName']);
                //print_r($response);
                $this->setResponse($response);
                $this->setResponseVersion(1);
                break;

            case 'orgtext':
                $response = array('orgText' => $textArray['siteAboutHTML']);
                $this->setResponse($response);
                $this->setResponseVersion(1);
                break;

            case 'abouttext':
                $response = array('aboutText' => $textArray['aboutHTML']);
                $this->setResponse($response);
                $this->setResponseVersion(1);
                break;

            case 'copyright':
                $response = array('copyright' => $textArray['coyright']);
                $this->setResponse($response);
                $this->setResponseVersion(1);
                break;

            case 'email':
                $response = array('email' => $textArray['email']);
                $this->setResponse($response);
                $this->setResponseVersion(1);
                break;

            case 'website':
                $response = array('website' => $textArray['website']);
                $this->setResponse($response);
                $this->setResponseVersion(1);
                break;

            // Use 'alldata' to get everything in one API-CALL
            case 'alldata':
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

?>
