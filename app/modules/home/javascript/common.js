/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

function runFederatedSearch(federatedSearchModules) {
    function federatedSearch(elem, url) {
        var httpRequest = new XMLHttpRequest();
        httpRequest.open("GET", url, true);
        httpRequest.onreadystatechange = function() {
            if (httpRequest.readyState == 4 && httpRequest.status == 200) {
                elem.innerHTML = httpRequest.responseText;
                onDOMChange();
            }
        }
        httpRequest.send(null);
    }

    for (var i = 0; i < federatedSearchModules.length; i++) {
        var elem = document.getElementById(federatedSearchModules[i]['elementId']);
        if (elem) {
            federatedSearch(elem, federatedSearchModules[i]['ajaxURL']);
        }
    }
}
