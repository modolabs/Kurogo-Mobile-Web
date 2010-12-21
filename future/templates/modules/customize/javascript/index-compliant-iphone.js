var currentId;
var objects = {};
var zIndex = 0;
var rows = [];
var autoscrollTimer = null;
var autoscrollInterval = 66; // 15 fps
var autoscrollTimeLimit = 1500;
var autoscrollTimeout = 0;
var lastDistanceFromBottom = null;
var busy = false;
var dirtyBit = false; // needs saving

function init() {
    // 1. set up reorderable list
    var cookie = getCookie("visiblemodules");
    var activeModules = (cookie) ? cookie.split(",") : [];
    cookie = getCookie("moduleorder");
    var moduleOrder = (cookie) ? cookie.split(",") : [];
    var container = document.getElementById("dragReorderList");
    var checkboxes = container.getElementsByTagName("input");
    // deal with sort order
    for (var j=0; j < moduleOrder.length; j++) {
        for (var i=0; i < checkboxes.length; i++) {
            if (moduleOrder[j] == checkboxes[i].getAttribute("name")) {
                container.appendChild(checkboxes[i].parentElement);
                break;
            }
        }
    }
    // put modules not in the cookie at the end
    for (var i=0; i < checkboxes.length - moduleOrder.length; i++) {
        container.appendChild(checkboxes[0].parentElement);
    }
    // deal with checkboxes
    for (var i=0; i < checkboxes.length; i++) {
        // mark active modules as active
        for (var j=0; j < activeModules.length; j++) {
            if (activeModules[j] == checkboxes[i].getAttribute("name")) {
                checkboxes[i].checked = true;
                break;
            }
        };
        // make sure inactive ones are marked as off, skipping required ones
        if (activeModules.length > 0 && j >= activeModules.length && !hasClass(checkboxes[i], "required")) {
            checkboxes[i].checked = false;
        }
        checkboxes[i].addEventListener("change", updateCookie, false);
    };
    // important to do this after applying changes from cookie. otherwise domIndex attr is wrong
    rows = [];
    var handles = container.getElementsByTagName("div");
    for (var i=0; i < handles.length; i++) {
        if (hasClass(handles[i], "draghandle")) {
            var row = handles[i].parentElement;
            handles[i].addEventListener("touchstart", startmove, false);
            handles[i].addEventListener("touchmove", move, false);
            handles[i].addEventListener("touchend", endmove, false);
            addClass(row, "movable");
            row.style['-webkit-transition-timing-function'] = "ease";
            row.offsetY = 0;
            row['domIndex'] = i;
            rows.push(row);
        }
    };
    
    window.scrollTo(0,1);
}

function startmove(event) {
    var touch = event.touches[0];
    var id = touch.identifier;
    var touchedRow = touch.target.parentElement;
    currentId = id;
    if (hasClass(touchedRow, "movable")) {
        window.scrollTo(0, window.pageYOffset);
        var srcIndex = -1;
        for (var i = rows.length - 1; i >= 0; i--){
            if (rows[i] == touchedRow) {
                srcIndex = i;
                break;
            }
        };
        objects[id] = {
            target: touchedRow,
            handle: touch.target,
            beginX: touch.clientX,
            beginY: touch.clientY,
            pozX: 0,
            pozY: 0,
            pozYinit: 0,
            index: srcIndex
        }
        addClass(touchedRow, "moving");
        touchedRow.style.zIndex = ++zIndex;
        touchedRow.style['-webkit-transition-duration'] = "0";
        autoscrollTimer = window.setInterval(autoscroll, autoscrollInterval);
    }
    event.preventDefault();
}

