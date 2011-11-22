<?php

class PhotoObject extends BaseObject implements KurogoObject {
    protected $id;
    protected $title;
    /**
     * url 
     * alternate photo url
     * 
     * @var mixed
     * @access protected
     */
    protected $url;
    protected $description;
    /**
     * type 
     * privider type name
     * eg: flickr, picasa
     * 
     * @var string
     * @access protected
     */
    protected $type;
    protected $mime_type;
    /**
     * m_url 
     * middle image url
     * 
     * @var string
     * @access protected
     */
    protected $m_url;
    /**
     * t_url 
     * small/thumb image url
     * 
     * @var string
     * @access protected
     */
    protected $t_url;
    /**
     * l_url 
     * large image url
     * 
     * @var string
     * @access protected
     */
    protected $l_url;
    /**
     * photo_url 
     * origin image url
     *
     * @var string
     * @access protected
     */
    protected $photo_url;
    /**
     * date_taken 
     * photo taken datetime
     * 
     * @var datetime
     * @access protected
     */
    protected $date_taken;
    protected $author_name;
    protected $author_url;
    protected $author_id;
    protected $author_icon;
    /**
     * published 
     * publish photo datetime
     * 
     * @var datetime
     * @access protected
     */
    protected $published;
    protected $width;
    protected $height;
    protected $tags;
    
    public function setDateTaken(DateTime $date) {
        $this->date_taken = $date;
    }

    public function setPublished(DateTime $date) {
        $this->published = $date;
    }

    public function filterItem($filters) {
        foreach ($filters as $filter=>$value) {
            switch ($filter) {
                case 'search':
                    return (stripos($this->getTitle(), $value)!==FALSE) ||
                        (stripos($this->getDescription(), $value)!==FALSE);
                    break;
            }
        }

        return true;
    }
}
