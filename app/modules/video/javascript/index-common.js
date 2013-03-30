/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

function loadSection(select) {
    redirectTo("index", { "feed" : select.value });
}

function toggleSearch() {
    var categorySwitcher = document.getElementById("category-switcher");
    
    if (categorySwitcher.className == "search-mode") {
        categorySwitcher.className = "category-mode";
    } else {
        categorySwitcher.className = "search-mode";
        document.getElementById("search_terms").focus();
    }
    return false;
}

function submitenter(myfield, e) {
    var keycode;
    if (window.event) {
        keycode = window.event.keyCode;
        
    } else if (e) {
        keycode = e.keyCode;        
        
    } else {
        return true;
    }

    if (keycode == 13) {
       myfield.form.submit();
       return false;
       
    } else {
        return true;        
    }
}
