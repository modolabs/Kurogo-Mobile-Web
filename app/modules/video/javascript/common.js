/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

// Initialize the ellipsis event handlers
var ytplayer;
function setupVideosListing() {
    var videosEllipsizer = new ellipsizer();
    
    // cap at 100 divs to avoid overloading phone
    for (var i = 0; i < 100; i++) {
        var elem = document.getElementById('ellipsis_'+i);
        if (!elem) { break; }
        videosEllipsizer.addElement(elem);
    }
}

function onYouTubePlayerAPIReady() {
    ytplayer = new YT.Player('ytplayer', {
    });
}
