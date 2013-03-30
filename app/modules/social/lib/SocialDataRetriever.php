<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

interface SocialDataRetriever 
{
    public function getPosts(); //the retriever is expected to limit the results
    public function getUser($userID);
    public function canRetrieve();
    public function canPost();
    public function auth(array $options);
    public function getServiceName();
    public function getAccount();
}

