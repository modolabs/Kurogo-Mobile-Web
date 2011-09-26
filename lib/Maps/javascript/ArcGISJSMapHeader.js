function resizeMapOnContainerResize() {
    if (map && map.loaded) {
        var mapTab = document.getElementById("mapTab");
        if (mapTab.clientHeight) {
            map.reposition();
            map.resize();
        }
    }
}

