<?php

class TestAPIModule extends NewsAPIModule {
    protected $configModule = 'test';
       
    protected function getNativePagelist() {
        return array('index', 'search', 'story');
    }
}
