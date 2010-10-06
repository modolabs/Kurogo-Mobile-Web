// Set initial values for drawing the map image
var mapW, mapH;	// integers: width and height of map image
var zoom = 0; // integer: zoom level -- should always default to 0
var mapBoxW = initMapBoxW;	// integer: western bound of map image (per IMS map API) 
var mapBoxN = initMapBoxN;	// integer: northern bound of map image (per IMS map API)
var mapBoxS = initMapBoxS;	// integer: southern bound of map image (per IMS map API)
var mapBoxE = initMapBoxE;	// integer: eastern bound of map image (per IMS map API)
var hasMoved = false;	// boolean: has the map been scrolled or zoomed?
var maxZoom = 2;	// integer: max zoom-in level
var minZoom = -8;	// integer: max zoom-out level

var loadedImages = {};

function loadPhoto(imageURL,imageID) {
  if (!loadedImages[imageID]) {
    // Loads an image from the given URL into the image with the specified ID
    var img = document.getElementById(imageID);
    if(img) {
      if(imageURL != "") {
        img.src = imageURL;
      } else {
        img.src = "/common/images/blank.png";
      }
    }
    loadedImages[imageID] = true;
  }
}

function loadImage(imageURL,imageID) {
// Loads an image from the given URL into the image with the specified ID
	var objMap = document.getElementById(imageID);
	show("loadingimage");
	if(objMap) {
		if(imageURL!="") {
			objMap.src = imageURL;
		} else {
			objMap.src = "/common/images/blank.png";
		}
	}
	// Since we're loading a new map image, update the link(s) to switch between fullscreen and smallscreen modes
	var objFullscreen = document.getElementById("fullscreen");
	if(objFullscreen) {
		objFullscreen.href = getMapURL(fullscreenBaseURL, true);
	}
	var objSmallscreen = document.getElementById("smallscreen");
	if(objSmallscreen) {
	    var tempW = mapBoxW;
	    var tempE = mapBoxE;
	    var tempS = mapBoxS;
	    var tempN = mapBoxN;

	    mapBoxW = initMapBoxW;
	    mapBoxE = initMapBoxE;
	    mapBoxN = initMapBoxN;
	    mapBoxS = initMapBoxS;

	    objSmallscreen.href = getMapURL(detailBaseURL, true);

	    mapBoxW = tempW;
	    mapBoxE = tempE;
	    mapBoxN = tempN;
	    mapBoxS = tempS;
	}
}


function getMapURL(strBaseURL, includeSelect) {
    // Returns a full URL for a map page or map image, using strBaseURL as the base
    var layerCount = mapLayers.split(",").length;
    if (layerCount > 0) {
	var mapStyles = "default";
	for (i = 1; i < layerCount; i++) {
	    mapStyles = mapStyles + ",default";
	}
    }

    var newURL = strBaseURL + "&width=" + mapW + "&height=" + mapH + "&bbox=" + mapBoxW + "," + mapBoxS + "," + mapBoxE + "," + mapBoxN + "&layers=" + mapLayers + "&styles=" + mapStyles + mapOptions;

    // Add parameters for the original bounding box, so Image can be recentered
    if(includeSelect) {
        newURL += "&bboxSelect=" + selectMapBoxW + "," + selectMapBoxS + "," + selectMapBoxE + "," + selectMapBoxN;
    }
    return(newURL);
}


