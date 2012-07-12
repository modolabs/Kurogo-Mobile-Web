/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

mapLoader.addPlacemark(
    "___ID___",
    new esri.Graphic(
        new esri.geometry.Polygon(___POLYGON_SPEC___),
        new esri.symbol.SimpleFillSymbol(
            esri.symbol.SimpleFillSymbol.STYLE_SOLID,
            new esri.symbol.SimpleLineSymbol(
                esri.symbol.SimpleLineSymbol.STYLE_SOLID,
                new dojo.Color(___STROKE_COLOR___),
                ___STROKE_WEIGHT___
            ),
            new dojo.Color(___FILL_COLOR___)
        )
    ),
    {
        title: ___TITLE___,
        subtitle: ___SUBTITLE___,
        url: "___URL___"
    }
);
