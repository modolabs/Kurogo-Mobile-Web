/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/* 
 * paneStories text block module
 * 
 * Handles multiple elements for efficiency
 */
 
(function (window) {

function paneStories (elementID) {
    this.element = document.getElementById(elementID);
    
    // find the elements in the pane DOM structure
    // currently not using getElementsByClassName for IE support
    for (var i = 0; i < this.element.childNodes.length; i++) {
        var elementChild = this.element.childNodes[i];
        
        if (hasClass(elementChild, 'pane-stories')) {
            this.stories = elementChild.childNodes;
            
        } else if (hasClass(elementChild, 'pane-stories-pager')) {
            this.pager = elementChild;
            
            for (var j = 0; j < this.pager.childNodes.length; j++) {
                var pagerChild = this.pager.childNodes[j];
                
                if (hasClass(pagerChild, 'pane-stories-pager-dots')) {
                    this.dots = pagerChild.childNodes;
                    
                } else if (hasClass(pagerChild, 'pane-stories-pager-prev')) {
                    this.prev = pagerChild;
                    
                } else if (hasClass(pagerChild, 'pane-stories-pager-next')) {
                    this.next = pagerChild;
                }
            }
        }
    }
    
    // set caller options
    if (typeof options == 'object') {
        for (var i in options) {
            this.options[i] = options[i];
        }
    }
}

paneStories.prototype = {
    element: null,
    stories: [],
    pager: null,
    dots: [],
    prev: null,
    next: null,
    ellipsizer: null,
    options: {
    },
	
	resizeHandler: function () {
        // set the size on the stories
        if (this.stories.length) {
            var storyClipHeight = getCSSHeight(this.element)
                - this.pager.offsetHeight
                - parseFloat(getCSSValue(this.stories[0], 'border-top-width')) 
                - parseFloat(getCSSValue(this.stories[0], 'border-bottom-width'))
                - parseFloat(getCSSValue(this.stories[0], 'padding-top'))
                - parseFloat(getCSSValue(this.stories[0], 'padding-bottom'))
                - parseFloat(getCSSValue(this.stories[0], 'margin-top'))
                - parseFloat(getCSSValue(this.stories[0], 'margin-bottom'));
              
            for (var i = 0; i < this.stories.length; i++) {
                this.stories[i].style.height = storyClipHeight+'px';
            }
        }
        
        if (!this.ellipsizer) {
            this.ellipsizer = new ellipsizer({refreshOnResize: false});
            this.ellipsizer.addElements(this.stories);
        } else {
            var that = this;
            setTimeout(function () {
                that.ellipsizer.refresh();
            }, 1);
        }
    },
    
    switchStory: function (elem, direction) {
        if (hasClass(elem, 'disabled')) { return false; }
        
        for (var i = 0; i < this.stories.length; i++) {
            if (hasClass(this.stories[i], 'current')) {
                var j = direction == 'next' ? i+1 : i-1;
                
                if (j >= 0 || j < this.stories.length) {
                    removeClass(this.stories[i], 'current');
                    addClass(this.stories[j], 'current');
                    
                    removeClass(this.dots[i], 'current');
                    addClass(this.dots[j], 'current');
                    
                    if (j == 0) {
                        addClass(this.prev, 'disabled');
                    } else {
                        removeClass(this.prev, 'disabled');
                    }
                    if (j == (this.stories.length-1)) {
                        addClass(this.next, 'disabled');
                    } else {
                        removeClass(this.next, 'disabled');
                    }
                    
                    this.ellipsizer.refresh();
                }
                
                break;
            }
        }
        
        return false;
    }
}

window.paneStories = paneStories;
})(window);
