<?php

class INIFileContactsListRetriever extends URLDataRetriever
{
    protected $DEFAULT_PARSER_CLASS = 'INIFileContactsParser';
}

class INIFileContactsParser extends INIFileParser
{
    protected function createContactsList($iniData) {
        if(isset($iniData['title'])) {
            foreach($iniData['title'] as $index => $title) {
                $contactsList[] = new EmergencyContactsListItem(
                    $iniData['title'][$index],
                    $iniData['subtitle'][$index],
                    $iniData['phone'][$index]
            	 );
            }
        }
        return $contactsList;
    }
    
    public function parseFile($file) {
        $data = parent::parseFile($file);
        $contactsList = array(
            'primary'=>isset($data['primary']) ? $this->createContactsList($data['primary']) : array(),
            'secondary'=>isset($data['secondary']) ? $this->createContactsList($data['secondary']) : array()
        );
        return $contactsList;
    }
    
    public function parseData($data) {
        $data = parent::parseData($data);
        $contactsList = array(
            'primary'=>isset($data['primary']) ? $this->createContactsList($data['primary']) : array(),
            'secondary'=>isset($data['secondary']) ? $this->createContactsList($data['secondary']) : array()
        );
        return $contactsList;
    }

    
}