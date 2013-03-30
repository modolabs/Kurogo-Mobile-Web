<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class SocialWebModule extends WebModule
{
    protected $id = 'social';
    protected $feeds = array();
    protected $maxPerPane = 5;
    protected function initialize() {
        $feeds = $this->loadFeedData();

        if ($feed = $this->getArg('feed')) {
            if (!isset($feeds[$feed])) {
                throw new Exception("Invalid feed $feed");
            }
            $feeds = array($feed=>$feeds[$feed]);
        }
        
        foreach ($feeds as $feed=>$feedData) {
            $modelClass = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : 'SocialDataModel';
            $this->feeds[$feed] = SocialDataModel::factory($modelClass, $feedData);
        }

    }
    
    public function linkForItem(KurogoObject $post, $data=null) {
        $options = array(
            'id'=>$post->getID()
        );
        
        foreach (array('feed','filter') as $field) {
            if (isset($data[$field])) {
                $options[$field] = $data[$field];
            }
        }
        
        $addBreadcrumb = isset($data['addBreadcrumb']) ? $data['addBreadcrumb'] : true;
        $noBreadcrumbs = isset($data['noBreadcrumbs']) ? $data['noBreadcrumbs'] : false;

        if ($noBreadcrumbs) {
          $url = $this->buildURL('detail', $options);
        } else {
          $url = $this->buildBreadcrumbURL('detail', $options, $addBreadcrumb);
        }

        $subtitle = $this->elapsedTime($post->getCreated()->format('U'));
        $author = false;
        if (isset($data['feed'])) {
            $subtitle = $this->feeds[$data['feed']]->getTitle() .' ' . $subtitle;
            $author = $this->feeds[$data['feed']]->getUser($post->getAuthor());
        }
        
        $link = array(
            'url'     =>$url,
            'body'    =>$post->getBody(),
            'title'    =>$post->getBody(),
            'created' =>$this->elapsedTime($post->getCreated()->format('U')),
            'subtitle'=>$this->elapsedTime($post->getCreated()->format('U')),
            'sort'    =>$post->getCreated()->format('U'),
            'class'   =>$post->getServiceName()
        );
        
        if ($author) {
            $link['author'] = $author->getName();
            $link['img'] = $author->getImageURL();
        }
        
        return $link;
        
    }

    protected function initializeForPage() {
        
        switch ($this->page)
        {
            case 'index':

                $items = array();
                $needToAuth = array();
                $posts = array();
                $sort = array();
                
                foreach ($this->feeds as $feed=>$controller) {                                
                    if ($controller->canRetrieve()) {
                        $items = $controller->getPosts();
                        foreach ($items as $post) {
                            $item = $this->linkForItem($post, array('feed'=>$feed));
                            $sort[] = $item['sort'];
                            $posts[] = $item;
                        }

                    } else {
                        $needToAuth[] = array(
                            'title'=>$controller->getTitle(),
                            'url'=>$this->buildBreadcrumbURL('auth',array('feed'=>$feed, 'returnPage'=>'index'))
                        );
                    }
                }
                
                // @TODO sort by whatever 
                array_multisort($sort, SORT_DESC, $posts);
                

                $firstPost = array_shift($posts);
                $this->assign('firstPost', $firstPost);

                $this->assign('titleTruncate', 140);
                $this->assign('firstPostTitleTruncate', 200);
                $this->assign('needToAuth',$needToAuth);
                $this->assign('posts',$posts);
                $this->assign('serviceLinks', $this->getOptionalModuleSection('services'));
                break;
                
            case 'detail':
                $id = $this->getArg('id');
                if (count($this->feeds)!=1) {
                    $this->redirectTo('index');
                }
                $controller = current($this->feeds);
                if (!$post = $controller->getItem($id)) {
                    throw new Exception("Cannot find item $id");
                }
                $this->assign('postDate', $post->getCreated()->format('l F j, Y g:i a'));
                $this->assign('postBody', nl2br($post->linkify($post->getBody())));
                $this->assign('postLinks', $post->getLinks());
                $this->assign('postImages', $post->getImages());
                if ($author = $controller->getUser($post->getAuthor())) {
                    $this->assign('authorName', $author->getName());
                    $this->assign('authorURL', $author->getProfileURL());
                    $this->assign('authorImageURL', $author->getImageURL());
                }
                break;
            case 'auth':
                if (!Kurogo::getSiteVar('AUTHENTICATION_ENABLED')) {
                    throw new KurogoConfigurationException($this->getLocalizedString("ERROR_AUTHENTICATION_DISABLED"));
                }

                if (count($this->feeds)!=1) {
                    $this->redirectTo('index');
                }
                $returnPage = $this->getArg('returnPage');
                $controller = current($this->feeds);
                $feed = key($this->feeds);
                $options = array(
                        'return_url' => FULL_URL_BASE . $this->configModule . '/' . $this->page . '?' . http_build_query(array(
                            'feed'=>$feed,
                            'returnPage'=>$returnPage
                            )),
                        'startOver'=>$this->getArg('startOver')                        
                );

                $result = $controller->auth($options);
                switch ($result) 
                { 
                    case AUTH_FAILED:
                        $this->assign('message', 'There was an error with your authorization');
                        break;
                    case AUTH_OK:
                        if ($returnPage) {
                            $this->redirectTo($returnPage);
                        } else {
                            $this->assign('message', 'You have been signed in to ' . $controller->getTitle());
                        }
                        break;
                    case AUTH_OAUTH_VERIFY:
                        $this->assign('feed', $feed);
                        $this->assign('providerTitle', $controller->getTitle());
                        $this->assign('returnPage', $returnPage);
                        $this->assign('verifierKey',$controller->getVerifierKey());
                        $this->setTemplatePage('oauth_verify.tpl');
                        break;
                    default:
                        Debug::die_here($result);
                }
                
                
                break;
            case 'pane':
                if ($this->ajaxContentLoad) {
                    $items = array();
                    $needToAuth = array();
                    $posts = array();
                    $sort = array();
                
                    foreach ($this->feeds as $feed=>$controller) {                                
                        if ($controller->canRetrieve()) {
                            $items = $controller->getPosts();
                            foreach ($items as $post) {
                                $item = $this->linkForItem($post, array('feed'=>$feed));
                                $sort[] = $item['sort'];
                                $posts[] = $item;
                            }

                        } else {
                            $needToAuth[] = array(
                                'title'=>$controller->getTitle(),
                                'url'=>$this->buildBreadcrumbURL('auth',array('feed'=>$feed, 'returnPage'=>'index'))
                            );
                        }
                    }
                
                    // @TODO sort by whatever 
                    array_multisort($sort, SORT_DESC, $posts);
                    $posts = array_slice($posts, 0, $this->maxPerPane);
                    $this->assign('stories',$posts);
                }
                $this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
                $this->addInternalJavascript('/common/javascript/lib/paneStories.js');
                break;
            
        }
    }
}
