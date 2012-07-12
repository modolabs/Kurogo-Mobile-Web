/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

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

