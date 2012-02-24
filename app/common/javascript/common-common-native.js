(function (window) {
    function kgoBridgeHandler(config) {
        if (typeof config == 'object') {
            for (var i in config) {
                this.config[i] = config[i];
            }
        }
    }
    
    kgoBridgeHandler.prototype = {
        config: {
            events: false,  // desktop browser simulation mode
            base: "",
            url: "",
            ajaxArgs: "",
            pagePage: "",
            pageArgs: "",
            serverURL: "",
            timeout: 60
        },
        
        ajaxLoad: function () {
            var pageURL = this.config.url+this.config.pagePath+"?"+this.config.ajaxArgs;
            if (this.config.pageArgs.length) {
                pageURL += "&"+this.config.pageArgs;
            }
            var timeout = this.config.timeout * 1000;
            
            var httpRequest = new XMLHttpRequest();
            httpRequest.open("GET", pageURL, true);
            
            var that = this;
            
            var requestTimer = setTimeout(function() {
                // some browsers set readyState to 4 on abort so remove handler first
                httpRequest.onreadystatechange = function() { };
                httpRequest.abort();
                
                that.onAjaxError(408); // http request timeout status code
            }, timeout);
            
            httpRequest.onreadystatechange = function() {
                // return if still in progress
                if (httpRequest.readyState != 4) { return; }
                
                // Got answer, don't abort
                clearTimeout(requestTimer);
                
                if (httpRequest.status == 200) {
                    // Success
                    var container = document.getElementById("container");
                    container.innerHTML = httpRequest.responseText;
                    
                    // Grab script tags and appendChild them so they get evaluated
                    var scripts = container.getElementsByTagName("script");
                    var count = scripts.length; // scripts.length will change as we add elements
                    
                    for (var i = 0; i < count; i++) {
                        var script = document.createElement("script");
                        script.type = "text/javascript";
                        script.text = scripts[i].text;
                        container.appendChild(script);
                    }
                    
                    if (typeof kgoBridgeOnAjaxLoad != 'undefined') {
                        kgoBridgeOnAjaxLoad();
                    } else {
                        console.log("Warning! onAjaxLoad is not defined by the page content");
                    }
                    
                } else {
                    // Error
                    that.onAjaxError(httpRequest.status);
                }
            }
            
            httpRequest.send(null);
        },
        
        onAjaxError: function (status) {
            this.triggerError("load", status);
        },
        
        onPageLoad: function (params) {
            this.triggerEvent("load", params);
        },
        
        triggerError: function (type, code) {
            this.triggerEvent("error", {"type" : type, "code" : code});
        },
        
        triggerEvent: function (type, params) {
            this.triggerNative("event", type, params);
        },
        
        triggerNative: function (category, type, params) {
            if (this.config.events) {
                var url = "kgobridge://"+escape(category)+"/"+escape(type);
                if (params) {
                    var paramStrings = [];
                    for (var key in params) {
                        paramStrings.push(escape(key)+"="+escape(params[key]));
                    }
                    if (paramStrings.length) {
                        url += "?"+paramStrings.join("&");
                    }
                }
                
                var iframe = document.createElement("IFRAME");
                iframe.setAttribute("src", url);
                document.documentElement.appendChild(iframe);
                iframe.parentNode.removeChild(iframe);
                iframe = null;
            }
        },
        
        bridgeToAjaxLink: function (href) {
            // must be able to pass through non-kgobridge links
            var bridgePrefix = "kgobridge://link/";
            var oldhref= href;
            if (href.indexOf(bridgePrefix) == 0) {
                href = this.config.url+"/"+href.substr(bridgePrefix.length);
                
                var anchor = '';
                var anchorPos = href.indexOf("#");
                if (anchorPos > 0) {
                    anchor = href.substr(anchorPos);
                    href = href.substr(0, anchorPos);
                }
                href = href+(href.indexOf("?") > 0 ? "&" : "?")+this.config.ajaxArgs+anchor;
            }
            return href;
        }
    };
    
    window.kgoBridgeHandler = kgoBridgeHandler;
})(window);
