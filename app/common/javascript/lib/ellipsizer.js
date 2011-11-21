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
	  element.originalInnerHTML = element.innerHTML;
	  element.oldOffsetWidth = 0;
	  
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

// private helper functions
function getCSSValue(element, key) {
  if (window.getComputedStyle) {
    return document.defaultView.getComputedStyle(element, null).getPropertyValue(key);
      
  } else if (element.currentStyle) {
    if (key == 'float') { 
      key = 'styleFloat'; 
    } else {
      var re = /(\-([a-z]){1})/g; // hyphens to camel case
      if (re.test(key)) {
        key = key.replace(re, function () {
          return arguments[2].toUpperCase();
        });
      }
    }
    return element.currentStyle[key] ? element.currentStyle[key] : null;
  }
  return '';
}

function getCSSWidth(element) {
  return element.offsetWidth
    - parseFloat(getCSSValue(element, 'border-left-width')) 
    - parseFloat(getCSSValue(element, 'border-right-width'))
    - parseFloat(getCSSValue(element, 'padding-right'))
    - parseFloat(getCSSValue(element, 'padding-left'));
}

// private function to refresh each element
function refreshElement(element) {
  // skip elements which have been removed from the DOM
  if (getCSSValue(element, 'display') == 'none') { return; }
  
  element.oldOffsetWidth = element.offsetWidth;
  // Create a copy of the element and put the full text in it
  // Let it grow so we can see how big it gets
  var copy = element.cloneNode(true);
  copy.innerHTML = element.originalInnerHTML;
  copy.id += '_ellipsisCopy';
  copy.style['visibility'] = 'hidden';
  copy.style['position'] = 'absolute';
  copy.style['top'] = '0';
  copy.style['left'] = '0';
  copy.style['overflow'] = 'visible';
  copy.style['max-width'] = 'none';
  copy.style['max-height'] = 'none';
  copy.style['width'] = getCSSWidth(element)+'px';
  copy.style['height'] = 'auto';
  
  element.parentNode.style['position'] = 'relative';
  element.parentNode.appendChild(copy);
  
  // Binary search through lengths to see where the copy gets
  // bigger than the real div.  Clip at that length.
  // Cap at 20 tries so we can't infinite loop.
  var clipHeight = element.offsetHeight;

  if (copy.offsetHeight > clipHeight) {
    var lastNodeClose = element.originalInnerHTML.lastIndexOf('>');
    
    var lastTestLoc = -1;
    var lower = lastNodeClose > 0 ? lastNodeClose + 1 : 0;
    var upper = element.originalInnerHTML.length;
    var initialLower = lower; // If we clip here we don't want to append an ellipsis

    for (var i = 0; i < 20 && lower < upper; i++) {
      var testLoc = Math.floor((lower + upper) / 2);
      if (testLoc == lastTestLoc) {
        break;
      } else {
        lastTestLoc = testLoc;
      }
      
      // only append an ellipsis if we are showing some of the text
      var suffix = testLoc > initialLower ? '&hellip;' : '';
       
      copy.innerHTML = element.originalInnerHTML.substr(0, testLoc)+suffix;
      if (copy.offsetHeight > clipHeight) {
        upper = testLoc;
        
      } else if (copy.offsetHeight < clipHeight) {
        lower = testLoc;
        
      } else if (upper - lower > 1) {
        lower = testLoc; // this works but try to fill out last line
        
      } else {
        upper = lower = testLoc; // found it!
      }
    }   
  }
  
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
