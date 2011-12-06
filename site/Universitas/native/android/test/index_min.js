
/* common.js */

/* 1   */ var currentTab;
/* 2   */ 
/* 3   */ String.prototype.strip = function() {
/* 4   */     return this.replace(/^\s+/, '').replace(/\s+$/, '');
/* 5   */ }
/* 6   */ 
/* 7   */ function showTab(strID, objTrigger) {
/* 8   */ // Displays the tab with ID strID
/* 9   */ 	var objTab = document.getElementById(strID);
/* 10  */ 	if(objTab) {
/* 11  */ 		show(strID);
/* 12  */ 		if(currentTab && (currentTab != objTab)) {
/* 13  */ 			hide(currentTab.id);
/* 14  */ 			//currentTab.style.display = "none";
/* 15  */ 		}
/* 16  */ 	}
/* 17  */ 	currentTab = objTab; // Remember which is the currently displayed tab
/* 18  */ 	
/* 19  */ 	// Set the clicked tab to look current
/* 20  */ 	var objTabs = document.getElementById("tabs");
/* 21  */   if (objTabs) {
/* 22  */     var arrTabs = objTabs.getElementsByTagName("li");
/* 23  */     if(objTrigger) {
/* 24  */       for(var i=0; i<arrTabs.length; i++) {
/* 25  */         arrTabs[i].className="";
/* 26  */       }
/* 27  */       var objTriggerTab = objTrigger.parentNode;
/* 28  */       if(objTriggerTab) {
/* 29  */         objTriggerTab.className="active";
/* 30  */       }
/* 31  */     }
/* 32  */ 
/* 33  */     // fake resize event in case tab body was resized while hidden 
/* 34  */     if (document.createEvent) {
/* 35  */       var e = document.createEvent('HTMLEvents');
/* 36  */       e.initEvent('resize', true, true);
/* 37  */       window.dispatchEvent(e);
/* 38  */     
/* 39  */     } else if( document.createEventObject ) {
/* 40  */       var e = document.createEventObject();
/* 41  */       document.documentElement.fireEvent('onresize', e);
/* 42  */     }
/* 43  */   }
/* 44  */ 	
/* 45  */ 	onDOMChange();
/* 46  */ }
/* 47  */ 
/* 48  */ function rotateScreen() {
/* 49  */   setOrientation(getOrientation());
/* 50  */   setTimeout(scrollToTop, 500);

/* common.js */

/* 51  */ }
/* 52  */ 
/* 53  */ function getOrientation() {
/* 54  */     if (typeof getOrientation.orientationIsFlipped == 'undefined') {
/* 55  */         // detect how we are detecting orientation
/* 56  */         getOrientation.orientationIsFlipped = false;
/* 57  */         
/* 58  */         if (!('orientation' in window)) {
/* 59  */             getOrientation.orientationMethod = 'size';
/* 60  */         } else {
/* 61  */             getOrientation.orientationMethod = 'orientation';
/* 62  */             var width = document.documentElement.clientWidth || document.body.clientWidth;
/* 63  */             var height = document.documentElement.clientHeight || document.body.clientHeight;
/* 64  */             
/* 65  */             /* at this point the method of orientation detection is not perfect */
/* 66  */             if (navigator.userAgent.match(/(PlayBook.+RIM Tablet|Android 3\.\d)/)) {
/* 67  */                 getOrientation.orientationIsFlipped = true;
/* 68  */             }
/* 69  */         }
/* 70  */     }
/* 71  */ 
/* 72  */     switch (getOrientation.orientationMethod) {
/* 73  */         case 'size':
/* 74  */             var width = document.documentElement.clientWidth || document.body.clientWidth;
/* 75  */             var height = document.documentElement.clientHeight || document.body.clientHeight;
/* 76  */ 
/* 77  */             return (width > height) ? 'landscape' : 'portrait';
/* 78  */             break;
/* 79  */ 
/* 80  */         case 'orientation':
/* 81  */             switch (window.orientation) {
/* 82  */                 case 0:
/* 83  */                 case 180:
/* 84  */                     return getOrientation.orientationIsFlipped ? 'landscape' : 'portrait';
/* 85  */                     break;
/* 86  */                 
/* 87  */                 case 90:
/* 88  */                 case -90:
/* 89  */                     return getOrientation.orientationIsFlipped ? 'portrait': 'landscape';
/* 90  */                     break;
/* 91  */             }
/* 92  */     }
/* 93  */ }
/* 94  */ 
/* 95  */ function setOrientation(orientation) {
/* 96  */     var body = document.getElementsByTagName("body")[0];
/* 97  */  
/* 98  */  //remove existing portrait/landscape class if there
/* 99  */     removeClass(body, 'portrait');
/* 100 */     removeClass(body, 'landscape');

/* common.js */

/* 101 */     addClass(body, orientation);
/* 102 */ }
/* 103 */ 
/* 104 */ 
/* 105 */ function showLoadingMsg(strID) {
/* 106 */ // Show a temporary loading message in the element with ID strID
/* 107 */ 	var objToStuff = document.getElementById(strID);
/* 108 */ 	if(objToStuff) {
/* 109 */ 		objToStuff.innerHTML = "<div class=\"loading\"><img src=\"../common/images/loading.gif\" width=\"27\" height=\"21\" alt=\"\" align=\"absmiddle\" />Loading data...</div >";
/* 110 */ 	}
/* 111 */ 	onDOMChange();
/* 112 */ }
/* 113 */ 
/* 114 */ function hide(strID) {
/* 115 */ // Hides the object with ID strID 
/* 116 */ 	var objToHide = document.getElementById(strID);
/* 117 */ 	if(objToHide) {
/* 118 */ 		objToHide.style.display = "none";
/* 119 */ 	}
/* 120 */ 	
/* 121 */ 	onDOMChange();
/* 122 */ }
/* 123 */ 
/* 124 */ function show(strID) {
/* 125 */ // Displays the object with ID strID 
/* 126 */ 	var objToHide = document.getElementById(strID);
/* 127 */ 	if(objToHide) {
/* 128 */ 		objToHide.style.display = "block";
/* 129 */ 	}
/* 130 */ 	
/* 131 */ 	onDOMChange();
/* 132 */ }
/* 133 */ 
/* 134 */ function showHideFull(objContainer) {
/* 135 */ 	var strClass = objContainer.className;
/* 136 */ 	if(strClass.indexOf("collapsed") > -1) {
/* 137 */ 		strClass = strClass.replace("collapsed","expanded");
/* 138 */ 	} else {
/* 139 */ 		strClass = strClass.replace("expanded","collapsed");
/* 140 */ 	}
/* 141 */ 	objContainer.className = strClass;
/* 142 */ 	objContainer.blur();
/* 143 */ 	
/* 144 */ 	onDOMChange();
/* 145 */ }
/* 146 */ 
/* 147 */ function clearField(objField,strDefault) {
/* 148 */ // Clears the placeholder text in an input field if it matches the default string - fixes a bug in Android
/* 149 */ 	if((objField.value==strDefault) || (objField.value=="")) {
/* 150 */ 		objField.value="";

/* common.js */

/* 151 */ 	}
/* 152 */ }
/* 153 */ 
/* 154 */ // Android doesn't respond to onfocus="clearField(...)" until the 
/* 155 */ // input field loses focus
/* 156 */ function androidPlaceholderFix(searchbox) {
/* 157 */     // this forces the search box to display the empty string
/* 158 */     // instead of the place holder when the search box takes focus
/* 159 */     if (searchbox.value == "") {
/* 160 */         searchbox.value = "";
/* 161 */     }
/* 162 */ }
/* 163 */ 
/* 164 */ function getCookie(name) {
/* 165 */   var cookie = document.cookie;
/* 166 */   var result = "";
/* 167 */   var start = cookie.indexOf(name + "=");
/* 168 */   if (start > -1) {
/* 169 */     start += name.length + 1;
/* 170 */     var end = cookie.indexOf(";", start);
/* 171 */     if (end < 0) {
/* 172 */       end = cookie.length;
/* 173 */     }
/* 174 */     result = unescape(cookie.substring(start, end));
/* 175 */   }
/* 176 */   return result;
/* 177 */ }
/* 178 */ 
/* 179 */ function setCookie(name, value, expireseconds, path) {
/* 180 */   var exdate = new Date();
/* 181 */   exdate.setTime(exdate.getTime() + (expireseconds * 1000));
/* 182 */   var exdateclause = (expireseconds == 0) ? "" : "; expires=" + exdate.toGMTString();
/* 183 */   var pathclause = (path == null) ? "" : "; path=" + path;
/* 184 */   document.cookie = name + "=" + escape(value) + exdateclause + pathclause;
/* 185 */ }
/* 186 */ 
/* 187 */ function getCookieArrayValue(name) {
/* 188 */   var value = getCookie(name);
/* 189 */   if (value && value.length) {
/* 190 */     return value.split('@@');
/* 191 */   } else {
/* 192 */     return new Array();
/* 193 */   }
/* 194 */ }
/* 195 */ 
/* 196 */ function setCookieArrayValue(name, values, expireseconds, path) {
/* 197 */   var value = '';
/* 198 */   if (values && values.length) {
/* 199 */     value = values.join('@@');
/* 200 */   }

/* common.js */

/* 201 */   setCookie(name, value, expireseconds, path);
/* 202 */ }
/* 203 */ 
/* 204 */ function hasClass(ele,cls) {
/* 205 */     return ele.className.match(new RegExp('(\\s|^)'+cls+'(\\s|$)'));
/* 206 */ }
/* 207 */         
/* 208 */ function addClass(ele,cls) {
/* 209 */     if (!this.hasClass(ele,cls)) ele.className += " "+cls;
/* 210 */ }
/* 211 */ 
/* 212 */ function removeClass(ele,cls) {
/* 213 */     if (hasClass(ele,cls)) {
/* 214 */         var reg = new RegExp('(\\s|^)'+cls+'(\\s|$)');
/* 215 */         ele.className=ele.className.replace(reg,' ').strip();
/* 216 */     }
/* 217 */ }
/* 218 */         
/* 219 */ function toggleClass(ele, cls) {
/* 220 */     if (hasClass(ele, cls)) {
/* 221 */         removeClass(ele, cls);
/* 222 */     } else {
/* 223 */         addClass(ele, cls);
/* 224 */     }
/* 225 */ }
/* 226 */ 
/* 227 */ // Share-related functions
/* 228 */ function showShare() {
/* 229 */     if (!document.getElementById("sharesheet")) {
/* 230 */         return;
/* 231 */     }
/* 232 */ 	document.getElementById("sharesheet").style.display="block";
/* 233 */ 	var iframes = document.getElementsByTagName('iframe');
/* 234 */ 	for (var i=0; i<iframes.length; i++) {
/* 235 */ 	    iframes[i].style.visibility = 'hidden';
/* 236 */ 	}
/* 237 */ 	window.scrollTo(0,0);
/* 238 */ }
/* 239 */ function hideShare() {
/* 240 */     if (!document.getElementById("sharesheet")) {
/* 241 */         return;
/* 242 */     }
/* 243 */ 	document.getElementById("sharesheet").style.display="none";
/* 244 */ 	var iframes = document.getElementsByTagName('iframe');
/* 245 */ 	for (var i=0; i<iframes.length; i++) {
/* 246 */ 	    iframes[i].style.visibility = 'visible';
/* 247 */ 	}
/* 248 */ }
/* 249 */ 
/* 250 */ // Bookmarks

