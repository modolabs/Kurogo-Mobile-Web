function moduleInit() {

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

    document.getElementById('storyDetailWrapper').style.height = 'auto';
    var wrapperHeight = document.getElementById('wrapper').offsetHeight;
    var headerHeight = document.getElementById('newsHeader').offsetHeight;
    var contentHeight = wrapperHeight - headerHeight;
    switch (getOrientation())
    {
        case 'landscape':
            document.getElementById('stories').style.height = contentHeight + 'px';
            document.getElementById('storyDetailWrapper').style.height = contentHeight + 'px';
            break;
        case 'portrait':
            document.getElementById('stories').style.height = '';
            // this is a hack because for some reason the width isn't being properly set
            var width = 0;
            var newsItems = document.getElementById('stories').getElementsByTagName('li');
            var list;
            for (var i=0; i<newsItems.length;i++) {
                list = newsItems[i].parentNode;
                width+=newsItems[i].offsetWidth;
            }
            list.style.width = width+'px';
            
            var storiesHeight = document.getElementById('stories').offsetHeight;
            document.getElementById('storyDetailWrapper').style.height = (contentHeight-storiesHeight) + 'px';
            break;
    }
}
