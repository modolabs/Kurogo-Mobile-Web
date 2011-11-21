
function doUpdateContainerDimensions() {
    var container = document.getElementById("container");
    if (container) {
        var newWidth = getWindowWidth() + "px";
        var newHeight = getWindowHeight() + "px";

        // check to see if the container height and width actually changed
        if (container.style && container.style.width && container.style.width == newWidth
                            && container.style.height && container.style.height == newHeight) {

           // nothing changed so exit early
           return;
        }

        container.style.width = newWidth;
        container.style.height = newHeight;

        if (typeof resizeMapOnContainerResize == 'function') {
            resizeMapOnContainerResize();
        }

    }
}
