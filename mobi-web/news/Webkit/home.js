function loadCategory(select) {
    window.location = "./?category_id=" + select.value;
}

function toggleSearch() {
    var searchForm = document.getElementById("search-form");
    var categoryForm = document.getElementById("category-form");
    
    if(searchForm.style.display == "none") {
        searchForm.style.display = null;
        categoryForm.style.display = "none";
        document.getElementById("search_terms").focus();
    } else {
        searchForm.style.display = "none";
        categoryForm.style.display = null;
    }
    return false;
}

// Initalize the ellipsis event handlers
clipWithEllipsis(function () {
    var elems = [];
    for (var i = 0; i < 100; i++) { // cap at 100 divs to avoid overloading phone
        var elem = document.getElementById('ellipsis_'+i);
        if (!elem) { break; }
        elems[i] = elem;
    }
    return elems;
});
