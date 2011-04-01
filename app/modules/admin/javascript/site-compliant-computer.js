var adminType='site';
var KUROGO_LOCAL_VERSION;
var KUROGO_CURRENT_VERSION;
$(document).ready(function() {
    
    makeAPICall('GET', 'admin','getconfigdata', { 'v':1,'type':'site','section':adminSection}, processAdminSectionData);
    
    if (adminSection=='setup') {
        checkVersion();
    }
    
    $('#adminForm').submit(function(e) {
        var params = { 'v':1, 'type':'site', 'section':adminSection, 'data':{}}

        params.data[adminSection] = {};
        $('#adminForm [section]').map(function() {
            if ($(this).attr('type')!='checkbox' || this.checked) {
                params.data[adminSection][$(this).attr('name')] = $(this).val();
            }
        });
        
        makeAPICall('POST','admin','setconfigdata', params, function() { showMessage('Configuration saved') });
        return false;
    });
    
    $('nav a[section]').click(function(e) {
        var section = $(this).attr('section');
        if (adminSection != section) {
            adminSection = section;
            $('nav ul li a[class=current]').attr('class','');
            $(this).attr('class','current');
            makeAPICall('GET','admin','getconfigdata', { 'v':1,'type':'site','section':adminSection}, processAdminSectionData);
            if (adminSection=='setup') {
                checkVersion();
            }
        }
        return false;
    });
});

function checkVersion() {
    makeAPICall('GET', 'admin','checkversion', { 'v':1}, processCheckVersion);
}

function processCheckVersion(data) {
    KUROGO_LOCAL_VERSION=data.local;
    KUROGO_CURRENT_VERSION = data.current;
    
    var li = $('<li />');
    li.append('<label>Kurogo Version</label>');
    if (KUROGO_LOCAL_VERSION != KUROGO_CURRENT_VERSION) {
        li.append('<div class="infotext">Your version of Kurogo is not the most recent version. The most recent version is <b>' + KUROGO_CURRENT_VERSION +'</b>. Your version is <b>' + KUROGO_LOCAL_VERSION + '</b>. Please visit <a href="http://modolabs.com/kurogo">http://modolabs.com/kurogo</a>.</div>');
    } else {
        li.append('<div class="infotext">Your version of Kurogo is up to date (' + KUROGO_LOCAL_VERSION +')</div>');
    }

    $('#adminFields').append(li);
}

function processAdminSectionData(data) {
    $('#sectionTitle').html(data.title);
    $('#sectionDescription').html(data.description);
    $('#section').val(data.section);
    $("#adminFields").html('');
    $.each(data.fields, function(key, data) {
        $.each(createFormFieldListItems(key, data), function(k,element) {
            $("#adminFields").append(element);
        });
    });
}
    
