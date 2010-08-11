function loadCategory(select) {
    window.location = "./?category_id=" + select.value;
}

function toggleSearch() {
    var button = document.getElementById("toggle-search-button")
    var buttonText = button.innerHTML;

    if(buttonText == "Search") {
        document.getElementById("search-form").style.display = null;
        document.getElementById("category-form").style.display = "none";
        button.innerHTML = "Cancel";
    } else {
        document.getElementById("search-form").style.display = "none";
        document.getElementById("category-form").style.display = null;
        button.innerHTML = "Search";
    }
}