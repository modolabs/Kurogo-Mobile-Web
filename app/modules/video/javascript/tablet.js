function setupVideoSplitView() {
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
    var videos = document.getElementById('videos');
    var videoDetailWrapper = document.getElementById('videoDetailWrapper');
    if (!videoDetailWrapper) {
      return;  // can happen for searches with no results or when feed is down
    }
    videoDetailWrapper.style.height = 'auto';
    
    var wrapperHeight = document.getElementById('wrapper').offsetHeight;
    var headerHeight = document.getElementById('videoHeader').offsetHeight;
    var contentHeight = wrapperHeight - headerHeight;
    
    switch (getOrientation()) {
        case 'landscape':
            videos.style.height = contentHeight + 'px';
            videoDetailWrapper.style.height = contentHeight + 'px';
            var list = videos.getElementsByTagName('li')[0].parentNode;
            list.style.width = '';
            break;
        
        case 'portrait':
            videos.style.height = '';
            // this is a hack because for some reason the width isn't being properly set
            var width = 0;
            var videoItems = videos.getElementsByTagName('li');
            var list;
            for (var i = 0; i < videoItems.length; i++) {
                list = videoItems[i].parentNode;
                width+=videoItems[i].offsetWidth;
            }
            list.style.width = width+'px';
            
            var videosHeight = videos.offsetHeight;
            videoDetailWrapper.style.height = (contentHeight - videosHeight) + 'px';
            break;
    }
}
