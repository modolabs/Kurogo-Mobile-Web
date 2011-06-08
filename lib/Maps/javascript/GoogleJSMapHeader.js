var map;
var initLat = ___INITIAL_LATITUDE___;
var initLon = ___INITIAL_LONGITUDE___;

function loadMap() {
    var mapImage = document.getElementById("___MAPELEMENT___");
    mapImage.style.display = "inline-block";
//    mapImage.style.width = "___IMAGE_WIDTH___";
//    mapImage.style.height = "___IMAGE_HEIGHT___";

    var initCoord = new google.maps.LatLng(___CENTER_LATITUDE___, ___CENTER_LONGITUDE___);
    var options = {
        zoom: ___ZOOMLEVEL___,
        center: initCoord,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        mapTypeControl: false,
        panControl: false,
        zoomControl: false,
        scaleControl: false,
        streetViewControl: false
    };

    map = new google.maps.Map(mapImage, options);

    var zoomIn = document.getElementById("zoomin");
    google.maps.event.addDomListener(zoomIn, "click", function() {
        map.setZoom(map.getZoom() + 1);
    });
    
    var zoomOut = document.getElementById("zoomout");
    google.maps.event.addDomListener(zoomOut, "click", function() {
        map.setZoom(map.getZoom() - 1);
    });
    
    var recenter = document.getElementById("recenter");
    google.maps.event.addDomListener(recenter, "click", function() {
        map.setCenter(initCoord)
    });

    setMapHeights();
}
