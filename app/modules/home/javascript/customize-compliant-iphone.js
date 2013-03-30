/*
 * Copyright © 2009 - 2010 Massachusetts Institute of Technology
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

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
    if (!document.getElementById('savedMessage')) {
        var savedMessage = document.createElement('p');
        savedMessage.id = 'savedMessage';
        savedMessage.innerHTML = 'Saved';
        document.getElementById('container').appendChild(savedMessage);
    }    

    rows = [];
    var container = document.getElementById("dragReorderList");
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
    
    scrollToTop();
    return;
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

function toggle(objClick) {
    updateCookie();
  /*
  for(var cnt=0; cnt < arrUpdatedHomeArray.length; cnt++) {
    if(arrUpdatedHomeArray[cnt].id == moduleID) {
      arrUpdatedHomeArray[cnt].active = objClick.checked ? 1 : 0;
    }
  }

  writeCookies();
  */
}

function updateCookie() {
    var modules = [];
    for (var i=0; i < rows.length; i++) {
        var checks = rows[i].getElementsByTagName("input");
        for (var j=0; j < checks.length; j++) {
            if (checks[j].getAttribute("type") == "checkbox") {
                modules.push('"'+checks[j].getAttribute('name')+'":'+(checks[j].checked ? '1' : 0));
                break;
            }
        };
    };

    setCookie(MODULE_NAV_COOKIE, '{' + modules.join() + '}', MODULE_NAV_COOKIE_LIFESPAN, COOKIE_PATH);

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

function reset() {
    clearCookie(MODULE_NAV_COOKIE, COOKIE_PATH);
}

