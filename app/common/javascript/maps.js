///// various base maps

function KGOMapLoader(attribs) {
    attribs = attribs || {};

    this.initLat = ("lat" in attribs) ? attribs["lat"] : 0;
    this.initLon = ("lon" in attribs) ? attribs["lon"] : 0;
    this.initZoom = ("zoom" in attribs) ? attribs["zoom"] : 1;
    this.mapElement = ("mapElement" in attribs) ? attribs["mapElement"] : null;

    this.placemarks = [];
    this.showUserLocation = true;
    this.userLocationMarker = null;
    this.markerOnTop = true; // track type of last placemark since it affects choice of function for positioning

    // user location
    this.locateMeButton = null; // CSS applies to an <a id="locateMe"> element
    this.locationWatchId = null;
    this.locationIsFirstPosition = true;
}

KGOMapLoader.prototype.loadMap = function() {}

// annotations
KGOMapLoader.prototype.showDefaultCallout = function() {
    if (this.placemarks.length == 1) {
        if (this.markerOnTop) {
            this.showCalloutForMarker(this.placemarks[0]);
        } else {
            this.showCalloutForOverlay(this.placemarks[0]);
        }
    }
}
KGOMapLoader.prototype.showCalloutForMarker = function(marker) {}
KGOMapLoader.prototype.showCalloutForOverlay = function(overlay) {}
KGOMapLoader.prototype.addMarker = function(marker, attribs) {}
KGOMapLoader.prototype.addOverlay = function(overlay, attribs) {}
KGOMapLoader.prototype.clearMarkers = function() {}
KGOMapLoader.prototype.createMarker = function(title, subtitle, lat, lon, url) {}

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
KGOMapLoader.prototype.generateInfoWindowContent = function(title, subtitle, url) {
    var content = '';
    if (title !== null) {
        content += '<div class="map_name">' + title + '</div>';
    }
    if (subtitle !== null) {
        content += '<div class="smallprint map_address">' + subtitle + '</div>';
    }
    // TODO don't reference an asset in a module directory here
    if (typeof url != 'undefined' && url !== null) {
        content = '<div class="calloutMain" style="float:left;">' + content + '</td>' +
                  '<div class="calloutDisclosure" style="flost:left;">' +
                      '<a href="' + url + '"><img src="' + URL_BASE + '/modules/map/images/info.png" /></a>' +
                  '</div>';
    }
    return content;
}

function KGOGoogleMapLoader(attribs) {
    KGOMapLoader.call(this, attribs);

    var currentInfoWindow = null;
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

    this.showCalloutForMarker = function(marker) {
        if (currentInfoWindow != marker.infoWindow) {
            marker.infoWindow.open(map, marker);
            setCurrentInfoWindow(marker.infoWindow);
        }
    }

    this.showCalloutForOverlay = function(overlay) {
        if (currentInfoWindow == overlay.infoWindow) {
            overlay.infoWindow.open(map);
            setCurrentInfoWindow(overlay.infoWindow);
        }
    }
    
}

KGOGoogleMapLoader.prototype = new KGOMapLoader();

KGOGoogleMapLoader.prototype.loadMap = function() {
    var mapImage = document.getElementById(this.mapElement);
    var initCoord = new google.maps.LatLng(this.initLat, this.initLon);
    var options = {
        zoom: this.initZoom,
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
        map.setZoom(this.initZoom);
    }
    controlDiv.appendChild(recenterButton);

    if ("geolocation" in navigator && this.showUserLocation) {
        this.locateMeButton = document.createElement('a');
        this.locateMeButton.id = "locateMe";
        var that = this;
        this.locateMeButton.onclick = function() {
            that.toggleLocationUpdates();
        }
        controlDiv.appendChild(this.locateMeButton);
    }

    map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(controlDiv);
}

