<?php

class MapDBDataModel extends MapDataModel
{
    protected $PARSER_INTERFACE = 'MapDataParser';

    protected $backupParser;
    protected $backupRetriever;

    protected function init($args)
    {
        parent::init($args);

        $this->backupParser = $this->getParser();
        $this->backupRetriever = $this->getRetriever();

        $this->setParser(DataParser::factory('MapDBDataParser', $args));
        $this->setRetriever(DataParser::factory('MapDBDataRetriever', $args));

        if ($this->parser->isStored() && $this->dbParser->getCategory()->getListItems()) {
            // make sure this category was populated before skipping
            $this->hasDBData = true;
            $this->useCache = false;
        }
    }
















}


