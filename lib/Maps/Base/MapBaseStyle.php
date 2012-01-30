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

    public function serialize() {
        return serialize(
            array(
                'styleParams' => serialize($this->styleParams),
            ));
    }

    public function unserialize($data) {
        $data = unserialize($data);
        $this->styleParams = unserialize($data['styleParams']);
    }
}