KGOGoogleMapLoader.prototype.locationUpdated = function(location, firstLocation) {
    var position = new google.maps.LatLng(location.coords.latitude, location.coords.longitude);
    if (this.userLocationMarker === null) {
        // TODO make these more customizable
        var icon = new google.maps.MarkerImage(URL_BASE + '/common/images/map-location.png',
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
        map.fitBounds(bounds);
    }
}

KGOGoogleMapLoader.prototype.locationUpdateStopped = function() {
    if (this.userLocationMarker !== null) {
        this.userLocationMarker.setMap(null); // remove marker
    }
}

// annotations

KGOGoogleMapLoader.prototype.addMarker = function(marker, attribs) {
    marker.infoWindow = new google.maps.InfoWindow({
        'content' : this.generateInfoWindowContent(attribs['title'], attribs['subtitle'], attribs['url']),
        'maxWidth' : 200
    });

    var that = this;
    google.maps.event.addListener(marker, 'mousedown', function() {
        that.showCalloutForMarker(marker);
    });

    this.placemarks.push(marker);
    this.markerOnTop = true;
}

KGOGoogleMapLoader.prototype.addOverlay = function(overlay, attribs) {
    overlay.infoWindow = new google.maps.InfoWindow({
        'content' : this.generateInfoWindowContent(attribs['title'], attribs['subtitle'], attribs['url']),
        'maxWidth' : 200,
        'position' : new google.maps.LatLng(attribs['lat'], attribs['lon'])
    });

    var that = this;
    google.maps.event.addListener(overlay, 'click', function() {
        that.showCalloutForOverlay(overlay);
    });

    this.placemarks.push(overlay);
    this.markerOnTop = false;
}

KGOGoogleMapLoader.prototype.clearMarkers = function() {
    for (var i = 0; i < this.placemarks.length; i++) {
        placemarks[i].setMap(null);
    }
    this.placemarks = [];
}

KGOGoogleMapLoader.prototype.createMarker = function(title, subtitle, lat, lon, url) {
    this.addMarker(new google.maps.Marker({
        position: new google.maps.LatLng(lat, lon),
        map: map,
        title: title
        }), {
            title: title,
            subtitle: subtitle,
            url: url
        });
}

// base map

KGOGoogleMapLoader.prototype.resizeMapOnContainerResize = function() {
    if (map) {
        var center = map.getCenter();
        google.maps.event.trigger(map, 'resize');
        map.setCenter(center);
    }
}

// top left bottom right
KGOGoogleMapLoader.prototype.setMapBounds = function(minLat, minLon, maxLat, maxLon) {
    var bounds = new google.maps.LatLngBounds();
    bounds.extend(new google.maps.LatLng(minLat, minLon));
    bounds.extend(new google.maps.LatLng(maxLat, maxLon));
    map.fitBounds(bounds);
}

////////////

function KGOEsriMapLoader(attribs) {
    KGOMapLoader.call(this, attribs);

    if ("wkid" in attribs) {
        this.projection = attribs['wkid'];
        this.spatialRef = new esri.SpatialReference({ wkid: this.projection });
    } else {
        this.spatialRef = new esri.SpatialReference({ wkid: 4326 });
    }
    this.userLocationMarkerOnMap = false;

    var that = this;
    this.loadMap = function() {
        that.center = new esri.geometry.Point(that.initLon, that.initLat, that.spatialRef);
        map = new esri.Map(that.mapElement, {
            'logo' : false,
            'slider': false,
            'resizeDelay' : 300
        });

        var basemap = new esri.layers.ArcGISTiledMapServiceLayer(attribs["baseURL"]);

        map.addLayer(basemap);

        // add map controls
        var controlDiv = document.createElement('div');
        controlDiv.id = "mapcontrols"
        controlDiv.style.position = "absolute";
        controlDiv.style.right = "10px";
        controlDiv.style.bottom = "10px";

        var zoominButton = document.createElement('a');
        zoominButton.id = "zoomin";
        zoominButton.onclick = function() {
            var zoomLevel = map.getLevel();
            var x = (map.extent.xmin + map.extent.xmax) / 2;
            var y = (map.extent.ymin + map.extent.ymax) / 2;
            map.centerAndZoom(new esri.geometry.Point(x, y, that.spatialRef), zoomLevel + 1);
        }
        controlDiv.appendChild(zoominButton);

        var zoomoutButton = document.createElement('a');
        zoomoutButton.id = "zoomout";
        zoomoutButton.onclick = function() {
            var zoomLevel = map.getLevel();
            var x = (map.extent.xmin + map.extent.xmax) / 2;
            var y = (map.extent.ymin + map.extent.ymax) / 2;
            map.centerAndZoom(new esri.geometry.Point(x, y, that.spatialRef), zoomLevel - 1);
        }
        controlDiv.appendChild(zoomoutButton);

        var recenterButton = document.createElement('a');
        recenterButton.id = "recenter";
        recenterButton.onclick = function() {
            map.centerAndZoom(that.center, that.initZoom);
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

        // this line doesn't seem to work if placed anywhere other than here
        dojo.connect(map, "onLoad", plotFeatures);
    }
}

KGOEsriMapLoader.prototype = new KGOMapLoader();

// annotations
KGOEsriMapLoader.prototype.showCalloutForMarker = function(marker) {
    map.infoWindow.setContent(marker.getContent());
    map.infoWindow.show(marker.geometry);
}

KGOEsriMapLoader.prototype.showCalloutForOverlay = function(overlay) {
    // TODO: construct centroid for polylgons/polylines
}

KGOEsriMapLoader.prototype.addMarker = function(marker, attribs) {
    infoTemplate = new esri.InfoTemplate();
    infoTemplate.setContent(
        this.generateInfoWindowContent(attribs["title"], attribs["subtitle"], attribs["url"]));
    marker.setInfoTemplate(infoTemplate);
    map.graphics.add(marker);
    this.placemarks.push(marker);
    this.markerOnTop = true;
}

KGOEsriMapLoader.prototype.addOverlay = function(overlay, attribs) {
    map.graphics.add(overlay);
    this.placemarks.push(overlay);
    infoTemplate = new esri.InfoTemplate();
    infoTemplate.setContent(
        this.generateInfoWindowContent(attribs["title"], attribs["subtitle"], attribs["url"]));
    overlay.setInfoTemplate(infoTemplate);
    this.markerOnTop = false;
}

KGOEsriMapLoader.prototype.clearMarkers = function() {
    map.graphics.clear();
}

KGOEsriMapLoader.prototype.createMarker = function(title, subtitle, lat, lon, url) {
    this.addMarker(
        new esri.Graphic(
            new esri.geometry.Point(lon, lat, this.spatialRef),
            new esri.symbol.SimpleMarkerSymbol( // add some styling because the default is a large empty black circle
                esri.symbol.SimpleMarkerSymbol.STYLE_CIRCLE,
                12,
                new esri.symbol.SimpleLineSymbol(),
                new dojo.Color([180, 0, 0]))),
        {
            title: title,
            subtitle: subtitle,
            url: url
        }
    );
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
    var extent = esri.geometry.Extent(minLon, minLat, maxLon, maxLat, this.spatialRef);
    extent = extent.expand(1.2);
    map.setExtent(extent, true);
}

// user location

KGOEsriMapLoader.prototype.locationUpdated = function(location, firstLocation) {
    var params = {
        'lat': location.coords.latitude,
        'lon': location.coords.longitude,
        'from': 4326,
        'to': this.projection
    };
    makeAPICall('GET', 'map', 'projectPoint', params, function(response) {
        var point = new esri.geometry.Point(response.lon, response.lat, this.spatialRef);

        if (typeof this.userLocationMarker !== null) {
            // TODO make these more customizable
            var pointSymbol = new esri.symbol.PictureMarkerSymbol(URL_BASE + '/common/images/map-location.png', 16, 16);
            this.userLocationMarker = new esri.Graphic(point, pointSymbol);

        } else {
            this.userLocationMarker.setGeometry(point);
        }
        
        if (!this.userLocationMarkerOnMap) {
            map.graphics.add(this.userLocationMarker);
            this.userLocationMarkerOnMap = true;
        }

        if (firstLocation) {
            // only recenter on first location so we don't rubber band on scrolling
            var points = esri.geometry.Multipoint(this.spatialRef);
            points.addPoint(this.center);
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

