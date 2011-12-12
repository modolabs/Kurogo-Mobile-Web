<?php

class ShapefilePlacemark extends BasePlacemark
{
    protected $titleField = null;
    protected $subtitleField = null;

    public function getTitle() {
        if ($this->titleField && isset($this->fields[$this->titleField])) {
            return $this->fields[$this->titleField];
        }
        // otherwise pick a random field
        if (count($this->fields) > 1) {
            $array = array_values($this->fields);
            return next($array);
        }
        return null;
    }

    public function getSubtitle() {
        if ($this->subtitleField && isset($this->fields[$this->subtitleField])) {
            return $this->fields[$this->subtitleField];
        }
        return null;
    }

    public function setTitleField($field) {
        $this->titleField = $field;
    }

    public function setSubtitleField($field) {
        $this->subtitleField = $field;
    }

    public function setFields($fields) {
        $this->fields = $fields;
    }
}


