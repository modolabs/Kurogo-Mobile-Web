//var modules = [];
var linksList;
//var moduleSelect;

function initAdmin() {
    document.getElementById('addLink').onclick = addLink;
    linksList = document.getElementById('admin_links');
//    moduleSelect = document.getElementById('addModuleID');
    initLinksList();
}

function initLinksList() {
    var items = linksList.getElementsByTagName('li');
    for (var i=0; i< items.length; i++) {
        var divs = items[i].getElementsByTagName('div');
        
        for (var j=0; j < divs.length; j++) {
            switch (divs[j].className)
            {
                case 'deletehandle':
                    divs[j].onclick = removeLink;
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

function removeLink()
{
    var parent = this.parentNode;
    if (parent.nodeName == 'LI') {
        parent.parentNode.removeChild(this.parentNode);
        initLinksList();
    }
}

function addLink()
{
    var section = getQuerystring('section');
    
    var item = document.createElement('li');
    var deleteHandle = document.createElement('div');
    deleteHandle.className = 'deletehandle';
    item.appendChild(deleteHandle);

    var label = document.createElement('label');
    label.innerHTML = 'Title';
    item.appendChild(label);

    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = '_type[moduleData][' + section +'][title][]';
    input.value='text';
    item.appendChild(input);

    var input = document.createElement('input');
    input.type = 'text';
    input.name = 'moduleData['+ section + '][title][]';
    item.appendChild(input);

    var label = document.createElement('label');
    label.innerHTML = 'URL';
    item.appendChild(label);

    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = '_type[moduleData][' + section +'][url][]';
    input.value='text';
    item.appendChild(input);

    var input = document.createElement('input');
    input.type = 'text';
    input.name = 'moduleData['+ section + '][url][]';
    item.appendChild(input);

    var label = document.createElement('label');
    label.innerHTML = 'Icon';
    item.appendChild(label);

    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = '_type[moduleData][' + section +'][icon][]';
    input.value='text';
    item.appendChild(input);

    var input = document.createElement('input');
    input.type = 'text';
    input.name = 'moduleData['+ section + '][icon][]';
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
    var items = linksList.getElementsByTagName('li');
    
    if (items.length==0) {
        linksList.appendChild(item);
    } else {
        linksList.insertBefore(item, items[0]);
    }
    initLinksList();
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
    
    initLinksList();
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
    
    initLinksList();
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

