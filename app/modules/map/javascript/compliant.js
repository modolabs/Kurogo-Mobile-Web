function doUpdateContainerDimensions() {
    var mapimage = document.getElementById("mapimage");
    if (mapimage) {
        var topoffset = findPosY(mapimage);
        var mapTargetHeight = (getWindowHeight() - topoffset) + "px";
        if (mapimage.style.height != mapTargetHeight) {
            mapimage.style.height = mapTargetHeight;
            var container = document.getElementById("container");
            if (container) {
                container.style.minHeight = 0;
            }
        }
    }

    if (typeof mapLoader.resizeMapOnContainerResize == 'function') {
        mapLoader.resizeMapOnContainerResize();
    }
}