function scroll(dir) {
// Scrolls the map image in the cardinal direction given by dir; amount of scrolling is scaled to zoom level and the pixel dimensions of the map image
	var objMap = document.getElementById("mapimage");
	if(objMap) {
		var mapDX, mapDY;
		if(zoom<maxZoom) {
			mapDX = mapW*(1-(zoom/2));
			mapDY = mapH*(1-(zoom/2));
		} else {
			mapDX = mapW/2.3;
			mapDY = mapH/2.3;
		}
		switch(dir) {
			case "n":
				mapBoxN = mapBoxN + mapDY;
				mapBoxS = mapBoxS + mapDY;
				loadImage(getMapURL(mapBaseURL),'mapimage');
				break;
			case "s":
				mapBoxN = mapBoxN - mapDY;
				mapBoxS = mapBoxS - mapDY;
				loadImage(getMapURL(mapBaseURL),'mapimage');
				break;
			case "e":
				mapBoxE = mapBoxE + mapDX;
				mapBoxW = mapBoxW + mapDX;
				loadImage(getMapURL(mapBaseURL),'mapimage');
				break;
			case "w":
				mapBoxE = mapBoxE - mapDX;
				mapBoxW = mapBoxW - mapDX;
				loadImage(getMapURL(mapBaseURL),'mapimage');
				break;
			case "ne":
				mapBoxN = mapBoxN + mapDY;
				mapBoxS = mapBoxS + mapDY;
				mapBoxE = mapBoxE + mapDX;
				mapBoxW = mapBoxW + mapDX;
				loadImage(getMapURL(mapBaseURL),'mapimage');
				break;
			case "nw":
				mapBoxN = mapBoxN + mapDY;
				mapBoxS = mapBoxS + mapDY;
				mapBoxE = mapBoxE - mapDX;
				mapBoxW = mapBoxW - mapDX;
				loadImage(getMapURL(mapBaseURL),'mapimage');
				break;
			case "se":
				mapBoxN = mapBoxN - mapDY;
				mapBoxS = mapBoxS - mapDY;
				mapBoxE = mapBoxE + mapDX;
				mapBoxW = mapBoxW + mapDX;
				loadImage(getMapURL(mapBaseURL),'mapimage');
				break;
			case "sw":
				mapBoxN = mapBoxN - mapDY;
				mapBoxS = mapBoxS - mapDY;
				mapBoxE = mapBoxE - mapDX;
				mapBoxW = mapBoxW - mapDX;
				loadImage(getMapURL(mapBaseURL),'mapimage');
				break;
		}
		checkIfMoved();		
	}
}


function recenter() {
// Reset the map image to its initially selected coordinates -- only if it's not already there
	if(hasMoved) {
		hasMoved = false;
		mapBoxW = selectMapBoxW;
		mapBoxN = selectMapBoxN;
		mapBoxS = selectMapBoxS;
		mapBoxE = selectMapBoxE;
		zoom = 0;	// reset zoom level
		loadImage(getMapURL(mapBaseURL),'mapimage');
		enable('zoomin');
		enable('zoomout');
		disable('recenter');
	} 
}


function zoomout() {
// Zoom the map out by an amount scaled to the pixel dimensions of the map image
	enable('zoomin');
	if(zoom > minZoom) {
		mapBoxN = mapBoxN + (mapH/2);
		mapBoxS = mapBoxS - (mapH/2);
		mapBoxE = mapBoxE + (mapW/2);
		mapBoxW = mapBoxW - (mapW/2);
		loadImage(getMapURL(mapBaseURL),'mapimage');
		zoom--;
	}
	if(zoom <= minZoom) {	// If we've reached the min zoom level
		disable('zoomout');
	}
	checkIfMoved();		
}


function zoomin() {
// Zoom the map in by an amount scaled to the pixel dimensions of the map image
	enable('zoomout');
	if(zoom < maxZoom) {
		mapBoxN = mapBoxN - (mapH/2);
		mapBoxS = mapBoxS + (mapH/2);
		mapBoxE = mapBoxE - (mapW/2);
		mapBoxW = mapBoxW + (mapW/2);
		loadImage(getMapURL(mapBaseURL),'mapimage');
		zoom++;
	}
	if(zoom >= maxZoom) {	// If we've reached the max zoom level
		disable('zoomin');
	}
	checkIfMoved();		
}

