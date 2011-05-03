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
    document.getElementById('stories').style.height = 'auto';
    document.getElementById('storyDetailWrapper').style.height = 'auto';
    var wrapperHeight = document.getElementById('wrapper').offsetHeight;
    var headerHeight = document.getElementById('newsHeader').offsetHeight;
    var contentHeight = wrapperHeight - headerHeight;
    document.getElementById('stories').style.height = contentHeight + 'px';
    document.getElementById('storyDetailWrapper').style.height = contentHeight + 'px';
}