function endmove(event) {
    // var touch = event.touches[0];
    var id = currentId;
    var obj = (id && objects && objects[id]) ? objects[id].target : null;
    var container = document.getElementById("dragReorderList");
    if (event) {
        event.preventDefault();
    }
    if (!obj) {
        return;
    }
    currentId = null;
    // hard set everything to where it should be
    if (autoscrollTimer) {
        window.clearInterval(autoscrollTimer);
        autoscrollTimer = false;
    }
    for (var i = 0; i < rows.length; i++){
        rows[i].domIndex = i;
        var domIndex = rows[i].domIndex;
        rows[i].offsetY = realHeight(obj) * (i - domIndex);
        rows[i].style['-webkit-transition-duration'] = (rows[i] == obj) ? "0.2s" : "";
        rows[i].style['-webkit-transform'] = '';
        container.appendChild(rows[i]);
    };
    
    if (objects[id] != null) {
        if (hasClass(obj, "movable")) {
            removeClass(obj, "moving");
            objects[id].pozYinit = 0;
        }
        delete objects[id];
    }
    if (dirtyBit) {
        updateCookie();
    }
}

function move(event) {
    var touch = event.touches[0];
    var id = touch.identifier;
    if (objects && objects[id] && objects[id].target && hasClass(objects[id].target, "movable")) {
        var newPozY = objects[id].pozY + touch.clientY - objects[id].beginY;
        // don't waste time on events which make no difference
        if (Math.abs(objects[id].pozYinit - newPozY) < 1) {
            return;
        } else {
            objects[id].pozYinit = newPozY;
        }
        redrawRow(objects[id]);
    }
    event.preventDefault();
}

function redrawRow(row) {
    var obj = row.target;
    var srcIndex = row.index;
    var curIndex = -1;
    for (var i = rows.length - 1; i >= 0; i--){
        if (rows[i] == obj) {
            curIndex = i;
            break;
        }
    };
    var dstIndex = Math.round(row.pozYinit / realHeight(obj)) + srcIndex;
    dstIndex = Math.min(Math.max(dstIndex, 0), rows.length - 1); // keep within array bounds
    // swap rows, animate static one to new position
    if (dstIndex != curIndex) {
        // swap rows in array
        var cur = rows[curIndex];
        var jama = rows[dstIndex];
        rows.splice(curIndex, 1);
        rows.splice(dstIndex, 0, cur);
        // swap visually
        var slideDistance = realHeight(jama);
        if (curIndex - dstIndex < 0) {
           slideDistance *= -1;
        }
        jama.offsetY += slideDistance;
        jama.style['-webkit-transition-duration'] = "0.2s";
        jama.style['-webkit-transform'] = 'translateY(' + jama.offsetY + 'px)';
        dirtyBit = true; // mark as needing to be saved
    }
    obj.style['-webkit-transform'] = 'translateY(' + (row.pozYinit + obj.offsetY) + 'px)';
}

function autoscroll() {
    if (currentId && objects && objects[currentId]) {
        var curObj = objects[currentId];
        var obj = curObj.target;
        var srcIndex = curObj.index;
        // top of the containing UL on the page + top of row as it would be without webkit-transform + amount of transform - how much the page has been scrolled
        // UL is assumed to be an immediate child of the body element
        var screenYPos = obj.parentElement.offsetTop + (realHeight(obj) * srcIndex) + curObj.pozYinit - window.pageYOffset;
        var offset = realHeight(obj) * 0.75;
        var distanceFromTop = Math.max(0, screenYPos + offset);
        var distanceFromBottom = Math.max(0, window.innerHeight + offset - (screenYPos + realHeight(obj)));
        var amount = 0;
        var factor = 25;
        var margin = realHeight(obj) * 0.8;
        if (distanceFromBottom < margin && window.pageYOffset + window.innerHeight < realHeight(document.body)) {
            amount = Math.round(factor * (margin - distanceFromBottom) / margin);
            // amount = realHeight(obj);
        } else if (distanceFromTop < margin && window.pageYOffset > 0) {
            amount = Math.round(-factor * (margin - distanceFromTop) / margin);
            // amount = -realHeight(obj);
        }
        if (amount != 0) {
            autoscrollTimeout = 0;
            curObj.pozYinit += amount;
            redrawRow(curObj);
            // window.setTimeout('window.scrollBy(0, ' + amount + ')', 50);
            window.scrollBy(0, amount);
        } else if (distanceFromBottom == lastDistanceFromBottom) {
            // mobilesafari doesn't report touchend when the touch is dragged off the bottom of the screen
            // so time out if scrolled to the bottom and trying to scroll farther down
            autoscrollTimeout += autoscrollInterval;
            if (autoscrollTimeout >= autoscrollTimeLimit) {
                // cancel timer, end drag
                autoscrollTimeout = 0;
                var event = document.createEvent("TouchEvent");
                event.initTouchEvent("touchend", true, true, window, 0, 0, 0, 0, 0, 0, 0, 0, 0, null, null, null, 0, 0);
                curObj['handle'].dispatchEvent(event);
                
                // endmove();
            }
        } else {
            // reset when moved from bottom
            autoscrollTimeout = 0;
        }
        lastDistanceFromBottom = distanceFromBottom;
    }
}

