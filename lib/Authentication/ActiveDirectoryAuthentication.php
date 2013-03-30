<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class ActiveDirectoryAuthentication extends LDAPAuthentication
{
    protected $authorityClass = 'ad';
    protected $userClass='ADUser';
    protected $groupClass='ADUserGroup';
    protected function defaultFieldMap() {
        return array_merge(parent::defaultFieldMap(), array(
            'uid'=>'samaccountname',
            'email'=>'mail',
            'firstname'=>'givenname',
            'lastname'=>'sn',
            'groupname'=>'cn',
            'members'=>'member',
            'memberuid'=>'dn', 
            'gid'=>'objectGUID'
        ));
    }
    
}

class ADUser extends LDAPUser
{
    protected $objectSID;
    
    protected function setObjectSID($value)
    {
        // All SID’s begin with S-
        $sid = "S-";
        // Convert Bin to Hex and split into byte chunks
        $sidinhex = str_split(bin2hex($value), 2);
        // Byte 0 = Revision Level
        $sid = $sid.hexdec($sidinhex[0]).'-';
        // Byte 1-7 = 48 Bit Authority
        $sid = $sid.hexdec($sidinhex[6].$sidinhex[5].$sidinhex[4].$sidinhex[3].$sidinhex[2].$sidinhex[1]);
        // Byte 8 count of sub authorities – Get number of sub-authorities
        $subauths = hexdec($sidinhex[7]);
        //Loop through Sub Authorities
        for($i = 0; $i < $subauths; $i++) {
            $start = 8 + (4 * $i);
            // X amount of 32Bit (4 Byte) Sub Authorities
            $sid = $sid.'-'.hexdec($sidinhex[$start+3].$sidinhex[$start+2].$sidinhex[$start+1].$sidinhex[$start]);
        }
        
        $this->objectSID = $sid;
    }
    
    public function getObjectSID() {
        return $this->objectSID;
    }
    
    protected function standardAttributes() {
        return array_merge(parent::standardAttributes(), array(
            'objectsid'
        ));
    }


    public function singleValueAttributes()
    {
        return array_merge(parent::singleValueAttributes(), 
            array('samaccountname','cn', 'distinguishedname','instancetype','whencreated','whenchanged',
                  'displayname','usncreated','usnchanged','name','objectguid','useraccountcontrol',
                  'badpwdcount','codepage','countrycode','badpasswordtime','lastlogoff','lastlogon',
                  'pwdlastset','primarygroupid','objectsid','accountexpires','logoncount','samaccounttype',
                  'userprincipalname','objectcategory','lastlogontimestamp'
            ));
    }
}

class ADUserGroup extends LDAPUserGroup
{
    public function singleValueAttributes()
    {
        return array_merge(parent::singleValueAttributes(), array(
            'cn','distinguishedname','instancetype','whencreated','whenchanged','usncreated','usnchanged','name','objectguid','objectsid',
            'samaccountname','samaccounttype','grouptype'
        ));
    }    
        
}
