function doUpdateContainerDimensions() {
    var mapimage = document.getElementById("mapimage");
    if (mapimage) {
        var topoffset = findPosY(mapimage);
        mapimage.style.height = (getWindowHeight() - topoffset) + "px";
        var container = document.getElementById("container");
        if (container) {
            container.style.minHeight = 0;
        }
    }

    if (typeof mapLoader.resizeMapOnContainerResize == 'function') {
        mapLoader.resizeMapOnContainerResize();
    }
}

