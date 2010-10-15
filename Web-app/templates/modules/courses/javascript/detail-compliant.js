function toggleMyClasses(objTrigger, classData) {
// Toggles the visible state of the object objTrigger on or off using CSS
	if(objTrigger.className=="ms_on") {
		objTrigger.className="ms_off";
		removeMyClasses(classData);
	} else if(objTrigger.className=="ms_off") {
		objTrigger.className="ms_on";
		addMyClasses(classData);
	}
}

function addMyClasses(classData) {
	var str = getMyClasses();
	if (str != "") {
		var arr = str.split(",");
	} else {
		var arr = new Array();
	}
	arr.push(classData.replace(/ /g, "+"));
	setMyClasses(arr.join());
}

function removeMyClasses(classData) {
	var arr = unescape(getMyClasses()).split(",");
	var arr2 = new Array();
	for (elt in arr) {
		if (!(arr[elt] == classData.replace(/ /g, "+")) && !(arr[elt] == "")) {
			arr2.push(arr[elt]);
		}
	}
	setMyClasses(arr2.join());
}

function getMyClasses() {
	name = MY_CLASSES_COOKIE+"=";
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

function setMyClasses(myClasses) {
	var current_date = new Date();
	if (myClasses == "") {
		ex_date = new Date(current_date.getTime() - (24 * 60 * 60 * 1000));
	} else {
		ex_date = new Date(current_date.getTime() + (160 * 24 * 60 * 60 * 1000));
	}
	document.cookie= MY_CLASSES_COOKIE+"="+ myClasses + ";expires=" + ex_date.toGMTString() + ";";
}