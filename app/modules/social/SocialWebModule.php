<?php

includePackage('Social');

class SocialWebModule extends WebModule
{
    protected $id = 'social';
    protected $feeds = array();
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
        if (isset($data['feed'])) {
            $subtitle = $this->feeds[$data['feed']]->getTitle() .' ' . $subtitle;
        }
        
        $link = array(
            'url'     =>$url,
            'title'   =>$post->getBody(),
            'subtitle'=>$subtitle,
            'sort'    =>$post->getCreated()->format('U')
        );
        
        if ($author = $post->getAuthorUser()) {
            $link['label'] = $author->getName();
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

                $this->assign('needToAuth',$needToAuth);
                $this->assign('posts',$posts);
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
                $this->assign('postBody', nl2br($post->getBody()));
                $this->assign('postLinks', $post->getLinks());
                if ($author = $post->getAuthorUser()) {
                    $this->assign('authorName', $author->getName());
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
        }
    }
}