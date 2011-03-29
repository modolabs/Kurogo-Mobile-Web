<?php

class AboutAPIModule extends APIModule
{
    protected $id = 'about';
    protected $vmin = 1;
    protected $vmax = 1;
    public function availableVersions() {
        return array(1);
    }
    
    public function initializeForCommand() {  

        switch ($this->command) {

            case 'index':
                $response = $this->getModuleSections('api-index');
                break;
            case 'about_site':
                $response = "<p>" . implode("</p><p>", $this->getModuleVar('SITE_ABOUT_HTML', 'strings')) . "</p>";
                break;
            case 'about':
                $response = "<p>" . implode("</p><p>", $this->getModuleVar('ABOUT_HTML', 'strings')) . "</p>";
                break;
            case 'credits':
                $response = file_get_contents(MODULES_DIR . "/{$this->id}/templates/credits_html.tpl");
                break;
            default:
                $this->invalidCommand();
                break;
        }

        $this->setResponse($response);
        $this->setResponseVersion(1);
    }
   
}
