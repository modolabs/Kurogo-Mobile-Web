/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

function supportsOverflowScroll() {
    // all desktops support overflow:scroll
    return true;
}

function setupSplitViewForListAndDetail(splitview, options) {
    var aSplitView = null;
    
    if (typeof splitview == 'string') {
        splitview = document.getElementById(splitview);
    }
    if (!splitview) { return; } // safety check
    
    var listWrapper = getFirstElementByClassName('splitview-listwrapper', splitview);
    var detailWrapper = getFirstElementByClassName('splitview-detailwrapper', splitview);
    var detail = getFirstElementByClassName('splitview-detail', splitview);
    if (!listWrapper || !detailWrapper || !detail) { return; } // safety check
    
    // manually size panes
    moduleHandleWindowResize = splitviewHandleWindowResize;
    moduleHandleWindowResize();

    options = options || {};
    options["list"] = listWrapper;
    options["detail"] = detailWrapper;
    options["content"] = detail;
    
    aSplitView = new splitView(options);

}

function splitviewHandleWindowResize() {
    var wrapper = document.getElementById('wrapper');
    
    var singleSplitviewHandleWindowResize = function(splitview) {
        var listWrapper = getFirstElementByClassName('splitview-listwrapper', splitview);
        var list = getFirstElementByClassName('splitview-list', splitview);
        var detailWrapper = getFirstElementByClassName('splitview-detailwrapper', splitview);
        var detail = getFirstElementByClassName('splitview-detail', splitview);
        if (!wrapper || !listWrapper || !list || !detailWrapper || !detail) { return; } // safety check
        
        var wrapperHeight = wrapper.offsetHeight - splitview.offsetTop;
        var listHeight = list.offsetHeight;
        var detailHeight = detail.offsetHeight;
        var height = Math.max(wrapperHeight, listHeight, detailHeight);
        
        var splitviewForceOffsetHeight = function(element, offsetHeight) {
            var cssHeight = getCSSHeight(element) - element.offsetHeight + offsetHeight;
            setCSSValue(element, 'height', cssHeight+"px");
        };
        splitviewForceOffsetHeight(splitview, height);
        splitviewForceOffsetHeight(listWrapper, height);
        splitviewForceOffsetHeight(detailWrapper, height);
    }
    
    var splitviews = getElementsByClassName('splitview');
    for (var i = 0; i < splitviews.length; i++) {
        singleSplitviewHandleWindowResize(splitviews[i]);
    }
}
