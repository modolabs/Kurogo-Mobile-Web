/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

var containerScroller = null;
var navScroller = null;

function onDOMChange() {
  if (containerScroller) {
    setContainerWrapperHeight();
    setTimeout(function () {
      containerScroller.refresh();
    }, 0);
  }
}

// Update the nav slide indicators
function updateNavSlider() {
    if (navScroller) {
        var current = Math.abs(navScroller.x);
        var max = Math.abs(navScroller.maxScrollX);
      
        var canScrollLeft = (current > 0);
        var canScrollRight = (current < max-1);
        
        document.getElementById('slideleft').style.display  = canScrollLeft  ? 'block' : 'none';
        document.getElementById('slideright').style.display = canScrollRight ? 'block' : 'none';
    }
}

function navSliderScrollLeft() {
  if (navScroller) {
    navScroller.scrollTo(0, navScroller.y, 500);
  }
}

function navSliderScrollRight() {
  if (navScroller) {
    navScroller.scrollTo(navScroller.maxScrollX, navScroller.y, 500);
  }
}

// Change wrapper height based on device orientation.
function setContainerWrapperHeight() {
  var footerNav = document.getElementById('footernav');
  
	var navbarHeight = document.getElementById('navbar').offsetHeight;
  var footerNavHeight = footerNav ? footerNav.offsetHeight : 0;
	var wrapperHeight = window.innerHeight - navbarHeight - footerNavHeight;
	var containerHeight = document.getElementById('container').offsetHeight;
	
	document.getElementById('wrapper').style.height = wrapperHeight + 'px';
	
	// when this exists, make it fill the screen
	var fillscreen = document.getElementById('fillscreen');
	if (fillscreen) {
	  fillscreen.style.height = wrapperHeight + 'px';
	}
}

function handleWindowResize(e) {
    if (!('orientation' in window)) {
        rotateScreen();
    }
    setContainerWrapperHeight();
  
    setTimeout(updateNavSlider, 0);
    
    if (typeof moduleHandleWindowResize != 'undefined') {
        moduleHandleWindowResize(e);
    }
    if (navigator.userAgent.match(/(Android [34]\.\d)/)) {
        // Android 3/4 browsers don't reliably set client and offset heights
        // before calling orientationchange or resize handlers.
        var self = this;
        setTimeout(function() {
            setContainerWrapperHeight();
            setTimeout(updateNavSlider, 0);
            if (typeof moduleHandleWindowResize != 'undefined') {
                moduleHandleWindowResize(e);
            }
        }, 600); // approx. how long after the event before the offsetHeights are correct
    }
} 

// form element-safe version of iScroll initialization
function iScrollInit(id, options) {
    options.useTransform = true;
    options.onBeforeScrollStart = function (e) {
        var target = e.target;
        while (target.nodeType != 1) { target = target.parentNode; }
        
        var tagName = target.tagName;
        if (target.tagName != 'SELECT' && target.tagName != 'INPUT' && target.tagName != 'TEXTAREA') {
            e.preventDefault();
        }
    };

    return new iScroll(id, options);
}

var moduleProvidesScrollers = false;

function tabletInit() {
    setOrientation(getOrientation());
    if (!document.getElementById('navbar')) {
        // page has no footer so do not attempt
        // to use fancy tablet container
        return;
    }

    setContainerWrapperHeight();
    
    // Adjust wrapper height on orientation change or resize
    var resizeEvent = 'onorientationchange' in window ? 'orientationchange' : 'resize';
    window.addEventListener(resizeEvent, function() { setTimeout(handleWindowResize, 0) }, false);
    
    if (document.getElementById('navsliderwrapper')) {
        navScroller = iScrollInit('navsliderwrapper', { 
            hScrollbar: false,
            vScrollbar: false,
            bounce: false,
            bounceLock: true,
            onScrollStart: updateNavSlider,
            onScrollEnd: updateNavSlider
        });
    }
    
    updateNavSlider();
    
    // run module init if present
    // module init can change value of moduleProvidesScrollers to
    // disable container scroller if it provides its own for a splitview
    if (typeof moduleInit != 'undefined') {
        moduleInit();
    }
  
    if (!moduleProvidesScrollers) {
        containerScroller = iScrollInit('wrapper', { 
            hScrollbar: false,
            bounce: false,
            bounceLock: true
        });
    }
    
    handleWindowResize();
}

