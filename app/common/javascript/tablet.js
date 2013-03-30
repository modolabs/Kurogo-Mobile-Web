/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
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
        setTimeout(function () {
            containerScroller.refresh();
        }, 0);
    }
    if (navScroller) {
        setTimeout(function () {
            navScroller.refresh();
        }, 0);
    }
}

function handleNavmenuButton(button) {
    try {
        toggleClass(button, 'selected');
        toggleClass(document.getElementById('navmenu'), 'open');
        if (navScroller) {
            navScroller.refresh();
        }
    } catch(e) { }
    return false;
}

function handleWindowResize(e) {
    if (!('orientation' in window)) {
        rotateScreen();
    }
  
    if (typeof moduleHandleWindowResize != 'undefined') {
        moduleHandleWindowResize(e);
    }
    if (navigator.userAgent.match(/(Android [34]\.\d)/)) {
        // Android 3/4 browsers don't reliably set client and offset heights
        // before calling orientationchange or resize handlers.
        var self = this;
        setTimeout(function() {
            if (typeof moduleHandleWindowResize != 'undefined') {
                moduleHandleWindowResize(e);
            }
        }, 600); // approx. how long after the event before the offsetHeights are correct
    }
} 

// form element-safe version of iScroll initialization
function iScrollInit(element, options) {
    if (typeof element == 'string') {
        element = document.getElementById(element);
        if (!element) { return };
    }
    if (supportsOverflowScroll()) {
        // Used by devices which support overflow:scroll
        // CSS will set overflow:auto where necessary
        element.style['-webkit-overflow-scrolling'] = 'touch'; // bouncy webkit touch scroll
        
        // There is a bug in iOS 5 where position:relative elements inside an 
        // element with webkit-overflow-scrolling set to touch don't draw 
        // properly when they are initially scrolled offscreen. This hack works 
        // around the problem but also degrades performance so we only do it on
        // iPads running iOS 5:
        // http://stackoverflow.com/questions/7808110/css3-property-webkit-overflow-scrollingtouch-error
        if (isIOS5Browser() && element.childNodes && element.childNodes.length) {
            for (var i = 0; i < element.childNodes.length; i++) {
                setCSSValue(element.childNodes[i], '-webkit-transform', 'translate3d(0, 0, 0)');
            }
        }
        addScrollingElement(element);
        return null;
        
    } else {
        // override CSS specifying overflow:scroll for browsers which support it
        setCSSValue(element, 'overflow', 'hidden');
        setCSSValue(element, 'overflow-x', 'hidden');
        setCSSValue(element, 'overflow-y', 'hidden');
        
        options.useTransform = true;
        options.onBeforeScrollStart = function (e) {
            var target = e.target;
            while (target.nodeType != 1) { target = target.parentNode; }
            
            var tagName = target.tagName;
            if (target.tagName != 'SELECT' && target.tagName != 'INPUT' && target.tagName != 'TEXTAREA') {
                e.preventDefault();
            }
        };
        
        return new iScroll(element, options);
    }
}

function isModernIPhoneBrowser() {
    // iPhones running Safari on iOS 5 or later
    return navigator.userAgent.match(/Safari/) && 
           (navigator.userAgent.match(/iPhone/) || navigator.userAgent.match(/iPad/)) && 
           !navigator.userAgent.match(/OS [234]/);
}

function isIOS5Browser() {
    // iPhones running Safari on iOS 5.x
    return navigator.userAgent.match(/Safari/) && 
           (navigator.userAgent.match(/iPhone/) || navigator.userAgent.match(/iPad/)) && 
           navigator.userAgent.match(/OS 5/);
}

function isModernAndroidBrowser() {
    // Android default and Chrome browsers on Android 4.x
    return navigator.userAgent.match(/Android 4/);
}

// Certain modern browsers support overflow:scroll
function supportsOverflowScroll() {
    // TODO: Add Androids running modern Chrome/default browser
    return isModernIPhoneBrowser() || isModernAndroidBrowser();
}

var moduleProvidesScrollers = false;

var kgoScrollingElements = [];

