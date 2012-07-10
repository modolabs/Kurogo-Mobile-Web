var oldSetContainerWrapperHeight = setContainerWrapperHeight;
setContainerWrapperHeight = function () {
    if (!hasClass(document.body, 'iscroll-off')) {
        oldSetContainerWrapperHeight();
    }
}

// form element-safe version of iScroll initialization
var oldIScrollInit = iScrollInit;
iScrollInit = function (id, options) {
    if (hasClass(document.body, 'iscroll-off')) {
        removeClass(document.body, 'iscroll-off');
        document.documentElement.style.height = "100%";
        document.documentElement.style.height = "hidden";
        
        setContainerWrapperHeight();
    }
    return oldIScrollInit(id, options);
}

function tabletInit() {
    addClass(document.body, 'iscroll-off');
    
    window.splitView.prototype.oldLinkSelect = window.splitView.prototype.linkSelect;
    window.splitView.prototype.linkSelect = function(e, link) {
        link.href = kgoBridge.bridgeToAjaxLink(link.href);
        this.oldLinkSelect(e, link);
    };
    
    setOrientation(getOrientation());
    
    setContainerWrapperHeight();
    
    // Adjust wrapper height on orientation change or resize
    var resizeEvent = 'onorientationchange' in window ? 'orientationchange' : 'resize';
    window.addEventListener(resizeEvent, function() {setTimeout(handleWindowResize,0)}, false);
    
    handleWindowResize();
    //run module init if present
    if (typeof moduleInit != 'undefined') {
        moduleInit();
    }
}
