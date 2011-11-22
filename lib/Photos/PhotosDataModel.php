<?php

includePackage('DataModel');
class PhotosDataModel extends ItemListDataModel {
    protected $cacheFolder = 'Photo';

    protected function init($args) {
        parent::init($args);
        $this->setUser($args);
        $this->setGroup($args);
        $this->setSet($args);
        $this->setAlbum($args);
        /**
         * use type to let retriever know which api will be use
         */
        if (isset($args['TYPE']) && strlen($args['TYPE'])) {
            $this->setOption('type', $args['TYPE']);
        }
    }

    protected function setUser($args) {
        if (isset($args['ID']) && strlen($args['ID'])) {
            $this->setOption('id', $args['ID']);
        }
    }

    protected function setGroup($args) {
        if (isset($args['GID']) && strlen($args['GID'])) {
            $this->setOption('group_id', $args['GID']);
        }
    }

    protected function setSet($args) {
        if (isset($args['SID']) && strlen($args['SID'])) {
            $this->setOption('set_id', $args['SID']);
        }
    }

    protected function setAlbum($args) {
        if (isset($args['ALBUM_ID']) && strlen($args['ALBUM_ID'])) {
            $this->setOption('album_id', $args['ALBUM_ID']);
        }
    }

    public function getPhotos() {
        return $this->items();
    }
    
    public function getDefaultPhoto(){
    	$this->setStart(0);
    	$this->setLimit(1);
    	$items = $this->items();
    	$this->clearInternalCache();
    	return reset($items);
    }

    public function getPhoto($id) {
        return $this->getItem($id);
    }
    
    public function getAlbumSize(){
		return count($this->getPhotos());
    }
}