/* common.js */

/* 251 */ function toggleBookmark(name, item, expireseconds, path) {
/* 252 */   // facility for module to respond to bookmark state change
/* 253 */   if (typeof moduleBookmarkWillToggle != 'undefined') {
/* 254 */     $result = moduleBookmarkWillToggle(name, item, expireseconds, path);
/* 255 */     if ($result === false) { return; }
/* 256 */   }
/* 257 */ 
/* 258 */   var bookmark = document.getElementById("bookmark");
/* 259 */   toggleClass(bookmark, "on");
/* 260 */   var items = getCookieArrayValue(name);
/* 261 */   var newItems = new Array();
/* 262 */   if (items.length == 0) {
/* 263 */     newItems[0] = item;
/* 264 */   } else {
/* 265 */     var found = false;
/* 266 */     for (var i = 0; i < items.length; i++) {
/* 267 */       if (items[i] == item) {
/* 268 */         found = true;
/* 269 */       } else {
/* 270 */         newItems.push(items[i]);
/* 271 */       }
/* 272 */     }
/* 273 */     if (!found) {
/* 274 */       newItems.push(item);
/* 275 */     }
/* 276 */   }
/* 277 */   setCookieArrayValue(name, newItems, expireseconds, path);
/* 278 */   
/* 279 */   // facility for module to respond to bookmark state change
/* 280 */   if (typeof moduleBookmarkToggled != 'undefined') {
/* 281 */     moduleBookmarkToggled(name, item, expireseconds, path);
/* 282 */   }
/* 283 */ }
/* 284 */ 
/* 285 */ // TODO this needs to handle encoded strings and parameter separators (&amp;)
/* 286 */ function apiRequest(baseURL, params, successCallback, errorCallback) {
/* 287 */   var urlParts = [];
/* 288 */   for (var paramName in params) {
/* 289 */     urlParts.push(paramName + "=" + params[paramName]);
/* 290 */   }
/* 291 */   var url = baseURL + "?" + urlParts.join("&");
/* 292 */   var httpRequest = new XMLHttpRequest();
/* 293 */ 
/* 294 */   httpRequest.open("GET", url, true);
/* 295 */   httpRequest.onreadystatechange = function() {
/* 296 */     // TODO better definition of error conditions below
/* 297 */     if (httpRequest.readyState == 4 && httpRequest.status == 200) {
/* 298 */       var obj;
/* 299 */       if (window.JSON) {
/* 300 */           obj = JSON.parse(httpRequest.responseText);

/* common.js */

/* 301 */           // TODO: catch SyntaxError
/* 302 */       } else {
/* 303 */           obj = eval('(' + httpRequest.responseText + ')');
/* 304 */       }
/* 305 */       if (obj !== undefined) {
/* 306 */         if ("error" in obj && obj["error"] !== null) {
/* 307 */           errorCallback(0, obj["error"]);
/* 308 */         } else if ("response" in obj) {
/* 309 */           successCallback(obj["response"]);
/* 310 */         } else {
/* 311 */           errorCallback(1, "response not found");
/* 312 */         }
/* 313 */       } else {
/* 314 */         errorCallback(2, "failed to parse response");
/* 315 */       }
/* 316 */     }
/* 317 */   }
/* 318 */   httpRequest.send(null);
/* 319 */ }
/* 320 */ 
/* 321 */ 
/* 322 */ 
/* 323 */ 
/* 324 */ 
/* 325 */ 
/* 326 */ 
/* 327 */ 
/* 328 */ 
/* 329 */ 

