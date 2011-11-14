var polypaths = [___MULTIPATHSTRING___];
var polygon = google.maps.Polygon({___PROPERTIES___}).setMap(map);

polygon.infoWindow = new google.maps.InfoWindow({
    'content' : '<div class="map_infowindow"><div class="map_name">'+___TITLE___+'</div><div class="smallprint map_address">'+___SUBTITLE___+'</div></div>',
    'maxWidth' : 120
});

google.maps.event.addListener(polygon, 'click', function() {
    showCalloutForPlacemark(polygon);
});