function rotateMap() {
// Load a rotated map image
	var objMap = document.getElementById("mapimage");
	var objContainer = document.getElementById("container");
	var objScrollers = document.getElementById("mapscrollers");
	if(objMap) {
		show("loadingimage");
		mapW = window.innerWidth;
		mapH = window.innerHeight;
		var bboxW = mapBoxE - mapBoxW;
		var bboxH = mapBoxN - mapBoxS;
		if (mapH / mapW != bboxH / bboxW) { // need taller image
			var newBBoxH = mapH * bboxW / mapW;
			mapBoxN = mapBoxN + (newBBoxH - bboxH) / 2;
			mapBoxS = mapBoxS - (newBBoxH - bboxH) / 2;
		}

		loadImage(getMapURL(mapBaseURL),'mapimage'); 
	}
	if(objContainer) {
		objContainer.style.width=mapW+"px";
		objContainer.style.height=mapH+"px";
		objMap.style.width=mapW+"px";
		objMap.style.height=mapH+"px";
	}
	if(objScrollers) {
		switch(window.orientation)
		{
			case 0:
			case 180:
				objScrollers.style.height=(mapH-42)+"px";
			break;
	
			case -90:
			case 90:
				objScrollers.style.height=mapH+"px";
			break;
	
		}
	}
}

function rotateMapAlternate() {
// Load a rotated map image - needs work to get innerWidth and innerHeight working correctly -- will be required once firmware 2.0 is released enabling full-screen chromeless browsing
	var objMap = document.getElementById("mapimage");
	if(objMap) {
		show("loadingimage");
		mapW = window.innerWidth;
		mapH = window.innerHeight;
		loadImage(getMapURL(mapBaseURL),'mapimage'); 
	}
}



function checkIfMoved() {
// Check to see if the map has been moved (zoomed or scrolled) away from its initial position, and disable/enable the 'recenter' button accordingly
	hasMoved = !((mapBoxW == selectMapBoxW) && (mapBoxN == selectMapBoxN) && (mapBoxS == selectMapBoxS) && (mapBoxE == selectMapBoxE));
	if(hasMoved) {
		enable('recenter');
	} else {
		disable('recenter');
	}

}


function disable(strID) {
// Visually dims and disables the anchor whose id is strID
	var objA = document.getElementById(strID);
	if(objA) {
		if(objA.className.indexOf("disabled") == -1) { // only disable if it's not already disabled!
			objA.className = objA.className + " disabled";
		}
	}
}


function enable(strID) {
// Visually undims and re-enables the anchor whose id is strID
	var objA = document.getElementById(strID);
	if(objA) {
		objA.className = objA.className.replace("disabled","");
	}
}

/*
function saveOptions(strFormID) {
// Applies full-screen map-option changes and hides the form
	var newLayers = "Towns,Hydro,Greenspace,Sport,Courtyards,Roads,Rail,Landmarks,Parking,Other+Buildings,Buildings,";
	
	// Code to manipulate the string newLayers should go here, based on what the user toggled in the form
	
	if(document.mapform.chkLabelBuildings.checked) { newLayers = newLayers + "," + document.mapform.chkLabelBuildings.value; }
	if(document.mapform.chkLabelRoads.checked) { newLayers = newLayers + "," + document.mapform.chkLabelRoads.value; }
	if(document.mapform.chkLabelCourts.checked) { newLayers = newLayers + "," + document.mapform.chkLabelCourts.value; }
	if(document.mapform.chkLabelLandmarks.checked) { newLayers = newLayers + "," + document.mapform.chkLabelLandmarks.value; }
	if(document.mapform.chkLabelParking.checked) { newLayers = newLayers + "," + document.mapform.chkLabelParking.value; }
	
	if(newLayers!=mapLayers) { 	// Only load a new map image if the user actually changed some options
		mapLayers = newLayers;
		loadImage(getMapURL(mapBaseURL),'mapimage'); 
	}

	hide("options");
}
*/

function cancelOptions(strFormID) {
// Should cancel map-option changes and hide the form; this is just a stub for future real function
	var objForm = document.getElementById(strFormID);
	if(objForm) { objForm.reset() }
	hide("options"); 
}


