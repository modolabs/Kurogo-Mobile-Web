var browseGroups = {};
var apiURL;

function sortGroupsByDistance() {
    if ('geolocation' in navigator) {
        navigator.geolocation.getCurrentPosition(locateSucceeded, locateFailed, {maximumAge:3600000, timeout:5000});
    } else {
        errorCallback();
    }
}

function locateSucceeded(location) {
    var navCategories = document.getElementById("categories").children;
    for (var i = 0; i < navCategories.length; i++) {
        var category = navCategories[i];
        var categoryId = category.getAttribute("class");
        browseGroups[categoryId] = category;
    }

    var params = {"lat": location.coords.latitude, "lon": location.coords.longitude};
    apiRequest(apiURL + "/sortGroupsByDistance", params, sortSucceeded, sortFailed);
}

function locateFailed() {
    // do nothing; leave content as is
}

function sortSucceeded(response) {
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

function sortFailed(code, message) {
    // do nothing; leave content as is
}

function search(form) {
    if (form.filter.value.length > 0 && typeof addMarker == 'function') {
        clearMarkers();
        apiRequest(apiURL + '/search', {'q': form.filter.value}, function(response) {
            hideSearchFormButtons();
            // TODO: make the "browse" button bring up results in a list

            var minLat = 90;
            var maxLat = -90;
            var minLon = 180;
            var maxLon = -180;
            for (var i = 0; i < response['results'].length; i++) {
                var markerData = response['results'][i];
                createMarker(markerData['title'], markerData['subtitle'],
                    markerData['lat'], markerData['lon'], markerData['url']);
                if (minLat > markerData['lat']) {
                    minLat = markerData['lat'];
                }
                if (minLon > markerData['lon']) {
                    minLon = markerData['lon'];
                }
                if (maxLat < markerData['lat']) {
                    maxLat = markerData['lat'];
                }
                if (maxLon < markerData['lon']) {
                    maxLon = markerData['lon'];
                }
            }
            setMapBounds(minLat, minLon, maxLat, maxLon);

        }, function(errorCode, errorMessage) {
            // TODO
        });
    }
}

function clearSearch(form) {
    form.filter.value = '';
}

function showSearchFormButtons() {
    addClass(document.getElementById("header"), "expanded");
    addClass(document.getElementById("searchbar"), "expanded");
    document.getElementById("searchFormButtons").style.display = "block";
    document.getElementById("searchBarButtons").style.display = "none";
    doUpdateContainerDimensions();
}

function hideSearchFormButtons() {
    removeClass(document.getElementById("header"), "expanded");
    removeClass(document.getElementById("searchbar"), "expanded");
    document.getElementById("searchFormButtons").style.display = "none";
    document.getElementById("searchBarButtons").style.display = "block";
    scrollTo(0, 1);
    doUpdateContainerDimensions();
}



