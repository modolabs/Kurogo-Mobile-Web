var map;
var mapLoader;
var browseGroups = {};

function sortGroupsByDistance() {
    if ('geolocation' in navigator) {
        navigator.geolocation.getCurrentPosition(function(location) {
            var navCategories = document.getElementById("categories").children;
            for (var i = 0; i < navCategories.length; i++) {
                var category = navCategories[i];
                var categoryId = category.getAttribute("class");
                browseGroups[categoryId] = category;
            }

            makeAPICall(
                'GET', 'map', 'sortGroupsByDistance',
                {"lat": location.coords.latitude, "lon": location.coords.longitude},
                function(response) {
                    var sortedGroups = [];
                    for (var i = 0; i < response.length; i++) {
                        var id = response[i]["id"];
                        if (id in browseGroups) {
                            if ("distance" in response[i]) {
                                browseGroups[id].innerHTML = browseGroups[id].innerHTML + "<div class=\"smallprint\">" + response[i]["distance"] + "</div>";
                            }
                            sortedGroups.push(browseGroups[id]);
                        }
                    }
                    var navList = document.getElementById("categories");
                    if (navList.children.length == sortedGroups.length) {
                        while (navList.children.length > 0) {
                            navList.removeChild(navList.children[0]);
                        }
                        for (var i = 0; i < sortedGroups.length; i++) {
                            navList.appendChild(sortedGroups[i]);
                        }
                    }
                }
            );
        },
        function() {},
        {maximumAge:3600000, timeout:5000});
    }
}

////// expanding search bar

function submitMapSearch(form) {
    if (form.filter.value.length > 0) {
        mapLoader.clearMarkers();
        params = {'q': form.filter.value};
        if (form.group.value) {
            params['group'] = form.group.value;
        }
        if ('projection' in mapLoader) {
            params['projection'] = mapLoader.projection;
        }
        makeAPICall('GET', 'map', 'search', params, function(response) {
            hideSearchFormButtons();
            if (response.results.length > 0) {
                // TODO: make the "browse" button bring up results in a list
                var minLat = 10000000;
                var maxLat = -10000000;
                var minLon = 10000000;
                var maxLon = -10000000;
                for (var i = 0; i < response.results.length; i++) {
                    var markerData = response.results[i];
                    mapLoader.createMarker(
                        markerData.title, markerData.subtitle,
                        markerData.lat, markerData.lon, markerData.url);
                    minLat = Math.min(minLat, markerData.lat);
                    minLon = Math.min(minLon, markerData.lon);
                    maxLat = Math.max(maxLat, markerData.lat);
                    maxLon = Math.max(maxLon, markerData.lon);
                }
                mapLoader.setMapBounds(minLat, minLon, maxLat, maxLon);
            }
        });
        var addFilterToHref = function(link) {
            var reg = new RegExp('&?filter=.+(&|$)');
            if (link.href.match(reg)) {
                link.href = link.href.replace(reg, '&filter='+form.filter.value);
            } else {
                link.href = link.href + '&filter='+form.filter.value;
            }
        }
        var mapButton = document.getElementById("mapLink");
        if (mapButton) {
            addFilterToHref(mapButton);
        }
        var browseButton = document.getElementById("browseLink");
        if (browseButton) {
            addFilterToHref(browseButton);
        }
    }
}

function clearSearch(form) {
    form.filter.value = '';
}

function showSearchFormButtons() {
    var header = document.getElementById("header");
    addClass(header, "expanded");
    if (document.getElementById("campus-select")) {
        addClass(header, "multi-campus");
    } else {
        addClass(header, "single-campus");
    }
}

function hideSearchFormButtons() {
    var header = document.getElementById("header");
    removeClass(header, "expanded");
    if (document.getElementById("campus-select")) {
        removeClass(header, "multi-campus");
    } else {
        removeClass(header, "single-campus");
    }
}

///// window size

// ie7 doesn't understand window.innerWidth and window.innerHeight
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

///// various base maps

