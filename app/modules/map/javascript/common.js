var map;
var mapLoader;

// id7 doesn't understand window.innerWidth and window.innerHeight
function getWindowHeight() {
    if (window.innerHeight !== undefined) {
        return window.innerHeight;
    } else {
        return document.documentElement.clientHeight;
    }
}

function getWindowWidth() {
    if (window.innerWidth !== undefined) {
        return window.innerWidth;
    } else {
        return document.documentElement.clientWidth;
    }
}

// assuming only one of updateMapDimensions or updateContainerDimensions
// gets used so they can reference the same ids
// updateMapDimensions is called for static maps
// updateContainerDimensions is called for dynamic maps
var updateMapDimensionsTimeoutIds = [];
function clearUpdateMapDimensionsTimeouts() {
    for(var i = 0; i < updateMapDimensionsTimeoutIds.length; i++) {
        window.clearTimeout(updateMapDimensionsTimeoutIds[i]);
    }
    updateMapDimensionsTimeoutIds = [];
}

// resizing counterpart for dynamic maps
function updateContainerDimensions() {
    clearUpdateMapDimensionsTimeouts();
    var timeoutId = window.setTimeout(doUpdateContainerDimensions, 200);
    updateMapDimensionsTimeoutIds.push(timeoutId);
    timeoutId = window.setTimeout(doUpdateContainerDimensions, 500);
    updateMapDimensionsTimeoutIds.push(timeoutId);
    timeoutId = window.setTimeout(doUpdateContainerDimensions, 1000);
    updateMapDimensionsTimeoutIds.push(timeoutId);
}

