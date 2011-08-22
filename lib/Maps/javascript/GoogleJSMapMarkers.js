var marker___IDENTIFIER___ = new google.maps.Marker({
    position: new google.maps.LatLng(___LATITUDE___,___LONGITUDE___),
    map: map,
    ___OPTIONS___ title: ___TITLE___
});

marker___IDENTIFIER___.infoWindow = new google.maps.InfoWindow({
    'content' : '<div class="map_infowindow"><div class="map_name">'+___TITLE___+'</div><div class="smallprint map_address">'+___SUBTITLE___+'</div></div>',
    'maxWidth' : 120
});

google.maps.event.addListener(marker___IDENTIFIER___, 'click', function() {
    marker___IDENTIFIER___.infoWindow.open(map, marker___IDENTIFIER___);
    google.maps.event.addDomListener(map, 'click', function() {
        marker___IDENTIFIER___.infoWindow.close();
    });
});
