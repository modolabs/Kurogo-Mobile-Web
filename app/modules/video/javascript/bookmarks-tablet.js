/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

function moduleInit() {
    setupSplitViewForListAndDetail('tabletVideos');
}

function moduleBookmarkWillToggle(name, item, expireseconds, path) {
    return confirm('Are you sure you want to remove this video from your bookmarks?');
}

function moduleBookmarkToggled(name, item, expireseconds, path) {
    var videoList = document.getElementById('videoList');
    if (videoList) {
        var videos = videoList.childNodes;
        
        if (videos && videos.length < 2) {
            document.getElementById('tabletVideos').style.display = 'none';
            document.getElementById('noBookmarks').style.display = 'block';
            
        } else {
            for (var i = 0; i < videos.length; i++) {
                var links = videos[i].getElementsByTagName('a');
                if (links && links.length && hasClass(links[0], 'listSelected')) {
                    videoList.removeChild(videos[i]);
                    
                    videos = videoList.childNodes;
                    var selectIndex = (i < videos.length) ? i : i-1;
                    var selectLinks = videos[selectIndex].getElementsByTagName('a');
                    if (selectLinks && selectLinks.length) {
                        addClass(selectLinks[0], 'listSelected');
                        if (document.createEvent) {
                             var e = document.createEvent("HTMLEvents");
                             e.initEvent('click', true, true ); // event type, bubbling, cancelable
                             selectLinks[0].dispatchEvent(e);
                         } else {
                             var e = document.createEventObject(); // IE
                             selectLinks[0].fireEvent('onclick', e)
                         }
                         break;
                    }
                }
            }
        }
    }
}
