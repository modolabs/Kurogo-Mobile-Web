<?php

includePackage('DataController');
class NewsDataController extends ItemsDataController
{
    protected $DEFAULT_PARSER_CLASS='RSSDataParser';
    protected $cacheFolder = 'News';
}