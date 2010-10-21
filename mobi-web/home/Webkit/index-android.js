function blinkemergency() {
	var objBlink = document.getElementById("emergencyicon");
        if(objBlink.src=="Webkit/images/emergency-on.png") {
               	objBlink.src="Webkit/images/emergency-off.png";
	} else {
		objBlink.src="Webkit/images/emergency-on.png";
	}
	setTimeout(blinkemergency,750);
}

<? $extra_onload = "blinkemergency();" ?>
