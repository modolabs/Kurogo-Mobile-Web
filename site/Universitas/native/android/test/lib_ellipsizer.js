
/* ellipsizer.js */

/* 1   */ /* 
/* 2   *|  * Ellipsizer text block module
/* 3   *|  * 
/* 4   *|  * Handles multiple elements for efficiency
/* 5   *|  */
/* 6   */  
/* 7   */ (function (window) {
/* 8   */ 
/* 9   */ function ellipsizer (options) {
/* 10  */   // set caller options
/* 11  */ 	if (typeof options == 'object') {
/* 12  */ 		for (var i in options) {
/* 13  */ 			this.options[i] = options[i];
/* 14  */ 		}
/* 15  */ 	}
/* 16  */   
/* 17  */   if (this.options.refreshOnResize) {
/* 18  */     // Bind the global event handler to the element
/* 19  */     if (window.addEventListener) {
/* 20  */       window.addEventListener(RESIZE_EVENT, this, false);
/* 21  */     
/* 22  */     } else if (window.attachEvent) {
/* 23  */       window.attachEvent(RESIZE_EVENT, this);
/* 24  */     }
/* 25  */   }
/* 26  */ }
/* 27  */ 
/* 28  */ ellipsizer.prototype = {
/* 29  */   elements: [],
/* 30  */   options: {
/* 31  */     refreshOnResize: true,
/* 32  */     beforeRefresh: null,
/* 33  */     afterRefresh: null
/* 34  */ 	},
/* 35  */ 	
/* 36  */ 	addElement: function (element) {
/* 37  */ 	  element.originalInnerHTML = element.innerHTML;
/* 38  */ 	  element.oldOffsetWidth = 0;
/* 39  */ 	  
/* 40  */ 	  refreshElement(element);
/* 41  */ 	  
/* 42  */ 	  this.elements.push(element);
/* 43  */ 	},
/* 44  */ 
/* 45  */ 	addElements: function (elements) {
/* 46  */ 	  for (var i = 0; i < elements.length; i++) {
/* 47  */ 	    this.addElement(elements[i]);
/* 48  */ 	  }
/* 49  */ 	},
/* 50  */ 

/* ellipsizer.js */

/* 51  */   handleEvent: function (e) {
/* 52  */     switch (e.type) {
/* 53  */       case 'orientationchange':
/* 54  */ 			case 'resize':
/* 55  */ 			    var self = this;
/* 56  */ 			    setTimeout(function() { self.refresh(); }, 0);
/* 57  */ 				break;
/* 58  */ 		}
/* 59  */   },
/* 60  */ 
/* 61  */   refresh: function () {
/* 62  */     
/* 63  */     if (this.options.beforeRefresh != null) {
/* 64  */       this.options.beforeRefresh(this.elements);
/* 65  */     }
/* 66  */     
/* 67  */     for (var i = 0; i < this.elements.length; i++) {
/* 68  */       refreshElement(this.elements[i]);
/* 69  */     }
/* 70  */     
/* 71  */     if (this.options.afterRefresh != null) {
/* 72  */       this.options.afterRefresh(this.elements);
/* 73  */     }
/* 74  */   },
/* 75  */   
/* 76  */   destroy: function () {
/* 77  */     if (this.options.refreshOnResize) {
/* 78  */       // Bind the global event handler to the element
/* 79  */       if (window.removeEventListener) {
/* 80  */         window.removeEventListener(RESIZE_EVENT, this, false);
/* 81  */       
/* 82  */       } else if (window.detachEvent) {
/* 83  */         window.detachEvent(RESIZE_EVENT, this);
/* 84  */       }
/* 85  */     }
/* 86  */     
/* 87  */     for (var i = 0; i < this.elements.length; i++) {
/* 88  */       this.elements[i].innerHTML = this.elements[i].originalInnerHTML;
/* 89  */     }
/* 90  */     
/* 91  */     return null;
/* 92  */   }
/* 93  */ };
/* 94  */ 
/* 95  */ // private helper functions
/* 96  */ function getCSSValue(element, key) {
/* 97  */   if (window.getComputedStyle) {
/* 98  */     return document.defaultView.getComputedStyle(element, null).getPropertyValue(key);
/* 99  */       
/* 100 */   } else if (element.currentStyle) {

/* ellipsizer.js */

/* 101 */     if (key == 'float') { 
/* 102 */       key = 'styleFloat'; 
/* 103 */     } else {
/* 104 */       var re = /(\-([a-z]){1})/g; // hyphens to camel case
/* 105 */       if (re.test(key)) {
/* 106 */         key = key.replace(re, function () {
/* 107 */           return arguments[2].toUpperCase();
/* 108 */         });
/* 109 */       }
/* 110 */     }
/* 111 */     return element.currentStyle[key] ? element.currentStyle[key] : null;
/* 112 */   }
/* 113 */   return '';
/* 114 */ }
/* 115 */ 
/* 116 */ function getCSSWidth(element) {
/* 117 */   return element.offsetWidth
/* 118 */     - parseFloat(getCSSValue(element, 'border-left-width')) 
/* 119 */     - parseFloat(getCSSValue(element, 'border-right-width'))
/* 120 */     - parseFloat(getCSSValue(element, 'padding-right'))
/* 121 */     - parseFloat(getCSSValue(element, 'padding-left'));
/* 122 */ }
/* 123 */ 
/* 124 */ // private function to refresh each element
/* 125 */ function refreshElement(element) {
/* 126 */   // skip elements which have been removed from the DOM
/* 127 */   if (getCSSValue(element, 'display') == 'none') { return; }
/* 128 */   
/* 129 */   element.oldOffsetWidth = element.offsetWidth;
/* 130 */   // Create a copy of the element and put the full text in it
/* 131 */   // Let it grow so we can see how big it gets
/* 132 */   var copy = element.cloneNode(true);
/* 133 */   copy.innerHTML = element.originalInnerHTML;
/* 134 */   copy.id += '_ellipsisCopy';
/* 135 */   copy.style['visibility'] = 'hidden';
/* 136 */   copy.style['position'] = 'absolute';
/* 137 */   copy.style['top'] = '0';
/* 138 */   copy.style['left'] = '0';
/* 139 */   copy.style['overflow'] = 'visible';
/* 140 */   copy.style['max-width'] = 'none';
/* 141 */   copy.style['max-height'] = 'none';
/* 142 */   copy.style['width'] = getCSSWidth(element)+'px';
/* 143 */   copy.style['height'] = 'auto';
/* 144 */   
/* 145 */   element.parentNode.style['position'] = 'relative';
/* 146 */   element.parentNode.appendChild(copy);
/* 147 */   
/* 148 */   // Binary search through lengths to see where the copy gets
/* 149 */   // bigger than the real div.  Clip at that length.
/* 150 */   // Cap at 20 tries so we can't infinite loop.

/* ellipsizer.js */

/* 151 */   var clipHeight = element.offsetHeight;
/* 152 */ 
/* 153 */   if (copy.offsetHeight > clipHeight) {
/* 154 */     var lastNodeClose = element.originalInnerHTML.lastIndexOf('>');
/* 155 */     
/* 156 */     var lastTestLoc = -1;
/* 157 */     var lower = lastNodeClose > 0 ? lastNodeClose + 1 : 0;
/* 158 */     var upper = element.originalInnerHTML.length;
/* 159 */     var initialLower = lower; // If we clip here we don't want to append an ellipsis
/* 160 */ 
/* 161 */     for (var i = 0; i < 20 && lower < upper; i++) {
/* 162 */       var testLoc = Math.floor((lower + upper) / 2);
/* 163 */       if (testLoc == lastTestLoc) {
/* 164 */         break;
/* 165 */       } else {
/* 166 */         lastTestLoc = testLoc;
/* 167 */       }
/* 168 */       
/* 169 */       // only append an ellipsis if we are showing some of the text
/* 170 */       var suffix = testLoc > initialLower ? '&hellip;' : '';
/* 171 */        
/* 172 */       copy.innerHTML = element.originalInnerHTML.substr(0, testLoc)+suffix;
/* 173 */       if (copy.offsetHeight > clipHeight) {
/* 174 */         upper = testLoc;
/* 175 */         
/* 176 */       } else if (copy.offsetHeight < clipHeight) {
/* 177 */         lower = testLoc;
/* 178 */         
/* 179 */       } else if (upper - lower > 1) {
/* 180 */         lower = testLoc; // this works but try to fill out last line
/* 181 */         
/* 182 */       } else {
/* 183 */         upper = lower = testLoc; // found it!
/* 184 */       }
/* 185 */     }   
/* 186 */   }
/* 187 */   
/* 188 */   element.innerHTML = copy.innerHTML;
/* 189 */   copy.parentNode.removeChild(copy);
/* 190 */ }
/* 191 */ 
/* 192 */ var RESIZE_EVENT = window.addEventListener ? 
/* 193 */   ('onorientationchange' in window ? 
/* 194 */     'orientationchange' :  // touch device
/* 195 */     'resize')              // desktop browser
/* 196 */   : ('onresize');          // IE
/* 197 */   
/* 198 */ window.ellipsizer = ellipsizer;
/* 199 */ })(window);
/* 200 */ 
