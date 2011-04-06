function loginFormSubmit() {
    document.getElementById('loginForm').submit();
    return false;
}

function toggleRememberMe() {
    var list = document.getElementById('indirectList');
    var items;
    if (list && (items = list.getElementsByTagName('a'))) {
        var value = document.getElementById('remember').checked ? 1 : 0;
        for (var i=0; i<items.length; i++) {
            var pos = items[i].href.lastIndexOf('remainLoggedIn=');
            if (pos) {
                items[i].href = items[i].href.substr(0,pos)+'remainLoggedIn='+value+items[i].href.substr(pos+16);
            }
        }
    }
}
