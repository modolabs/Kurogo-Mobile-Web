function submitMapSearch(form) {
    if (form.filter.value.length > 0) {
        mapLoader.clearMarkers();
        params = {'q': form.filter.value};
        if ('projection' in mapLoader) {
            params['projection'] = mapLoader.projection;
        }
        makeAPICall('GET', 'map', 'search', params, function(response) {
            hideSearchFormButtons();
            // TODO: make the "browse" button bring up results in a list
            var minLat = 90;
            var maxLat = -90;
            var minLon = 180;
            var maxLon = -180;
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
        });
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