;
/* native.js */

/* 1 */ function scrollToTop() {
/* 2 */ 	scrollTo(0,1); 
/* 3 */ }
/* 4 */ 
/* 5 */ function onDOMChange() {
/* 6 */   // Not needed for native
/* 7 */ }
/* 8 */ 

;
/* common.js */

/* 1  */ // Initalize the ellipsis event handlers
/* 2  */ var newsEllipsizer;
/* 3  */ function setupNewsListing() {
/* 4  */     newsEllipsizer = new ellipsizer();
/* 5  */     
/* 6  */     // cap at 100 divs to avoid overloading phone
/* 7  */     for (var i = 0; i < 100; i++) {
/* 8  */         var elem = document.getElementById('ellipsis_'+i);
/* 9  */         if (!elem) { break; }
/* 10 */         newsEllipsizer.addElement(elem);
/* 11 */     }
/* 12 */ }
/* 13 */ 

;
/* index-common.js */

/* 1  */ function loadSection(select) {
/* 2  */     window.location = "kgolink://test/index?section=" + select.value;
/* 3  */ }
/* 4  */ 
/* 5  */ function toggleSearch() {
/* 6  */     var categorySwitcher = document.getElementById("category-switcher");
/* 7  */     
/* 8  */     if (categorySwitcher.className == "search-mode") {
/* 9  */         categorySwitcher.className = "category-mode";
/* 10 */     } else {
/* 11 */         categorySwitcher.className = "search-mode";
/* 12 */         document.getElementById("search_terms").focus();
/* 13 */     }
/* 14 */     return false;
/* 15 */ }
/* 16 */ 
/* 17 */ function submitenter(myfield, e) {
/* 18 */     var keycode;
/* 19 */     if (window.event) {
/* 20 */         keycode = window.event.keyCode;
/* 21 */         
/* 22 */     } else if (e) {
/* 23 */         keycode = e.keyCode;        
/* 24 */         
/* 25 */     } else {
/* 26 */         return true;
/* 27 */     }
/* 28 */ 
/* 29 */     if (keycode == 13) {
/* 30 */        myfield.form.submit();
/* 31 */        return false;
/* 32 */        
/* 33 */     } else {
/* 34 */         return true;        
/* 35 */     }
/* 36 */ }
/* 37 */ 
