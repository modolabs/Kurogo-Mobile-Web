<?php

class MapBaseStyle implements MapStyle
{
    protected $styleParams = array();

    public function getStyleForTypeAndParam($type, $param) {
        if (isset($this->styleParams[$type], $this->styleParams[$type][$param])) {
            return $this->styleParams[$type][$param];
        }
        return null;
    }

    public function setStyleForTypeAndParam($type, $param, $value)
    {
        if (!isset($this->styleParams[$type])) {
            $this->styleParams[$type] = array();
        }
        $this->styleParams[$type][$param] = $value;
    }
}

