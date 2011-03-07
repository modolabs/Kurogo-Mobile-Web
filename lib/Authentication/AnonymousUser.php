<?php

/**
 * Anonymous User
 * @package Authentication
 */
class AnonymousUser extends User
{
    public function __construct()
    {
    }

    public function getUserHash() {
        return '';
    }
    
}

