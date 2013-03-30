/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

mapLoader = new KGOGoogleMapLoader({
    lat: ___CENTER_LATITUDE___,
    lon: ___CENTER_LONGITUDE___,
    zoom: ___ZOOMLEVEL___,
    ___OPTIONS___
    mapElement: "___MAPELEMENT___",
    minZoom: "___MINZOOM___",
    maxZoom: "___MAXZOOM___"
});

mapLoader.loadMap();

___PLACEMARK_SCRIPT___

mapLoader.showDefaultCallout();
