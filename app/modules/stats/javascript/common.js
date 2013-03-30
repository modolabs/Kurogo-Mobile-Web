/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

function updateIntervalTab(interval) {
    document.getElementById('statsoptionscustom').className = interval;
    if (interval != 'custom') {
        return true;
    }
    
    var listitems = document.getElementById('intervalTabstrip').getElementsByTagName('li');
    for (var i=0; i<listitems.length; i++) {
        var listinterval = listitems[i].getAttribute('interval');

        if (listitems[i].className=='active') {
            listitems[i].className='';
        }
        
        if (listinterval==interval) {
            listitems[i].className = 'active';
        }
    }
    
    
    return false;
}
