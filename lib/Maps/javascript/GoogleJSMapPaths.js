coordinates = [___COORDINATES___];
var path = new google.maps.Polyline({___PROPERTIES___});
path.setMap(map);

path.infoWindow = new google.maps.InfoWindow({
    'content' : '<div class="map_infowindow"><div class="map_name">'+___TITLE___+'</div><div class="smallprint map_address">'+___SUBTITLE___+'</div></div>',
    'maxWidth' : 120
});

google.maps.event.addListener(path, 'click', function() {
    showCalloutForPlacemark(path);
});
