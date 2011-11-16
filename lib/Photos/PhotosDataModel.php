<?php

includePackage('DataModel');
class PhotosDataModel extends ItemListDataModel {
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
        
        if (isset($args['ID']) && strlen($args['ID'])) {
            $this->setOption('id', $args['ID']);
        }

        /**
         * use type to let retriever know which api will be use
         */
        if (isset($args['TYPE']) && strlen($args['TYPE'])) {
            $this->setOption('type', $args['TYPE']);
        }
    }
}
