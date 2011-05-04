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
	var wrapperHeight = document.getElementById('wrapper').offsetHeight;
    var headerHeight = document.getElementById('videoHeader').offsetHeight;
    var contentHeight = wrapperHeight - headerHeight;

    switch (getOrientation())
    {
        case 'landscape':
            document.getElementById('videos').style.height = contentHeight + 'px';
            document.getElementById('videoDetailWrapper').style.height = contentHeight + 'px';
            break;
        case 'portrait':
            document.getElementById('videos').style.height = '';
            // this is a hack because for some reason the width isn't being properly set
            var width = 0;
            var videos = document.getElementById('videos').getElementsByTagName('li');
            var list;
            for (var i=0; i<videos.length;i++) {
                list = videos[i].parentNode;
                width+=videos[i].offsetWidth;
            }
            list.style.width = width+'px';
            
            var videosHeight = document.getElementById('videos').offsetHeight;
            document.getElementById('videoDetailWrapper').style.height = (contentHeight-videosHeight) + 'px';
            break;
    }
}
