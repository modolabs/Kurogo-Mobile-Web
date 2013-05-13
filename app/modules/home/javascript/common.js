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

function addFormNotEmptyValidator() {
    var forms = document.getElementsByTagName("form");
    /*
     * for all forms which can be submitted:
     * require at least one text input to be non-empty 
     * note we currently assume no <textarea>'s are used in the form
     */
    for (var j = 0; j < forms.length; j++){
        var form_var = forms[j];
        form_var.onsubmit = function() {
            var inputs = this.getElementsByTagName("input");
            for(var i = 0; i < inputs.length; i++) {
                if (inputs[i].type == "text") {
                    if (inputs[i].value.trim().length > 0) {
                        return true;
                    }     
                }
            }
        return false;
        }
    }


    //below could be used if we want separate responses
   /* if (typeof kgoBridge != "undefined" && "alertDialog" in kgoBridge) {
   *     kgoBridge.alertDialog("", "Search query is empty.", "OK");
   * } else {
   *     alert("Search query is empty.");
   * }
   */
    
}