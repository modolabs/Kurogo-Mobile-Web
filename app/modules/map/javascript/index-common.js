function submitMapSearch(form) {
    if (form.filter.value.length > 0 && typeof addMarker == 'function') {
        mapLoader.clearMarkers();
        makeAPICall('GET', 'map', 'search', {'q': form.filter.value}, function(response) {
            hideSearchFormButtons();
            // TODO: make the "browse" button bring up results in a list

            var minLat = 90;
            var maxLat = -90;
            var minLon = 180;
            var maxLon = -180;
            for (var i = 0; i < response['results'].length; i++) {
                var markerData = response['results'][i];
                mapLoader.createMarker(
                    markerData['title'], markerData['subtitle'],
                    markerData['lat'], markerData['lon'], markerData['url']);
                minLat = min(minLat, markerData['lat']);
                minLon = min(minLon, markerData['lon']);
                maxLat = max(maxLat, markerData['lat']);
                maxLon = max(maxLon, markerData['lon']);
            }
            mapLoader.setMapBounds(minLat, minLon, maxLat, maxLon);
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



