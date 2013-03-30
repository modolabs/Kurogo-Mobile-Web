/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
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
    
    this.stories = getElementsByClassName('pane-story', this.element);
    
    this.pager = getFirstElementByClassName('pane-stories-pager', this.element);
    if (this.pager) {
        this.dots = getElementsByClassName('pane-stories-pager-dot', this.pager);
        this.prev = getFirstElementByClassName('pane-stories-pager-prev', this.pager);
        this.next = getFirstElementByClassName('pane-stories-pager-next', this.pager);
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
        if (!this.ellipsizer) {
            var elements = getElementsByClassName('ellipsis', this.element);
            this.ellipsizer = new ellipsizer({refreshOnResize: false});
            this.ellipsizer.addElements(elements);
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
