/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

String.prototype.strip = function() {
    return this.replace(/^\s+/, '').replace(/\s+$/, '');
}

function showTab(id) {
    var tabId = id+'-tab';
    var tabbodyId = id+'-tabbody';
    
    var tab = document.getElementById(tabId);
    var tabbody = document.getElementById(tabbodyId);
    if (!tab || !tabbody) { return; } // safety check
    
    var tabs = tab.parentNode.getElementsByTagName('li');
    if (!tabs) { return; } // safety check
    
    var tabBodies = tabbody.parentNode.childNodes;
    if (!tabBodies) { return; } // safety check
    
    // Display the tab body and hide others
    for (var i = 0; i < tabBodies.length; i++) {
        if (tabBodies[i].id == tabbodyId) {
            show(tabBodies[i].id);
        } else {
            hide(tabBodies[i].id);
        }
    }
    
    // Display the tab and hide others
    for (var i = 0; i < tabs.length; i++) {
        if (tabs[i].id == tabId) {
            addClass(tabs[i], 'active');
        } else {
            removeClass(tabs[i], 'active');
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

function onOrientationChange() {
    /* the galaxy tab sends orientation change events constantly */
    if (typeof onOrientationChange.lastOrientation == 'undefined') {
        onOrientationChange.lastOrientation = null;
    }
    
    var newOrientation = getOrientation();
    
    if (newOrientation != onOrientationChange.lastOrientation) {
        rotateScreen();
        
        if (typeof onOrientationChange.callbackFunctions !== 'undefined') {
            for (var i = 0; i < onOrientationChange.callbackFunctions.length; i++) {
                onOrientationChange.callbackFunctions[i]();
            }
        }
        
        onOrientationChange.lastOrientation = newOrientation;
    }
}

function onResize() {
    if (typeof onResize.callbackFunctions !== 'undefined') {
        for (var i = 0; i < onResize.callbackFunctions.length; i++) {
            onResize.callbackFunctions[i]();
        }
    }

    setOrientation(getOrientation());
}

function addOnOrientationChangeCallback(callback) {
    if (typeof onOrientationChange.callbackFunctions == 'undefined') {
        onOrientationChange.callbackFunctions = [];
    }
    onOrientationChange.callbackFunctions.push(callback);
    
    if (typeof onResize.callbackFunctions == 'undefined') {
        onResize.callbackFunctions = [];
    }
    onResize.callbackFunctions.push(callback);
}

function setupOrientationChangeHandlers() {
    if (window.addEventListener) {
        window.addEventListener("orientationchange", onOrientationChange, false);
    } else if (window.attachEvent) {
        window.attachEvent("onorientationchange", onOrientationChange);
    }
    if (window.addEventListener) {
        window.addEventListener("resize", onResize, false);
    } else if (window.attachEvent) {
        window.attachEvent("onresize", onResize);
    }
}

function rotateScreen() {
    setTimeout(scrollToTop, 500);
}

function getOrientation() {
    var width = document.documentElement.clientWidth || document.body.clientWidth;
    var height = document.documentElement.clientHeight || document.body.clientHeight;

    return (width > height) ? 'landscape' : 'portrait';
}

function setOrientation(orientation) {
    var body = document.getElementsByTagName("body")[0];
 
    // remove existing portrait/landscape class if there
    removeClass(body, 'portrait');
    removeClass(body, 'landscape');
    addClass(body, orientation);
}

// Localized ajax loading and error content
// takes either an element or an id
function showAjaxLoadingMsg(e) {
    if (typeof e == 'string') {
        e = document.getElementById(element);
    }
    if (e) {
      e.innerHTML = AJAX_CONTENT_LOADING_HTML;
    }
    onDOMChange();
}

function showAjaxErrorMsg(e) {
    if (typeof e == 'string') {
        e = document.getElementById(element);
    }
    if (e) {
      e.innerHTML = AJAX_CONTENT_ERROR_HTML;
    }
    onDOMChange();
}

function hide(strID) {
    // Hides the object with ID strID 
    var objToHide = document.getElementById(strID);
    if (objToHide) {
        objToHide.style.display = "none";
    }
    onDOMChange();
}

function show(strID) {
    // Displays the object with ID strID 
    var objToHide = document.getElementById(strID);
    if (objToHide) {
        objToHide.style.display = "block";
    }
    onDOMChange();
}

function showHideFull(objContainer) {
    var strClass = objContainer.className;
    if (strClass.indexOf("collapsed") > -1) {
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
	  if ((objField.value == strDefault) || (objField.value == "")) {
		    objField.value = "";
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

function clearCookie(name, path) {
    var value = 'deleted';
    var exdate = new Date(0);
    var exdateclause = "; expires=" + exdate.toGMTString();
    var pathclause = (path == null) ? "" : "; path=" + path;
    document.cookie = name + "=" + escape(value) + exdateclause + pathclause;
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
    var sharesheet = document.getElementById("sharesheet");
    if (!sharesheet) {
        return;
    }
    if (!sharesheet.parentNode || sharesheet.parentNode.nodeName != 'BODY') {
        var elements = document.getElementsByTagName('body');
        if (elements.length) {
            var body = elements[0];
            body.appendChild(sharesheet);
        }
    }
    sharesheet.style.display="block";
    var iframes = document.getElementsByTagName('iframe');
    for (var i = 0; i < iframes.length; i++) {
        iframes[i].style.visibility = 'hidden';
        iframes[i].style.height = '0';
    }
    window.scrollTo(0,0);
}
function hideShare() {
    if (!document.getElementById("sharesheet")) {
        return;
    }
    document.getElementById("sharesheet").style.display="none";
    var iframes = document.getElementsByTagName('iframe');
    for (var i = 0; i < iframes.length; i++) {
        iframes[i].style.visibility = 'visible';
        iframes[i].style.height = '';
    }
}

// Bookmarks
function toggleBookmark(name, item, expireseconds, path, bookmarkId) {
    // facility for module to respond to bookmark state change
    if (typeof moduleBookmarkWillToggle != 'undefined') {
        $result = moduleBookmarkWillToggle(name, item, expireseconds, path);
        if ($result === false) { return; }
    }
  
    if (!bookmarkId) {
        bookmarkId = "bookmark";
    }
    var bookmark = document.getElementById(bookmarkId);
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
    
    // facility for module to respond to bookmark state change
    if (typeof moduleBookmarkToggled != 'undefined') {
        moduleBookmarkToggled(name, item, expireseconds, path);
    }
}

// TODO this needs to handle encoded strings and parameter separators (&amp;)
if (typeof makeAPICall === 'undefined' && typeof jQuery === 'undefined') {
    function makeAPICall(type, module, command, data, callback) {
        var urlParts = [];
        for (var param in data) {
            urlParts.push(param + "=" + data[param]);
        }
        url = URL_BASE + API_URL_PREFIX + '/' + module + '/' + command + '?' + urlParts.join('&');
        var handleError = function(errorObj) {}
    
        var httpRequest = new XMLHttpRequest();
        httpRequest.open("GET", url, true);
        httpRequest.onreadystatechange = function() {
            if (httpRequest.readyState == 4 && httpRequest.status == 200) {
                var obj;
                if (window.JSON) {
                    obj = JSON.parse(httpRequest.responseText);
                    // TODO: catch SyntaxError
                } else {
                    obj = eval('(' + httpRequest.responseText + ')');
                }
                if (obj !== undefined) {
                    if ("response" in obj) {
                      callback(obj["response"]);
                    }
          
                    if ("error" in obj && obj["error"] !== null) {
                      handleError(obj["error"]);
                    } else {
                      handleError("response not found");
                    }
                } else {
                    handleError("failed to parse response");
                }
            }
        }
        httpRequest.send(null);
    }
}

function ajaxContentIntoContainer(options) {
    if (typeof options != 'object') { return; } // safety
    
    if (typeof ajaxContentIntoContainer.pendingRequests == 'undefined') {
        ajaxContentIntoContainer.pendingRequests = new Array();
    }
    
    var _removeRequestsForContainer = function (container) {
        // go backwards so removing items doesn't cause us to skip requests
        for (var i = ajaxContentIntoContainer.pendingRequests.length-1; i >= 0; i--) {
            if (ajaxContentIntoContainer.pendingRequests[i].options.container == container) {
                ajaxContentIntoContainer.pendingRequests[i].httpRequest.abort();
                ajaxContentIntoContainer.pendingRequests.splice(i, 1);
            }
        }
    }
    
    var _removeCompletedRequest = function (httpRequest) {
        for (var i = 0; i < ajaxContentIntoContainer.pendingRequests.length; i++) {
            if (ajaxContentIntoContainer.pendingRequests[i].httpRequest == httpRequest) {
                ajaxContentIntoContainer.pendingRequests.splice(i, 1);
                break;
            }
        }
    }
   
    var defaults = {
        url: null, 
        container: null, 
        timeout: 60, 
        addAjaxParameter: true,
        loadMessage: true,
        errorMessage: true,
        success: function () {},
        error: function (code) {} 
    };
    for (var i in defaults) {
        if (typeof options[i] == 'undefined') {
            options[i] = defaults[i];
        }
    }
    if (!options.url || !options.container) { return; } // safety
    
    if (options.addAjaxParameter && options.url.search(/[\?\&]ajax=/) < 0) {
        options.url += (options.url.search(/\?/) < 0 ? "?" : "&")+"ajax=1";
    }
    
    _removeRequestsForContainer(options.container);
    
    var httpRequest = new XMLHttpRequest();
    httpRequest.open("GET", options.url, true);
    
    var requestTimer = setTimeout(function() {
        // some browsers set readyState to 4 on abort so remove handler first
        httpRequest.onreadystatechange = function() { };
        httpRequest.abort();
        
        options.error(408); // http request timeout status code
    }, options.timeout * 1000);
    
    httpRequest.onreadystatechange = function() {
        // return if still in progress
        if (httpRequest.readyState != 4) { return; }
        
        // Got answer, don't abort
        clearTimeout(requestTimer);
        
        if (httpRequest.status == 200) { // Success
            options.container.innerHTML = "";
            
            insertContentIntoContainer({
                "container" : options.container,
                "html"      : httpRequest.responseText
            });
            
            options.success();
            
        } else {
            if (options.errorMessage) {
                showAjaxErrorMsg(options.container);
            }
            options.error(httpRequest.status);
        }
        
        _removeCompletedRequest(httpRequest);
    };
    
    if (options.loadMessage) {
        showAjaxLoadingMsg(options.container);
    }
    
    httpRequest.send(null);
    
    ajaxContentIntoContainer.pendingRequests.push({
        'options'     : options,
        'httpRequest' : httpRequest
    });
}

function insertContentIntoContainer(options) {
    if (typeof options != 'object') { return; } // safety

    var defaults = {
        html: null, 
        container: null
    };
    for (var i in defaults) {
        if (typeof options[i] == 'undefined') {
            options[i] = defaults[i];
        }
    }
    if (!options.html || !options.container) { return; } // safety
    
    // If there are no non-empty non-script nodes before scripts then IE8 will 
    // remove all the scripts when innerHTML is used.  So temporarily add a 
    // non-empty div to the beginning of the HTML.
    var ie8HackId = '__insertContentIntoContainer_ie8Hack';
    options.container.innerHTML = '<div id="'+ie8HackId+'" style="display:none;">&nbsp;</div>'+options.html;
    
    var scripts = options.container.getElementsByTagName('script');
    for (var i = 0; i < scripts.length; i++) {
        var script = scripts[i];
        
        // Manually execute scripts
        var src = (script.text || script.textContent || script.innerHTML || "");
        if (src.length) {
            try {
                if (window.execScript) {
                    window.execScript(src);
                } else {
                    (function(src) {
                        window.eval.call(window, src);
                    })(src);
                }
            } catch (e) {
            }
        } else if (script.src && script.src.length) {
            // create new javascript include and add to head
            // which is the only cross-browser way to ensure it executes
            var copy = document.createElement("script");
            if (script.type && script.type.length) {
                copy.type = script.type;
            }
            copy.src = script.src;
            document.getElementsByTagName("head")[0].appendChild(copy);
        }
    }
    
    // move styles to head tag
    var styles = options.container.getElementsByTagName('style');
    for (var i = 0; i < styles.length; i++) {
        document.getElementsByTagName("head")[0].appendChild(styles[i]);
    }
    
    // remove IE8 hack
    var ie8Hack = document.getElementById(ie8HackId);
    if (ie8Hack) {
        ie8Hack.parentNode.removeChild(ie8Hack);
    }
    
    onDOMChange();
}

function getCSSValue(element, key) {
    if (window.getComputedStyle) {
      return document.defaultView.getComputedStyle(element, null).getPropertyValue(key);
        
    } else if (element.currentStyle) {
        if (key == 'float') { 
            key = 'styleFloat'; 
        } else {
            var re = /(\-([a-z]){1})/g; // hyphens to camel case
            if (re.test(key)) {
                key = key.replace(re, function () {
                    return arguments[2].toUpperCase();
                });
            }
        }
        var style = element.currentStyle[key] ? element.currentStyle[key] : '';
        
        // Fix IE8 border width and margins so that parseFloat doesn't return NaN on them
        var parts = [ 'Top', 'Left', 'Bottom', 'Right' ];
        for (var i = 0; i < parts.length; i++) {
            if (key == "border"+parts[i]+"Width" && element.currentStyle["border"+parts[i]+"Style"] == "none") {
                style = "0px";
                break;
            }
        }
        for (var i = 0; i < parts.length; i++) {
            if (key == "margin"+parts[i] && style == "auto") {
                style = "0px";
                break;
            }
        }
        return style;
    }
    return '';
}

function setCSSValue(element, key, value) {
    if (key == 'float') { 
        key = 'styleFloat'; 
    } else {
        var re = /(\-([a-z]){1})/g; // hyphens to camel case
        if (re.test(key)) {
            key = key.replace(re, function () {
                return arguments[2].toUpperCase();
            });
        }
    }
    
    try {
        element.style[key] = value; // IE will go kaboom here if the style is bad
    } catch (e) {}
}

function getCSSValueNumber(element, key) {
    var number = parseFloat(getCSSValue(element, key));
    return isNaN(number) ? 0 : number;
}

function getCSSHeight(element) {
    return element.offsetHeight
        - getCSSValueNumber(element, 'border-top-width')
        - getCSSValueNumber(element, 'border-bottom-width')
        - getCSSValueNumber(element, 'padding-top')
        - getCSSValueNumber(element, 'padding-bottom');
}

function getCSSWidth(element) {
    return element.offsetWidth
        - getCSSValueNumber(element, 'border-left-width') 
        - getCSSValueNumber(element, 'border-right-width')
        - getCSSValueNumber(element, 'padding-left')
        - getCSSValueNumber(element, 'padding-right');
}

function _getStringForArgs(args) {
    var argString = "";
    if (typeof args == "string" && args.length) {
        argString = "?" + args;
    } else if (typeof args == "object") {
        for (var param in args) {
            argString += (argString.length ? "&" : "?") + 
                param + "=" + encodeURIComponent(args[param]);
        }
    }
    return argString;    
}

function redirectTo(page, args) {
    window.location = "./" + page + _getStringForArgs(args);
}

function redirectToModule(module, page, args) {
    window.location = "../" + module + "/" + page + _getStringForArgs(args);
}

/*
	Developed by Robert Nyman, http://www.robertnyman.com
	Code/licensing: http://code.google.com/p/getelementsbyclassname/
	
	Reversed element and tag arguments for convenience
*/	
var getElementsByClassName = function (className, elm, tag) {
    if (document.getElementsByClassName) {
        getElementsByClassName = function (className, elm, tag) {
            elm = elm || document;
            var elements = elm.getElementsByClassName(className),
                nodeName = (tag)? new RegExp("\\b" + tag + "\\b", "i") : null,
                returnElements = [],
                current;
            for (var i=0, il=elements.length; i<il; i+=1){
                current = elements[i];
                if (!nodeName || nodeName.test(current.nodeName)) {
                    returnElements.push(current);
                }
            }
            return returnElements;
        };
    }
    else if (document.evaluate) {
        getElementsByClassName = function (className, elm, tag) {
          tag = tag || "*";
          elm = elm || document;
          var classes = className.split(" "),
              classesToCheck = "",
              xhtmlNamespace = "http://www.w3.org/1999/xhtml",
              namespaceResolver = (document.documentElement.namespaceURI === xhtmlNamespace)? xhtmlNamespace : null,
              returnElements = [],
              elements,
              node;
          for (var j=0, jl=classes.length; j<jl; j+=1){
              classesToCheck += "[contains(concat(' ', @class, ' '), ' " + classes[j] + " ')]";
          }
          try	{
              elements = document.evaluate(".//" + tag + classesToCheck, elm, namespaceResolver, 0, null);
          }
          catch (e) {
              elements = document.evaluate(".//" + tag + classesToCheck, elm, null, 0, null);
          }
          while ((node = elements.iterateNext())) {
              returnElements.push(node);
          }
          return returnElements;
        };
    }
    else {
        getElementsByClassName = function (className, elm, tag) {
            tag = tag || "*";
            elm = elm || document;
            var classes = className.split(" "),
                classesToCheck = [],
                elements = (tag === "*" && elm.all)? elm.all : elm.getElementsByTagName(tag),
                current,
                returnElements = [],
                match;
            for (var k=0, kl=classes.length; k<kl; k+=1){
                classesToCheck.push(new RegExp("(^|\\s)" + classes[k] + "(\\s|$)"));
            }
            for (var l=0, ll=elements.length; l<ll; l+=1){
                current = elements[l];
                match = false;
                for(var m=0, ml=classesToCheck.length; m<ml; m+=1){
                    match = classesToCheck[m].test(current.className);
                    if (!match) {
                        break;
                    }
                }
                if (match) {
                    returnElements.push(current);
                }
            }
            return returnElements;
        };
    }
    return getElementsByClassName(className, elm, tag);
};

function getFirstElementByClassName(className, elem, tag) {
    var elements = getElementsByClassName(className, elem, tag);
    return elements.length ? elements[0] : null;
}

function setUserContext(context, container, url, ajax, success) {
    if (!url) {
        return;
    }
    
    if (!ajax) {
        if (url.charAt(0)=='/') {
            url = URL_BASE + url.substr(1);
        }

        window.location = url;
        return;
    }
    
    if (!document.getElementById(container)) {
        return;
    }
    
    var opts = {
     url: url,
     container: document.getElementById(container),
     loadMessage: false,
     success: success
    }
    
    var userData = getCookie(MODULE_NAV_COOKIE);
    if (userData) {
        if (!confirm('Changing the home screen layout will reset your customized module order preferences. Are you sure you wish to update the layout?')) {
            return;
        }
        clearCookie(MODULE_NAV_COOKIE, COOKIE_PATH);
    }

    ajaxContentIntoContainer(opts);
    scrollToTop();
    return false;
}

function updateUserContextSelect(select, container) {
    var option = select.options[select.selectedIndex];
    var context = option.value;
    var url = option.getAttribute('url');
    var ajax = option.getAttribute('ajax');
    setUserContext(context, container, url, ajax);
}

function updateUserContextLink(link, container) {
    var li = link.parentNode;
    var list = li.parentNode;
    var context = li.getAttribute('context');
    var url = li.getAttribute('url');
    var ajax = li.getAttribute('ajax');
    var result = setUserContext(context, container, url, ajax);
    if (typeof result == 'undefined') {
        return;
    }
    var lis = list.children;
    for (var i=0; i<lis.length; i++) {
        lis[i].className = '';
        if (context == lis[i].getAttribute('context')) {
            lis[i].className = 'contextSelected';
        }
    }
}