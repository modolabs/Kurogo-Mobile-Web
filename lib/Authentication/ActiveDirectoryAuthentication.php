<?php

class ActiveDirectoryAuthentication extends LDAPAuthentication
{
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