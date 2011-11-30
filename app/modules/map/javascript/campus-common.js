var browseGroups = {};

function sortGroupsByDistance() {
    if ('geolocation' in navigator) {
        navigator.geolocation.getCurrentPosition(function(location) {
            var navCategories = document.getElementById("categories").children;
            for (var i = 0; i < navCategories.length; i++) {
                var category = navCategories[i];
                var categoryId = category.getAttribute("class");
                browseGroups[categoryId] = category;
            }

            makeAPICall(
                'GET', 'map', 'sortGroupsByDistance',
                {"lat": location.coords.latitude, "lon": location.coords.longitude},
                function(response) {
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
            );
        },
        function() {},
        {maximumAge:3600000, timeout:5000});
    }
}

