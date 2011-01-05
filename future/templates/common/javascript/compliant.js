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
function androidPlaceholderFix(searchbox) {
    // this forces the search box to display the empty string
    // instead of the place holder when the search box takes focus
    if (searchbox.value == "") {
        searchbox.value = "";
    }
}

function clipWithEllipsis(getElements) {

   function getCSSValue(elem, key) {
        if (window.getComputedStyle) {
            return document.defaultView.getComputedStyle(elem, null)
                    .getPropertyValue(key);
        } else if (elem.currentStyle) {
            return elem.currentStyle[key];
        }
        return '';
    }
    
    function getCSSWidth(elem) {
        return elem.offsetWidth
            - parseFloat(getCSSValue(elem, 'borderLeftWidth')) 
            - parseFloat(getCSSValue(elem, 'borderRightWidth'))
            - parseFloat(getCSSValue(elem, 'paddingRight'))
            - parseFloat(getCSSValue(elem, 'paddingLeft'))
            - parseFloat(getCSSValue(elem, 'marginRight'))
            - parseFloat(getCSSValue(elem, 'marginLeft'));
    }
    
    function clipIfNeeded (elem) { 
        // check for first call
        if (typeof elem.originalInnerHTML == 'undefined') {
            elem.originalInnerHTML = elem.innerHTML;
        } else {
            elem.innerHTML = elem.originalInnerHTML;
        }
        if (typeof elem.oldOffsetWidth == 'undefined') {
            elem.oldOffsetWidth = 0;
        }
        
        // Check to see if the element size changed... if not abort
        if (elem.offsetWidth == elem.oldOffsetWidth) { 
            return;  // no size change
        }
        elem.oldOffsetWidth = elem.offsetWidth;
        
        var fullText = elem.originalInnerHTML;
        var clipHeight = elem.offsetHeight;
        // Create a copy of the element and put the full text in it
        // Let it grow so we can see how big it gets
        var copy = elem.cloneNode(true);
        copy.innerHTML = fullText;
        copy.id += 'Copy';
        copy.style['visibility'] = 'hidden';
        copy.style['position'] = 'absolute';
        copy.style['top'] = '0';
        copy.style['left'] = '0';
        copy.style['overflow'] = 'visible';
        copy.style['max-width'] = 'none';
        copy.style['max-height'] = 'none';
        copy.style['width'] = getCSSWidth(elem)+'px';
        copy.style['height'] = 'auto';
        
        elem.parentElement.style['position'] = 'relative';
        elem.parentElement.appendChild(copy);
        
        // Binary search through lengths to see where the copy gets
        // bigger than the real div.  Clip at that length.
        // Cap at 20 tries so we can't infinite loop.
        if (copy.offsetHeight > clipHeight) {
            var lastTestLoc = -1;
            var lower = 0;
            var upper = fullText.length;

            for (var i = 0; i < 20 && lower < upper; i++) {
                var testLoc = Math.floor((lower + upper) / 2);
                if (testLoc == lastTestLoc) {
                    break;
                } else {
                    lastTestLoc = testLoc;
                }
                
                copy.innerHTML = fullText.substr(0, testLoc)+'&hellip;';
                if (copy.offsetHeight > clipHeight) {
                    upper = testLoc;
                } else if (copy.offsetHeight < clipHeight) {
                    lower = testLoc;
                } else {
                    // found it
                    lower = upper = testLoc;
                }
            }   
        }
        elem.innerHTML = copy.innerHTML;
        copy.parentElement.removeChild(copy);
    }
    
    function clipAllIfNeeded(elems) {
        for (var i = 0; i < elems.length; i++) {
            if (getCSSValue(elems[i], 'overflow') != 'hidden') { continue; } // won't clip
            clipIfNeeded(elems[i]);
        }
    }
    
    var ellipsisInit = function () {
        if (!document.body) {
            return setTimeout(ellipsisInit, 13); // No body yet, wait
        }
			
        if (typeof ellipsisInit.initialized == 'undefined') {
            ellipsisInit.initialized = true;
            var elems = getElements();
            
            // Initial clip
            clipAllIfNeeded(elems);
            
            // Bind the global event handler to the element
            if (window.addEventListener) {
                window.addEventListener('resize', function() { clipAllIfNeeded(elems); }, false );
            
            } else if (window.attachEvent) {
                window.attachEvent('onresize', function() { clipAllIfNeeded(elems); });
            }
        }
    }
    
    // Run ellipsis init on DOM load if possible
    if (document.addEventListener) {
        var DOMContentLoaded = function() {
            document.removeEventListener( "DOMContentLoaded", DOMContentLoaded, false );
            ellipsisInit();
        };
        document.addEventListener( "DOMContentLoaded", DOMContentLoaded, false );

        // fallback
        window.addEventListener("load", ellipsisInit, false);

	} else if (document.attachEvent) { // fallback to window.onload on IE
        window.attachEvent("onload", ellipsisInit);
    }
}

function getCookie(c_name) {
  if (document.cookie.length > 0) {
    c_start = document.cookie.indexOf(c_name + "=");
    if (c_start != -1) {
      c_start = c_start + c_name.length + 1;
      c_end = document.cookie.indexOf(";", c_start);
      if (c_end == -1) {
        c_end = document.cookie.length;
      }
      return unescape(document.cookie.substring(c_start,c_end));
    }
  }
  return "";
}

function setCookie(c_name, value, expiredays) {
  var exdate = new Date();
  exdate.setDate(exdate.getDate() + expiredays);
  document.cookie = c_name + "=" + escape(value) +
    ((expiredays == null) ? "" : ";expires=" + exdate.toUTCString());
}

// Share-related functions
function showShare() {
	document.getElementById("sharesheet").style.visibility="visible";
	document.addEventListener('touchmove', doNotScroll, true);
}
function hideShare() {
	document.getElementById("sharesheet").style.visibility="hidden";
	document.removeEventListener('touchmove', doNotScroll, true);
}
function doNotScroll( event ) {
	event.preventDefault(); event.stopPropagation();
}
