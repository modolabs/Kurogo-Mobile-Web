
function doUpdateContainerDimensions() {
    var mapimage = document.getElementById("mapimage");
    var maptab = document.getElementById("mapTab");
    if (mapimage) { 
        var topoffset = findPosY(document.getElementById("tabbodies"));
        var bottomoffset = 0;
        // TODO lots of hard coding here, need better way to get these values
        var zoomControlsHeight = 56;
        var footernav = document.getElementById("footernav");
        if (footernav) {
            bottomoffset = 75;
        }
        var tabHeight = getWindowHeight() - topoffset - bottomoffset;
        var tabPadding = 8 * 2;
        maptab.style.height = (tabHeight - tabPadding) + "px";
        mapimage.style.height = (tabHeight - zoomControlsHeight - tabPadding) + "px";
    }

    if (typeof resizeMapOnContainerResize == 'function') {
        resizeMapOnContainerResize();
    }
}
/*
function setMapHeights() {
    // Set the height of the tabs container to fill the browser window height
    var mapimage = document.getElementById("mapimage");
    var maptab = document.getElementById("mapTab");
    if (maptab) {
        maptab.style.height="1000px";
    }
    setTimeout("scrollTo(0,1)",500);
    setTimeout("setHeight()",600);
}

function setHeight() {
    var mapimage = document.getElementById("mapimage");
    var maptab = document.getElementById("mapTab");
    if (mapimage) { 
        var topoffset = findPosY(document.getElementById("tabbodies"));
        var bottomoffset = 0;
        var zoomControlsHeight = 56;
        var footernav = document.getElementById("footernav");
        if (footernav) {
            bottomoffset = 75;
        }
        var tabHeight = getWindowHeight() - topoffset - bottomoffset;
        maptab.style.height = tabHeight + "px";
        var tabPadding = 8 * 2;
        mapimage.style.height = (tabHeight - zoomControlsHeight - tabPadding) + "px";
    }

    if (typeof resizeMapOnContainerResize == 'function') {
        resizeMapOnContainerResize();
    }
}
*/
function findPosY(obj) {
// Function for finding the y coordinate of the object passed as an argument.
// Returns the y coordinate as an integer, relative to the top left origin of the document.
    var intCurlTop = 0;
    if (obj.offsetParent) {
        while (obj.offsetParent) {
            intCurlTop += obj.offsetTop;
            obj = obj.offsetParent;
        }
    }
    return intCurlTop;
}