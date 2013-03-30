<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

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
            $styleRef = $this->normalStyle;
            if (is_string($styleRef) && $this->styleContainer) {
                // recover style from parser for pairs that were parsed before
                // the simple style was populated
                $styleRef = $this->styleContainer->getStyle($styleRef);
                if ($styleRef) {
                    $this->normalStyle = $styleRef;
                }
            }
            if ($styleRef instanceof KMLStyle) {
                switch ($type) {
                    case MapStyle::POINT: $style = $styleRef->getIconStyle(); break;
                    case MapStyle::LINE: $style = $styleRef->getLineStyle(); break;
                    case MapStyle::POLYGON: $style = $styleRef->getPolyStyle(); break;
                }
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
            && isset($style[MapStyle::SHOULD_OUTLINE])
            && $style[MapStyle::SHOULD_OUTLINE]
        ) {
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
                    MapStyle::WIDTH => $element->getProperty('W'),
                    MapStyle::HEIGHT => $element->getProperty('H'),
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
                $styleRef = substr($element->getProperty('STYLEURL'), 1);
                $style = $this->styleContainer->getStyle($styleRef);
                // store the style URL if the parser hasn't yet loaded the associated simple style
                if ($state == 'normal') {
                    $this->normalStyle = $style ? $styleRef : $style;
                } else if ($state == 'highlighted') {
                    $this->highlightStyle = $style ? $styleRef : $style;
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

    public function serialize() {
        // convert string style references to actual styles before serializing
        if (!$this->isSimpleStyle) {
            $styleRef = $this->normalStyle;
            if (is_string($styleRef) && $this->styleContainer) {
                $styleRef = $this->styleContainer->getStyle($styleRef);
                $this->normalStyle = $styleRef;
            }
            $styleRef = $this->highlightStyle;
            if (is_string($styleRef) && $this->styleContainer) {
                $styleRef = $this->styleContainer->getStyle($styleRef);
                $this->highlightStyle = $styleRef;
            }
        }

        return serialize(
            array(
                'isSimpleStyle' => $this->isSimpleStyle,
                'iconStyle' => serialize($this->iconStyle),
                'balloonStyle' => serialize($this->balloonStyle),
                'lineStyle' => serialize($this->lineStyle),
                'listStyle' => serialize($this->listStyle),
                'polyStyle' => serialize($this->polyStyle),
                'normalStyle' => serialize($this->normalStyle),
                'highlightStyle' => serialize($this->highlightStyle),
            ));
    }

    public function unserialize($data) {
        $data = unserialize($data);
        $this->isSimpleStyle = $data['isSimpleStyle'];
        $this->iconStyle = unserialize($data['iconStyle']);
        $this->balloonStyle = unserialize($data['balloonStyle']);
        $this->lineStyle = unserialize($data['lineStyle']);
        $this->listStyle = unserialize($data['listStyle']);
        $this->polyStyle = unserialize($data['polyStyle']);
        $this->normalStyle = unserialize($data['normalStyle']);
        $this->highlightStyle = unserialize($data['highlightStyle']);
    }
}
