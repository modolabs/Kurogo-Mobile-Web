<?php

class MapDBStyle implements MapStyle
{
    private $id;
    private $color;
    private $fillColor;
    private $strokeColor;
    private $strokeWidth;
    private $height;
    private $width;
    private $icon;
    private $style;
    private $scale;
    private $shape;
    private $consistency;

    private static function fieldMap() {
        return array(
            'strokeColor' => 'stroke_color',
            'fillColor' => 'fill_color',
            'strokeWidth' => 'stroke_width',
            'id' => 'style_id',
            );
    }

    public function getStyleForTypeAndParam($type, $param)
    {
        $style = array();

        switch ($type) {
            case MapStyle::LINE:
                if (isset($this->strokeWidth)) {
                    $style[MapStyle::WEIGHT] = $this->strokeWidth;
                }
                if (isset($this->strokeColor)) {
                    $style[MapStyle::COLOR] = $this->strokeColor;
                }
                if (isset($this->consistency)) {
                    $style[MapStyle::CONSISTENCY] = $this->consistency;
                }
                break;
            case MapStyle::POLYGON:
                if (isset($this->fillColor)) {
                    $style[MapStyle::FILLCOLOR] = $this->fillColor;
                }
                if (isset($this->strokeWidth)) {
                    $style[MapStyle::WEIGHT] = $this->strokeWidth;
                    if ($this->strokeWidth > 0) {
                        $style[MapStyle::SHOULD_OUTLINE] = 1;
                    }
                }
                if (isset($this->strokeColor)) {
                    $style[MapStyle::COLOR] = $this->strokeColor;
                }
                if (isset($this->consistency)) {
                    $style[MapStyle::CONSISTENCY] = $this->consistency;
                }
                break;
            case MapStyle::CALLOUT:
                // TODO: we probably want separate properties for these
                // instead of sharing with polygons
                if (isset($this->fillColor)) {
                    $style[MapStyle::FILLCOLOR] = $this->fillColor;
                }
                if (isset($this->fillColor)) {
                    $style[MapStyle::COLOR] = $this->color;
                }
                break;
            case MapStyle::POINT:
            default:
                if (isset($this->icon)) {
                    $style[MapStyle::ICON] = $this->icon;
                } else if (isset($this->color)) {
                    $style[MapStyle::COLOR] = $this->color;
                }

                if (isset($this->width)) {
                    $style[MapStyle::WIDTH] = $this->width;
                }
                if (isset($this->height)) {
                    $style[MapStyle::HEIGHT] = $this->height;
                }
                if (isset($this->scale)) {
                    $style[MapStyle::SCALE] = $this->scale;
                }

                break;
        }

        return $style;
    }

    public function getId()
    {
        return $this->id;
    }
    
    public function __construct($dbFields)
    {
        $fieldMap = self::fieldMap();
        foreach (get_class_vars(get_class($this)) as $name => $value) {
            $mappedName = isset($fieldMap[$name]) ? $fieldMap[$name] : $name;
            if (isset($dbFields[$mappedName])) {
                $this->{$nmae} = $dbFields[$name];
            }
        }
    }

    public function dbFields()
    {
        $fields = array();
        $fieldMap = self::fieldMap();
        foreach (get_class_vars(get_class($this)) as $name => $value) {
            $mappedName = isset($fieldMap[$name]) ? $fieldMap[$name] : $name;
            $fields[$mappedName] = $value;
        }
        return $fields;
    }
}
