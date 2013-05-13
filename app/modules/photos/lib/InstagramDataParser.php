<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class InstagramDataParser extends JSONDataParser {

    protected function parseEntry($entry) {
        $photo = new InstagramPhotoObject();
        $photo->setURL($entry['link']);
        $photo->setID($entry['id']);
        $user_data = $entry['user'];
        $photo->setAuthor($user_data['full_name']);
        $published = new DateTime();
        $published->setTimestamp($entry['created_time']);
        //$photo->setTitle(date_format($published,"d F Y"));
        $photo->setPublished($published);

        $images = $entry['images'];
        $image_std = $images['standard_resolution'];
        $image_thumb = $images['thumbnail'];

        $photo->setHeight($image_std['height']);
        $photo->setWidth($image_std['width']);
        $photo->setURL($image_std['url']);
        $photo->setThumbnailURL($image_thumb['url']);

        $caption = $entry['caption'];
        $photo->setDescription($caption['text']);

        return $photo;
    }

    public function parseData($data) {
        $data = parent::parseData($data);
        $photos = array();
        $items = $data['data'];
        $this->setTotalItems(count($data['data']));
        foreach ($items as $entry) {
            $photos[] = $this->parseEntry($entry);
        }
        return $photos;
    }
}
    
 

class InstagramPhotoObject extends PhotoObject {
    protected $type = 'instagram';
}


