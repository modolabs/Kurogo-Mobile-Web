<?php

// implemented by map categories, which have no geometry
interface MapListElement extends KurogoObject
{
    public function getTitle();
    public function getSubtitle();
}

