<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

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

    public function serialize() {
        return serialize(
            array(
                'subtitleField' => $this->subtitleField,
                'titleField' => $this->titleField,
                'parent' => parent::serialize(),
            ));
    }

    public function unserialize($data) {
        $data = unserialize($data);
        parent::unserialize($data['parent']);
        $this->titleField = $data['titleField'];
        $this->subtitleField = $data['subtitleField'];
    }
}


