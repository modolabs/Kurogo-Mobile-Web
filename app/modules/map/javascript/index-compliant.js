if (!getCookie('map_lat')) {
    if ('geolocation' in navigator) {
        navigator.geolocation.getCurrentPosition(mapFoundLocation, mapNoLocation, {timeout:5000});
    } else {
        mapNoLocation();
    }
}

function mapFoundLocation(location) {
    var curLat = location.coords.latitude;
    var curLon = location.coords.longitude;
    setCookie('map_lat', curLat, 3600);
    setCookie('map_long', curLon, 3600);
    document.location.reload();
}
    
function mapNoLocation() {
    setCookie('map_lat', 'na', 3600);
    setCookie('map_long', 'na', 3600);
    document.location.reload();
}