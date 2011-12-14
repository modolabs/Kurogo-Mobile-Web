<?php

includePackage('DataModel');
class PhotosDataModel extends ItemListDataModel {
    protected $cacheFolder = 'Photo';

    public function getPhotos() {
        return $this->items();
    }
    
    public function getDefaultPhoto(){
    	$this->setStart(0);
    	$this->setLimit(1);
    	$items = $this->items();
    	//clear cache so calls to subsequent albums don't return 1.
    	$this->clearInternalCache();
    	return reset($items);
    }

    public function getPhoto($id) {
        return $this->getItem($id);
    }
    
    public function getAlbumSize() {
		return count($this->getPhotos());
    }
    
    public static function getPhotoDataRetrievers() {
        return array(
            'FlickrFeedRetriever'=>'Flickr (Feed)',
            'FlickrAPIRetriever'=>'Flickr (API)',
            'PicasaRetriever'=>'Picasa',
            'URLDataRetriever'=>'Basic URL'
        );
    }
    
}
