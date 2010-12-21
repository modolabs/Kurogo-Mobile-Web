
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
    // add to active module list
    activeModules.push(module);
  } else {
    // remove from active module list
    var newActiveModules = [];
    for(var cnt=0; cnt < activeModules.length; cnt++) {
      if(activeModules[cnt] != module) {
        newActiveModules.push(activeModules[cnt]);
      }
    }
    activeModules = newActiveModules;
  }
  sortActiveModules();
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
  sortActiveModules();
}

function moveModuleDown(index) {
  swap(index, index+1);
  sortActiveModules();
}

function swap(index1, index2) {
  var module1 = modules[index1];
  var module2 = modules[index2];
  modules[index1] = module2;
  modules[index2] = module1;
}

function sortActiveModules() {
  var newActiveModules = [];
  for(var cnt=0; cnt < modules.length; cnt++) {
    if(isActiveModule(modules[cnt])) {
      newActiveModules.push(modules[cnt]);
    }
  }
  activeModules = newActiveModules;
}

function isActiveModule(module) {
  for(var cnt=0; cnt < activeModules.length; cnt++) {
    if(activeModules[cnt] == module) {
      return true;
    }
  }
  return false;
}

function writeCookies() {
  // null means never expire
  writeCookie("moduleorder", modules.join(), null, httpRoot);
  writeCookie("visiblemodules", activeModules.join(), null, httpRoot);
}

function writeCookie(name, value, expiredays, path) {
  var exdate=new Date();
  exdate.setDate(exdate.getDate()+expiredays);
  var exdateclause = (expiredays == null) ? "" : "; expires="+exdate.toGMTString();
  var pathclause = (path == null) ? "" : "; path="+path;
  document.cookie= name + "=" + value + exdateclause + pathclause;
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


