function loadCategory(select) {
    window.location = "./?category_id=" + select.value;
}

function toggleSearch() {
    var searchForm = document.getElementById("search-form");
    var categoryForm = document.getElementById("category-form");
    
    if(searchForm.style.display == "none") {
        searchForm.style.display = null;
        categoryForm.style.display = "none";
    } else {
        searchForm.style.display = "none";
        categoryForm.style.display = null;
    }
    return false;
}
