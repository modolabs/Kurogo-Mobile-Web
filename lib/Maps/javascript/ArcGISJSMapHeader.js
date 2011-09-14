function resizeMapOnContainerResize() {
    if (map && map.loaded) {
        map.reposition();
        map.resize();
    }
}

