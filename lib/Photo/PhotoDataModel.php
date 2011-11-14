<?php

includePackage('DataModel');
class PhotoDataModel extends ItemListDataModel {
    protected $cacheFolder = 'Photo';

    public function getTag() {
        return $this->tag;
    }

    public function getAuthor() {
        return $this->author;
    }
    
    protected function init($args) {
        parent::init($args);

        if (isset($args['TAG']) && strlen($args['TAG'])) {
            $this->setOption('tag', $args['TAG']);
        }
        
        if (isset($args['AUTHOR']) && strlen($args['AUTHOR'])) {
            $this->setOption('author', $args['AUTHOR']);
        }
    }
}
