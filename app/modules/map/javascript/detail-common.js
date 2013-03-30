/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

var loadedImages = {};
var centerZoomBased;
var staticMapOptions;
var mapWidth;
var mapHeight;

function loadImage(imageURL,imageID) {
    if (!loadedImages[imageID]) {
        // Loads an image from the given URL into the image with the specified ID
        var img = document.getElementById(imageID);
        if (img) {
            if (imageURL != "") {
                img.src = imageURL;
            } else {
                img.src = "/common/images/blank.png";
            }
        }
        loadedImages[imageID] = true;
    }
}

function loadMapImage(newSrc) {
    var query = newSrc.substring(newSrc.indexOf("?") + 1, newSrc.length);
    staticMapOptions["query"] = escape(query);

    var mapImage = document.getElementById("staticmapimage");
    var oldSrc = mapImage.src;
    mapImage.src = newSrc;
    if (oldSrc != mapImage.src) {
        show("loadingimage");
    }
    mapImage.src = newSrc; // guarentee onload handler gets called at least 
                           // once after showing the loading image (even for cached images)
    mapImage.width = mapWidth;
    mapImage.style.width = mapWidth + "px";
    mapImage.height = mapHeight;
    mapImage.style.height = mapHeight + "px";
}

var mapControls = {
    recenterMap: function() {},
    locationUpdated: function(location) {},
    locationUpdateStopped: function(error) {},
    locationWatchId: null,
    locationIsFirstPosition: true,
    locateMeButton: null,
    toggleLocationUpdates: function() {
        if (this.locationWatchId === null) {
            this.startLocationUpdates();
        } else {
            this.stopLocationUpdates();
        }
    },
    startLocationUpdates: function() {
        this.locateMeButton.style.backgroundPosition = "-200px 0px";

        var that = this;
        that.locationIsFirstPosition = true;
        that.locationWatchId = navigator.geolocation.watchPosition(
            function (location) {
                that.locationUpdated(location, that.locationIsFirstPosition);
                that.locationIsFirstPosition = false;
            },
            function (error) {}, // don't really want to stop trying to locate here
            {enableHighAccuracy: true}
        );
    },
    stopLocationUpdates: function() {
        this.locateMeButton.style.backgroundPosition = "-160px 0px";
        if (this.locationWatchId != null) {
            navigator.geolocation.clearWatch(this.locationWatchId);
            this.locationWatchId = null;
            this.locationUpdateStopped(null);
        }
    },

    // params: { zoomin:Function,zoomout:Function,recenter:Function,
    //   ?locationUpdated:Function,?locationUpdateStopped:Function }
    setup: function(args) {
        this.recenterMap = args.recenter;
        if ("locationUpdated" in args) {
            this.locationUpdated = args.locationUpdated;
        }
        if ("locationUpdateStopped" in args) {
            this.locationUpdateStopped = args.locationUpdateStopped;
        }

        var zoominButton = document.getElementById("zoomin");
        if (zoominButton) {
            zoominButton.onclick = args.zoomin;
        }

        var zoomoutButton = document.getElementById("zoomout");
        if (zoomoutButton) {
            zoomoutButton.onclick = args.zoomout;
        }

        var recenterButton = document.getElementById("recenter");
        if (recenterButton) {
            recenterButton.onclick = this.recenterMap;
        }

        this.locateMeButton = document.getElementById("locateMe");
        if (this.locateMeButton) {
            if ("geolocation" in navigator && typeof(showUserLocation) != 'undefined') {
                var that = this;
                this.locateMeButton.onclick = function() {
                    that.toggleLocationUpdates();
                };
            } else {
                this.locateMeButton.parentNode.removeChild(this.locateMeButton);
                // realign other buttons
                zoomoutButton.style.left = "35%";
                recenterButton.style.left = "64%";
            }
        }
    }
}

function addStaticMapControls() {
    if (!staticMapOptions) {
        return;
    }

    var objMap = document.getElementById("staticmapimage");
    mapWidth = objMap.clientWidth;
    mapHeight = objMap.clientHeight;

    var query = objMap.src;
    query = query.substring(query.indexOf("?") + 1, query.length);
    staticMapOptions["query"] = escape(query);

    centerZoomBased = ("center" in staticMapOptions);

    var recenter;

    if (centerZoomBased) {
        var initCenterLat = staticMapOptions['center']['lat'];
        var initCenterLon = staticMapOptions['center']['lon'];
        var initZoom = staticMapOptions['zoom'];

        recenter = function() {
            updateMapImage(null, null, {
                "center": initCenterLat + "," + initCenterLon,
                "zoom": initZoom
            });
        }

    } else {
        var initBBox = staticMapOptions['bbox'];

        recenter = function() {
            var bboxStr = bbox['xmin'] + "," + bbox['ymin'] + "," + bbox['xmax'] + "," + bbox['ymax'];
            updateMapImage(null, null, {"bbox": bboxStr});
        }
    }

    mapControls.setup({
        zoomin: function() {
            updateMapImage("in", null, null);
        },
        zoomout: function() {
            updateMapImage("out", null, null);
        },
        recenter: recenter,
        locationUpdated: function(location, firstLocation) {
            var params = {
                'userLat': location.coords.latitude,
                'userLon': location.coords.longitude
            };
            
            // only recenter on first location so we don't rubber band on scrolling
            if (firstLocation) {
                params['center'] = location.coords.latitude + "," + location.coords.longitude;
            }
            
            updateMapImage(null, null, params);
        },
        locationUpdateStopped: function(error) {
            recenter();
        }
    });
}