function scrollToTop() {
  if (containerScroller) {
  	containerScroller.scrollTo(0,0,0); 
  }
}

(function(window) {

    function splitView (options) {
      // set caller options
        if (typeof options == 'object') {
            for (var i in options) {
                switch (i) {
                    case 'linkSelect':
                    case 'actionForLink':
                        this[i] = options[i];
                        break;
                    default:
                        this.options[i] = options[i];
                        break;
                }
            }
        }
      
        if (window.addEventListener) {
          window.addEventListener(RESIZE_EVENT, this, false);
        } else if (window.attachEvent) {
          window.attachEvent(RESIZE_EVENT, this);
        }
        
        if (!document.getElementById(this.options.list) || !document.getElementById(this.options.detail)) {
            return;
        }

        this.orientation = getOrientation();
        this.list = document.getElementById(this.options.list);
        this.detail = document.getElementById(this.options.detail);
        this.detailScroller = iScrollInit(this.options.detail, {
            hScrollbar : false,
            hScroll : false
        });
        
        if ('content' in this.options) {
            this.content = document.getElementById(this.options.content);
        } else {
            this.options.content = this.options.detail;
            this.content = this.detail;
        }
        
        var self = this;
        
        var links = this.list.getElementsByTagName('a');

        var linkInAnchor = null;
        var anchor = location.hash;
        if (anchor.length > 1) {
            var possibleLinkHref = removeBreadcrumbParameter(decodeURIComponent(anchor.slice(1)));
            if (possibleLinkHref) {
              for (var i=0;i<links.length;i++) {
                  if (possibleLinkHref == removeBreadcrumbParameter(links[i].href)) {
                     linkInAnchor = links[i];
                     break;
                  }
              }
            }
        }
        
        var first = true;
        for (var i=0;i<links.length;i++) {
            links[i].onclick = function(e) {
                var action = self.actionForLink(this);
                self[action](e, this);
            }

            if (!linkInAnchor && first && this.options.selectFirst && this.actionForLink(links[i])=='linkSelect') {
                links[i].onclick();
                first = false;
            }
        }
        if (linkInAnchor) {
            linkInAnchor.onclick();
        }

        this.updateListScroller();
    }

    splitView.prototype = {
        orientation: '',
        options: {
            selectFirst: true,
            selectID: null
        },
        baseActionForLink: function(link) {
            if (link.parentNode.className.match(/pagerlink/)) {
                return 'linkFollow';
            }
            
            return 'linkSelect';
        },
        actionForLink: function(link) {
            return this.baseActionForLink(link);
        },
        linkFollow: function(e, link) {
            //just follow the link
        },
        linkSelect: function(e, link) {
            //ajax fun
            hideShare();
            var self = this;
            var selected = this.list.getElementsByTagName('a');
            for (var j=0;j<selected.length;j++) {
                removeClass(selected[j],'listSelected');
            }
            addClass(link,'listSelected');
            this.detailScroller.scrollTo(0,0);
            
            ajaxContentIntoContainer({
                url: link.href+'&ajax=1', 
                container: self.content, 
                timeout: 60, 
                success: function () {
                    var hash = '#'+encodeURIComponent(removeBreadcrumbParameter(link.href));
                    if (window.history && window.history.pushState && window.history.replaceState && // Regexs from history js plugin
                      !((/ Mobile\/([1-7][a-z]|(8([abcde]|f(1[0-8]))))/i).test(navigator.userAgent) || // disable for versions of iOS < 4.3 (8F190)
                         (/AppleWebKit\/5([0-2]|3[0-3])/i).test(navigator.userAgent))) { // disable for the mercury iOS browser and older webkit/uiwebview
                      window.history.pushState({}, document.title, hash);
                    } else {
                      location.hash = hash;
                    }
                    
                    if (typeof moduleHandleWindowResize != 'undefined') {
                        moduleHandleWindowResize(e);
                    }
                    
                    var refreshOnLoad = function () {
                        setTimeout(function () {
                            self.detailScroller.refresh();
                        }, 0);
                    };
                    
                    // As images load the height of the detail view will change so
                    // refresh the scroller when each image loads:
                    var images = self.content.getElementsByTagName("img");
                    for (var i = 0; i < images.length; i++) {
                        // ignore images with a height attribute since the DOM already knows their height
                        if (images[i].getAttribute("height")) { continue; }
                        
                        if (images[i].addEventListener) {
                            images[i].addEventListener("load", refreshOnLoad, false);
                        } else if (images[i].attachEvent) {
                            images[i].attachEvent("onload", refreshOnLoad);
                        }
                    }
                    refreshOnLoad();
                },
                error: function (code) {
                }
            });
            
            e && e.preventDefault();
            return false;
        },
        listScroller: null,
        detailScroller: null,
        handleEvent: function (e) {
            switch (e.type) {
                case 'orientationchange':
                case 'resize':
                    if (this.orientation != getOrientation()) {
                        this.orientation = getOrientation();
                        this.updateListScroller();
                        if (typeof moduleHandleWindowResize != 'undefined') {
                            moduleHandleWindowResize(e);
                        }
                    }
                    break;
            }
        },
        updateListScroller: function() {
            var self = this, options={};
            switch (getOrientation()) {
                case 'portrait':
                    options.vScrollbar = false;
                    options.hScrollbar = true;
                    options.vScroll = false;
                    options.hScroll = true;
                    break;
                case 'landscape':
                    options.vScrollbar = true;
                    options.hScrollbar = false;
                    options.hScroll = false;
                    options.vScroll = true;
                    break;
            }

            if (this.listScroller) {
                for (var i in options) {
                    this.listScroller.options[i] = options[i];
                }
                
                setTimeout(function() {
                    self.listScroller.refresh();
                    var items = self.list.getElementsByTagName('a');
                    for (var i=0;i<items.length; i++) {
                        if (hasClass(items[i],'listSelected')) {
                            self.listScroller.scrollToElement(items[i].parentNode,0);
                        }
                    }
                },0);
                return;
            } else {
              this.listScroller = iScrollInit(this.options.list, options);
            }
        },
        refreshScrollers: function () {
            setTimeout(function() {
                if (self.detailScroller) {
                    self.detailScroller.refresh();
                }
                if (self.listScroller) {
                    self.listScroller.refresh();
                }
            }, 0);
        },
    }
    
    function removeBreadcrumbParameter(url) {
        return url.replace(/[?&]_b=[^&]*/, '');
    }

    var RESIZE_EVENT = window.addEventListener ? 
    ('onorientationchange' in window ? 
    'orientationchange' :  // touch device
    'resize')              // desktop browser
    : ('onresize');          // IE
    
    window.splitView = splitView;

})(window)

