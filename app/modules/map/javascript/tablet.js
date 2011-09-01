

function setTabsHeight() {
    // Set the height of the tabs container to fill the browser window height
    var tc = document.getElementById("tabscontainer");
    if(tc) { tc.style.height=(getWindowHeight()-56) + "px" }
}