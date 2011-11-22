<?php

Kurogo::includePackage('Athletics');

class AthleticsAPIModule extends APIModule
{
    protected $id = 'athletics';
    protected $vmin = 1;
    protected $vmax = 1;


    public function  initializeForCommand() {

        switch ($this->command) {
            case 'sports':
                // sports 
                $gender = $this->getArg('gender');
                break;

            case 'news':
                // news 
                $sport = $this->getArg('sport'); // COULD BE TOPNEWS
                
                break;

            case 'schedule':
                // schedule
                $sport = $this->getArg('sport');
                break;

            default:
                $this->invalidCommand();
                break;
        }
    }

}
