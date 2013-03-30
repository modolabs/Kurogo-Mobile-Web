/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

function loadModulePages(modulePanes) {
    function loadModulePage(info) {
        var elem = document.getElementById(info['elementId']);
        if (!elem) { return; }
        
        ajaxContentIntoContainer({
            url: info['ajaxURL'], 
            container: elem, 
            timeout: 60, 
            loadMessage: false,
            success: function () {
                removeClass(elem, 'loading');
                onDOMChange();
                moduleHandleWindowResize();
            },
            error: function (code) {
                removeClass(elem, 'loading');
            }
        });
    }
    
    for (var i = 0; i < modulePanes.length; i++) {
        loadModulePage(modulePanes[i]);
    }
}

var paneResizeHandlers = [];
function registerPaneResizeHandler(handler) {
    paneResizeHandlers.push(typeof handler == 'string' ? window[handler] : handler);
}

function callPaneResizeHandlers() {
    for (var i = 0; i < paneResizeHandlers.length; i++) {
        paneResizeHandlers[i]();
    }
}

// figure out where an element is relative to its enclosing portlet
function getPortletOffsetTop(elem) {
    var offsetTop = 0;
    if (elem && elem.offsetParent) {
        while (!hasClass(elem, 'portlet-content') && elem.offsetParent) {
            offsetTop += elem.offsetTop;
            elem = elem.offsetParent;
        }
        if (!hasClass(elem, 'portlet-content')) {
            offsetTop = 0;  // error!
        }
    }
    return offsetTop;
}

function moduleHandleWindowResize() {
    callPaneResizeHandlers();
    
    var portlets = getElementsByClassName('portlet-content');
    if (portlets && portlets.length) {
        for (var i = 0; i < portlets.length; i++) {
            var portlet = portlets[i];
            var clipHeight = getCSSHeight(portlet);
            
            if (hasClass(portlet, 'portlet-no-truncate')) {
                continue;  // module handling this
            }
            
            var lists = portlet.getElementsByTagName('ul');
            if (!lists || !lists.length) {
                continue; // no lists
            }
            
            var done = false;
            for (var j = lists.length - 1; j >= 0; j--) {
                var list = lists[j];
                
                var items = list.getElementsByTagName('li');
                
                // make all list items visible
                for (var k = 0; k < items.length; k++) {
                    items[k].style.display = 'list-item';
                }
                
                // hide items that are clipped by the portlet
                for (var k = items.length - 1; k >= 0; k--) {
                    var item = items[k];
                    
                    var bottomOffset = getPortletOffsetTop(item) + item.offsetHeight;
                    if (bottomOffset > clipHeight) {
                        item.style.display = 'none';
                    } else {
                        done = true; // to break us out of the next loop up
                        break;
                    }
                }
                
                if (done) {
                    break;
                }
            }
        }
    }
}
