/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/* 
 * Ellipsizer text block module
 * 
 * Handles multiple elements for efficiency
 */
 
(function (window) {

function ellipsizer (options) {
    // set caller options
    if (typeof options == 'object') {
        for (var i in options) {
            this.options[i] = options[i];
        }
    }
    
    if (this.options.refreshOnResize) {
        // Bind the global event handler to the element
        if (window.addEventListener) {
            window.addEventListener(RESIZE_EVENT, this, false);
        
        } else if (window.attachEvent) {
            window.attachEvent(RESIZE_EVENT, this);
        }
    }
}

ellipsizer.prototype = {
    elements: [],
    options: {
        refreshOnResize: true,
        beforeRefresh: null,
        afterRefresh: null
    },
    
    addElement: function (element) {
        element.ellipsizer = {
            'original' : element.cloneNode(true)
        };
        
        refreshElement(element);
        
        this.elements.push(element);
    },
  
    addElements: function (elements) {
        for (var i = 0; i < elements.length; i++) {
            this.addElement(elements[i]);
        }
    },
  
    handleEvent: function (e) {
        switch (e.type) {
          case 'orientationchange':
          case 'resize':
              var self = this;
              setTimeout(function() { self.refresh(); }, 0);
              break;
        }
    },
  
    refresh: function () {
        if (this.options.beforeRefresh != null) {
            this.options.beforeRefresh(this.elements);
        }
        
        for (var i = 0; i < this.elements.length; i++) {
            refreshElement(this.elements[i]);
        }
        
        if (this.options.afterRefresh != null) {
            this.options.afterRefresh(this.elements);
        }
    },
    
    destroy: function () {
        if (this.options.refreshOnResize) {
            // Bind the global event handler to the element
            if (window.removeEventListener) {
                window.removeEventListener(RESIZE_EVENT, this, false);
            
            } else if (window.detachEvent) {
                window.detachEvent(RESIZE_EVENT, this);
            }
        }
        
        for (var i = 0; i < this.elements.length; i++) {
            this.elements[i].innerHTML = this.elements[i].originalInnerHTML;
        }
        
        return null;
    }
};

function cloneNodes(nodeList) {
    var children = [];
    for (var i = 0; i < nodeList.length; i++) {
        children.push(nodeList[i].cloneNode(true));
    }
    return children;
}

function setTextContent(element, content) {
    try {
        if ("innerText" in element) {
            element.innerText = content;
            
        } else if ("nodeValue" in element) {
            element.nodeValue = content;
            
        } else if ("textContent" in element) {
            element.textContent = content;
        }
    } catch (e) {
        // ignore errors here they may be caused by elements which cannot have
        // text (e.g. images)
    }
}
  
function getTextContent(element) {
    if (element.innerText) {
        return element.innerText;
        
    } else if (element.nodeValue) {
        return element.nodeValue;
        
    } else if (element.textContent) {
        return element.textContent;
        
    } else {
        return "";
    }
}

// returns true when the element needs to be entirely removed
function walkForTruncation(element, maxHeight, ellipsizedElement) {
    if (!ellipsizedElement) {
        ellipsizedElement = element; // first call
    }
    
    var killCount = 0;
    while (ellipsizedElement.offsetHeight > maxHeight) {
        if (killCount++ > 30) break;
        
        if (element.childNodes.length) {
            var lastElement = element.childNodes[element.childNodes.length-1];
            if (walkForTruncation(lastElement, maxHeight, ellipsizedElement)) {
                element.removeChild(lastElement);
            }
        } else {
            var text = getTextContent(element);
            
            // quick check to short circuit if the entire node needs to be removed
            setTextContent(element, "");
            if (element.offsetHeight > maxHeight) {
                return true;
            }
            
            var lastTestLoc = -1;
            var lower = 0;
            var upper = text.length;
            
            var killCount2 = 0;
            while (lower < upper) {
                if (killCount2++ > 30) break;
                
                var testLoc = Math.floor((lower + upper) / 2);
                if (testLoc == lastTestLoc) {
                    break
                } else {
                    lastTestLoc = testLoc;
                }
                
                // only append an ellipsis if we are showing some of the text
                var suffix = testLoc > 0 ? '...' : '';
                 
                setTextContent(element, text.substr(0, testLoc) + suffix);
                if (ellipsizedElement.offsetHeight > maxHeight) {
                    upper = testLoc;
                    
                } else if (ellipsizedElement.offsetHeight < maxHeight) {
                    lower = testLoc;
                    
                } else if (upper - lower > 1) {
                    lower = testLoc; // this works but try to fill out last line
                    
                } else {
                    upper = lower = testLoc; // found it!
                }
            }
            
            newText = getTextContent(element);
            return !newText.length
        }
    }
    return false;
}

// private function to refresh each element
function refreshElement(element) {
    // skip elements which have been removed from the DOM
    if (getCSSValue(element, 'display') == 'none') { return; }
    
    var maxHeight = element.offsetHeight;
    var width = getCSSWidth(element)+'px';

    // Create a copy of the element and put the full text in it
    // Let it grow so we can see how big it gets
    var copy = element.ellipsizer.original.cloneNode(true);
    copy.id = '__ellipsisCopy';
    setCSSValue(copy, 'visibility', 'hidden');
    setCSSValue(copy, 'display',    'block');
    //setCSSValue(copy, 'color',      'pink');
    setCSSValue(copy, 'position',   'absolute');
    setCSSValue(copy, 'top',        '0');
    setCSSValue(copy, 'left',       '0');
    setCSSValue(copy, 'bottom',     'auto');
    setCSSValue(copy, 'right',      'auto');
    setCSSValue(copy, 'overflow',   'visible');
    setCSSValue(copy, 'max-width',  'none');
    setCSSValue(copy, 'max-height', 'none');
    setCSSValue(copy, 'width',      width);
    setCSSValue(copy, 'height',     'auto');
    
    var parentPosition = getCSSValue(element.parentNode, 'position');
    if (parentPosition != 'absolute' && parentPosition != 'relative') {
        setCSSValue(element.parentNode, 'position', 'relative');
    }
    element.parentNode.appendChild(copy);
    
    // Binary search through lengths to see where the copy gets
    // bigger than the real div.  Clip at that length.
    
    walkForTruncation(copy, maxHeight);
    
    element.innerHTML = copy.innerHTML;
    copy.parentNode.removeChild(copy);
}

var RESIZE_EVENT = window.addEventListener ? 
    ('onorientationchange' in window ? 
      'orientationchange' :  // touch device
      'resize')              // desktop browser
    : ('onresize');          // IE
  
window.ellipsizer = ellipsizer;
})(window);
