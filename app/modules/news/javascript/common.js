// Initalize the ellipsis event handlers
function setupNewsListing() {
    document.write('I see Paris');
    var newsEllipsizer = new ellipsizer();
    
    // cap at 100 divs to avoid overloading phone
    for (var i = 0; i < 100; i++) {
        var elem = document.getElementById('ellipsis_'+i);
        if (!elem) { break; }
        newsEllipsizer.addElement(elem);
    }
}
