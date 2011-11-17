<?php

includePackage('DataModel');
class AthleticsDataModel extends ItemListDataModel
{
    protected $DEFAULT_PARSER_CLASS='RSSDataParser';
    protected $cacheFolder = 'Athletics';

}