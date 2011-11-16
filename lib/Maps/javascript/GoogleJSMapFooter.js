loadMap();

var placemarks = [];

function addPlacemark(aPlacemark, title, subtitle, url) {
    aPlacemark.infoWindow = new google.maps.InfoWindow({
        'content' : '<table><tr><td class="calloutMain">' +
                        '<div class="map_name">' + title + '</div>' + 
                        '<div class="smallprint map_address">' + subtitle + '</div>' +
                    '</td>' +
                    '<td class="calloutDisclosure"><a href="' + url + '"><img src="/modules/map/images/info.png" /></a></td></tr></table>',
        'maxWidth' : 200
    });

    google.maps.event.addListener(aPlacemark, 'click', function() {
        showCalloutForPlacemark(aPlacemark);
    });

    placemarks.push(aPlacemark);
}

___POLYGON_SCRIPT___
___PATH_SCRIPT___
___MARKER_SCRIPT___

if (placemarks.length == 1) {
    showCalloutForPlacemark(placemarks[0]);
}
