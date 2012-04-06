<?php

includePackage('Social');

class SocialAPIModule extends APIModule
{
    protected $id = 'social';
    protected $vmin = 1;
    protected $vmax = 1;
    protected $feeds = array();
    protected $items = array();

    protected function initializeForCommand() {
        $feeds = $this->loadFeedData();

        foreach ($feeds as $feed=>$feedData) {
            $modelClass = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : 'SocialDataModel';
            $this->feeds[$feed] = SocialDataModel::factory($modelClass, $feedData);
        }
        
        switch ($this->command)
        {
            case 'refresh':

                $items = array();
                $needToAuth = array();
                $posts = array();
                $sort = array();
                
                foreach ($this->feeds as $feed=>$model) {
                    if ($model->canRetrieve())
                    {
                        $model->setCacheLifetime(1);
                        $items = $model->getPosts();
                        foreach($items as $item)
                        {
                            file_get_contents($model->getUser($item->getAuthor())->getImageURL());
                        }
                    } else {
                        // Can't auth in an api.
                    }
                }
                $this->setResponse(true);
                $this->setResponseVersion(1);
                
        }
    }
}