function kgoMapLoader(attribs) {
    this.initLat = ("lat" in attribs) ? attribs["lat"] : 0;
    this.initLon = ("lon" in attribs) ? attribs["lon"] : 0;
    this.initZoom = ("zoom" in attribs) ? attribs["zoom"] : 1;
    this.mapElement = ("mapElement" in attribs) ? attribs["mapElement"] : null;

    this.placemarks = [];
    this.showUserLocation = true;
    this.userLocationMarker = null;
    this.markerOnTop = true; // track type of last placemark since it affects choice of function for positioning

    this.loadMap = function() {}

    // annotations
    this.showDefaultCallout = function() {
        if (this.placemarks.length == 1) {
            if (this.markerOnTop) {
                this.showCalloutForMarker(this.placemarks[0]);
            } else {
                this.showCalloutForOverlay(this.placemarks[0]);
            }
        }
    }
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
    this.generateInfoWindowContent = function(title, subtitle, url) {
        var content = '';
        if (title !== null) {
            content += '<div class="map_name">' + title + '</div>';
        }
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
}

function kgoGoogleMapLoader(attribs) {
    var that = new kgoMapLoader(attribs);
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
        if (that.userLocationMarker === null) {
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
            bounds.extend(new google.maps.LatLng(that.initLat, that.initLon));
            bounds.extend(position);
            bounds.extend(map.getCenter());
            map.fitBounds(bounds);
        }
    }

    that.locationUpdateStopped = function() {
        if (that.userLocationMarker === null) {
            that.userLocationMarker.setMap(null); // remove marker
        }
    }

    // annotations

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
            'content' : that.generateInfoWindowContent(attribs['title'], attribs['subtitle'], attribs['url']),
            'maxWidth' : 200
        });

        google.maps.event.addListener(marker, 'click', function() {
            that.showCalloutForMarker(marker);
        });

        that.placemarks.push(marker);
        that.markerOnTop = true;
    }

    that.addOverlay = function(overlay, attribs) {
        overlay.infoWindow = new google.maps.InfoWindow({
            'content' : that.generateInfoWindowContent(attribs['title'], attribs['subtitle'], attribs['url']),
            'maxWidth' : 200,
            'position' : new google.maps.LatLng(attribs['lat'], attribs['lon'])
        });

        google.maps.event.addListener(overlay, 'click', function() {
            that.showCalloutForOverlay(overlay);
        });

        that.placemarks.push(overlay);
        that.markerOnTop = false;
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
            }), {
                title: title,
                subtitle: subtitle,
                url: url
            });
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

    if ("wkid" in attribs) {
        that.projection = attribs['wkid'];
        that.spatialRef = new esri.SpatialReference({ wkid: that.projection });
    } else {
        that.spatialRef = new esri.SpatialReference({ wkid: 4326 });
    }
    that.userLocationMarkerOnMap = false;

    that.loadMap = function() {
        that.center = new esri.geometry.Point(that.initLon, that.initLat, that.spatialRef);

        map = new esri.Map(that.mapElement, {
            'logo' : false,
            'resizeDelay' : 300
        });

        var basemapURL = attribs["baseURL"];
        var basemap = new esri.layers.ArcGISTiledMapServiceLayer(basemapURL);

        map.addLayer(basemap);

        // this line doesn't seem to work if placed anywhere other than here
        dojo.connect(map, "onLoad", plotFeatures);
    }

    // annotations
    that.showCalloutForMarker = function(marker) {
        //marker.infoTemplate.show(marker.geometry);

        map.infoWindow.setContent(marker.getContent());
        map.infoWindow.show(marker.geometry);
    }

    that.showCalloutForOverlay = function(overlay) {
        // TODO: construct centroid for polylgons/polylines
    }

    that.addMarker = function(marker, attribs) {
        infoTemplate = new esri.InfoTemplate();
        //infoTemplate.setTitle(attribs["title"]);
        infoTemplate.setContent(
            that.generateInfoWindowContent(attribs["title"], attribs["subtitle"], attribs["url"]));
        marker.setInfoTemplate(infoTemplate);
        map.graphics.add(marker);
        that.placemarks.push(marker);
        that.markerOnTop = true;
    }

    that.addOverlay = function(overlay, attribs) {
        map.graphics.add(overlay);
        that.placemarks.push(overlay);
        infoTemplate = new esri.InfoTemplate();
        infoTemplate.setTitle(attribs["title"]);
        infoTemplate.setContent(attribs["subtitle"]);
        overlay.setInfoTemplate(infoTemplate);
        that.markerOnTop = false;
    }

    that.clearMarkers = function() {
        map.graphics.clear();
    }

    that.createMarker = function(title, subtitle, lat, lon, url) {
        that.addMarker(
            new esri.Graphic(
                new esri.geometry.Point(lon, lat, that.spatialRef),
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
        var extent = esri.geometry.Extent(minLon, minLat, maxLon, maxLat, that.spatialRef);
        extent = extent.expand(1.2);
        map.setExtent(extent, true);
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
                points.addPoint(that.center);
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