function doUpdateContainerDimensions() {
    var mapimage = document.getElementById("mapimage");
    if (mapimage) {
        var topoffset = findPosY(mapimage);
        mapimage.style.height = (getWindowHeight() - topoffset) + "px";
    }

    if (typeof mapLoader.resizeMapOnContainerResize == 'function') {
        mapLoader.resizeMapOnContainerResize();
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

function kgoMapLoader(attribs) {
    this.initLat = ("lat" in attribs) ? attribs["lat"] : 0;
    this.initLon = ("lon" in attribs) ? attribs["lon"] : 0;
    this.initZoom = ("zoom" in attribs) ? attribs["zoom"] : 1;
    this.mapElement = ("mapElement" in attribs) ? attribs["mapElement"] : null;

    this.placemarks = [];
    this.showUserLocation = true;
    this.userLocationMarker = null;

    this.loadMap = function() {}

    // annotations
    this.showDefaultCallout = function() {}
    this.showCalloutForMarker = function(marker) {}
    this.showCalloutForOverlay = function(overlay) {}
    this.addMarker = function(marker, attribs) {}
    this.addOverlay = function(overlay, attribs) {}
    this.clearMarkers = function() {}
    this.createMarker = function(title, subtitle, lat, lon, url) {}

    // base map
    this.resizeMapOnContainerResize = function() {}
    this.setMapBounds = function(minLat, minLon, maxLat, maxLon) {} // top left bottom right

    // user location
    this.locateMeButton = null; // CSS applies to an <a id="locateMe"> element
    this.locationWatchId = null;
    this.locationIsFirstPosition = true,
    this.locationUpdated = function(location) {}
    this.locationUpdateStopped = function(error) {}
    this.toggleLocationUpdates = function() {
        if (this.locationWatchId === null) {
            this.startLocationUpdates();
        } else {
            this.stopLocationUpdates();
        }
    }
    this.startLocationUpdates = function() {
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
    }
    this.stopLocationUpdates = function() {
        this.locateMeButton.style.backgroundPosition = "-160px 0px";
        if (this.locationWatchId != null) {
            navigator.geolocation.clearWatch(this.locationWatchId);
            this.locationWatchId = null;
            this.locationUpdateStopped(null);
        }
    }
}

function kgoGoogleMapLoader(attribs) {
    var that = new kgoMapLoader(attribs);
    var currentInfoWindow = null;
    var markerOnTop = true; // track type of last placemark since it affects choice of function for positioning

    var setCurrentInfoWindow = function(infoWindow) {
        if (currentInfoWindow !== null) {
            currentInfoWindow.close();
        }
        currentInfoWindow = infoWindow;
        var calloutListener = google.maps.event.addDomListener(map, 'click', function() {
            currentInfoWindow.close();
            currentInfoWindow = null;
            google.maps.event.removeListener(calloutListener);
        });
    }

    var generateInfoWindowContent = function(title, subtitle, url) {
        var content = '<div class="map_name">' + title + '</div>';
         
        if (subtitle !== null) {
            content += '<div class="smallprint map_address">' + subtitle + '</div>';
        }

        if (typeof url != 'undefined' && url !== null) {
            var query = url.match(/\?(.+)/)[1];
            content = '<table><tr>' + 
                        '<td class="calloutBookmark">' + 
                          '<a onclick="toggleBookmark(\'mapbookmarks\', \'' + query + '\', 3600, \'/kurogo/\')">' +
                            '<div id="bookmark" ontouchend="removeClass(this, \'pressed\')" ontouchstart="addClass(this, \'pressed\')"></div>' +
                          '</a></td>' +
                        '<td class="calloutMain">' + content + '</td>' +
                        '<td class="calloutDisclosure">' +
                          '<a href="' + url + '"><img src="' + URL_BASE + '/modules/map/images/info.png" /></a>' +
                        '</td>' + 
                      '</tr></table>';
        }
        return content;
    }

    that.loadMap = function() {
        var mapImage = document.getElementById(that.mapElement);

        var initCoord = new google.maps.LatLng(that.initLat, that.initLon);
        var options = {
            zoom: that.initZoom,
            center: initCoord,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            disableDefaultUI: true
        };

        map = new google.maps.Map(mapImage, options);

        // setup zoom and other controls

        var controlDiv = document.createElement('div');
        controlDiv.id = "mapcontrols"

        var zoominButton = document.createElement('a');
        zoominButton.id = "zoomin";
        zoominButton.onclick = function() {
            map.setZoom(map.getZoom() + 1);
        }
        controlDiv.appendChild(zoominButton);

        var zoomoutButton = document.createElement('a');
        zoomoutButton.id = "zoomout";
        zoomoutButton.onclick = function() {
            map.setZoom(map.getZoom() - 1);
        }
        controlDiv.appendChild(zoomoutButton);

        var recenterButton = document.createElement('a');
        recenterButton.id = "recenter";
        recenterButton.onclick = function() {
            map.setCenter(initCoord);
            map.setZoom(that.initZoom);
        }
        controlDiv.appendChild(recenterButton);

        if ("geolocation" in navigator && that.showUserLocation) {
            that.locateMeButton = document.createElement('a');
            that.locateMeButton.id = "locateMe";
            that.locateMeButton.onclick = function() {
                that.toggleLocationUpdates();
            }
            controlDiv.appendChild(that.locateMeButton);
        }

        map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(controlDiv);
    }

    that.locationUpdated = function(location, firstLocation) {
        var position = new google.maps.LatLng(location.coords.latitude, location.coords.longitude);
        if (typeof that.userLocationMarker == 'undefined') {
            // TODO make these more customizable
            var icon = new google.maps.MarkerImage(URL_BASE + API_URL_PREFIX + '/modules/map/images/map-location@2x.png',
                null, // original size
                null, // origin (0, 0)
                new google.maps.Point(8, 8), // anchor
                new google.maps.Size(16, 16)); // scaled size

            that.userLocationMarker = new google.maps.Marker({
                'clickable' : false,
                'map'       : map,
                'position'  : position,
                'flat'      : true,
                'icon'      : icon
            });

        } else {
            if (that.userLocationMarker.getMap() === null) {
                that.userLocationMarker.setMap(map);
            }
            that.userLocationMarker.setPosition(position);
        }

        // only recenter on first location so we don't rubber band on scrolling
        // include current map center on map so zoom/pan is not as confusing
        if (firstLocation) {
            var bounds = new google.maps.LatLngBounds();
            bounds.extend(initCoord);
            bounds.extend(position);
            bounds.extend(map.getCenter());
            map.fitBounds(bounds);
        }
    }

    that.locationUpdateStopped = function() {
        if (typeof that.userLocationMarker != 'undefined') {
            that.userLocationMarker.setMap(null); // remove marker
        }
    }

    // annotations

    that.showDefaultCallout = function() {
        if (that.placemarks.length == 1) {
            if (markerOnTop) {
                that.showCalloutForMarker(that.placemarks[0]);
            } else {
                that.showCalloutForOverlay(that.placemarks[0]);
            }
        }
    }

    that.showCalloutForMarker = function(marker) {
        marker.infoWindow.open(map, marker);
        setCurrentInfoWindow(marker.infoWindow);
    }

    that.showCalloutForOverlay = function(overlay) {
        overlay.infoWindow.open(map);
        setCurrentInfoWindow(overlay.infoWindow);
    }

    that.addMarker = function(marker, attribs) {
        marker.infoWindow = new google.maps.InfoWindow({
            'content' : generateInfoWindowContent(attribs['title'], attribs['subtitle'], attribs['url']),
            'maxWidth' : 200
        });

        google.maps.event.addListener(marker, 'click', function() {
            that.showCalloutForMarker(marker);
        });

        that.placemarks.push(marker);
        markerOnTop = true;
    }

    that.addOverlay = function(overlay, attribs) {
        overlay.infoWindow = new google.maps.InfoWindow({
            'content' : generateInfoWindowContent(attribs['title'], attribs['subtitle'], attribs['url']),
            'maxWidth' : 200,
            'position' : new google.maps.LatLng(attribs['lat'], attribs['lon'])
        });

        google.maps.event.addListener(overlay, 'click', function() {
            that.showCalloutForOverlay(overlay);
        });

        that.placemarks.push(overlay);
        markerOnTop = false;
    }

    that.clearMarkers = function() {
        for (var i = 0; i < that.placemarks.length; i++) {
            placemarks[i].setMap(null);
        }
        that.placemarks = [];
    }

    that.createMarker = function(title, subtitle, lat, lon, url) {
        that.addMarker(new google.maps.Marker({
            position: new google.maps.LatLng(lat, lon),
            map: map,
            title: title
            }), title, subtitle, url);
    }

    // base map

    that.resizeMapOnContainerResize = function() {
        if (map) {
            var center = map.getCenter();
            google.maps.event.trigger(map, 'resize');
            map.setCenter(center);
        }
    }

    // top left bottom right
    that.setMapBounds = function(minLat, minLon, maxLat, maxLon) {
        var bounds = new google.maps.LatLngBounds();
        bounds.extend(new google.maps.LatLng(minLat, minLon));
        bounds.extend(new google.maps.LatLng(maxLat, maxLon));
        map.fitBounds(bounds);
    }

    return that;
}

function kgoEsriMapLoader(attribs) {
    var that = new kgoMapLoader(attribs);

    var wkid = ("wkid" in attribs) ? attribs["wkid"] : 4326;
    that.spatialRef = new esri.SpatialReference({ wkid: wkid });
    that.userLocationMarkerOnMap = false;

    that.loadMap = function() {
        center = new esri.geometry.Point(that.initLon, that.initLat, that.spatialRef);

        map = new esri.Map(that.mapElement, {
            'logo' : false,
            //'slider' : false,
            'resizeDelay' : 300
        });

        var basemapURL = attribs["baseURL"];
        var basemap = new esri.layers.ArcGISTiledMapServiceLayer(basemapURL);

        map.addLayer(basemap);

        // this line doesn't seem to work if placed anywhere other than here
        dojo.connect(map, "onLoad", plotFeatures);
    }

    // annotations
    that.showDefaultCallout = function() {}
    that.showCalloutForMarker = function(marker) {}
    that.showCalloutForOverlay = function(overlay) {}

    that.addMarker = function(marker, attribs) {
        infoTemplate = new esri.InfoTemplate();
        infoTemplate.setTitle(attribs["title"]);
        infoTemplate.setContent(attribs["subtitle"]);
        marker.setInfoTemplate(infoTemplate);
        map.graphics.add(marker);
        that.placemarks.push(marker);
    }

    that.addOverlay = function(overlay, attribs) {
        map.graphics.add(overlay);
        that.placemarks.push(overlay);
        // TODO: make callouts work
    }

    that.clearMarkers = function() {}
    that.createMarker = function(title, subtitle, lat, lon, url) {}

    // base map

    that.resizeMapOnContainerResize = function() {
        if (map && map.loaded) {
            var mapimage = document.getElementById(that.mapElement);
            if (mapimage && mapimage.clientHeight) {
                map.reposition();
                map.resize();
            }
        }
    }

    that.setMapBounds = function(minLat, minLon, maxLat, maxLon) {
        
    }

    // user location

    that.locationUpdated = function(location, firstLocation) {
        var params = {
            'lat': location.coords.latitude,
            'lon': location.coords.longitude,
            'from': 4326,
            'to': wkid
        };
        makeAPICall('GET', 'map', 'projectPoint', params, function(response) {
            var point = new esri.geometry.Point(response.lon, response.lat, spatialRef);

            if (typeof that.userLocationMarker == 'undefined') {
                // TODO make these more customizable
                var pointSymbol = new esri.symbol.PictureMarkerSymbol(URL_BASE + 'modules/map/images/map-location@2x.png', 16, 16);
                that.userLocationMarker = new esri.Graphic(point, pointSymbol);

            } else {
                that.userLocationMarker.setGeometry(point);
            }
            
            if (!that.userLocationMarkerOnMap) {
                map.graphics.add(uthat.serLocationMarker);
                that.userLocationMarkerOnMap = true;
            }

            if (firstLocation) {
                // only recenter on first location so we don't rubber band on scrolling
                var points = esri.geometry.Multipoint(spatialRef);
                points.addPoint(center);
                points.addPoint(point);
                
                var extent = points.getExtent();
                extent = extent.expand(1.5); // add padding around markers
                map.setExtent(extent);
            }
        });
    }

    that.locationUpdateStopped = function() {
        if (typeof that.userLocationMarker != 'undefined') {
            map.graphics.remove(that.userLocationMarker);
            that.userLocationMarkerOnMap = false;
        }
    }
    
    return that;   
}

