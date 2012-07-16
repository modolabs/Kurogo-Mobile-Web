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
        new esri.geometry.Point(___X___, ___Y___, mapLoader.spatialRef),
        new esri.symbol.___SYMBOL_TYPE___(___SYMBOL_ARGS___)),
    {
        title: ___TITLE___,
        subtitle: ___SUBTITLE___,
        url: "___URL___"
    }
);

