<?php

includePackage('DataModel');
class PhotosDataModel extends ItemListDataModel {
    protected $cacheFolder = 'Photo';

    public function getPhotos() {
        return $this->items();
    }
    
    public function items(){
    	$items = parent::items();
    	foreach ($items as $item) {
    		$item->setRetriever($this->retriever);
    	}
    	return $items;
    }
    
    public function getDefaultPhoto(){
    	$this->setStart(0);
    	$this->setLimit(1);
    	$items = $this->items();
    	return reset($items);
    }

    public function getPhoto($index) {
        return $this->getPhotoByIndex($index);
    }
    
    public function getPrevAndNextID($index){
 
        if($index-1 >= 0){
            $preId = $index-1;
        }else{
            $preId = false;
        }

        if($index+1 < $this->getAlbumSize()){
            $nextId = $index+1;
        }else{
            $nextId = false;
        }
        return array('prev' => $preId,
                     'next' => $nextId);
    }
    public function getAlbumSize() {
        return $this->getTotalItems();
    }

    public function getPhotoByIndex($index){
        $offset = $index % $this->getLimit();
        $start = $index - $offset;
        $this->setStart($start);
        $items = $this->items();
        if(isset($items[$offset])){
            return $items[$offset];    
        }
        return null;
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
