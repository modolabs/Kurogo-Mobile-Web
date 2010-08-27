function clipWithEllipsis(getElements) {

   function getCSSValue(elem, key) {
        if (window.getComputedStyle) {
            return document.defaultView.getComputedStyle(elem, null)
                    .getPropertyValue(key);
        } else if (elem.currentStyle) {
            return elem.currentStyle[key];
        }
        return '';
    }
    
    function getCSSWidth(elem) {
        return elem.offsetWidth
            - parseFloat(getCSSValue(elem, 'borderLeftWidth')) 
            - parseFloat(getCSSValue(elem, 'borderRightWidth'))
            - parseFloat(getCSSValue(elem, 'paddingRight'))
            - parseFloat(getCSSValue(elem, 'paddingLeft'))
            - parseFloat(getCSSValue(elem, 'marginRight'))
            - parseFloat(getCSSValue(elem, 'marginLeft'));
    }
    
    function clipIfNeeded (elem) { 
        // check for first call
        if (typeof elem.originalInnerHTML == 'undefined') {
            elem.originalInnerHTML = elem.innerHTML;
        }
        if (typeof elem.oldOffsetWidth == 'undefined') {
            elem.oldOffsetWidth = 0;
        }
        
        // Check to see if the element size changed... if not abort
        if (elem.offsetWidth == elem.oldOffsetWidth) { 
            return;  // no size change
        }
        elem.oldOffsetWidth = elem.offsetWidth;
        
        var fullText = elem.originalInnerHTML;
        var clipHeight = elem.offsetHeight;
        
        // Create a copy of the element and put the full text in it
        // Let it grow so we can see how big it gets
        var copy = elem.cloneNode(true);
        copy.innerHTML = fullText;
        copy.id += 'Copy';
        copy.style['visibility'] = 'hidden';
        copy.style['position'] = 'absolute';
        copy.style['top'] = '0';
        copy.style['left'] = '0';
        copy.style['overflow'] = 'visible';
        copy.style['max-width'] = 'none';
        copy.style['max-height'] = 'none';
        copy.style['width'] = getCSSWidth(elem)+'px';
        copy.style['height'] = 'auto';
        
        elem.parentElement.style['position'] = 'relative';
        elem.parentElement.appendChild(copy);
        
        // Binary search through lengths to see where the copy gets
        // bigger than the real div.  Clip at that length.
        // Cap at 20 tries so we can't infinite loop.
        if (copy.offsetHeight > clipHeight) {
            var lastTestLoc = -1;
            var lower = 0;
            var upper = fullText.length;

            for (var i = 0; i < 20 && lower < upper; i++) {
                var testLoc = Math.floor((lower + upper) / 2);
                if (testLoc == lastTestLoc) {
                    break;
                } else {
                    lastTestLoc = testLoc;
                }
                
                copy.innerHTML = fullText.substr(0, testLoc)+'&hellip;';
                if (copy.offsetHeight > clipHeight) {
                    upper = testLoc;
                } else if (copy.offsetHeight < clipHeight) {
                    lower = testLoc;
                } else {
                    // found it
                    lower = upper = testLoc;
                }
            }   
        }
        elem.innerHTML = copy.innerHTML;
        copy.parentElement.removeChild(copy);
    }
    
    function clipAllIfNeeded(elems) {
        for (var i = 0; i < elems.length; i++) {
            if (getCSSValue(elems[i], 'overflow') != 'hidden') { continue; } // won't clip
            clipIfNeeded(elems[i]);
        }
    }
    
    var ellipsisInit = function () {
        if (!document.body) {
            return setTimeout(ellipsisInit, 13); // No body yet, wait
        }
			
        if (typeof ellipsisInit.initialized == 'undefined') {
            ellipsisInit.initialized = true;
            var elems = getElements();
            
            // Initial clip
            clipAllIfNeeded(elems);
            
            // Bind the global event handler to the element
            if (window.addEventListener) {
                window.addEventListener('resize', function() { clipAllIfNeeded(elems); }, false );
            
            } else if (window.attachEvent) {
                window.attachEvent('onresize', function() { clipAllIfNeeded(elems); });
            }
        }
    }
    
    // Run ellipsis init on DOM load if possible
    if (document.addEventListener) {
        var DOMContentLoaded = function() {
            document.removeEventListener( "DOMContentLoaded", DOMContentLoaded, false );
            ellipsisInit();
        };
        document.addEventListener( "DOMContentLoaded", DOMContentLoaded, false );

        // fallback
        window.addEventListener("load", ellipsisInit, false);

	} else if (document.attachEvent) { // fallback to window.onload on IE
        window.attachEvent("onload", ellipsisInit);
    }
}
