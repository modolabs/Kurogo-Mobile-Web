<?php

class NewsAPIModule extends APIModule {

    protected $id = 'news';
    protected $vmin = 1;
    protected $vmax = 1;
    
    protected function initializeForCommand() {
        $feeds = $this->loadFeedData();

        switch($this->command) {
            case 'categories':
                $response = array();
                foreach ($feeds as $index => $feedData) {
                    $response[] = $feedData['TITLE'];
                }
                $this->setResponse($response);
                $this->setResponseVersion(1);

                break;

            default:
                 $this->invalidCommand();
                 break;
        }
    }
}