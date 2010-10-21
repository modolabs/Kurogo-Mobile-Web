function toggleBar(strID1,strID2,strIDFocus) {
// In a toolbar that contains two elements, toggle the visibility from the object with strID1 to the one with ID strID2
	var obj1 = document.getElementById(strID1);
	var obj2 = document.getElementById(strID2);
	if(strIDFocus) { var objFocus = document.getElementById(strIDFocus); }
	if(obj1&&obj2) {
		obj1.style.display = "none";
		obj2.style.display = "block";
		if(objFocus) {
			objFocus.focus();
		}
	}
}

function switchSection(objSwitcher) {
// Switches to the category indicated by the value of the form object objSwitcher
// Obviously a placeholder; requires real switching code
	if(objSwitcher) {
		window.location.href="./?channel_id=" + objSwitcher.value;
	}
}