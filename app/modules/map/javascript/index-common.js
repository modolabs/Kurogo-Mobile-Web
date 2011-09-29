var browseGroups = {};
var apiURL;

function sortGroupsByDistance() {
    if ('geolocation' in navigator) {
        navigator.geolocation.getCurrentPosition(locateSucceeded, locateFailed, {maximumAge:3600000, timeout:5000});
    } else {
        errorCallback();
    }
}

function locateSucceeded(location) {
    var navCategories = document.getElementById("categories").children;
    for (var i = 0; i < navCategories.length; i++) {
        var category = navCategories[i];
        var categoryId = category.getAttribute("class");
        browseGroups[categoryId] = category;
    }

    var params = {"lat": location.coords.latitude, "lon": location.coords.longitude};
    apiRequest(apiURL + "/sortGroupsByDistance", params, sortSucceeded, sortFailed);
}

function locateFailed() {
    // do nothing; leave content as is
}

function sortSucceeded(response) {
    var sortedGroups = [];
    for (var i = 0; i < response.length; i++) {
        var id = response[i]["id"];
        if (id in browseGroups) {
            if ("distance" in response[i]) {
                browseGroups[id].innerHTML = browseGroups[id].innerHTML + "<div class=\"smallprint\">" + response[i]["distance"] + "</div>";
            }
            sortedGroups.push(browseGroups[id]);
        }
    }
    var navList = document.getElementById("categories");
    if (navList.children.length == sortedGroups.length) {
        while (navList.children.length > 0) {
            navList.removeChild(navList.children[0]);
        }
        for (var i = 0; i < sortedGroups.length; i++) {
            navList.appendChild(sortedGroups[i]);
        }
    }
}

function sortFailed(code, message) {
    // do nothing; leave content as is
}


