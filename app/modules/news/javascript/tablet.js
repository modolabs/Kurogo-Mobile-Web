function setupNewsSplitView() {
    newsSplitView = new splitView({
        list: 'stories',
        detail: 'storyDetailWrapper',
        content: 'storyDetail'
    });

    containerScroller.destroy();
    containerScroller = null;

    moduleHandleWindowResize();
}

function moduleHandleWindowResize() {
    var stories = document.getElementById('stories');
    var storyDetailWrapper = document.getElementById('storyDetailWrapper');
    if (!storyDetailWrapper) {
      return;  // can happen for searches with no results or when feed is down
    }
    storyDetailWrapper.style.height = 'auto';
    
    var wrapperHeight = document.getElementById('wrapper').offsetHeight;
    var headerHeight = document.getElementById('newsHeader').offsetHeight;
    var contentHeight = wrapperHeight - headerHeight;
    
    switch (getOrientation()) {
        case 'landscape':
            stories.style.height = contentHeight + 'px';
            storyDetailWrapper.style.height = contentHeight + 'px';
            var list = document.getElementById('stories').getElementsByTagName('li')[0].parentNode;
            list.style.width = '';
            break;
        
        case 'portrait':
            stories.style.height = '';
            // this is a hack because for some reason the width isn't being properly set
            var width = 0;
            var newsItems = stories.getElementsByTagName('li');
            var list;
            for (var i = 0; i < newsItems.length; i++) {
                list = newsItems[i].parentNode;
                width+=newsItems[i].offsetWidth;
            }
            list.style.width = width+'px';
            
            var storiesHeight = stories.offsetHeight;
            storyDetailWrapper.style.height = (contentHeight - storiesHeight) + 'px';
            break;
    }
}
