<?php

class HomeAPIModule extends APIModule
{
    protected $id = 'home';
    protected $vmin = 1;
    protected $vmax = 1;

    public function initializeForCommand() {
        $this->invalidCommand();
    }
}


