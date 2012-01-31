function doUpdateContainerDimensions() {
    var mapimage = document.getElementById("mapimage");
    if (mapimage) {
        var topoffset = findPosY(mapimage);
        mapimage.style.height = (getWindowHeight() - topoffset) + "px";
    }

    if (typeof mapLoader.resizeMapOnContainerResize == 'function') {
        mapLoader.resizeMapOnContainerResize();
    }
}

