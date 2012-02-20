<?php

includePackage('DataModel');
class NewsDataModel extends ItemListDataModel
{
    protected $DEFAULT_PARSER_CLASS='RSSDataParser';
    protected $cacheFolder = 'News';
}