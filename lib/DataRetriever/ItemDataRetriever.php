<?php

interface ItemDataRetriever
{
    public function getItem($id, &$response=null);

}