function updateCookie() {
    var cookieName;
    var moduleNames = [];
    for (var i=0; i < rows.length; i++) {
        var checks = rows[i].getElementsByTagName("input");
        for (var j=0; j < checks.length; j++) {
            if (checks[j].getAttribute("type") == "checkbox") {
                moduleNames.push(checks[j].getAttribute("name"));
                break;
            }
        };
    };

    cookieName = "moduleorder";
    var expiredays = null; // never expire
    setCookie(cookieName, moduleNames.join(), null, httpRoot);

    moduleNames = [];
    for (var i=0; i < rows.length; i++) {
        var checks = rows[i].getElementsByTagName("input");
        for (var j=0; j < checks.length; j++) {
            if (checks[j].getAttribute("type") == "checkbox" && checks[j].checked) {
                moduleNames.push(checks[j].getAttribute("name"));
                break;
            }
        };
    };

    cookieName = "visiblemodules";
    expiredays = null; // never expire
    setCookie(cookieName, moduleNames.join(), null, httpRoot);
    
    dirtyBit = false; // reset "needs saving" flag
    
    // display saved message
    var element = document.getElementById("savedMessage");
    removeClass(element, "fadeinout");
    window.setTimeout(function() {
        addClass(element, "fadeinout");
        // golden ratio vertically within screen
        element.style.top = ((window.innerHeight * .382) + window.pageYOffset - (element.clientHeight / 2)) + "px";
        // centered horizontally within list
        element.style.left = ((element.offsetParent.clientWidth - element.clientWidth) / 2) + "px";
    }, 0);
}

function setCookie(name, value, expiredays, path) {
    var exdate=new Date();
    exdate.setDate(exdate.getDate()+expiredays);
    var exdateclause = (expiredays == null) ? "" : "; expires="+exdate.toGMTString();
    var pathclause = (path == null) ? "" : "; path="+path;
    document.cookie= name + "=" + value + exdateclause + pathclause;
}

function getCookie(name) {
    var cookie = document.cookie;
    var result = null;
    var start = cookie.indexOf(name + "=");
    if (start > -1) {
        start += name.length + 1;
        end=cookie.indexOf(";", start);
        if (end < 0) {
            end = cookie.length;
        }
        result = unescape(cookie.substring(start, end));
    }
    return result;
}

function hasClass(ele,cls) {
    return (ele && ele.className && ele.className.match(new RegExp('(\\s|^)'+cls+'(\\s|$)')));
}
function addClass(ele,cls) {
    if (!this.hasClass(ele,cls)) ele.className += " " + cls;
}
function removeClass(ele,cls) {
    if (hasClass(ele,cls)) {
        var reg = new RegExp('(\\s|^)'+cls+'(\\s|$)');
        ele.className=ele.className.replace(reg,' ');
    }
}

function realHeight(ele) {
    // offsetHeight includes border
    // This will need to be expanded if styling is changed to include margins
    return (!ele) ? 0 : ele.offsetHeight;
}


function showHideFuller(objContainer) {
    var e = window.event;
    if (e.target.hasAttribute("href")) {
        return true;
    }
  var strClass = objContainer.className;
  if(strClass.indexOf("collapsed") > -1) {
    strClass = strClass.replace("collapsed","expanded");
  } else {
    strClass = strClass.replace("expanded","collapsed");
  }
  objContainer.className = strClass;
  return false;
}
