function moduleInit() {

    var videoSplitView = new splitView({
        list: 'videos',
        detail: 'videoDetailWrapper',
        content: 'videoDetail'
    });
    containerScroller.destroy();
    containerScroller = null;    

    moduleHandleWindowResize();
    
}

function moduleHandleWindowResize() {
    document.getElementById('videos').style.height = 'auto';
    document.getElementById('videoDetailWrapper').style.height = 'auto';
	var wrapperHeight = document.getElementById('wrapper').offsetHeight;
    var headerHeight = document.getElementById('videoHeader').offsetHeight;
    var contentHeight = wrapperHeight - headerHeight;
	document.getElementById('videos').style.height = contentHeight + 'px';
	document.getElementById('videoDetailWrapper').style.height = contentHeight + 'px';
}
