<?php

interface MapStyle
{
    const POINT = 0;
    const LINE = 1;
    const POLYGON = 2;
    const CALLOUT = 3;

    // these just have to be unique within the enclosing style type
    const COLOR = 'color';             // points
    const FILLCOLOR = 'fillColor';     // polygons, callouts, list view
    const STROKECOLOR = 'strokeColor'; // lines
    const TEXTCOLOR = self::COLOR;     // callouts
    const HEIGHT = 'height';           // points
    const WIDTH = 'width';             // points and lines
    const SIZE = self::WIDTH;          // points
    const WEIGHT = self::WIDTH;        // lines
    const ICON = 'icon';               // points, cell image in list view
    const SCALE = 'scale';             // points, labels -- kml
    const SHAPE = 'shape';             // points -- esri
    const CONSISTENCY = 'consistency'; // lines -- dotted/dashed/etc
    const SHOULD_OUTLINE = 'outline';  // polygons

    public function getStyleForTypeAndParam($type, $param);
}