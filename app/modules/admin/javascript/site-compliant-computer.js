var adminType='site';
$(document).ready(function() {
    
    makeAPICall('GET', 'admin','getconfigdata', { 'v':1,'type':'site','section':adminSection}, processAdminSectionData);
    
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
        }
        return false;
    });
});

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
    
