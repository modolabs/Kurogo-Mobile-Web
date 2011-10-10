function resizeMapOnContainerResize() {
    if (map && map.loaded) {
        var mapimage = document.getElementById("___MAPELEMENT___");
        if (mapimage && mapimage.clientHeight) {
            map.reposition();
            map.resize();
        }
    }
}

