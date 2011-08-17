var loadedImages = {};
var centerZoomBased;
var staticMapOptions;
var mapWidth;
var mapHeight;
var apiURL;

function hideMapTabChildren() {
    var mapTab = document.getElementById("mapTab");
    for (var i = 0; i < mapTab.childNodes.length; i++) {
        var node = mapTab.childNodes[i];
        if (node.className == "image") {
            mapTab.removeChild(node);
            break;
        }
    }
    for (var i = 0; i < mapTab.childNodes.length; i++) {
        var node = mapTab.childNodes[i];
        if (node.className == "scrollers") {
            mapTab.removeChild(node);
            break;
        }
    }
}

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
    var mapImage = document.getElementById("staticmapimage");
    var oldSrc = mapImage.src;
    mapImage.src = newSrc;
    if (oldSrc != mapImage.src) {
        show("loadingimage");
    }
    mapImage.src = newSrc; // guarentee onload handler gets called at least 
                           // once after showing the loading image (even for cached images)
}

// next three functions for compliant

function setMapHeights() {
    // Set the height of the tabs container to fill the browser window height
    var mapimage = document.getElementById("mapimage");
    var maptab = document.getElementById("mapTab");
    if (mapimage) { 
        mapimage.style.height="50%";
    }
    if (maptab) {
        maptab.style.height="1000px";
    }
    setTimeout("scrollTo(0,1)",500);
    setTimeout("setHeight()",600);
}

function setHeight() {
    var mapimage = document.getElementById("mapimage");
    var maptab = document.getElementById("mapTab");
    if (mapimage) { 
        var topoffset = findPosY(document.getElementById("tabbodies"));
        var bottomoffset = 56;
        document.getElementById("mapzoom").style.height=bottomoffset + "px";
        mapimage.style.height=(window.innerHeight - topoffset- bottomoffset) + "px";
    }
    if (maptab) {
        maptab.style.height="auto";
    }
}

function findPosY(obj) {
// Function for finding the y coordinate of the object passed as an argument.
// Returns the y coordinate as an integer, relative to the top left origin of the document.
    var intCurlTop = 0;
    if (obj.offsetParent) {
        while (obj.offsetParent) {
            intCurlTop += obj.offsetTop;
            obj = obj.offsetParent;
        }
    }
    return intCurlTop;
}

// next function for tablet

function setTabsHeight() {
    // Set the height of the tabs container to fill the browser window height
    var tc = document.getElementById("tabscontainer");
    if(tc) { tc.style.height=(window.innerHeight-56) + "px" }
}

function pixelsFromString(aString) {
    if (aString.substring(aString.length - 2, aString.length) == "px") {
        return aString.substring(0, aString.length - 2);
    } else if (aString.substring(aString.length - 1, aString.length) == "%") {
        return aString.substring(0, aString.length - 1);
    }
    return aString;
}

function zoomInFromCenter() {
    staticMapOptions['zoom'] = parseInt(staticMapOptions['zoom']) + 1;
    updateMapImage();
}

function zoomOutFromCenter() {
alert("zoomin");

    staticMapOptions['zoom'] = parseInt(staticMapOptions['zoom']) - 1;
    updateMapImage();
}

function zoomInFromBBox() {
    var bbox = staticMapOptions['bbox'];
    var dLat = (bbox['ymax'] - bbox['ymin']) / 4;
    var dLon = (bbox['xmax'] - bbox['xmin']) / 4;
    bbox['ymax'] = bbox['ymax'] - dLat;
    bbox['xmax'] = bbox['xmax'] - dLon;
    bbox['ymin'] = bbox['ymin'] + dLat;
    bbox['xmin'] = bbox['xmin'] + dLon;
    staticMapOptions['bbox'] = bbox;
    updateMapImage();
}

function zoomOutFromBBox() {
    var bbox = staticMapOptions['bbox'];
    var dLat = (bbox['ymax'] - bbox['ymin']) / 2;
    var dLon = (bbox['xmax'] - bbox['xmin']) / 2;
    bbox['ymax'] = bbox['ymax'] + dLat;
    bbox['xmax'] = bbox['xmax'] + dLon;
    bbox['ymin'] = bbox['ymin'] - dLat;
    bbox['xmin'] = bbox['xmin'] - dLon;
    staticMapOptions['bbox'] = bbox;
    updateMapImage();
}

