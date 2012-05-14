function loadModulePages(modulePanes) {
    function loadModulePage(info) {
        var elem = document.getElementById(info['elementId']);
        if (!elem) { return; }
        
        var httpRequest = new XMLHttpRequest();
        httpRequest.open("GET", info['ajaxURL'], true);
        httpRequest.onreadystatechange = function() {
            if (httpRequest.readyState == 4 && httpRequest.status == 200) {
                var div = document.createElement("div");
                div.innerHTML = httpRequest.responseText;
                
                removeClass(elem, 'loading');
                elem.innerHTML = "";
                
                for (var i = 0; i < div.childNodes.length; i++) {
                    var node = div.childNodes[i].cloneNode(true);
                    var nodeName = node.nodeName;
                    
                    if (nodeName == "SCRIPT") {
                        document.body.appendChild(node);
                    } else if (nodeName == "STYLE") {
                        document.getElementsByTagName("head")[0].appendChild(node);
                    } else {
                        elem.appendChild(node);
                    }
                }
                
                onDOMChange();
                moduleHandleWindowResize();
            }
        }
        httpRequest.send(null);
    }
    
    for (var pane in modulePanes) {
        loadModulePage(modulePanes[pane]);
    }
}

var paneResizeHandlers = [];
function registerPaneResizeHandler(handler) {
    paneResizeHandlers.push(typeof handler == 'string' ? window[handler] : handler);
}

function callPaneResizeHandlers() {
    for (var i = 0; i < paneResizeHandlers.length; i++) {
        paneResizeHandlers[i]();
    }
}

function moduleHandleWindowResize() {
    var blocks = document.getElementById('fillscreen').childNodes;
    
    for (var i = 0; i < blocks.length; i++) {
        var blockborder = blocks[i].childNodes[0];
        if (!blockborder) { continue; }
          
        var clipHeight = getCSSHeight(blocks[i])
            - parseFloat(getCSSValue(blockborder, 'border-top-width')) 
            - parseFloat(getCSSValue(blockborder, 'border-bottom-width'))
            - parseFloat(getCSSValue(blockborder, 'padding-top'))
            - parseFloat(getCSSValue(blockborder, 'padding-bottom'))
            - parseFloat(getCSSValue(blockborder, 'margin-top'))
            - parseFloat(getCSSValue(blockborder, 'margin-bottom'));
        
        blockborder.style.height = clipHeight+'px';
        
        // If the block ends in a list, clip off items in the list so that 
        // we don't see partial items
        if (blockborder.childNodes.length < 2) { continue; }
        var blockheader = blockborder.childNodes[0];
        var blockcontent = blockborder.childNodes[1];
        
        // How big can the content be?
        var contentClipHeight = clipHeight 
            - blockheader.offsetHeight
            - parseFloat(getCSSValue(blockheader, 'margin-top'))
            - parseFloat(getCSSValue(blockheader, 'margin-bottom'))
            - parseFloat(getCSSValue(blockheader, 'border-top-width'))
            - parseFloat(getCSSValue(blockheader, 'border-bottom-width'))
            - parseFloat(getCSSValue(blockcontent, 'border-top-width')) 
            - parseFloat(getCSSValue(blockcontent, 'border-bottom-width'))
            - parseFloat(getCSSValue(blockcontent, 'padding-top'))
            - parseFloat(getCSSValue(blockcontent, 'padding-bottom'))
            - parseFloat(getCSSValue(blockcontent, 'margin-top'))
            - parseFloat(getCSSValue(blockcontent, 'margin-bottom'));
        
        if (!blockcontent.childNodes.length) { continue; }
        var last = blockcontent.childNodes[blockcontent.childNodes.length - 1];
        
        blockcontent.style.height = 'auto';
        
        if (last.nodeName == 'UL') {
            var listItems = last.childNodes;
            for (var j = 0; j < listItems.length; j++) {
                listItems[j].style.display = 'list-item'; // make all list items visible
            }
            
            var k = listItems.length - 1;
            while (getCSSHeight(blockcontent) > contentClipHeight) {
                listItems[k].style.display = 'none';
                if (--k < 0) { break; } // hid everything, stop
            }
        }
    
        blockcontent.style.height = contentClipHeight+'px'; // set block content height
    }
  
    callPaneResizeHandlers();
}
