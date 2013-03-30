/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

///// various base maps

function KGOMapLoader(attribs) {
    attribs = attribs || {};

    this.provider = null;

    this.initLat = ("lat" in attribs) ? attribs["lat"] : 0;
    this.initLon = ("lon" in attribs) ? attribs["lon"] : 0;
    this.initZoom = ("zoom" in attribs) ? attribs["zoom"] : 1;
    this.mapElement = ("mapElement" in attribs) ? attribs["mapElement"] : null;
    this.minZoomLevel = ("minZoom" in attribs) ? parseInt(attribs["minZoom"]) : 0;
    this.maxZoomLevel = ("maxZoom" in attribs) ? parseInt(attribs["maxZoom"]) : 25;

    this.placemarks = [];
    this.showUserLocation = true;
    this.userLocationMarker = null;
    this.currentPlacemark = null;

    // user location
    this.locateMeButton = null; // CSS applies to an <a id="locateMe"> element
    this.locationWatchId = null;
    this.locationIsFirstPosition = true;

    if ("onShowCallout" in attribs) {
        this.onShowCallout = attribs["onShowCallout"];
    }
}

KGOMapLoader.prototype.loadMap = function() {}

// annotations
KGOMapLoader.prototype.showDefaultCallout = function() {
    var count = 0;
    var thePlacemark = null;
    for (var id in this.placemarks) {
        count++;
        thePlacemark = id;
        if (count > 1) {
            break;
        }
    }

    if (count == 1) {
        this.showCalloutForPlacemark(thePlacemark);
    }
}
KGOMapLoader.prototype.showCalloutForPlacemark = function(placemarkId) {}
KGOMapLoader.prototype.addPlacemark = function(id, placemark, attribs) {}
KGOMapLoader.prototype.clearMarkers = function() {}

// base map
KGOMapLoader.prototype.resizeMapOnContainerResize = function() {}
KGOMapLoader.prototype.setMapBounds = function(minLat, minLon, maxLat, maxLon) {} // top left bottom right

