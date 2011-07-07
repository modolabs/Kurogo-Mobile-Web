<?php

class ShapefileDataController extends MapDataController
{
    // a shapefile consists of at least 3 files (shp, dbf, shx)
    // and may also include a projection (prj) file.
    // assuming we are not memory-constrained, we can do without
    // the shx file and interleave the parsing of the dbf and
    // shp files.  we need to override basic methods in the
    // superclass to do multiple file and interleaved parsing.
    // TODO better support for files created on case-insensitive
    // file systems (ArcGIS desktop only runs on windows).  we
    // assume here that all files have the same capitalization and 
    // all extensions are lowercase
    protected $shpParser;
    protected $dbfParser;
    protected $mapProjection;

    protected $DEFAULT_PARSER_CLASS = 'ShapefileDataParser';

    protected function initStreamContext($args)
    {
        // do nothing
    }

    /*
    public function getParsedData(DataParser $parser=null) {
        // TODO if these are over the network, retrieve them to
        // a local cache file before opening
        $this->shpParser = new ShapefileDataParser($this->baseURL);
        $this->shpParser->setCategory($this->getCategory());

        $this->shpParser->setDBFParser($this->dbfParser);
        $this->shpParser->parseData();

        return $this->shpParser->getParsedData();
    }
    */
    /*
    public function items($start=0, $limit=null) {
        $items = $this->getParsedData();
        return $this->limitItems($items,$start, $limit);
    }
    */

    public function getData() {
        // do nothing
    }
}

