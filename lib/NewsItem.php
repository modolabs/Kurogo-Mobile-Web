<?php

interface NewsItem extends KurogoObject
{
    public function getTitle();
    public function getAuthor();
    public function getDescription();
    public function getImage();
    public function getGUID();
    public function getLink();
    public function getContent();
    public function getPubDate();
    
}