// user location
KGOMapLoader.prototype.locationUpdated = function(location) {}
KGOMapLoader.prototype.locationUpdateStopped = function(error) {}
KGOMapLoader.prototype.toggleLocationUpdates = function() {
    if (this.locationWatchId === null) {
        this.startLocationUpdates();
    } else {
        this.stopLocationUpdates();
    }
}
KGOMapLoader.prototype.startLocationUpdates = function() {
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
KGOMapLoader.prototype.stopLocationUpdates = function() {
    this.locateMeButton.style.backgroundPosition = "-160px 0px";
    if (this.locationWatchId != null) {
        navigator.geolocation.clearWatch(this.locationWatchId);
        this.locationWatchId = null;
        this.locationUpdateStopped(null);
    }
}

KGOMapLoader.prototype.generateInfoWindowContent = function(attribs) {
    var content = '';
    if ("title" in attribs && attribs["title"] !== null) {
        content += '<div class="map_name">' + attribs["title"] + '</div>';
    }
    if ("subtitle" in attribs && attribs["subtitle"] !== null) {
        content += '<div class="smallprint map_address">' + attribs["subtitle"] + '</div>';
    }
    content += '<div class="calloutTail"></div>';

    var div = document.createElement("div");
    div.className = "calloutMain";
    var a = null;

    if ("url" in attribs && attribs["url"] !== null) {
        a = document.createElement("a");
        a.href = attribs["url"];
    }

    if ("onclick" in attribs) {
        if (!a) {
            a = document.createElement("a");
        }
        a.onclick = attribs["onclick"];
    }

    if (a) {
        div.appendChild(a);
        a.innerHTML = content;
    } else {
        div.innerHTML = content;
    }
    return div;
}

function KGOGoogleMapLoader(attribs) {
    KGOMapLoader.call(this, attribs);

    this.provider = 'google';

    var that = this;
    var currentInfoWindow = null;
    var setCurrentInfoWindow = function(infoWindow) {
        if (currentInfoWindow !== null) {
            currentInfoWindow.close();
        }
        currentInfoWindow = infoWindow;
        var calloutListener = google.maps.event.addDomListener(map, 'click', function() {
            if (currentInfoWindow !== null) {
                currentInfoWindow.close();
                currentInfoWindow = null;
            }
            google.maps.event.removeListener(calloutListener);
        });
    }

    this.closeCurrentInfoWindow = function() {
        setCurrentInfoWindow(null);
    }

    this.showCalloutForPlacemark = function(placemark) {
        var marker = placemark;
        if (typeof placemark == 'number' || typeof placemark == 'string') {
            marker = this.placemarks[placemark];
        }
        if (currentInfoWindow != marker.infoWindow) {
            if (typeof marker.getPosition == 'function') {
                marker.infoWindow.open(map, marker);
            } else {
                marker.infoWindow.open(map);
            }
            setCurrentInfoWindow(marker.infoWindow);

            if (typeof that.onShowCallout == 'function') {
                that.onShowCallout(placemark);
            }
        }
    }
}

KGOGoogleMapLoader.prototype = new KGOMapLoader();

KGOGoogleMapLoader.prototype.createMapControls = function() {
    var controlDiv = document.createElement("div");
    controlDiv.id = "mapcontrols";

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
    var that = this;
    recenterButton.onclick = function() {
        map.setCenter(new google.maps.LatLng(that.initLat, that.initLon));
        map.setZoom(that.initZoom);
    }
    controlDiv.appendChild(recenterButton);

    this.locateMeButton = document.createElement('a');
    this.locateMeButton.id = "locateMe";
    var that = this;
    this.locateMeButton.onclick = function() {
        that.toggleLocationUpdates();
    }
    controlDiv.appendChild(this.locateMeButton);

    return controlDiv;
}

KGOGoogleMapLoader.prototype.loadMap = function() {
    var that = this;    
    var mapImage = document.getElementById(this.mapElement);
    var initCoord = new google.maps.LatLng(this.initLat, this.initLon);
    var options = {
        zoom: this.initZoom,
        center: initCoord,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        disableDefaultUI: true
    };
    map = new google.maps.Map(mapImage, options);
    var tilesLoadedListener = google.maps.event.addListener(map, 'tilesloaded', function() {
        map.setCenter(initCoord);
        google.maps.event.removeListener(tilesLoadedListener);
    });

    google.maps.event.addListener(map, 'zoom_changed', function() {
        currZoom = map.getZoom();
        if (currZoom < that.minZoomLevel) map.setZoom(that.minZoomLevel);
        if (currZoom > that.maxZoomLevel) map.setZoom(that.maxZoomLevel);
    });

    var controlDiv = this.createMapControls();
    map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(controlDiv);
}

KGOGoogleMapLoader.prototype.locationUpdated = function(location, firstLocation) {
    var position = new google.maps.LatLng(location.coords.latitude, location.coords.longitude);
    if (this.userLocationMarker === null) {
        // TODO make these more customizable
        var icon = new google.maps.MarkerImage(URL_BASE + 'common/images/map-location.png',
            null, // original size
            null, // origin (0, 0)
            new google.maps.Point(8, 8), // anchor
            new google.maps.Size(16, 16)); // scaled size

        this.userLocationMarker = new google.maps.Marker({
            'clickable' : false,
            'map'       : map,
            'position'  : position,
            'flat'      : true,
            'icon'      : icon
        });

    } else {
        if (this.userLocationMarker.getMap() === null) {
            this.userLocationMarker.setMap(map);
        }
        this.userLocationMarker.setPosition(position);
    }

    // only recenter on first location so we don't rubber band on scrolling
    // include current map center on map so zoom/pan is not as confusing
    if (firstLocation) {
        var bounds = new google.maps.LatLngBounds();
        bounds.extend(new google.maps.LatLng(this.initLat, this.initLon));
        bounds.extend(position);
        bounds.extend(map.getCenter());
        if (typeof MIN_LAT_SPAN != 'undefined') {
            bounds.extend(new google.maps.LatLng(position.lat() - MIN_LAT_SPAN / 2, position.lng()));
            bounds.extend(new google.maps.LatLng(position.lat() + MIN_LAT_SPAN / 2, position.lng()));
        }
        if (typeof MIN_LON_SPAN != 'undefined') {
            bounds.extend(new google.maps.LatLng(position.lat(), position.lng() - MIN_LON_SPAN / 2));
            bounds.extend(new google.maps.LatLng(position.lat(), position.lng() + MIN_LON_SPAN / 2));
        }
        map.fitBounds(bounds);
    }
}

KGOGoogleMapLoader.prototype.locationUpdateStopped = function() {
    if (this.userLocationMarker !== null) {
        this.userLocationMarker.setMap(null); // remove marker
    }
}

// annotations

// google maps specific function
KGOGoogleMapLoader.prototype.generateInfoWindow = function(attribs, needsSetPosition) {
    var content = this.generateInfoWindowContent(attribs);
    if (typeof InfoBox != 'undefined') {
        var options = {
            content: content,
            boxStyle: {
                background: "#fff",
                width: "180px",
                height: "65px",
                opacity: 0.92,
            },
            alignBottom: true,
            pixelOffset: new google.maps.Size(-90, -35),
            closeBoxMargin: "4px 2px 2px 2px",
            closeBoxURL: document.location.protocol +"//www.google.com/intl/en_us/mapfiles/close.gif",
            infoBoxClearance: new google.maps.Size(1, 1),
            pane: "floatPane",
            enableEventPropagation: false
        };
        if (needsSetPosition) {
            options['position'] = new google.maps.LatLng(attribs['lat'], attribs['lon']);
        }
        return new InfoBox(options);
    } else {
        var options = {
            'content' : content,
            'maxWidth' : 200
        }
        if (needsSetPosition) {
            options['position'] = new google.maps.LatLng(attribs['lat'], attribs['lon']);
        }
        return new google.maps.InfoWindow(options);
    }
}

KGOGoogleMapLoader.prototype.addPlacemark = function(id, placemark, attribs) {
    attribs["id"] = id;
    var isOverlay = typeof placemark.getPosition != 'function';
    placemark.infoWindow = this.generateInfoWindow(attribs, isOverlay);

    var that = this;
    google.maps.event.addListener(placemark, 'mousedown', function() {
        that.showCalloutForPlacemark(id);
    });

    this.placemarks[id] = placemark;
    this.currentPlacemark = placemark;
}

KGOGoogleMapLoader.prototype.clearMarkers = function() {
    for (var id in this.placemarks) {
        this.placemarks[id].setMap(null);
    }
    this.placemarks = [];
    this.closeCurrentInfoWindow();
}

// base map

KGOGoogleMapLoader.prototype.resizeMapOnContainerResize = function() {
    if (map) {
        // the recentering code causes placemarks to appear un-centered
        // sometimes on ios and android depending on when the address bar disappears
        var center = map.getCenter();
        google.maps.event.trigger(map, 'resize');
        map.setCenter(center);
    }
}

// top left bottom right
KGOGoogleMapLoader.prototype.setMapBounds = function(minLat, minLon, maxLat, maxLon) {
    var dx = 0;
    var dy = 0;

    var hRange = maxLon - minLon;
    var vRange = maxLat - minLat;

    var maxVRange = 180 / Math.pow(2, this.minZoomLevel);
    var maxHRange = maxVRange * 2;

    var minVRange = 180 / Math.pow(2, this.maxZoomLevel);
    var minHRange = minVRange * 2;

    if (hRange > maxHRange) {
        dx = (hRange - maxHRange) / 2;
    } else if (hRange < minHRange) {
        dx = (hRange - minHRange) / 2;
    }

    if (vRange > maxVRange) {
        dy = (vRange - maxVRange) / 2;
    } else if (vRange < minVRange) {
        dy = (vRange - minVRange) / 2;
    }

    var bounds = new google.maps.LatLngBounds();
    bounds.extend(new google.maps.LatLng(minLat + dy, minLon + dx));
    bounds.extend(new google.maps.LatLng(maxLat - dy, maxLon - dx));
    map.fitBounds(bounds);
}

////////////

function KGOEsriMapLoader(attribs) {
    KGOMapLoader.call(this, attribs);

    this.provider = 'esri';

    if ("wkid" in attribs) {
        this.projection = attribs['wkid'];
    } else {
        this.projection = 4326;
    }
    this.userLocationMarkerOnMap = false;

    var that = this;
    this.loadMap = function() {
        var baseLayer;

        that.spatialRef = new esri.SpatialReference({ wkid: that.projection });
        that.center = new esri.geometry.Point(that.initLon, that.initLat, that.spatialRef);

        map = new esri.Map(that.mapElement, {
            'logo' : false,
            'slider': false,
            'resizeDelay' : 300
        });

        if (attribs["baseURL"].length > 0) {
            baseLayer = new esri.layers.ArcGISTiledMapServiceLayer(attribs["baseURL"]);
            map.addLayer(baseLayer);
        }

        if ("layers" in attribs) {
            for (var i = 0; i < attribs["layers"].length; i++) {
                var mapLayer = new esri.layers.ArcGISDynamicMapServiceLayer(attribs["layers"][i], 1.0);
                if (typeof baseLayer == 'undefined') {
                    baseLayer = mapLayer;
                }
                map.addLayer(mapLayer);
            }
        }

        // add map controls
        var controlDiv = document.createElement('div');
        controlDiv.id = "mapcontrols"
        controlDiv.style.position = "absolute";
        controlDiv.style.right = "5px";
        controlDiv.style.bottom = "10px";

        var zoominButton = document.createElement('a');
        zoominButton.id = "zoomin";
        zoominButton.onclick = function() {
            var currentZoom = Math.round(that.getZoomWrapper());
            if (currentZoom >= that.maxZoomLevel) {
                return;
            }
            var zoomLevel = map.getLevel();
            var targetZoomLevel;
            if (zoomLevel == -1) {
                targetZoomLevel = 0.5;
            } else {
                targetZoomLevel = zoomLevel + 1;
            }
            var x = (map.extent.xmin + map.extent.xmax) / 2;
            var y = (map.extent.ymin + map.extent.ymax) / 2;

            map.centerAndZoom(new esri.geometry.Point(x, y, that.spatialRef), targetZoomLevel);
        }
        controlDiv.appendChild(zoominButton);

        var zoomoutButton = document.createElement('a');
        zoomoutButton.id = "zoomout";
        zoomoutButton.onclick = function() {
            var currentZoom = Math.round(that.getZoomWrapper());
            if (currentZoom <= that.minZoomLevel) {
                return;
            }
            var zoomLevel = map.getLevel();
            var targetZoomLevel;
            if (zoomLevel == -1) {
                targetZoomLevel = 2;
            } else {
                targetZoomLevel = zoomLevel - 1;
            }
            var x = (map.extent.xmin + map.extent.xmax) / 2;
            var y = (map.extent.ymin + map.extent.ymax) / 2;
            map.centerAndZoom(new esri.geometry.Point(x, y, that.spatialRef), targetZoomLevel);
        }
        controlDiv.appendChild(zoomoutButton);

        var recenterButton = document.createElement('a');
        recenterButton.id = "recenter";
        recenterButton.onclick = function() {
            that.centerAndZoomWrapper(that.center, that.initZoom);
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

        var mapElement = document.getElementById(that.mapElement);
        if (mapElement) {
            mapElement.appendChild(controlDiv);
        }

        map.infoWindow.setFixedAnchor(esri.dijit.InfoWindow.ANCHOR_UPPERRIGHT);

        // put all dojo.connect actions here

        dojo.connect(map, "onZoomEnd", function(evt) {
            var currentZoom = that.getZoomWrapper();
            if (currentZoom >= that.maxZoomLevel) {
                map.disableDoubleClickZoom();
            } else {
                map.enableDoubleClickZoom();
            }
        });

        dojo.connect(map, "onClick", function(evt) {
            if (map.infoWindow.isShowing) {
                if (evt.screenPoint.x < map.infoWindow.coords.x
                    || evt.screenPoint.x > map.infoWindow.coords.x + 250
                    || evt.screenPoint.y < map.infoWindow.coords.y - 100
                    || evt.screenPoint.y > map.infoWindow.coords.y
                ) {
                    map.infoWindow.hide();
                }
            }
        });

        dojo.connect(map, "onLoad", function() {
            plotFeatures();

            var unitsPerMeter = 1;
            var dpi = 96;

            if ("tileInfo" in baseLayer) {
                dpi = baseLayer.tileInfo.dpi;
            } else {
                dpi = baseLayer.dpi;
            }

            if (baseLayer.units == esri.Units.FEET) {
                unitsPerMeter = 3.28084;
            }

            that.maxScaleConstant = 559082264 * unitsPerMeter;
            that.unitsPerPixel = dpi / 2.54 * 100 * unitsPerMeter;
        });
    }
}

KGOEsriMapLoader.prototype = new KGOMapLoader();

// http://robblakemore.wordpress.com/2009/10/07/extending-the-arcgis-server-javascript-api-to-include-scale-information/
KGOEsriMapLoader.prototype.getScaleFromExtent = function(extent) {
    var scale = (extent.xmax - extent.xmin) * this.unitsPerMeter / map.width;
    return scale;
}

KGOEsriMapLoader.prototype.getZoomWrapper = function() {
    var scale = map.getScale();
    if (scale > 0) {
        currentZoom = Math.log(559082264 / scale) / Math.log(2);
    }
    return currentZoom;
}

KGOEsriMapLoader.prototype.centerAndZoomWrapper = function(center, zoomLevel) {
    if (map.getZoom() == -1) {
        // http://wiki.openstreetmap.org/wiki/MinScaleDenominator
        var scale = 559082264 / Math.pow(2, zoomLevel);
        map.setScale(scale);
        map.centerAt(center);
    } else {
        map.centerAndZoom(center, zoomLevel);
    }
}

// annotations
KGOEsriMapLoader.prototype.showCalloutForPlacemark = function(placemark) {
    var graphic = placemark;
    if (typeof placemark == 'number' || typeof placemark == 'string') {
        graphic = this.placemarks[placemark];
    }
    map.infoWindow.setContent(graphic.getContent());
    if (graphic.geometry.type == 'point') {
        map.infoWindow.show(graphic.geometry);
    } else {
        var point = graphic.geometry.getExtent().getCenter();
        map.infoWindow.show(point);
    }
}

KGOEsriMapLoader.prototype.addPlacemark = function(id, placemark, attribs) {
    attribs["id"] = id;
    infoTemplate = new esri.InfoTemplate();
    infoTemplate.setContent(
        this.generateInfoWindowContent(attribs));
    placemark.setInfoTemplate(infoTemplate);
    map.graphics.add(placemark);
    this.placemarks[id] = placemark;
    this.currentPlacemark = placemark;
}

KGOEsriMapLoader.prototype.clearMarkers = function() {
    map.graphics.clear();
    this.placemarks = [];
}

// base map

KGOEsriMapLoader.prototype.resizeMapOnContainerResize = function() {
    if (map && map.loaded) {
        var mapimage = document.getElementById(this.mapElement);
        if (mapimage && mapimage.clientHeight) {
            map.reposition();
            map.resize();
        }
    }
}

KGOEsriMapLoader.prototype.setMapBounds = function(minLat, minLon, maxLat, maxLon) {
    // acceptable meters per pixel
    var minMPP = this.maxScaleConstant / Math.pow(2, this.maxZoomLevel) / this.unitsPerMeter;
    var maxMPP = this.maxScaleConstant / Math.pow(2, this.minZoomLevel) / this.unitsPerMeter;

    var extentWidth = maxLon - minLon;
    var extentHeight = maxLat - minLat;

    var minWidth = map.width * minMPP;
    var minHeight = map.height * minMPP;

    var maxWidth = map.width * maxMPP;
    var maxHeight = map.height * maxMPP;

    var dx = 0;
    var dy = 0;

    if (extentWidth < minWidth) {
        dx = (extentWidth - minWidth) / 2;
    } else if (extentWidth > maxWidth) {
        dx = (extentWidth - maxWidth) / 2;
    }

    if (extentHeight < minHeight) {
        dy = (extentHeight - minHeight) / 2;
    } else if (extentHeight > maxHeight) {
        dy = (extentHeight - maxHeight) / 2;
    }

    var extent = new esri.geometry.Extent(minLon + dx, minLat + dy, maxLon - dx, maxLat - dy, this.spatialRef);
    extent = extent.expand(1.2);

    map.setExtent(extent);
}

// user location

KGOEsriMapLoader.prototype.locationUpdated = function(location, firstLocation) {
    var params = {
        'lat': location.coords.latitude,
        'lon': location.coords.longitude,
        'from': 4326,
        'to': this.projection
    };
    that = this
    makeAPICall('GET', 'map', 'projectPoint', params, function(response) {
        var point = new esri.geometry.Point(response.lon, response.lat, that.spatialRef);

        if (typeof that.userLocationMarker !== null) {
            // TODO make these more customizable
            var pointSymbol = new esri.symbol.PictureMarkerSymbol(URL_BASE + 'common/images/map-location.png', 16, 16);
            that.userLocationMarker = new esri.Graphic(point, pointSymbol);

        } else {
            that.userLocationMarker.setGeometry(point);
        }
        
        if (!that.userLocationMarkerOnMap) {
            map.graphics.add(that.userLocationMarker);
            that.userLocationMarkerOnMap = true;
        }

        if (firstLocation) {
            // only recenter on first location so we don't rubber band on scrolling
            var points = esri.geometry.Multipoint(that.spatialRef);
            points.addPoint(that.center);
            points.addPoint(point);
            
            var extent = points.getExtent();
            extent = extent.expand(1.5); // add padding around markers
            map.setExtent(extent);
        }
    });
}

KGOEsriMapLoader.prototype.locationUpdateStopped = function() {
    if (this.userLocationMarker !== null) {
        map.graphics.remove(this.userLocationMarker);
        this.userLocationMarkerOnMap = false;
    }
}

