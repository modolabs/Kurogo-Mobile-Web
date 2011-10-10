var containerScroller = null;
var navScroller = null;

function onDOMChange() {
  if (containerScroller) {
    setContainerWrapperHeight();
    containerScroller.refresh();
  }
}

// Update the nav slide indicators
function updateNavSlider() {
  var current = Math.abs(navScroller.x);
  var max = Math.abs(navScroller.maxScrollX);

  var canScrollLeft = (current > 0);
  var canScrollRight = (current < max-1);
  
  document.getElementById('slideleft').style.display  = canScrollLeft  ? 'block' : 'none';
  document.getElementById('slideright').style.display = canScrollRight ? 'block' : 'none';
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
  document.getElementById('container').style.height = 'auto';

	var navbarHeight = document.getElementById('navbar').offsetHeight;
  var footerNavHeight = document.getElementById('footernav').offsetHeight;
	var wrapperHeight = window.innerHeight - navbarHeight - footerNavHeight;
	var containerHeight = document.getElementById('container').offsetHeight;
	
	document.getElementById('wrapper').style.height = wrapperHeight + 'px';
	
	if (containerHeight < wrapperHeight) {
	  document.getElementById('container').style.height = wrapperHeight + 'px';
	}
	
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
    if (navigator.userAgent.match(/(Android 3\.\d)/)) {
        // Android 3 browsers don't reliably set client and offset heights
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

function tabletInit() {
   setOrientation(getOrientation());
    if(!document.getElementById('navbar')) {
        // page has no footer so do not attempt
        // to use fancy tablet container
        return;
    }

  setContainerWrapperHeight();
  
  // Adjust wrapper height on orientation change or resize
  var resizeEvent = 'onorientationchange' in window ? 'orientationchange' : 'resize';
  window.addEventListener(resizeEvent, function() {setTimeout(handleWindowResize,0)}, false);

  document.addEventListener('touchmove', function(e) { e.preventDefault(); }, false);
  
  containerScroller = new iScroll('wrapper', { 
    checkDOMChanges: false, 
    hScrollbar: false,
    desktopCompatibility: true,
    bounce: false,
    bounceLock: true
  });


  navScroller = new iScroll('navsliderwrapper', { 
    checkDOMChanges: false, 
    hScrollbar: false,
    vScrollbar: false,
    desktopCompatibility: true,
    bounce: false,
    bounceLock: true,
    onScrollStart: updateNavSlider,
    onScrollEnd: updateNavSlider
  });

    handleWindowResize();
    updateNavSlider();

  //run module init if present
  if (typeof moduleInit != 'undefined') {
    moduleInit();
  }
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
        this.detailScroller = new iScroll(this.options.detail, {checkDOMChange: true});
        
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
            var httpRequest = new XMLHttpRequest();
            httpRequest.open("GET", link.href+'&ajax=1', true);
            httpRequest.onreadystatechange = function() {
                if (httpRequest.readyState == 4 && httpRequest.status == 200) {
                    self.content.innerHTML = httpRequest.responseText;
                    
                    var hash = '#'+encodeURIComponent(removeBreadcrumbParameter(link.href));
                    if (window.history && window.history.pushState && window.history.replaceState && // Regexs from history js plugin
                      !((/ Mobile\/([1-7][a-z]|(8([abcde]|f(1[0-8]))))/i).test(navigator.userAgent) || // disable for versions of iOS < 4.3 (8F190)
                         (/AppleWebKit\/5([0-2]|3[0-2])/i).test(navigator.userAgent))) { // disable for the mercury iOS browser and older webkit
                      history.pushState({}, document.title, hash);
                    } else {
                      location.hash = hash;
                    }
                    
                    self.detailScroller.refresh();
                    if (typeof moduleHandleWindowResize != 'undefined') {
                        moduleHandleWindowResize(e);
                    }
                }
            }
            showLoadingMsg(this.options.content);
            httpRequest.send(null);
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
              this.listScroller = new iScroll(this.options.list, options);
            }
        },
        refreshScrollers: function () {
            if (self.detailScroller) {
                self.detailScroller.refresh();
            }
            if (self.listScroller) {
                self.listScroller.refresh();
            }
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
function setupSplitViewForListAndDetail(headerId, listWrapperId, detailWrapperId, detailId) {
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
    
    containerScroller.destroy();
    containerScroller = null;
    
    moduleHandleWindowResize();
    
    aSplitView = new splitView({
        list: listWrapperId,
        detail: detailWrapperId,
        content: detailId
    });
}