function initHandleScrollingElements() {
    // hacks only needed for iOS when using overflow scroll:
    if (isModernIPhoneBrowser()) {
        window.addEventListener('touchstart', function(event) {
            for (var i = 0; i < kgoScrollingElements.length; i++) {
                var element = kgoScrollingElements[i];
                if (element.offsetHeight < element.scrollHeight) {
                    // Trick Safari into bouncing the scroll view, not the whole page:
                    if (element.scrollTop <= 0) {
                        element.scrollTop = 1;
                    }
                    
                    if (element.scrollTop + element.offsetHeight >= element.scrollHeight) {
                        element.scrollTop = element.scrollHeight - element.offsetHeight - 1;
                    }
                }
            }
        }, false);
        
        // try to stop page bounce
        window.addEventListener('touchmove', function(event) {
            if (document.body) { document.body.scrollTop = 0; }
        }, false);
    }
    
    if (supportsOverflowScroll() && KUROGO_PLATFORM != 'computer') {
        addOnOrientationChangeCallback(function () {
            // hack which forces the navmenu to redraw and reposition so 
            // that it remains scrollable
            var navmenu = document.getElementById('navmenu');
            if (navmenu) {
                navmenu.style.display = 'none';
                navmenu.offsetHeight;
                navmenu.style.display = 'block';
            }
        });
    }
}

function addScrollingElement(element) {
    if (typeof element == 'string') {
        element = document.getElementById(element);
    }
    if (element) {
        kgoScrollingElements.push(element);
    }
}

function removeScrollingElement(element) {
    if (typeof element == 'string') {
        element = document.getElementById(element);
    }
    if (element) {
        var newKGOScrollingElements = [];
        for (var i = 0; i < kgoScrollingElements.length; i++) {
            if (kgoScrollingElements[i] != element) {
                newKGOScrollingElements.push(kgoScrollingElements[i]);
            }
        }
        kgoScrollingElements = newKGOScrollingElements;
    }
}

