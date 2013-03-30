/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

// Initialize the ellipsis event handlers
function setupListing() {
    var anEllipsizer = new ellipsizer();
    
    // cap at 100 divs to avoid overloading phone
    for (var i = 0; i < 100; i++) {
        var elem = document.getElementById('ellipsis_'+i);
        if (!elem) { break; }
        anEllipsizer.addElement(elem);
    }
}

function initMap() {
    var map = null;
    
    var handleMapResize = function () {
        if (map) {
            google.maps.event.trigger(map, 'resize');
        }
        if (KUROGO_PAGETYPE == "compliant" && KUROGO_PLATFORM == "iphone") {
            setCSSValue(container, 'height', 'auto');
            setCSSValue(container, 'min-height', '550px');
            
            scrollTo(0, 0);
            setTimeout(function () {
                var container = document.getElementById('container');
                if (container) {
                    setCSSValue(container, 'height', window.innerHeight + 'px');
                    setCSSValue(container, 'min-height', '0');
                }
                if (map) {
                    google.maps.event.trigger(map, 'resize');
                }
           }, 100);
        }
    }
    addOnOrientationChangeCallback(handleMapResize);
    handleMapResize();
    
    var element = document.getElementById('map');
    if (!element) {
        showAlert("Error", "No map element in DOM");
        return;
    }
    
    map = new google.maps.Map(element, {
        'zoom' : 17,
        'center' : new google.maps.LatLng(-28.643387, 153.612224),
        'disableDefaultUI' : true,
        'panControl' : false,
        'scaleControl' : false,
        'streetViewControl' : false,
        'mapTypeControl' : true,
        'mapTypeControlOptions' : {
            'style' : google.maps.ZoomControlStyle.DEFAULT,
            'position' : google.maps.ControlPosition.TOP_LEFT
        },
        'zoomControl' : true,
        'zoomControlOptions' : {
            'style' : google.maps.ZoomControlStyle.DEFAULT,
            'position' : google.maps.ControlPosition.RIGHT_BOTTOM
        },
        'mapTypeId' : google.maps.MapTypeId.ROADMAP
    });
    
    // Add the location tracking control
    WatchPositionControl.prototype.track = false;
    WatchPositionControl.prototype.element = null;

    function WatchPositionControl(map) {
        this.locationWatchId = null;
        this.firstLocationUpdate = false;
        this.userLocationMarker = null;

        this.element = document.createElement('div');
        addClass(this.element, 'watch-position-button');
        this.element.innerHTML = '<div>Watch Position</div>';
        
        var that = this;
        google.maps.event.addDomListener(this.element, 'click', function() {
            if (!that.track) {
                that.firstLocationUpdate = true;
                
                that.locationWatchId = navigator.geolocation.watchPosition(
                    function (location) {
                        var position = new google.maps.LatLng(location.coords.latitude, location.coords.longitude);
                
                        if (that.userLocationMarker === null) {
                            that.userLocationMarker = new google.maps.Marker({
                                'clickable' : false,
                                'map'       : map, 
                                'position'  : position,
                                'flat'      : true,
                                'icon'      : new google.maps.MarkerImage(
                                    locationMarkerURL,
                                    null, // original size
                                    null, // origin (0, 0)
                                    new google.maps.Point(8, 8),  // anchor
                                    new google.maps.Size(16, 16)) // scaled size

                            });
                        } else {
                            if (that.userLocationMarker.getMap() === null) {
                                that.userLocationMarker.setMap(map);
                            }
                            that.userLocationMarker.setPosition(position);
                        }
                    
                        // only recenter on first location so we don't rubber band on scrolling
                        // include current map center on map so zoom/pan is not as confusing
                        if (that.firstLocationUpdate) {
                            map.panTo(position);
                            map.setZoom(18);
                            that.firstLocationUpdate = false;
                        }
                    },
                    function (error) {
                        showAlert("Error", "navigator.geolocation.watchPosition failed with error "+error);
                    },
                    { enableHighAccuracy: true }
                );
                that.track = true;
                addClass(that.element, 'watching');
                
            } else {
                // remove marker from map and stop watching location
                if (that.userLocationMarker !== null) {
                    that.userLocationMarker.setMap(null);
                }
                if (that.locationWatchId != null) {
                    navigator.geolocation.clearWatch(that.locationWatchId);
                    that.locationWatchId = null;
                }
                that.track = false;
                removeClass(that.element, 'watching');
            }
        });
    }
    
    if ("geolocation" in navigator) {
        var watchPositionControl = new WatchPositionControl(map);
        map.controls[google.maps.ControlPosition.TOP_RIGHT].push(watchPositionControl.element);
    } else {
        showAlert("Error", "navigator.geolocation undefined");
    }
}

function getLocation(highAccuracy) {
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(
            function (location) {
                showAlert("Success", (highAccuracy ? "High accuracy l" : "L")+"ocation is "+
                    location.coords.latitude+", "+location.coords.longitude);
            },
            function (error) {
                showAlert("Error", "navigator.geolocation.getCurrentPosition failed with error "+error);
            },
            { enableHighAccuracy: true }
        );
    } else {
        showAlert("Error", "navigator.geolocation undefined");
    }
}

function initGeolocation(map) {
}

function showAlert(title, message) {
    if (typeof kgoBridge != "undefined" && "alertDialog" in kgoBridge) {
        kgoBridge.alertDialog(title, message, "OK");
    } else {
        alert(title+": "+message);
    }
}
