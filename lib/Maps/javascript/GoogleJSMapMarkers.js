var placemark___IDENTIFIER___ = new google.maps.Marker({
    position: new google.maps.LatLng(___LATITUDE___,___LONGITUDE___),
    map: map,
    ___OPTIONS___ title: ___TITLE___
});

placemark___IDENTIFIER___.infoWindow = new google.maps.InfoWindow({
    'content' : '<div class="map_infowindow"><div class="map_name">'+___TITLE___+'</div><div class="smallprint map_address">'+___SUBTITLE___+'</div></div>',
    'maxWidth' : 120
});

google.maps.event.addListener(placemark___IDENTIFIER___, 'click', function() {
    showCalloutForPlacemark(placemark___IDENTIFIER___);
});
