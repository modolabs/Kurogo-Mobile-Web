<?php

Kurogo::includePackage('Photos');

class PhotosAPIModule extends APIModule
{
    protected $id = 'photos';
    protected $vmin = 1;
    protected $vmax = 1;


    public function  initializeForCommand() {

        switch ($this->command) {
            case 'albums':
                // get albums
                break;

            case 'photos':
                // photos for an album..
                $album = $this->getArg('album');
                
                break;

            default:
                $this->invalidCommand();
                break;
        }
    }

}
