/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

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
                'GET', CONFIG_MODULE, 'sortGroupsByDistance',
                {"lat": location.coords.latitude, "lon": location.coords.longitude},
                function(response) {
                    var sortedGroups = [];
                    for (var i = 0; i < response.length; i++) {
                        var id = response[i]["id"];
                        if (id in browseGroups) {
                            if ("distance" in response[i]) {
                                var a = browseGroups[id].firstChild;
                                a.innerHTML = a.innerHTML + "<div class=\"smallprint\">" + response[i]["distance"] + "</div>";
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
        form.filter.blur();
        mapLoader.clearMarkers();
        hideSearchFormButtons();
        params = {'q': form.filter.value, 'v': 2, 'provider': mapLoader.provider};
        if (form.group.value) {
            params['group'] = form.group.value;
        }
        if ('projection' in mapLoader) {
            params['projection'] = mapLoader.projection;
        }
        makeAPICall('GET', CONFIG_MODULE, 'search', params, function(response) {
            if (typeof response.results == 'object') {
                if (response.results.length > 0) {
                    // TODO: make the "browse" button bring up results in a list
                    var minLat = 10000000;
                    var maxLat = -10000000;
                    var minLon = 10000000;
                    var maxLon = -10000000;
                    for (var i = 0; i < response.results.length; i++) {
                        var markerData = response.results[i];
                        var attribs = markerData.attribs;
                        mapLoader.addPlacemark(attribs.url, eval(markerData.placemark), attribs);

                        minLat = Math.min(minLat, attribs.lat);
                        minLon = Math.min(minLon, attribs.lon);
                        maxLat = Math.max(maxLat, attribs.lat);
                        maxLon = Math.max(maxLon, attribs.lon);
                    }
                    if (maxLon - minLon < MIN_LON_SPAN) {
                        maxLon += MIN_LON_SPAN / 2;
                        minLon -= MIN_LON_SPAN / 2;
                    }
                    if (maxLat - minLat < MIN_LAT_SPAN) {
                        maxLat += MIN_LAT_SPAN / 2;
                        minLat -= MIN_LAT_SPAN / 2;
                    }
                    mapLoader.setMapBounds(minLat, minLon, maxLat, maxLon);
                } else {
                    alert(NO_RESULTS_FOUND);
                }
            }
        });
        
        var addFilterToHref = function(link) {
            var reg = new RegExp('&?filter=.+(&|$)');
            if (link.href.match(reg)) {
                link.href = link.href.replace(reg, '&filter='+form.filter.value);
            } else {
                link.href = link.href + '&filter='+form.filter.value;
            }
            if (form.group.value) {
                link.href = link.href + '&group=' + form.group.value;
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

function clearSearch(e, form) {
    e.preventDefault();
    form.filter.value = '';
}

function showSearchFormButtons() {
    var toolbar = document.getElementById("toolbar");
    addClass(toolbar, "expanded");
    if (document.getElementById("campus-select")) {
        addClass(toolbar, "multi-campus");
    } else {
        addClass(toolbar, "single-campus");
    }
}

function hideSearchFormButtons() {
    var toolbar = document.getElementById("toolbar");
    removeClass(toolbar, "expanded");
    if (document.getElementById("campus-select")) {
        removeClass(toolbar, "multi-campus");
    } else {
        removeClass(toolbar, "single-campus");
    }
    scrollToTop();
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
    if (typeof doUpdateContainerDimensions == 'function') {
        clearUpdateMapDimensionsTimeouts();
        //var timeoutId = window.setTimeout(doUpdateContainerDimensions, 200);
        //updateMapDimensionsTimeoutIds.push(timeoutId);
        //var timeoutId = window.setTimeout(doUpdateContainerDimensions, 500);
        //updateMapDimensionsTimeoutIds.push(timeoutId);
        var timeoutId = window.setTimeout(doUpdateContainerDimensions, 1000);
        updateMapDimensionsTimeoutIds.push(timeoutId);
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

/*
if (typeof KGOMapLoader != 'undefined') {
    KGOMapLoader.prototype.generateInfoWindowContent = function(title, subtitle, url) {
        var content = '';
        if (title !== null) {
            content += '<div class="map_name">' + title + '</div>';
        }
        if (subtitle !== null) {
            content += '<div class="smallprint map_address">' + subtitle + '</div>';
        }
        if (typeof url != 'undefined' && url !== null && typeof COOKIE_PATH != 'undefined' && typeof BOOKMARK_LIFESPAN != 'undefined') {
            // we need to match the parameter order produced by php
            if (typeof this.regexes == 'undefined') {
                this.regexes = [
                    /[?&](category=[\w\.\,\+\-:%]+)/,
                    /[?&](featureindex=[\w\.\,\+\-:%]+)/,
                    /[?&](lat=[\w\.\,\+\-:%]+)/,
                    /[?&](lon=[\w\.\,\+\-:%]+)/,
                    /[?&](feed=[\w\.\,\+\-:%]+)/,
                    /[?&](title=[\w\.\,\+\-:%]+)/
                ];
            }

            var parts = [];
            for (var i = 0; i < this.regexes.length; i++) {
                var match = url.match(this.regexes[i]);
                if (match) {
                    parts.push(match[1]);
                }
            }
            query = parts.join('&').replace(/\+/g, ' ').replace(/%3A/g, ':');
            var items = getCookieArrayValue("mapbookmarks");
            var bookmarkState = "";
            for (var i = 0; i < items.length; i++) {
                if (items[i] == query) {
                    bookmarkState = "on";
                    break;
                }
            }

            content = '<div id="calloutWrapper"><div id="bookmarkWrapper" style="float:left;">' +
                        '<a onclick="toggleBookmark(\'mapbookmarks\', \'' + query + '\', BOOKMARK_LIFESPAN, COOKIE_PATH)">' +
                          '<div id="bookmark"' +
                              ' ontouchend="toggleClass(this, \'on\');"' +
                              ' class="' + bookmarkState + '"></div>' +
                        '</a>' + 
                      '</div>' +
                      '<div class="calloutMain" style="float:left;">' + content + '</div>' +
                      '<div class="calloutDisclosure" style="flost:left;">' +
                        '<a href="' + url + '"><img src="' + URL_BASE.replace(/\/$/,'') + '/modules/map/images/info.png" /></a>' +
                      '</div></div>';
        }
        return content;
    }
}
*/
