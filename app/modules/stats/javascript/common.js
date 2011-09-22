function updateIntervalTab(interval) {
    document.getElementById('statsoptionscustom').className = interval;
    if (interval != 'custom') {
        return true;
    }
    
    var listitems = document.getElementById('intervalTabstrip').getElementsByTagName('li');
    for (var i=0; i<listitems.length; i++) {
        var listinterval = listitems[i].getAttribute('interval');

        if (listitems[i].className=='active') {
            listitems[i].className='';
        }
        
        if (listinterval==interval) {
            listitems[i].className = 'active';
        }
    }
    
    
    return false;
}