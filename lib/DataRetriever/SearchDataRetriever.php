<?php

interface SearchDataRetriever
{
    public function search($searchTerms, &$response=null);
}