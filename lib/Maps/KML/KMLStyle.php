<?php

class KMLStyle extends XMLElement implements MapStyle
{
    protected $styleID;
    protected $isSimpleStyle = true;

    protected $iconStyle; // color, colorMode, scale, heading, hotSpot, icon>href
    protected $balloonStyle; // bgColor, textColor, text, displayMode
    protected $lineStyle; // color, colorMode, width
    protected $labelStyle;
    protected $listStyle;
    protected $polyStyle;

    // pointers to simple style objects
    protected $normalStyle;
    protected $highlightStyle;
    protected $styleContainer; // pointer to whoever owns the lookup table of simple styles

    public function getIconStyle() {
        return $this->iconStyle;
    }

    public function getLineStyle() {
        return $this->lineStyle;
    }

    public function getPolyStyle() {
        return $this->polyStyle;
    }

    private function getStyleForType($type) {
        $style = null;
        if ($this->isSimpleStyle) {
            switch ($type) {
                case MapStyle::POINT: $style = $this->iconStyle; break;
                case MapStyle::LINE: $style = $this->lineStyle; break;
                case MapStyle::POLYGON: $style = $this->polyStyle; break;
            }
        } else {
            $styleRef = $this->styleContainer->getStyle($this->normalStyle);
            switch ($type) {
                case MapStyle::POINT: $style = $styleRef->getIconStyle(); break;
                case MapStyle::LINE: $style = $styleRef->getLineStyle(); break;
                case MapStyle::POLYGON: $style = $styleRef->getPolyStyle(); break;
            }
        }
        return $style;
    }

    public function getStyleForTypeAndParam($type, $param) {
        $style = $this->getStyleForType($type);
        if (!$style) return null;

        if (isset($style[$param])) {
            return $style[$param];
        } else if ($type == MapStyle::POLYGON
            && $this->getStyleForTypeAndParam(MapStyle::POLYGON, MapStyle::SHOULD_OUTLINE))
        {
            $outlineStyle = $this->getStyleForType(MapStyle::LINE);
            if (isset($outlineStyle[$param])) {
                return $outlineStyle[$param];
            }
        }
        return null;
    }

    // xml parsing

    public function setStyleContainer($container) {
        $this->styleContainer = $container;
    }

    public function addElement(XMLElement $element)
    {
        $name = $element->name();
        $value = $element->value();
        
        switch ($name)
        {
            case 'ICONSTYLE':
                $iconChild = $element->getChildElement('ICON');
                $this->iconStyle = array(
                    MapStyle::ICON => $iconChild->getProperty('HREF'),
                    MapStyle::WIDTH => $iconChild->getProperty('W'),
                    MapStyle::HEIGHT => $iconChild->getProperty('H'),
                    MapStyle::SCALE => $element->getProperty('SCALE'),
                    );
                break;
            case 'BALLOONSTYLE':
                $this->balloonStyle = array(
                    MapStyle::FILLCOLOR => $element->getProperty('BGCOLOR'),
                    MapStyle::COLOR => $element->getProperty('TEXTCOLOR'),
                    );
                break;
            case 'LINESTYLE':
                $this->lineStyle = array(
                    MapStyle::COLOR => $element->getProperty('COLOR'),
                    MapStyle::WEIGHT => $element->getProperty('WEIGHT'),
                    );
                break;
            case 'POLYSTYLE':
                // if OUTLINE == 1, keep track and use lineStyle for outlines
                // if FILL == 1, use supplied color, otherwise just ignore color
                $shouldFill = $element->getProperty('FILL');
                $color = $shouldFill ? $element->getProperty('COLOR') : null;
                $this->polyStyle = array(
                    MapStyle::FILLCOLOR => $color,
                    MapStyle::SHOULD_OUTLINE => $element->getProperty('OUTLINE'),
                    );
                break;
            case 'LABELSTYLE':
                $this->labelStyle = array();
                break;
            case 'LISTSTYLE':
                $this->listStyle = array();
                break;
            case 'PAIR':
                $state = $element->getProperty('KEY');
                if ($state == 'normal') {
                    $this->normalStyle = substr($element->getProperty('STYLEURL'), 1);
                } else if ($state == 'highlighted') {
                    $this->highlightStyle = substr($element->getProperty('STYLEURL'), 1);
                }
                break;
            default:
                parent::addElement($element);
                break;
        }
        
    }
    
    public function __construct($name, $attribs)
    {
        $this->isSimpleStyle = ($name === 'STYLE');
        $this->setAttribs($attribs);
    }
}
