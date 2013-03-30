/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

function scrollToTop() {
	scrollTo(0,0); 
}

function getCookie(name) {
    return kgoBridge.getCookie(name);
}

function setCookie(name, value, expireseconds) {
    kgoBridge.setCookie(name, value, expireseconds);
}

function redirectTo(page, args) {
    kgoBridge.redirectTo(page, args);
}

function redirectToModule(module, page, args) {
    kgoBridge.redirectToModule(module, page, args);
}


(function (window) {
    function kgoBridgeHandler(config) {
        if (typeof config == 'object') {
            for (var i in config) {
                this.config[i] = config[i];
            }
        }
        
        if (!this.config.events) {
            // desktop emulation mode does not provide cookies
            var pairs = document.cookie.split(";");
            this.config.cookies = {};
            for (var i = 0; i < pairs.length; i++) {
                var pair = pairs[i].split("=");
                if (pair.length == 2) {
                    var name = pair[0].replace(/^\s+|\s+$/g, "");
                    var value = pair[1].replace(/^\s+|\s+$/g, "");
                    this.config.cookies[name] = value;
                }
            }
        }
        for (var name in this.config.cookies) {
            this.config.cookies[name] = unescape(this.config.cookies[name]);
        }
        
        if (this.config.geolocation) {
            var that = this; // makes sure that "this" is the kgoBridge object inside the functions below
            navigator.geolocation.getCurrentPosition = function (successCallback, errorCallback, options) {
                that.geolocationGetCurrentPosition(successCallback, errorCallback, options);
            };
            navigator.geolocation.watchPosition = function (successCallback, errorCallback, options) {
                return that.geolocationWatchPosition(successCallback, errorCallback, options);
            };
            navigator.geolocation.clearWatch = function (watchId) {
                that.geolocationClearWatch(watchId);
            };
        }
    }
    
    kgoBridgeHandler.prototype = {
        config: {
            events: false,  // desktop browser simulation mode
            geolocation: false, // use native bridge for geolocation, not supported in all clients
            urlPrefix: "",
            ajaxArgs: "",
            module: "",
            page: "",
            pageArgs: "",
            ajaxContent: "", // if provided, use instead of ajax request
            timeout: 60,
            cookiePath: "/",
            cookies: {},
            localizedStrings: {}
        },
        callbacks : {},
        callbackIdCounter : 0,
        
        formTargetCounter : 0,
        
        // This code list is duplicated in iOS and Android code.  
        // Do not change existing codes!
        errorCodes : {
            KGOBridgeErrorAPINotSupported : 1,
            KGOBridgeErrorJSONConvertFailed : 2
        },
        
        // These url components are duplicated in iOS and Android code.  
        // Do not change existing strings!
        BRIDGE_LINK_INTERNAL : "kgobridge://link/",
        BRIDGE_LINK_EXTERNAL : "kgobridge://external/link?url=",
        BRIDGE_FORM_POST_PARAMS : "kgoBridgeFormPost=1",
        
        // ====================================================================
        // Bridge API
        // ====================================================================
        
        //
        // Page load
        //
        
        setConfig: function (config) {
            if (typeof config == 'object') {
                for (var i in config) {
                    this.config[i] = config[i];
                }
            }
        },
        
        initPage: function (params, statusCallback) {
            if (typeof statusCallback == "undefined") { statusCallback = null; }
            
            // notify native side
            this.nativeAPI("page", "init", params, statusCallback);
        },
        
        //
        // Errors
        //
        
        initPageError: function (httpStatus, title, message) {
            switch (httpStatus) {
                case 401:
                case 407:
                    title = this.localizedString("ERROR_HTTP_UNAUTHORIZED_REQUEST_TITLE");
                    message = this.localizedString("ERROR_HTTP_UNAUTHORIZED_REQUEST_MESSAGE");
                    break;
                case 408:
                    title = this.localizedString("ERROR_HTTP_CONNECTION_TIMEOUT_TITLE");
                    message = this.localizedString("ERROR_HTTP_CONNECTION_TIMEOUT_MESSAGE");
                    break;
                case 404:
                case 503:
                default:
                    title = this.localizedString("ERROR_HTTP_CONNECTION_FAILED_TITLE");
                    message = this.localizedString("ERROR_HTTP_CONNECTION_FAILED_MESSAGE");
                    break;
            }
            
            this.handleError("pageinit", httpStatus, title, message);
        },

        handleError: function (errorType, code, title, message) {
            if (typeof title   != "string") { title   = ""; }
            if (typeof message != "string") { message = ""; }
            
            this.nativeAPI("error", errorType, {
                "code"    : code, 
                "title"   : title, 
                "message" : message
            });
        },
        
        //
        // Dialogs
        //
        
        alert: function (message, responseCallback /* optional */) {
            var ok = this.localizedString("BUTTON_OK");

            this.alertDialog(message, null, ok, null, null, function (error, params) {
                if (typeof responseCallback != "undefined" && responseCallback && error !== null) {
                    responseCallback();
                }
            }, function (error, params) {
                if (typeof responseCallback != "undefined" && responseCallback) {
                    responseCallback();
                }
            });
        },
        
        confirm: function (question, responseCallback) {
            var ok = this.localizedString("BUTTON_OK");
            var cancel = this.localizedString("BUTTON_CANCEL");
            
            this.alertDialog(message, null, ok, cancel, null, function (error, params) {
                if (error !== null) {
                    responseCallback(false);
                }
            }, function (error, params) {
                // Return true when main button is pressed
                responseCallback(error === null && params["button"] == "main");
            });
        },
        
        shareDialog: function (buttonConfig) {
            var buttonTitles = [];
            var actionURLs = [];
            if ("mail" in buttonConfig) {
                buttonTitles.push(this.localizedString("SHARE_OPTION_EMAIL"));
                actionURLs.push(buttonConfig["mail"]);
            }
            if ("facebook" in buttonConfig) {
                buttonTitles.push(this.localizedString("SHARE_OPTION_FACEBOOK"));
                actionURLs.push(buttonConfig["facebook"]);
            }
            if ("twitter" in buttonConfig) {
                buttonTitles.push(this.localizedString("SHARE_OPTION_TWITTER"));
                actionURLs.push(buttonConfig["twitter"]);
            }
            
            var title = this.localizedString("SHARE_THIS_ITEM");
            var cancel = this.localizedString("BUTTON_CANCEL");
            
            var that = this;
            this.actionDialog(title, cancel, null, buttonTitles, null, function(error, params) {
                if ("button" in params && params["button"].indexOf('alternate') === 0) {
                    var index = +params["button"].substr(9);
                    if (index >= 0 && index < actionURLs.length) {
                        that.loadURL(actionURLs[index]);
                    }
                }
            });
        },
        
        alertDialog: function (title, message, 
                               cancelButtonTitle, mainButtonTitle, alternateButtonTitle, 
                               statusCallback, buttonCallback) {
            // required params
            var params = {
                "title" : title,
                "cancelButtonTitle" : cancelButtonTitle
            };
            
            // optional params
            if (typeof message == "string") {
                params["message"] = message;
            }
            if (typeof mainButtonTitle == "string") {
                params["mainButtonTitle"] = mainButtonTitle;
            }
            if (typeof alternateButtonTitle == "string") {
                params["alternateButtonTitle"] = alternateButtonTitle;
            }
            
            // optional callbacks
            if (typeof statusCallback == "undefined") { statusCallback = null; }
            
            var additionalCallbacks = [];
            if (typeof buttonCallback != "undefined") {
                additionalCallbacks.push({
                    "param"     : "buttonClickedCallback",
                    "callback"  : buttonCallback,
                    "repeating" : false
                });
            }
            
            this.nativeAPI("dialog", "alert", params, statusCallback, additionalCallbacks);
        },
        
        actionDialog: function (title, 
                                cancelButtonTitle, destructiveButtonTitle, alternateButtonTitles, 
                                statusCallback, buttonCallback) {
            // required params
            var params = {
                "title" : title,
                "cancelButtonTitle" : cancelButtonTitle
            };
            
            // optional params
            if (typeof destructiveButtonTitle == "string") {
                params["destructiveButtonTitle"] = destructiveButtonTitle;
            }
            if (typeof alternateButtonTitles != "undefined") {
                for (var i = 0; i < alternateButtonTitles.length; i++) {
                    params["alternateButtonTitle"+i] = alternateButtonTitles[i];
                }
            }
            
            // optional callbacks
            if (typeof statusCallback == "undefined") { statusCallback = null; }
            
            var additionalCallbacks = [];
            if (typeof buttonCallback != "undefined") {
                additionalCallbacks.push({
                    "param"     : "buttonClickedCallback",
                    "callback"  : buttonCallback,
                    "repeating" : false
                });
            }
            
            this.nativeAPI("dialog", "action", params, statusCallback, additionalCallbacks);
        },

        //
        // Geolocation
        //
        
        // Supported params for getCurrentPosition and watchPosition:
        // enableHighAccuracy – A boolean which indicates to the device 
        //           that you wish to obtain it’s most accurate readings 
        //           (this parameter may or may not make a difference, 
        //           depending on your hardware)
        // maximumAge – The maximum age (in milliseconds) of the reading 
        //           (this is appropriate as the device may cache readings 
        //           to save power and/or bandwidth)
        // timeout – The maximum time (in milliseconds) for which you are 
        //           prepared to allow the device to try to obtain a Geo 
        //           location
        _geolocationRegisterForPosition: function (type, successCallback, errorCallback, params) {
            var that = this;
            
            // Make sure all params are set
            var defaults = {
                enableHighAccuracy : false,
                maximumAge : 0,
                timeout : Infinity,
            };
            if (typeof params == "undefined") {
                params = {};
            }
            for (defaultParam in defaults) {
                if (typeof params[defaultParam] == "undefined") {
                    params[defaultParam] = defaults[defaultParam];
                }
            }
            
            var statusCallback = function (error, params) {
                if (error !== null && typeof errorCallback != "undefined") {
                    errorCallback(new PositionError(null, "Unable to initialize geolocation"));
                }
            };
            
            var eventHandlerCallback = function (error, params) {
                if (error === null && params && params.coords && params.timestamp) {
                    var timestamp = params.timestamp;
                    if (typeof timestamp == "string") {
                        timestamp = parseInt(timestamp);
                    }
                    successCallback(new Position(params.coords, timestamp));
                    
                } else if (typeof errorCallback != "undefined") {
                    if (error !== null) {
                        errorCallback(new PositionError(error.title, error.message));
                    } else {
                        errorCallback(new PositionError(null, "Invalid response from native bridge"));
                    }
                }
            };
            
            this.nativeAPI("geolocation", type, params, statusCallback, [{
                "param"     : "eventHandlerCallback",
                "callback"  : eventHandlerCallback,
                "repeating" : type == "watch" ? true : false
            }]);
            
            return this.callbackIdForCallback(eventHandlerCallback);
        },
        
        geolocationGetCurrentPosition: function (successCallback, errorCallback, params) {
            this._geolocationRegisterForPosition("get", successCallback, errorCallback, params);
        },
        
        geolocationWatchPosition: function (successCallback, errorCallback, params) {
            return this._geolocationRegisterForPosition("watch", successCallback, errorCallback, params);
        },
        
        geolocationClearWatch: function (watchID) {
            var params = {};
            
            if (typeof this.callbacks[watchID] !== "undefined") {
                var statusCallback = function (error, params) { }; // ignore errors
            
                this.nativeAPI("geolocation", "unwatch", params, statusCallback, [{
                    "param"     : "eventHandlerCallback",
                    "callback"  : this.callbacks[watchID]["callback"],
                    "repeating" : true,
                    "remove"    : true
                }]);
            }
        },
        
        //
        // Events
        //
        
        addEventListener: function (eventType, eventHandlerCallback, statusCallback) {
            var params = {
                "event" : eventType
            };
            
            this.nativeAPI("listener", "add", params, statusCallback, [{
                "param"     : "eventHandlerCallback",
                "callback"  : eventHandlerCallback,
                "repeating" : true
            }]);
        },
        
        removeEventListener: function (eventType, eventHandlerCallback, statusCallback) {
            var params = {
                "event" : eventType
            };
            
            this.nativeAPI("listener", "remove", params, statusCallback, [{
                "param"     : "eventHandlerCallback",
                "callback"  : eventHandlerCallback,
                "repeating" : true,
                "remove"    : true
            }]);
        },
        
        //
        // Cookies
        //
        
        getCookie: function (name) {
            return (name in this.config.cookies) ? this.config.cookies[name] : "";
        },
        
        setCookie: function (name, value, expireseconds) {
            var params = {
                "name"     : name,
                "value"    : escape(value),
                "duration" : expireseconds,
                "path"     : this.config.cookiePath
            };
            
            // set in cookies array so getCookie is consistent
            var oldCookieValue = (name in this.config.cookies) ? this.config.cookies[name] : null;
            this.config.cookies[name] = value;

            if (this.config.events) {
                // tell native side to set the cookie for us
                var that = this;
                this.nativeAPI("cookie", "set", params, function (error, params) {
                    if (error !== null) {
                        // reset to old value on error:
                        if (oldCookieValue !== null) {
                            that.config.cookies[name] = oldCookieValue;
                        } else {
                            delete that.config.cookies[name];
                        }
                    }
                });
            } else {
                // emulation mode, set cookie in js and remember in cookie object
                var expires = new Date();
                expires.setTime(expires.getTime() + (expireseconds * 1000));

                var cookie = name + "=" + escape(value) + 
                    (expireseconds == 0 ? "" : "; expires=" + expires.toGMTString()) + 
                    "; path=" + this.config.cookiePath;
                document.cookie = cookie;
                this.log("kgoBridge would have set cookie: '"+cookie+"'");
            }
        },
        
        // ====================================================================
        // Low level implementation
        // ====================================================================
        
        nativeAPI: function (category, type, params, statusCallback, additionalCallbacks) {
            var url = "kgobridge://"+escape(category)+"/"+escape(type);
            var paramStrings = [];
            if (typeof params == "object") {
                for (var key in params) {
                    paramStrings.push(escape(key)+"="+escape(params[key]));
                }
            }
            
            // status callback
            var that = this;
            var callbackId = this.callbackIdCounter++;
            this.callbacks[callbackId] = {
                "callback" : function (error, params) {
                    if (error !== null && "code" in error) {
                        var code = error["code"];
                        var title = "title" in error ? error["title"] : "Unknown Title";
                        var message = "message" in error ? error["message"] : "Unknown message";
                        
                        for (codeKey in that.errorCodes) {
                            if (that.errorCodes[codeKey] == code) {
                                code = codeKey;
                                break;
                            }
                        }
                        that.log("kgoBridge api returned error "+code+" ("+title+" : "+message+")");
                    }
                    if (typeof statusCallback != "undefined" && statusCallback) {
                        statusCallback(error, params);
                    }
                    if (error !== null && typeof additionalCallbacks != "undefined") {
                        // Remove other callbacks on error
                        for (var i = 0; i < additionalCallbacks.length; i++) {
                            if (typeof additionalCallbacks[i]["remove"] == "undefined" || !additionalCallbacks[i]["remove"]) {
                                var callbackId = that.callbackIdForCallback(additionalCallbacks[i]["callback"]);
                                if (callbackId) {
                                    delete that.callbacks[callbackId];
                                }
                            }
                        }
                    }
                },
                "repeating" : false
            };
            paramStrings.push("statusCallback="+callbackId);
            
            // additional callbacks
            if (typeof additionalCallbacks != "undefined") {
                for (var i = 0; i < additionalCallbacks.length; i++) {
                    if (typeof additionalCallbacks[i]["remove"] == "undefined" || !additionalCallbacks[i]["remove"]) {
                        // Adding a callback
                        var callbackId = this.callbackIdCounter++;
                        this.callbacks[callbackId] = {
                            "callback"  : additionalCallbacks[i]["callback"],
                            "repeating" : additionalCallbacks[i]["repeating"]
                        };
                        paramStrings.push(additionalCallbacks[i]["param"]+"="+callbackId);
                        
                    } else {
                        // Removing a callback
                        var callbackId = this.callbackIdForCallback(additionalCallbacks[i]["callback"]);
                        if (callbackId) {
                            paramStrings.push(additionalCallbacks[i]["param"]+"="+callbackId);
                            delete this.callbacks[callbackId];
                        }
                    }
                }
            }
            
            if (paramStrings.length) {
                url += "?"+paramStrings.join("&");
            }
            
            this.loadURL(url);
        },
        
        nativeAPICallback: function (callbackId, error, params) {
            if (callbackId in this.callbacks && this.callbacks[callbackId]) {
                if (typeof params !== "object") {
                    params = {};
                }
                
                // Callbacks frequently perform operations which will not work
                // at the time the native app sends the callback (alert, log, etc)
                // So delay the callback by 100ms to avoid these problems.
                var that = this;
                setTimeout(function () {
                    that.callbacks[callbackId]["callback"].call(that, error, params);
                    
                    if (!that.callbacks[callbackId]["repeating"]) {
                        delete that.callbacks[callbackId];
                    }
                }, 100);
            }
        },
        
        callbackIdForCallback: function (callback) {
            for (var callbackId in this.callbacks) {
                if (this.callbacks[callbackId]["callback"] === callback) {
                    return callbackId;
                }
            }
            return null;
        },
        
        loadURL: function (url) {
            var lcURL = url.toLowerCase();
            if (lcURL.indexOf("http://") == 0 || lcURL.indexOf("https://") == 0) {
                // wrap external URLs so that we don't get confused by other iframes
                url = this.BRIDGE_LINK_EXTERNAL+encodeURIComponent(url);
            }
            
            if (this.config.events) {
                var iframe = document.createElement("IFRAME");
                iframe.setAttribute("src", url);
                document.documentElement.appendChild(iframe);
                iframe.parentNode.removeChild(iframe);
                iframe = null;
            } else {
                this.log("kgoBridge would have called "+url);
            }
        },
        
        localizedString: function (key) {
            if (key in this.config.localizedStrings) {
                return this.config.localizedStrings[key];
            } else {
                return key;
            }
        },
        
        ajaxLoad: function () {
            var ajaxContainer = document.getElementById("container");
            
            var that = this;
            var onLoadSuccess = function () {
                // check for forms with kgobridge links and method="post"
                var forms = document.getElementsByTagName("form");
                if (forms) {
                    for (var i = 0; i < forms.length; i++) {
                        var action = forms[i].action;
                        var method = forms[i].method.toLowerCase();
                    
                        if (method.toLowerCase() === "post" && (action.indexOf(that.BRIDGE_LINK_INTERNAL) === 0 || 
                                                                (!that.config.events && 
                                                                 action.indexOf(that.config.urlPrefix) === 0))) {
                            that.log("found a form with method POST and action "+action);
                            
                            // get the full url
                            var url = that.bridgeToAjaxLink(action);
                            
                            // add special param so native side won't launch external browser
                            url += (url.indexOf("?") > 0 ? "&" : "?")+that.BRIDGE_FORM_POST_PARAMS;
                            
                            var iframeName = "form_bridge_"+that.formTargetCounter;
                            var iframeId = iframeName+"_iframe";
                            that.formTargetCounter++;
                            
                            // create an iframe to post the form to
                            var iframe = document.createElement("iframe");
                            iframe.name = iframeName;
                            iframe.id = iframeId;
                            iframe.setAttribute('frameborder', '0');
                            iframe.width = 0;
                            iframe.height = 0;
                            setCSSValue(iframe, 'border', 'none');
                            setCSSValue(iframe, 'width', '0px');
                            setCSSValue(iframe, 'height', '0px');
                            
                            forms[i].action = url;
                            forms[i].target = iframeName;
                            forms[i].parentNode.appendChild(iframe);
                            
                            var onLoad = function (e) {
                                if (window.kgoNativeBridge) {
                                    // Some OSes (Android) register a function for us to call
                                    var resultHTML = that.formPostGetResult(iframeId);
                                    if (resultHTML) {
                                        try {
                                            var success = window.kgoNativeBridge.handleFormPost(action, resultHTML);
                                            if (!success) {
                                                that.log("kgoNativeBridge.handleFormPost() form post failed");
                                            }
                                        } catch (e) {
                                            that.log("kgoNativeBridge.handleFormPost() native bridge failed");
                                        }
                                    }
                                    
                                } else {
                                    // Other OSes (iOS) call formPostGetResult when they gets this event
                                    var params = {
                                        "id"  : iframeId,
                                        "url" : action // original bridge url
                                    };
                                    that.nativeAPI("form", "post", params, function (error, params) {
                                        if (error !== null) {
                                            that.log("Form post failed with error '"+error+"'");
                                        }
                                    });
                                }
                            };
                            if (window.addEventListener) {
                                iframe.addEventListener("load", onLoad, true);
                            } else if (window.attachEvent) {
                                iframe.attachEvent("onload", onLoad);
                            }
                        }
                    }
                }
            };
            
            if (this.config.ajaxContent.length) {
                // native side already loaded content for us
                // this happens on form posts where method="POST"
                insertContentIntoContainer({
                    "container" : ajaxContainer,
                    "html"      : this.htmlEntityDecode(this.config.ajaxContent)
                });
                onLoadSuccess();
                
            } else {
                // load content via ajax
                var pageURL = this.config.urlPrefix + "/" + this.config.module + "/" + this.config.page + "?" + this.config.ajaxArgs;
                if (this.config.pageArgs.length) {
                    pageURL += "&" + this.config.pageArgs;
                }
                
                ajaxContentIntoContainer({
                    url: pageURL, 
                    container: ajaxContainer, 
                    timeout: this.config.timeout, 
                    error: this.initPageError,
                    success: onLoadSuccess
                });
            }
        },
        
        formPostGetResult: function (iframeId) {
            var iframe = document.getElementById(iframeId);
            if (iframe) {
                var frameContent = (iframe.contentDocument || iframe.contentWindow);
                var postResult = frameContent.documentElement.innerHTML;
                frameContent.documentElement.innerHTML = "";
                
                return this.htmlEntityEncode(postResult);
            }
            this.log("Attempt to get form post results from missing iframe '"+contentId+"'");
        },
        
        bridgeToAjaxLink: function (href) {
            // must be able to pass through non-kgobridge links
            var oldhref= href;
            if (href.indexOf(this.BRIDGE_LINK_INTERNAL) === 0) {
                href = this.config.urlPrefix + "/" + href.substr(this.BRIDGE_LINK_INTERNAL.length);
                
                var anchor = '';
                var anchorPos = href.indexOf("#");
                if (anchorPos > 0) {
                    anchor = href.substr(anchorPos);
                    href = href.substr(0, anchorPos);
                }
                href = href+(href.indexOf("?") > 0 ? "&" : "?")+this.config.ajaxArgs+anchor;
            }
            return href;
        },
        
        redirectTo: function (page, args) {
            this.redirectToModule(this.config.module, page, args);
        },
        
        redirectToModule: function (module, page, args) {
            var url = module + "/" + page + _getStringForArgs(args);
            this.loadURL(this.BRIDGE_LINK_INTERNAL + url);
            
            if (!this.config.events) {
                window.location = "../" + url; // use traditional redirect in emulation mode
            }
        },
        
        log: function (message) {
            if (this.config.events) {
                this.loadURL("kgobridge://console/log?message="+encodeURIComponent(message));
                
            } else if (typeof console != "undefined" && typeof console.log != "undefined") {
                console.log("KGO_LOG: "+message);
            }
        },
        
        // The following functions do enough HTML escaping for script blocks
        htmlEntityEncode: function (string) {
            return string.replace(/&/g, '&amp;')
                         .replace(/>/g, '&gt;')
                         .replace(/</g, '&lt;')
                         .replace(/"/g, '&quot;')
                         .replace(/'/g, '&#39;');
        },
        
        htmlEntityDecode: function (string) {
            return string.replace(/&#39;/g, "'")
                         .replace(/&quot;/g, '"')
                         .replace(/&lt;/g, '<')
                         .replace(/&gt;/g, '>')
                         .replace(/&amp;/g, '&');
        }
    };
    
    
    //
    // Fake geolocation Position object
    //
    function Coordinates(coords) {
        for (var d in coords) {
            this[d] = coords[d];
        }
    }
    Coordinates.prototype = {
        latitude         : 0,
        longitude        : 0,
        altitude         : null,
        accuracy         : 0,
        altitudeAccuracy : null,
        heading          : null,
        speed            : null
    };
    function Position(coords, timestamp) {
        this.coords = new Coordinates(coords);
        this.timestamp = timestamp ? new Date(timestamp) : new Date();
    };
    function PositionError(codeString, message) {
        this.code = (codeString == "PERMISSION_DENIED") ? 1 : 2;
        this.message = message || "";
    };
    PositionError.PERMISSION_DENIED = 1;
    PositionError.POSITION_UNAVAILABLE = 2;
    PositionError.TIMEOUT = 3;

    
    window.kgoBridgeHandler = kgoBridgeHandler;
})(window);
