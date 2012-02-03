function tabletInit() {
    window.splitView.prototype.oldLinkSelect = window.splitView.prototype.linkSelect;
    window.splitView.prototype.linkSelect = function(e, link) {
        link.href = webBridgeLinkToAjaxLinkIfNeeded(link.href);
        this.oldLinkSelect(e, link);
    };

    setOrientation(getOrientation());

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

    handleWindowResize();
  
    //run module init if present
    if (typeof moduleInit != 'undefined') {
        moduleInit();
    }
}

