<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class SocialAPIModule extends APIModule
{
    protected $id = 'social';
    protected $vmin = 1;
    protected $vmax = 1;
    protected $feeds = array();
    protected $items = array();

    public function arrayForPost(SocialMediaPost $post, $data=null) {
        $array = array(
            'id'=>$post->getID(),
            'service'=>$post->getServiceName(),
            'postHTML'=>nl2br($post->linkify($post->getBody())),
            'postLinks'=>$post->getLinks(),
            'postImages' =>$post->getImages(),
            'created' =>$post->getCreated()->format('U'),
            'sort' =>$post->getCreated()->format('U'),
            'author'  =>$post->getAuthor(),
        );
        
        if (isset($data['feed'])) {
            $array['feed'] = $data['feed'];
            if ($author = $this->feeds[$data['feed']]->getUser($post->getAuthor())) {
                $array['authorName'] = $author->getName();
                $array['authorURL'] = $author->getProfileURL();
                $array['authorImageURL'] = $author->getImageURL();
            }
        }
        
        return $array;
        
    }    
    protected function initializeForCommand() {

        $feeds = $this->loadFeedData();

        foreach ($feeds as $feed=>$feedData) {
            $modelClass = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : 'SocialDataModel';
            $this->feeds[$feed] = SocialDataModel::factory($modelClass, $feedData);
        }
        
        switch ($this->command)
        {
            case 'feeds':
                $feeds = array();
                foreach($this->feeds as $id => $controller) {
                    $feed = array(
                        'id'    => $id,
                        'title' => $controller->getTitle(),
                        'service'=> $controller->getServiceName(),
                        'account'=> $controller->getAccount(),
                        'serviceIcon'=>'', //@TODO,
                        'accountIcon'=>'' //@TODO
                    );
                    $feeds[] = $feed;
                }

                $this->setResponse($feeds);
                $this->setResponseVersion(1);

                break;
            case 'posts':
                $posts = array();
                $limit = $this->getArg('limit');
                
                if ($feed = $this->getArg('feed', null)) {
                
                    if (isset($this->feeds[$feed])) {
                        $feeds = array($feed=>$this->feeds[$feed]);
                    } else {
                        throw new KurogoDataException("Invalid feed $feed");
                    }
                } else {
                    $feeds = $this->feeds;
                }
                
                foreach ($feeds as $feed=>$controller) {                                
                    if ($controller->canRetrieve()) {
                        if ($limit) {
                            $controller->setOption('limit', $limit);
                        }
                        $items = $controller->getPosts();
                        foreach ($items as $post) {
                            $item = $this->arrayForPost($post, array('feed'=>$feed));
                            $sort[] = $item['sort'];
                            $posts[] = $item;
                        }
                    } else {
                        throw new KurogoException("Authenticated feeds are not yet supported in the API");
                    }
                    
                }

                // @TODO sort by whatever 
                array_multisort($sort, SORT_DESC, $posts);
                
                $this->setResponse($posts);
                $this->setResponseVersion(1);
                
                break;
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
            default:
                $this->invalidCommand();
                break;
                
        }
    }
}
