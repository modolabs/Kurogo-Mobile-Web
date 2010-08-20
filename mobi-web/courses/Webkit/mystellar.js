function toggleMyStellar(objTrigger, classData) {
// Toggles the visible state of the object objTrigger on or off using CSS
	if(objTrigger.className=="ms_on") {
		objTrigger.className="ms_off";
		removeMyStellar(classData);
	} else if(objTrigger.className=="ms_off") {
		objTrigger.className="ms_on";
		addMyStellar(classData);
	}
}

function addMyStellar(classData) {
	var str = getMyStellar();
	if (str != "") {
		var arr = str.split(",");
	} else {
		var arr = new Array();
	}
	arr.push(classData.replace(/ /g, "+"));
	setMyStellar(arr.join());
}

function removeMyStellar(classData) {
	var arr = unescape(getMyStellar()).split(",");
	var arr2 = new Array();
	for (elt in arr) {
		if (!(arr[elt] == classData.replace(/ /g, "+")) && !(arr[elt] == "")) {
			arr2.push(arr[elt]);
		}
	}
	setMyStellar(arr2.join());
}

function getMyStellar() {
	name = "mystellar=";
	var arr = document.cookie.split(';');

	for(var i=0;i < arr.length;i++) {
		var c = arr[i];
		while (c.charAt(0)==' ') {
			c = c.substring(1,c.length);
		}
		if (c.indexOf(name) == 0) {
			return c.substring(name.length,c.length);
		}
	}

	return "";
}

function setMyStellar(myStellar) {
	var current_date = new Date();
	if (myStellar == "") {
		ex_date = new Date(current_date.getTime() - (24 * 60 * 60 * 1000));
	} else {
		ex_date = new Date(current_date.getTime() + (160 * 24 * 60 * 60 * 1000));
	}
	document.cookie= "mystellar=" + myStellar + ";expires=" + ex_date.toGMTString() + ";";
}