// Used by news and video modules for news article listings
function setupSplitViewForListAndDetail(headerId, listWrapperId, detailWrapperId, detailId, options) {
    var aSplitView = null;

    moduleHandleWindowResize = function () {
        var listWrapper = document.getElementById(listWrapperId);
        var detailWrapper = document.getElementById(detailWrapperId);
        if (!detailWrapper) {
          return;  // can happen for searches with no results or when feed is down
        }
        detailWrapper.style.height = 'auto';
        
        var wrapperHeight = document.getElementById('wrapper').offsetHeight;
        var headerHeight = document.getElementById(headerId).offsetHeight;
        var contentHeight = wrapperHeight - headerHeight;
        
        switch (getOrientation()) {
            case 'landscape':
                listWrapper.style.height = contentHeight + 'px';
                detailWrapper.style.height = contentHeight + 'px';
                var list = listWrapper.getElementsByTagName('li')[0].parentNode;
                list.style.width = '';
                break;
            
            case 'portrait':
                listWrapper.style.height = '';
                // this is a hack because for some reason the width isn't being properly set
                var width = 0;
                var listItems = listWrapper.getElementsByTagName('li');
                var list;
                for (var i = 0; i < listItems.length; i++) {
                    list = listItems[i].parentNode;
                    width+=listItems[i].offsetWidth;
                }
                list.style.width = width+'px';
                
                var listWrapperHeight = listWrapper.offsetHeight;
                detailWrapper.style.height = (contentHeight - listWrapperHeight) + 'px';
                break;
        }
        
        if (aSplitView) {
            aSplitView.refreshScrollers();
        }
    }
    
    moduleProvidesScrollers = true;
    document.getElementById('container').style.height = "100%";
    
    moduleHandleWindowResize();

    options = options || {};
    options["list"] = listWrapperId;
    options["detail"] = detailWrapperId;
    options["content"] = detailId;
    
    aSplitView = new splitView(options);
}