function tabletInit() {
    initHandleScrollingElements();

    // Add class to body when browser supports overflow: scroll
    // This allows us to conditionally apply styles which might
    // interfere with iScroll
    if (supportsOverflowScroll()) {
        addClass(document.body, 'kgo-supports-overflow-scroll');
    }

    setOrientation(getOrientation());
    
    // Adjust wrapper height on orientation change or resize
    var resizeHandler = function() { setTimeout(handleWindowResize, 0) };
    if (window.addEventListener) {
      var resizeEvent = 'onorientationchange' in window ? 'orientationchange' : 'resize';
      window.addEventListener(resizeEvent, resizeHandler, false);
    } else if (window.attachEvent) {
      window.attachEvent('onresize', resizeHandler);
    }
    
    
    if (document.getElementById('navmenu')) {
        navScroller = iScrollInit('navmenu', { 
            hScrollbar: false,
            vScrollbar: true,
            bounce: false,
            bounceLock: true
        });
    }
    
    // run module init if present
    // module init can change value of moduleProvidesScrollers to
    // disable container scroller if it provides its own for a splitview
    if (typeof moduleInit != 'undefined') {
        moduleInit();
    }
  
    if (!moduleProvidesScrollers) {
        containerScroller = iScrollInit('container-wrapper', { 
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
        
        if (typeof this.options.list == 'string') {
            this.options.list = document.getElementById(this.options.list);
            if (!this.options.list) { return; }
        }
        if (typeof this.options.detail == 'string') {
            this.options.detail = document.getElementById(this.options.detail);
            if (!this.options.detail) { return; }
        }
        if (typeof this.options.content == 'string') {
            this.options.content = document.getElementById(this.options.content);
            if (!this.options.content) { return; }
        }
        
        if (window.addEventListener) {
          window.addEventListener(RESIZE_EVENT, this, false);
        } else if (window.attachEvent) {
          window.attachEvent(RESIZE_EVENT, this);
        }
        
        this.orientation = getOrientation();
        this.list = this.options.list;
        this.detail = this.options.detail;
        this.detailScroller = iScrollInit(this.options.detail, {
            hScrollbar : false,
            hScroll : false
        });
        
        if ('content' in this.options) {
            this.content = this.options.content;
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
                return self[action](e, this);
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
            
            if (this.detailScroller) {
                this.detailScroller.scrollTo(0,0);
            }
            
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
                            splitviewHandleWindowResize();
                            if (self.detailScroller) {
                                self.detailScroller.refresh();
                            }
                        }, 100);
                    };
                    
                    // As images load the height of the detail view will change so
                    // refresh the scroller when each image loads:
                    var images = self.content.getElementsByTagName("img");
                    for (var i = 0; i < images.length; i++) {
                        if (images[i].addEventListener) {
                            images[i].addEventListener("load", refreshOnLoad, false);
                        } else if (images[i].attachEvent) {
                            images[i].attachEvent("onload", refreshOnLoad);
                        }
                    }
                    // As iframes load the height of the detail view may change so
                    // refresh the scroller when each iframe loads:
                    var iframes = self.content.getElementsByTagName("iframe");
                    for (var i = 0; i < iframes.length; i++) {
                        if (iframes[i].addEventListener) {
                            iframes[i].addEventListener("load", refreshOnLoad, false);
                        } else if (iframes[i].attachEvent) {
                            iframes[i].attachEvent("onload", refreshOnLoad);
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
                    // delay updating until after the main resize handlers have run
                    var that = this;
                    setTimeout(function () {
                        if (that.orientation != getOrientation()) {
                            that.orientation = getOrientation();
                            that.updateListScroller();
                            if (typeof moduleHandleWindowResize != 'undefined') {
                                moduleHandleWindowResize(e);
                            }
                        }
                    }, 0);
                    break;
            }
        },
        updateListScroller: function() {
            var self = this, options={};
            if (this.detail.offsetTop > 0) {
                options.vScrollbar = false;
                options.hScrollbar = true;
                options.vScroll = false;
                options.hScroll = true;
                
            } else {
                options.vScrollbar = true;
                options.hScrollbar = false;
                options.hScroll = false;
                options.vScroll = true;
            }

            if (this.listScroller) {
                for (var i in options) {
                    this.listScroller.options[i] = options[i];
                }
                
                setTimeout(function() {
                    self.listScroller.refresh();
                    var items = self.list.getElementsByTagName('a');
                    for (var i = 0; i < items.length; i++) {
                        if (hasClass(items[i], 'listSelected')) {
                            self.listScroller.scrollToElement(items[i].parentNode, 0);
                        }
                    }
                }, 0);
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
        }
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

})(window);

// sets up css styles to size the container so it is 100% height and width
function setModuleFillScreen() {
    addClass(document.body, 'fillscreen');
}

// Used by news and video modules for news article listings
function setupSplitViewForListAndDetail(splitview, options) {
    var aSplitView = null;
    
    if (typeof splitview == 'string') {
        splitview = document.getElementById(splitview);
    }
    if (!splitview) { return; } // safety check
    
    var listWrapper = getFirstElementByClassName('splitview-listwrapper', splitview); 
    var list = getFirstElementByClassName('splitview-list', splitview); 
    var detailWrapper = getFirstElementByClassName('splitview-detailwrapper', splitview); 
    var detail = getFirstElementByClassName('splitview-detail', splitview); 
    
    if (!listWrapper || !list || !detailWrapper || !detail) { return; } // safety check
    
    moduleHandleWindowResize = function () {
        var wrapperHeight = document.getElementById('wrapper').offsetHeight;
        var offsetTop = splitview.offsetTop;
        var splitviewHeight = wrapperHeight - offsetTop;
        
        // set the height of the splitview manually because there may be
        // a variable sized header element above it
        splitview.style.height = splitviewHeight + "px";
        
        switch (getOrientation()) {
            case 'landscape':
                if (hasClass(splitview, 'portrait')) {
                    removeClass(splitview, 'portrait');
                }
                list.style['width'] = ''; // default to whatever is in CSS
                break;
            
            case 'portrait':
                if (!hasClass(splitview, 'portrait')) {
                    addClass(splitview, 'portrait');
                }
                var lists = list.getElementsByTagName('ul');
                if (lists.length) {
                    // When in portrait mode the list elements are float:left
                    // so the results list does not have a width.  Figure out its
                    // width programmatically so that the browser/iScroll can tell 
                    // if the content is wider than the container.
                    var width = 0;
                    var listItems = listWrapper.getElementsByTagName('li');
                    for (var i = 0; i < listItems.length; i++) {
                        width += listItems[i].offsetWidth;
                    }
                    list.style['width'] = width+'px';
                }
                break;
        }
        
        if (aSplitView) {
            aSplitView.refreshScrollers();
        }
    }
    
    moduleProvidesScrollers = true;
    
    setModuleFillScreen();
    moduleHandleWindowResize();

    options = options || {};
    options["list"] = listWrapper;
    options["detail"] = detailWrapper;
    options["content"] = detail;
    
    aSplitView = new splitView(options);
}

function splitviewHandleWindowResize() {
    // used by tablet-computer
}
