<?php

interface PeopleRetriever extends SearchDataRetriever
{
    const MIN_PHONE_SEARCH = 3;
    public function getUser($id);
    public function setAttributes($attributes);
}
