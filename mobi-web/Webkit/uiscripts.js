var currentTab;

function showTab(strID, objTrigger) {
// Displays the tab with ID strID
	var objTab = document.getElementById(strID);
	if(objTab) {
		show(strID);
		if(currentTab && (currentTab != objTab)) {
			hide(currentTab.id);
			currentTab.style.display = "none";
		}
	}
	currentTab = objTab; // Remember which is the currently displayed tab
	
	// Set the clicked tab to look current
	var objTabs = document.getElementById("tabs");
	var arrTabs = objTabs.getElementsByTagName("li");
	if(objTrigger) {
		for(var i=0; i<arrTabs.length; i++) {
			arrTabs[i].className="";
		}
		var objTriggerTab = objTrigger.parentNode;
		if(objTriggerTab) {
			objTriggerTab.className="active";
		}
	} 
}

function rotateScreen() {
// Switch stylesheet and viewport based on screen orientation
	switch(window.orientation)
	{
		case 0:
		case 180:
                        setOrientation('portrait');
		break;

		case -90:
		case 90:
                        setOrientation('landscape');
		break;

	}
	setTimeout(scrollToTop, 500);
}

function setOrientation(orientation) {
        document.getElementsByTagName("body")[0].className = orientation;
}

function scrollToTop() {
	scrollTo(0,1); 
}


function showLoadingMsg(strID) {
// Show a temporary loading message in the element with ID strID
	var objToStuff = document.getElementById(strID);
	if(objToStuff) {
		objToStuff.style.height = objToStuff.offsetHeight + "px";
		objToStuff.innerHTML = "<div class=\"loading\"><img src=\"../Webkit/images/loading.gif\" width=\"27\" height=\"21\" alt=\"\" align=\"absmiddle\" />Loading data...</div >";
	}
}

function hide(strID) {
// Hides the object with ID strID 
	var objToHide = document.getElementById(strID);
	if(objToHide) {
		objToHide.style.display = "none";
	}
}

function show(strID) {
// Displays the object with ID strID 
	var objToHide = document.getElementById(strID);
	if(objToHide) {
		objToHide.style.display = "block";
	}
}

function showHideFull(objContainer) {
	var strClass = objContainer.className;
	if(strClass.indexOf("collapsed") > -1) {
		strClass = strClass.replace("collapsed","expanded");
	} else {
		strClass = strClass.replace("expanded","collapsed");
	}
	objContainer.className = strClass;
	objContainer.blur();
}

function clearField(objField,strDefault) {
// Clears the placeholder text in an input field if it matches the default string - fixes a bug in Android
	if((objField.value==strDefault) || (objField.value=="")) {
		objField.value="";
	}
}

// Android doesn't respond to onfocus="clearField(...)" until the 
// input field loses focus
function androidPlaceholderFix() {
	//alert("fuck you android placeholder bug");
	searchbox = document.getElementById("filter");
	searchbox.onfocus = function() {
		if (searchbox.value == "") searchbox.value = "";
	}
}
