<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

abstract class SocialMediaUser
{
    protected $userID;
    protected $name;
    protected $email;
    protected $imageURL;
    protected $image;
    protected $retriever;
    
    public function setUserID($userID) {
        $this->userID = $userID;
    }

    public function getUserID() {
        return $this->userID;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function getEmail() {
        return $this->email;
    }

    public function setImage($image) {
        $this->image = $image;
    }

    public function getImage() {
        return $this->image;
    }

    public function setImageURL($imageURL) {
        $this->imageURL = $imageURL;
    }

    public function getImageURL() {
        return $this->imageURL;
    }

}

