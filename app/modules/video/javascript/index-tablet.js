function moduleInit() {
    setupSplitViewForListAndDetail('videoHeader', 'videos', 'videoDetailWrapper', 'videoDetail');
}

function moduleBookmarkToggled(name, item, expireseconds, path) {
    var items = getCookieArrayValue(name);
    if (items.length) {
        document.getElementById('bookmarkscontainer').style.display = 'table-cell';
    } else {
        document.getElementById('bookmarkscontainer').style.display = 'none';
    }
}

