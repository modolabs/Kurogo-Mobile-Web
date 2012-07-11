/*
 * Copyright © 2009 - 2010 Massachusetts Institute of Technology
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

// Global variables 
var arrUpdatedHomeArray = new Array;	// array that stores the updated order of icons
var arrInitialArray = new Array;	// array that stores the order of icons as when the page first loads

function init() {
  initializeHomeArray();
  var objBusybox = document.createElement("IMG");
  objBusybox.src = "../common/images/loading.gif";
  objBusybox.className = "busybox";
}

function toggle(objClick) {
  var module = getParentLI(objClick).id;
  if(objClick.checked) {
    // remove from active module list
    var newDisabledModules = [];
    for(var cnt=0; cnt < disabledModules.length; cnt++) {
      if(disabledModules[cnt] != module) {
        newDisabledModules.push(disabledModules[cnt]);
      }
    }
    disabledModules = newDisabledModules;
  } else {
    // add to disabled module list
    disabledModules.push(module);
  }
  sortDisabledModules();
  writeCookies();
}

function moveUp(objClick) {
// Moves an <li> element up in the <ul> or <ol> unless it's already at the top
  var objList = document.getElementById("homepageList");
  if(objList) {
    var objChildren = objList.getElementsByTagName("li");
    var objClickLI = getParentLI(objClick);
    var intClickIndex = -1;
    for(var i=0;i<objChildren.length;i++) {
      if(objChildren[i]==objClickLI) {
        intClickIndex = i;
        break;
      }
    }
    if(intClickIndex>0) {
      var objTemp = document.createElement("LI");
      objTemp = objChildren[intClickIndex];
      objList.removeChild(objChildren[intClickIndex]);
      objList.insertBefore(objTemp,objChildren[intClickIndex-1]);
      fadeUp(objTemp.id,0.5);
      fadeDown(arrUpdatedHomeArray[intClickIndex-1],0.5);
      //fadeToWhite(objTemp.id,"c6d6e6");
      if(intClickIndex==1) {
        //alert("Moving to top");
      }
      moveModuleUp(intClickIndex);
      writeCookies();
    }
  }
  parseHomeArray();
}
    
function moveDown(objClick) {
// Moves an <li> element down in the <ul> or <ol> unless it's already at the bottom
  var objList = document.getElementById("homepageList");
  if(objList) {
    var objChildren = objList.getElementsByTagName("li");
    var objClickLI = getParentLI(objClick);
    var intClickIndex = objChildren.length;
    for(var i=0;i<objChildren.length;i++) {
      if(objChildren[i]==objClickLI) {
        intClickIndex = i;
        break;
      }
    }
    if(intClickIndex<objChildren.length-1) {
      var objTemp = document.createElement("LI");
      objTemp = objChildren[intClickIndex];
      objList.removeChild(objChildren[intClickIndex]);
      objList.insertBefore(objTemp,objChildren[intClickIndex+1]);
      fadeDown(objTemp.id,0.5);
      fadeUp(arrUpdatedHomeArray[intClickIndex+1],0.5);
      //fadeToWhite(objTemp.id,"c6d6e6");
      if(intClickIndex==objChildren.length-2) {
        //alert("Moving to end");
      }
      moveModuleDown(intClickIndex);
      writeCookies();
    }
  }
  parseHomeArray();
}

function moveModuleUp(index) {
  swap(index, index-1);
  sortDisabledModules();
}

function moveModuleDown(index) {
  swap(index, index+1);
  sortDisabledModules();
}

function swap(index1, index2) {
  var module1 = modules[index1];
  var module2 = modules[index2];
  modules[index1] = module2;
  modules[index2] = module1;
}

function sortDisabledModules() {
  var newDisabledModules = [];
  for(var cnt=0; cnt < modules.length; cnt++) {
    if(isDisabledModule(modules[cnt])) {
      newDisabledModules.push(modules[cnt]);
    }
  }
  disabledModules = newDisabledModules;
}

function isDisabledModule(module) {
  for(var cnt=0; cnt < disabledModules.length; cnt++) {
    if(disabledModules[cnt] == module) {
      return true;
    }
  }
  return false;
}

function writeCookies() {
  // null means never expire
  setCookie(MODULE_ORDER_COOKIE, modules.join(), MODULE_ORDER_COOKIE_LIFESPAN, COOKIE_PATH);
  setCookie(DISABLED_MODULES_COOKIE, disabledModules.join(), MODULE_ORDER_COOKIE_LIFESPAN, COOKIE_PATH);
}

function fadeUp(whichObjID,whichOpac) {
  document.getElementById(whichObjID).style.opacity=whichOpac;
  document.getElementById(whichObjID).style.top=(60-(60*whichOpac))+"px";
  if (whichOpac < 1) {
    var newOpac = whichOpac + .21;
    setTimeout("fadeUp(\'" + whichObjID + "\'," + newOpac + ")", 32);
  } else {
    document.getElementById(whichObjID).style.opacity=1.00;
  document.getElementById(whichObjID).style.top="0";
  }
}
    
function fadeDown(whichObjID,whichOpac) {
  document.getElementById(whichObjID).style.opacity=whichOpac;
  document.getElementById(whichObjID).style.top=(0-(60-(60*whichOpac)))+"px";
  if (whichOpac < 1) {
    var newOpac = whichOpac + .21;
    setTimeout("fadeDown(\'" + whichObjID + "\'," + newOpac + ")", 32);
  } else {
    document.getElementById(whichObjID).style.opacity=1.00;
  document.getElementById(whichObjID).style.top="0";
  }
}
    
function getParentLI(whichObj) {
// Recursive function that returns whichObj's nearest parent that is of type <li>
  if(whichObj.parentNode.tagName.toLowerCase() == "li") {
    return whichObj.parentNode;
  } else {
    if(whichObj.parentNode) {
      return getParentLI(whichObj.parentNode);
    } else {
      return null;
    }
  }
}

function parseHomeArray() {
// Parses the homepage list and stores its current order in the global variable arrUpdatedHomeArray
  var objList = document.getElementById("homepageList");
  if(objList) {
    var objChildren = objList.getElementsByTagName("li");
    for(var i=0;i<objChildren.length;i++) {
      arrUpdatedHomeArray[i] = objChildren[i].id;
    }
  }
}

function initializeHomeArray() {
// Parses the initial homepage list and stores its  order in the global variable arrInitialArray
  var objList = document.getElementById("homepageList");
  if(objList) {
    var objChildren = objList.getElementsByTagName("li");
    for(var i=0;i<objChildren.length;i++) {
      arrInitialArray[i] = objChildren[i];
      arrUpdatedHomeArray[i] = objChildren[i].id;
    }
  }
}


