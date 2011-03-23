    
$(document).ready(function() {
    makeAPICall('GET', 'admin','getsitedata', { 'v':1,'section':adminSection}, processAdminSectionData);
    
    $('#adminForm').submit(function(e) {
        var params = { 'v':1, 'type':'site', 'section':'', 'data':{}};
        $.each($(this).serializeArray(), function(index,value) {
            switch (value.name) {
                case 'section':
                    params[value.name] = value.value;
                    break;
                default:
                    params.data[value.name] = value.value;
                    break;
            }
        });        
        
        makeAPICall('POST','admin','setconfigdata', params, function() { alert('Configuration saved') });
        return false;
    });
    
    $('nav a[section]').click(function(e) {
        var section = $(this).attr('section');
        if (adminSection != section) {
            adminSection = section;
            $('nav ul li a[class=current]').attr('class','');
            $(this).attr('class','current');
            makeAPICall('GET','admin','getsitedata', { 'v':1,'section':adminSection}, processAdminSectionData);
        }
        return false;
    });
});

function processAdminSectionData(data) {
    $('#sectionTitle').html(data.title);
    $('#sectionDescription').html(data.description);
    $('#section').val(data.section);
    $("#adminFields").html('');
    $.each(data.fields, function(i, data) {
        $("#adminFields").append(createFormFieldListItem(data));
    });
}
    
