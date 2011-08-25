<?php

class MapDBCategory extends MapCategory
{
    private $parentCategoryId;
    private $features = array();
    private $childCategories = array();
    private $stored = false;
    private $projection;

    public function __construct($dbFields, $fromDB=false)
    {
        $this->id = $dbFields['category_id'];
        $this->stored = $fromDB;
        if (isset($dbFields['parent_category_id'])) {
            $this->parentCategoryId = $dbFields['parent_category_id'];
        }
        if (isset($dbFields['name'])) {
            $this->title = $dbFields['name'];
        }
        if (isset($dbFields['description'])) {
            $this->description = $dbFields['description'];
        }
        if (isset($dbFields['projection'])) {
            $this->projection = $dbFields['projection'];
        }
    }

    public function setParentCategoryId($id)
    {
        $this->parentCategoryId = $id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function setSubtitle($subtitle)
    {
        $this->description = $subtitle;
    }

    public function isStored()
    {
        return $this->stored;
    }

    public function dbFields()
    {
        $fields = array(
            'category_id' => $this->id,
            'parent_category_id' => $this->parentCategoryId,
            'name' => $this->title,
            'projection' => $this->projection,
            'description' => $this->description,
            );

        return $fields;
    }

    public function getProjection()
    {
        return $this->projection;
    }

    public function getChildCategories()
    {
        return $this->childCategories;
    }

    public function getAllPlacemarks()
    {
        return $this->features;
    }

    public function getListItems()
    {
        if ($this->childCategories) {
            return $this->childCategories;
        } else if ($this->features) {
            return $this->features;
        }

        // no data in memory, check db
        $this->childCategories = MapDB::childrenForCategory($this->id);
        if ($this->childCategories) {
            return $this->childCategories;
        }

        $this->features = MapDB::featuresForCategory($this->id);
        if ($this->features) {
            return $this->features;
        }

        return array();
    }
}
