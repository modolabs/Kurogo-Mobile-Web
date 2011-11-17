<?php

interface SocialDataRetriever 
{
    public function search($q, $start=0, $limit=null);
    public function getUser($userID);
    public function canRetrieve();
    public function canPost();
    public function auth(array $options);
}