// n, s, e, w, ne, nw, se, sw
function scrollMap(direction) {
    updateMapImage(null, direction, null);
}

function updateMapImage(zoomDir, scrollDir, overrides) {
    if (!("query" in staticMapOptions)) {
        return;
    }

    params = {
        "baseURL": staticMapOptions["baseURL"],
        "mapClass": staticMapOptions["mapClass"],
        "query": staticMapOptions["query"]};

    if (zoomDir) {
        params["zoom"] = zoomDir;
    }
    if (scrollDir) {
        params["scroll"] = scrollDir;
    }
    if (overrides) {
        overrideParams = [];
        for (var override in overrides) {
            overrideParams.push(override + "=" + overrides[override]);
        }
        if (overrideParams.length) {
            params["overrides"] = escape(overrideParams.join("&"));
        }
    }
    
    makeAPICall('GET', 'map', 'staticImageURL', params, function(response) {
        loadMapImage(response);
    });
}


// Prevent firebombing the browser with Ajax calls on browsers which fire lots
// of resize events
function updateMapDimensions() {
    clearUpdateMapDimensionsTimeouts();
    var timeoutId = window.setTimeout(doUpdateMapDimensions, 200);
    updateMapDimensionsTimeoutIds.push(timeoutId);
    timeoutId = window.setTimeout(doUpdateMapDimensions, 500);
    updateMapDimensionsTimeoutIds.push(timeoutId);
}

function doUpdateMapDimensions() {
    var oldHeight = mapHeight;
    var oldWidth = mapWidth;

    // TODO google static maps does not generate maps
    // larger than 1000px in either direction
    // need to set caps on mapWidth and mapHeight
    var mapImage = getFirstElementByClassName("mapimage");
    if (!mapImage) { return; } // using basic UI (bbplus)
    
    var mapTab = getFirstElementByClassName("map-tabbody");
    if (mapTab) { // not fullscreen
        mapTab.style.height="auto";

        var topoffset = findPosY(document.getElementById("tabbodies"));
        var bottomoffset = 56;
        
        document.getElementById("mapzoom").style.height = bottomoffset + "px";

        // 16 is top + bottom padding of mapimage
        // TODO don't hard code these numbers
        var testHeight = (getWindowHeight() - topoffset - bottomoffset - 16);
        if (testHeight > 80) { // if they can't get a useful map without scrolling, then let them scroll
            mapHeight = testHeight;
        }

        var s = window.getComputedStyle(mapImage.parentNode, null);
        mapWidth = parseInt(s.getPropertyValue('width'));

        mapImage.style.width = (mapWidth + 2) + "px"; // border

    } else { // fullscreen
        mapHeight = getWindowHeight();
        mapWidth = getWindowWidth();
        mapImage.style.width = mapWidth+"px";

        var objContainer = document.getElementById("container");
        if (objContainer) {
            objContainer.style.width = mapWidth+"px";
            objContainer.style.height = mapHeight+"px";
        }
    }
    mapImage.style.height = mapHeight + "px";
    setCSSValue(mapImage, 'min-height', '0');

    var objScrollers = document.getElementById("mapscrollers");
    if (objScrollers) {
        if (mapTab) {
            objScrollers.style.height = mapHeight+"px";
            objScrollers.style.width = mapWidth+"px";

        } else {
            switch (getOrientation()) {
                case 'portrait':
                  objScrollers.style.height = (mapHeight-42)+"px";
                  objScrollers.style.width = mapWidth+"px";
                 break;
        
                case 'landscape':
                  objScrollers.style.height = mapHeight+"px";
                  objScrollers.style.width = (mapWidth-42)+"px";
                break;
            }
        }
    }

    // request new map image if needed

    var overrides = {};

    if ((oldWidth && oldWidth != mapWidth) || (oldHeight && oldHeight != mapHeight)) {
        // sometimes centerZoomBased gets defined later
        if (!centerZoomBased && "bbox" in staticMapOptions) {
            // if width and height changed, we need to update the bbox
            var bbox = staticMapOptions['bbox'];
            var bboxWidth = bbox['xmax'] - bbox['xmin'];
            var bboxHeight = bbox['ymax'] - bbox['ymin'];
            var newBBoxWidth = bboxWidth * mapWidth / oldWidth;
            var newBBoxHeight = bboxHeight * mapHeight / oldHeight;
            
            var dWidth = (newBBoxWidth - bboxWidth) / 2;
            var dHeight = (newBBoxHeight - bboxHeight) / 2;
            
            bbox['xmax'] += dWidth;
            bbox['xmin'] -= dWidth;
            bbox['ymax'] += dHeight;
            bbox['ymin'] -= dHeight;
            
            staticMapOptions['bbox'] = bbox;

            overrides["bbox"] = bbox['xmin'] + "," + bbox['ymin'] + "," + bbox['xmax'] + "," + bbox['ymax'];
            overrides["size"] = mapWidth + "," + mapHeight;
            overrides["width"] = mapWidth;
            overrides["height"] = mapHeight;
        } else {
            overrides["size"] = mapWidth + "x" + mapHeight;
        }
    
        updateMapImage(null, null, overrides);
    }
}

function addDirectionsLink() {
    if ("geolocation" in navigator) {
        var directionsLink = document.getElementById("directionsLink");
        if (directionsLink) {
            navigator.geolocation.getCurrentPosition(
                function(location) {
                    directionsLink.href += "&saddr=" + location.coords.latitude + "," + location.coords.longitude;
                },
                function() {}, {}
            );
        }
    }
}


