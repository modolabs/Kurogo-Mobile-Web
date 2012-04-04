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
    
    public function getPrevAndNextID($id){
    	$photos = $this->items();
    	foreach ($photos as $index =>$photo){
    		if($photo->getID() == $id){
    			
				if($index-1 >= 0){
					$preId = $photos[$index-1]->getID();
				}else{
					$preId = 0;
				}
				
				if($index+1 < count($photos)){
					$nextId = $photos[$index+1]->getID();
				}else{
					$nextId = 0;
				}
				return array('prev' => $preId,
							 'next' => $nextId);
    		}
    	}
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
