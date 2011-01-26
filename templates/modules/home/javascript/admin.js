var modules = [];
var moduleList;
var moduleSelect;

function initAdmin() {
    document.getElementById('addModule').onclick = addModule;
    moduleList = document.getElementById('module_order');
    moduleSelect = document.getElementById('addModuleID');
    initModuleList();
}

function initModuleList() {
    var items = moduleList.getElementsByTagName('li');
    for (var i=0; i< items.length; i++) {
        var divs = items[i].getElementsByTagName('div');
        
        for (var j=0; j < divs.length; j++) {
            switch (divs[j].className)
            {
                case 'deletehandle':
                    divs[j].onclick = removeModule;
                    break;
                case 'moveup':
                    divs[j].onclick = moveUp;
                    break;
                case 'movedown':
                    divs[j].onclick = moveDown;
                    break;
            }
        }
    }
}

function getQuerystring(key, default_)
{
  if (default_==null) default_=""; 
  key = key.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
  var regex = new RegExp("[\\?&]"+key+"=([^&#]*)");
  var qs = regex.exec(window.location.href);
  if(qs == null)
    return default_;
  else
    return qs[1];
}

function removeModule()
{
    var parent = this.parentNode;
    if (parent.nodeName == 'LI') {
        parent.parentNode.removeChild(this.parentNode);
        initModuleList();
    }
}

function addModule()
{
    var section = getQuerystring('section');
    var moduleID = moduleSelect.options[moduleSelect.selectedIndex].value;
    var moduleTitle = moduleSelect.options[moduleSelect.selectedIndex].text;
    if (!moduleID) {
        return false;
    }
    
    var item = document.createElement('li');
    var deleteHandle = document.createElement('div');
    deleteHandle.className = 'deletehandle';
    item.appendChild(deleteHandle);

    var label = document.createElement('label');
    label.innerHTML = moduleID;
    item.appendChild(label);

    var input = document.createElement('input');
    input.type = 'text';
    input.name = 'moduleData['+ section + ']['+moduleID+']';
    input.value = moduleTitle;
    item.appendChild(input);
    
    var span = document.createElement('span');
    span.className = 'movebuttons';
    var moveup = document.createElement('div');
    moveup.className = 'moveup';
    span.appendChild(moveup);
    var movedown = document.createElement('div');
    movedown.className = 'movedown';
    span.appendChild(movedown);

    item.appendChild(span);    
    moduleList.appendChild(item);
    initModuleList();
    return false;
}

function moveUp()
{  
    var parent = this.parentNode;
    while (parent.parentNode && parent.nodeName != 'LI') {
        parent = parent.parentNode;
    }
    
    if (parent.previousSibling) {
        parent.parentNode.insertBefore(parent, parent.previousSibling);
    }
    
    initModuleList();
}

function moveDown()
{
    var parent = this.parentNode;
    while (parent.parentNode && parent.nodeName != 'LI') {
        parent = parent.parentNode;
    }

    if (parent.nextSibling) {
        if (parent.nextSibling.nextSibling) {
            parent.parentNode.insertBefore(parent, parent.nextSibling.nextSibling);
        } else {
            var parentNode = parent.parentNode;
            parentNode.removeChild(parent);
            parentNode.appendChild(parent);
        }
    }
    
    initModuleList();
}

if (document.addEventListener) {
    var DOMContentLoaded = function() {
        document.removeEventListener( "DOMContentLoaded", DOMContentLoaded, false );
        initAdmin();
    };
    document.addEventListener( "DOMContentLoaded", DOMContentLoaded, false );

    // fallback
    window.addEventListener("load", initAdmin, false);

} else if (document.attachEvent) { // fallback to window.onload on IE
    window.attachEvent("onload", initAdmin);
}

