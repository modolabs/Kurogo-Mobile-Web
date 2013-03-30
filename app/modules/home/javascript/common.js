/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

function runFederatedSearch(federatedSearchModules) {
    var federatedSearchEllipsizer = new ellipsizer();

    for (var i = 0; i < federatedSearchModules.length; i++) {
        var elem = document.getElementById(federatedSearchModules[i]['elementId']);
        if (elem) {
            (function (elem) {
                ajaxContentIntoContainer({
                    url: federatedSearchModules[i]['ajaxURL'], 
                    container: elem, 
                    timeout: 60,
                    success: function () {
                        console.log(elem.id);
                        setTimeout(function () {
                            var items = getElementsByClassName('ellipsis', elem);
                            for (var j = 0; j < items.length; j++) {
                                federatedSearchEllipsizer.addElement(items[j]);
                            }
                        }, 0);
                    }
                });
            })(elem);
        }
    }
}
