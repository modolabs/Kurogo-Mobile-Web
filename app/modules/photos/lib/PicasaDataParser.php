<?php

class PicasaDataParser extends DataParser {

    public function parseData($data) {
        if($data = json_decode($data, true)) {

            if(isset($data['feed']) && isset($data['feed']['entry']) && is_array($data['feed']['entry'])) {
                $photos = array();
                if (isset($data['feed']['author'][0])) {
                    $this->setOption('author', $data['feed']['author'][0]['name']['$t']);
                }

                foreach($data['feed']['entry'] as $entry) {
                    $photos[] = $this->parseEntry($entry);
                }
                $this->setTotalItems(count($photos));
                return $photos;
            }
        }
        

        return array();
    }

    protected function parseEntry($entry) {
        $photo = new PicasaPhotoObject();
        $photo->setID($entry['id']['$t']);
        $photo->setTitle($entry['title']['$t']);
        $photo->setDescription($entry['media$group']['media$description']['$t']);
        $photo->setAuthor($this->getOption('author'));
        $photo->setMimeType($entry['content']['type']);
        $photo->setURL($entry['content']['src']);
        $photo->setHeight($entry['gphoto$height']['$t']);
        $photo->setWidth($entry['gphoto$width']['$t']);

        if (isset($entry['media$group']['media$keywords']['$t'])) {
            $photo->setTags($entry['media$group']['media$keywords']['$t']);
        }

        $published = new DateTime($entry['published']['$t']);
        $photo->setPublished($published);
        
        $thumbnail = end($entry['media$group']['media$thumbnail']);
        $photo->setThumbnailURL($thumbnail['url']);

        return $photo;
    }
}

class PicasaPhotoObject extends PhotoObject {
    protected $type = 'picasa';
}
