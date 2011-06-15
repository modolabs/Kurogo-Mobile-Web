var currentTab;
var orientationMethod;
var orientationIsFlipped=false;

// detect how we are detecting orientation
(function (window) {
    if (!('orientation' in window)) {
        window.orientationMethod = 'size';
        return;
    }

    window.orientationMethod = 'orientation';
    var width = document.documentElement.clientWidth || document.body.clientWidth;
    var height = document.documentElement.clientHeight || document.body.clientHeight;
    
    /* at this point the method of orientation detection is not perfect */
    if (navigator.userAgent.match(/(PlayBook.+RIM Tablet|Android 3\.\d)/)) {
        window.orientationIsFlipped = true;
    }
    
})(window);

String.prototype.strip = function() {
    return this.replace(/^\s+/, '').replace(/\s+$/, '');
}

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
	
	// fake resize event in case tab body was resized while hidden 
  if (document.createEvent) {
    var e = document.createEvent('HTMLEvents');
    e.initEvent('resize', true, true);
    window.dispatchEvent(e);
  
  } else if( document.createEventObject ) {
    var e = document.createEventObject();
    document.documentElement.fireEvent('onresize', e);
  }
	
	onDOMChange();
}

function rotateScreen() {
  setOrientation(getOrientation());
  setTimeout(scrollToTop, 500);
}

function getOrientation() {
    switch (window.orientationMethod) {
        case 'size':
            var width = document.documentElement.clientWidth || document.body.clientWidth;
            var height = document.documentElement.clientHeight || document.body.clientHeight;

            return (width > height) ? 'landscape' : 'portrait';
            break;

        case 'orientation':
            switch (window.orientation) {
                case 0:
                case 180:
                    return window.orientationIsFlipped ? 'landscape' : 'portrait';
                    break;
                
                case 90:
                case -90:
                    return window.orientationIsFlipped ? 'portrait': 'landscape';
                    break;
            }
    }
}

function setOrientation(orientation) {
    var body = document.getElementsByTagName("body")[0];
 
 //remove existing portrait/landscape class if there
    removeClass(body, 'portrait');
    removeClass(body, 'landscape');
    addClass(body, orientation);
}


function showLoadingMsg(strID) {
// Show a temporary loading message in the element with ID strID
	var objToStuff = document.getElementById(strID);
	if(objToStuff) {
		objToStuff.innerHTML = "<div class=\"loading\"><img src=\"../common/images/loading.gif\" width=\"27\" height=\"21\" alt=\"\" align=\"absmiddle\" />Loading data...</div >";
	}
	onDOMChange();
}

function hide(strID) {
// Hides the object with ID strID 
	var objToHide = document.getElementById(strID);
	if(objToHide) {
		objToHide.style.display = "none";
	}
	
	onDOMChange();
}

function show(strID) {
// Displays the object with ID strID 
	var objToHide = document.getElementById(strID);
	if(objToHide) {
		objToHide.style.display = "block";
	}
	
	onDOMChange();
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
	
	onDOMChange();
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

function getCookie(name) {
  var cookie = document.cookie;
  var result = "";
  var start = cookie.indexOf(name + "=");
  if (start > -1) {
    start += name.length + 1;
    var end = cookie.indexOf(";", start);
    if (end < 0) {
      end = cookie.length;
    }
    result = unescape(cookie.substring(start, end));
  }
  return result;
}

function setCookie(name, value, expireseconds, path) {
  var exdate = new Date();
  exdate.setTime(exdate.getTime() + (expireseconds * 1000));
  var exdateclause = (expireseconds == 0) ? "" : "; expires=" + exdate.toGMTString();
  var pathclause = (path == null) ? "" : "; path=" + path;
  document.cookie = name + "=" + escape(value) + exdateclause + pathclause;
}

function getCookieArrayValue(name) {
  var value = getCookie(name);
  if (value && value.length) {
    return value.split('@@');
  } else {
    return new Array();
  }
}

function setCookieArrayValue(name, values, expireseconds, path) {
  var value = '';
  if (values && values.length) {
    value = values.join('@@');
  }
  setCookie(name, value, expireseconds, path);
}

function hasClass(ele,cls) {
    return ele.className.match(new RegExp('(\\s|^)'+cls+'(\\s|$)'));
}
        
function addClass(ele,cls) {
    if (!this.hasClass(ele,cls)) ele.className += " "+cls;
}

function removeClass(ele,cls) {
    if (hasClass(ele,cls)) {
        var reg = new RegExp('(\\s|^)'+cls+'(\\s|$)');
        ele.className=ele.className.replace(reg,' ').strip();
    }
}
        
function toggleClass(ele, cls) {
    if (hasClass(ele, cls)) {
        removeClass(ele, cls);
    } else {
        addClass(ele, cls);
    }
}

// Share-related functions
function showShare() {
    if (!document.getElementById("sharesheet")) {
        return;
    }
	document.getElementById("sharesheet").style.display="block";
	var iframes = document.getElementsByTagName('iframe');
	for (var i=0; i<iframes.length; i++) {
	    iframes[i].style.visibility = 'hidden';
	}
	window.scrollTo(0,0);
}
function hideShare() {
    if (!document.getElementById("sharesheet")) {
        return;
    }
	document.getElementById("sharesheet").style.display="none";
	var iframes = document.getElementsByTagName('iframe');
	for (var i=0; i<iframes.length; i++) {
	    iframes[i].style.visibility = 'visible';
	}
}

// Bookmarks
function setBookmarkStates(name, item) {
  var bookmark = document.getElementById("bookmark");
  var items = getCookieArrayValue(name);
  for (var i = 0; i < items.length; i++) {
    if (items[i] == item) {
      addClass(bookmark, "on");
      break;
    }
  }
  if (bookmark.addEventListener) {
    bookmark.addEventListener("touchstart", function() {
        addClass(bookmark, "pressed");
    }, false);
    bookmark.addEventListener("touchend", function() {
        removeClass(bookmark, "pressed");
    }, false);
    
  } else if (bookmark.attachEvent) {
    bookmark.attachEvent("ontouchstart", function() {
        addClass(bookmark, "pressed");
    });
    bookmark.attachEvent("ontouchend", function() {
        removeClass(bookmark, "pressed");
    });
  }
}

function toggleBookmark(name, item, expireseconds, path) {
  var bookmark = document.getElementById("bookmark");
  toggleClass(bookmark, "on");
  var items = getCookieArrayValue(name);
  var newItems = new Array();
  if (items.length == 0) {
    newItems[0] = item;
  } else {
    var found = false;
    for (var i = 0; i < items.length; i++) {
      if (items[i] == item) {
        found = true;
      } else {
        newItems.push(items[i]);
      }
    }
    if (!found) {
      newItems.push(item);
    }
  }
  setCookieArrayValue(name, newItems, expireseconds, path);
}
