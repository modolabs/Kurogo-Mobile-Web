<?php

interface MapDataParser extends MapFolder
{
    public function getProjection();
    public function getId();
}

