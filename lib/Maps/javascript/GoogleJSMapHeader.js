//mapLoader = kgoGoogleMapLoader({});

/*
var currentInfoWindow = null;
var placemarks = [];
var markerOnTop = true; // track type of last placemark since it affects choice of function for positioning

var initLat = "___CENTER_LATITUDE___";
var initLon = "___CENTER_LONGITUDE___";

// the following lines variables are also used toward the beginning of the ArcGISJSMap javascript files.
// TODO: find a way to put these in a common file
var initZoom = ___ZOOMLEVEL___;
var fullURLPrefix = "___FULL_URL_PREFIX___";
var userLocationMarker;

function loadMap() {
    var mapImage = document.getElementById("___MAPELEMENT___");

    var initCoord = new google.maps.LatLng(initLat, initLon);
    var options = {
        zoom: initZoom,
        center: initCoord,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        disableDefaultUI: true
    };

    map = new google.maps.Map(mapImage, options);

    mapControls.locationUpdated = function(location, firstLocation) {
        var position = new google.maps.LatLng(location.coords.latitude, location.coords.longitude);
        if (typeof userLocationMarker == 'undefined') {
            // TODO make these more customizable
            var icon = new google.maps.MarkerImage(fullURLPrefix + 'modules/map/images/map-location@2x.png',
                null, // original size
                null, // origin (0, 0)
                new google.maps.Point(8, 8), // anchor
                new google.maps.Size(16, 16)); // scaled size

            userLocationMarker = new google.maps.Marker({
                'clickable' : false,
                'map'       : map,
                'position'  : position,
                'flat'      : true,
                'icon'      : icon
            });

        } else {
            if (userLocationMarker.getMap() === null) {
                userLocationMarker.setMap(map);
            }
            userLocationMarker.setPosition(position);
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
    };
    mapControls.locationUpdateStopped = function() {
        if (typeof userLocationMarker != 'undefined') {
            userLocationMarker.setMap(null); // remove marker
        }
    };

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
        map.setZoom(initZoom);
    }
    controlDiv.appendChild(recenterButton);

    if ("geolocation" in navigator && typeof(showUserLocation) != 'undefined') {
        mapControls.locateMeButton = document.createElement('a');
        mapControls.locateMeButton.id = "locateMe";
        mapControls.locateMeButton.onclick = function() {
            mapControls.toggleLocationUpdates();
        }
        controlDiv.appendChild(mapControls.locateMeButton);
    }

    map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(controlDiv);
}

function setCurrentInfoWindow(infoWindow) {
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

function generateInfoWindowContent(title, subtitle, url) {
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
                      '<a href="' + url + '"><img src="/modules/map/images/info.png" /></a>' +
                    '</td>' + 
                  '</tr></table>';
    }
    return content;
}

function showCalloutForMarker(marker) {
    marker.infoWindow.open(map, marker);
    setCurrentInfoWindow(marker.infoWindow);
}

function showCalloutForOverlay(overlay) {
    overlay.infoWindow.open(map);
    setCurrentInfoWindow(overlay.infoWindow);
}

function addMarker(marker, title, subtitle, url) {
    marker.infoWindow = new google.maps.InfoWindow({
        'content' : generateInfoWindowContent(title, subtitle, url),
        'maxWidth' : 200
    });

    google.maps.event.addListener(marker, 'click', function() {
        showCalloutForMarker(marker);
    });

    placemarks.push(marker);
    markerOnTop = true;
}

function addOverlay(overlay, title, subtitle, url, lat, lon) {
    overlay.infoWindow = new google.maps.InfoWindow({
        'content' : generateInfoWindowContent(title, subtitle, url),
        'maxWidth' : 200,
        'position' : new google.maps.LatLng(lat, lon)
    });

    google.maps.event.addListener(overlay, 'click', function() {
        showCalloutForOverlay(overlay);
    });

    placemarks.push(overlay);
    markerOnTop = false;
}

// functions that need to be implemented for all base maps

function clearMarkers() {
    for (var i = 0; i < placemarks.length; i++) {
        placemarks[i].setMap(null);
    }
    placemarks = [];
}

function createMarker(title, subtitle, lat, lon, url) {
    addMarker(new google.maps.Marker({
        position: new google.maps.LatLng(lat, lon),
        map: map,
        title: title
        }), title, subtitle, url);
}

function resizeMapOnContainerResize() {
    if (map) {
        var center = map.getCenter();
        google.maps.event.trigger(map, 'resize');
        map.setCenter(center);
    }
}

function setMapBounds(minLat, minLon, maxLat, maxLon) {
    var bounds = new google.maps.LatLngBounds();
    bounds.extend(new google.maps.LatLng(minLat, minLon));
    bounds.extend(new google.maps.LatLng(maxLat, maxLon));
    map.fitBounds(bounds);
}
*/