var mapControls = {
    recenterMap: function() {},
    locationUpdated: function(location) {},
    locationUpdateStopped: function() {},
    locateMeButton: null,
    timerId: null,
    toggleLocationUpdates: function() {
        if (this.timerId === null) {
            this.startLocationUpdates();
        } else {
            this.stopLocationUpdates();
            this.recenterMap();
        }
    },
    startLocationUpdates: function() {
        this.locateMeButton.style.backgroundPosition = "-200px 0";
        if (this.timerId === null) {
            var that = this;
            this.timerId = setInterval(function() {
                navigator.geolocation.getCurrentPosition(
                    that.locationUpdated,
                    that.locationUpdateStopped,
                    {enableHighAccuracy: true});
               }, 5000);
        }
    },
    // draggable maps should also call this if user drags the map while updates are on
    stopLocationUpdates: function() {
        this.locateMeButton.style.backgroundPosition = "-160px 0";
        if (this.timerId !== null) {
            clearInterval(this.timerId);
            this.timerId = null;
            this.locationUpdateStopped();
        }
    },

    // params: { zoomin:Function,zoomout:Function,recenter:Function,
    //   ?locationUpdated:Function,?locationUpdateStopped:Function }
    setup: function(args) {
        this.recenter = args.recenter;
        if ("locationUpdated" in args) {
            this.locationUpdated = args.locationUpdated;
        }
        if ("locationUpdateStopped" in args) {
            this.locationUpdateStopped = args.locationUpdateStopped;
        }

        var zoominButton = document.getElementById("zoomin");
        zoominButton.onclick = args.zoomin;

        var zoomoutButton = document.getElementById("zoomout");
        zoomoutButton.onclick = args.zoomout;

        var recenterButton = document.getElementById("recenter");
        recenterButton.onclick = this.recenter;

        this.locateMeButton = document.getElementById("locateMe");
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

function addStaticMapControls() {
    if (!staticMapOptions) {
        return;
    }

    var objMap = document.getElementById("staticmapimage");
    mapWidth = objMap.clientWidth;
    mapHeight = objMap.clientHeight;

    /*
    var zoomIn = document.getElementById("zoomin");
    var zoomOut = document.getElementById("zoomout");
    var recenter = document.getElementById("recenter");
    //var locateMe = document.getElementById("locateMe");
    */
    centerZoomBased = ("center" in staticMapOptions);

    if (centerZoomBased) {


        //zoomIn.onclick = zoomInFromCenter;
        //zoomOut.onclick = zoomOutFromCenter;
        
        var initCenterLat = staticMapOptions['center']['lat'];
        var initCenterLon = staticMapOptions['center']['lon'];
        var initZoom = staticMapOptions['zoom'];
        /*
        recenter.onclick = function() {
            staticMapOptions['center'] = {'lat': initCenterLat, 'lon': initCenterLon};
            staticMapOptions['zoom'] = initZoom;
            updateMapImage();
        }
        */

        mapControls.setup({
            zoomin: zoomInFromCenter,
            zoomout: zoomOutFromCenter,
            recenter: function() {
                staticMapOptions['center'] = {'lat': initCenterLat, 'lon': initCenterLon};
                staticMapOptions['zoom'] = initZoom;
                updateMapImage();
            }
        });

    } else {
        //zoomIn.onclick = zoomInFromBBox;
        //zoomOut.onclick = zoomOutFromBBox;
        
        var initBBox = staticMapOptions['bbox'];
        /*
        recenter.onclick = function() {
            staticMapOptions['bbox'] = initBBox;
            updateMapImage();
        }
        */

        mapControls.setup({
            zoomin: zoomInFromBBox,
            zoomout: zoomOutFromBBox,
            recenter: function() {
                staticMapOptions['bbox'] = initBBox;
                updateMapImage();
            }
        });
    }
}

// north and east are sign arguments, e.g.:
// northeast is (1, 1)
// northwest is (1, -1)
// south is (-1, 0)
function scrollMap(north, east) {

    if (centerZoomBased) {
        var zoom = staticMapOptions['zoom'];
        var lat = staticMapOptions['center']['lat'];
        var lon = staticMapOptions['center']['lon'];
        var degreesCovered = 360 / Math.pow(2, parseInt(zoom) + 1);

        lat = parseFloat(lat) + degreesCovered * north;
        lon = parseFloat(lon) + degreesCovered * east;

        // round to 4 decimal places (roughly 10 meters)
        staticMapOptions['center']['lat'] = Math.round(lat * 10000) / 10000;
        staticMapOptions['center']['lon'] = Math.round(lon * 10000) / 10000;

    } else {
        var bbox = staticMapOptions['bbox'];
        var dLat = (bbox['ymax'] - bbox['ymin']) / 2;
        var dLon = (bbox['xmax'] - bbox['xmin']) / 2;
        bbox['ymax'] = bbox['ymax'] + dLat * north;
        bbox['ymin'] = bbox['ymin'] + dLat * north;
        bbox['xmax'] = bbox['xmax'] + dLon * east;
        bbox['xmin'] = bbox['xmin'] + dLon * east;
        staticMapOptions['bbox'] = bbox;
    }
    updateMapImage();
}

function updateMapImage() {
    var params = {
        "baseURL": staticMapOptions['baseURL'],
        "mapClass": staticMapOptions['mapClass'],
        "width": mapWidth,
        "height": mapHeight};

    if (centerZoomBased) {
        params["lat"] = staticMapOptions['center']['lat'];
        params["lon"] = staticMapOptions['center']['lon'];
        params["zoom"] = staticMapOptions['zoom'];
    } else {
        var bbox = staticMapOptions['bbox'];
        var bboxStr = bbox['xmin'] + "," + bbox['ymin'] + "," + bbox['xmax'] + "," + bbox['ymax'];
        params["bbox"] = bboxStr;
    }

    if ("projection" in staticMapOptions) {
        params["projection"] = staticMapOptions['projection'];
    }
    if ('markers' in staticMapOptions && staticMapOptions['markers']) {
        params["markers"] = staticMapOptions['markers'];
    }
    if ('polygons' in staticMapOptions) {
        for (arg in staticMapOptions['polygons']) {
            params[arg] = staticMapOptions['polygons'][arg];
        }
    }
    if ('paths' in staticMapOptions) {
        for (arg in staticMapOptions['paths']) {
            params[arg] = staticMapOptions['args'][arg];
        }
    }

    apiRequest(apiURL, params, function(response) {
alert(response);
        loadMapImage(response);
    }, function(code, message) {
alert(message);
    });
}


// assuming only one of updateMapDimensions or updateContainerDimensions
// gets used so they can reference the same ids
var updateMapDimensionsTimeoutIds = [];
function clearUpdateMapDimensionsTimeouts() {
    for(var i = 0; i < updateMapDimensionsTimeoutIds.length; i++) {
        window.clearTimeout(updateMapDimensionsTimeoutIds[i]);
    }
    updateMapDimensionsTimeoutIds = [];
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
    if (!centerZoomBased) {
        // if width and height proprotions changed, we need to update the bbox
        var oldHeight = mapHeight;
        var oldWidth = mapWidth;
    }
    
    if (window.innerHeight !== undefined) {
        mapHeight = window.innerHeight;
    } else {
        mapHeight = document.documentElement.clientHeight; // ie7
    }

    if (window.innerWidth !== undefined) {
        mapWidth = window.innerWidth;
    } else {
        mapWidth = document.documentElement.clientWidth; // ie7
    }

    if (!centerZoomBased) {
        // if width and height changed, we need to update the bbox
        if ((oldWidth && oldWidth != mapWidth) || (oldHeight && oldHeight != mapHeight)) {
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
        }
    }

    var objMap = document.getElementById("mapimage");
	var objContainer = document.getElementById("container");
	var objScrollers = document.getElementById("mapscrollers");
    if (objContainer && objMap.className == "fullmap") {
        objContainer.style.width = mapWidth+"px";
        objContainer.style.height = mapHeight+"px";
        objMap.style.width = mapWidth+"px";
        objMap.style.height = mapHeight+"px";
    }
    if (objScrollers) {
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
    
    updateMapImage();
}

// resizing counterpart for dynamic maps
function updateContainerDimensions() {
    clearUpdateMapDimensionsTimeouts();
    var timeoutId = window.setTimeout(doUpdateContainerDimensions, 200);
    updateMapDimensionsTimeoutIds.push(timeoutId);
    timeoutId = window.setTimeout(doUpdateContainerDimensions, 500);
    updateMapDimensionsTimeoutIds.push(timeoutId);
}

function doUpdateContainerDimensions() {
    var container = document.getElementById("container");
    if (container) {
        var newWidth;
        if (window.innerWidth !== undefined) {
            newWidth = window.innerWidth + "px";
        } else {
            newWidth = document.documentElement.clientWidth + "px"; // ie7
        }
        var newHeight;
        if (window.innerHeight !== undefined) {
            newHeight = window.innerHeight + "px";
        } else {
            newHeight = document.documentElement.clientHeight + "px"; // ie7
        }

        // check to see if the container height and width actually changed
        if (container.style && container.style.width && container.style.width == newWidth
                            && container.style.height && container.style.height == newHeight) {

           // nothing changed so exit early
           return;
        }

        container.style.width = newWidth;
        container.style.height = newHeight;

        if (typeof resizeMapOnContainerResize == 'function') {
            resizeMapOnContainerResize();
        }
    }
}

/*
function disable(strID) {
// Visually dims and disables the anchor whose id is strID
	var objA = document.getElementById(strID);
	if(objA) {
		if(objA.className.indexOf("disabled") == -1) { // only disable if it's not already disabled!
			objA.className = objA.className + " disabled";
		}
	}
}

function enable(strID) {
// Visually undims and re-enables the anchor whose id is strID
	var objA = document.getElementById(strID);
	if(objA) {
		objA.className = objA.className.replace("disabled","");
	}
}

function cancelOptions(strFormID) {
// Should cancel map-option changes and hide the form; this is just a stub for future real function
	var objForm = document.getElementById(strFormID);
	if(objForm) { objForm.reset() }
	hide("options"); 
}
*/

