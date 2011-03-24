    
$(document).ready(function() {
    if (typeof moduleID != 'undefined') {
        makeAPICall('GET', 'admin','getmoduledata', { 'v':1,'module':moduleID}, processModuleData);
    }
    
    $('#adminForm').submit(function(e) {
        var params = { 'v':1, 'type':'module', 'module':moduleID, 'data':{}};
        $.each($(this).serializeArray(), function(index,value) {
            console.log('' + value.name + ' = ' + value.value);
            switch (value.name) {
                default:
                    params.data[value.name] = value.value;
                    break;
            }
        });        
        
        makeAPICall('POST','admin','setconfigdata', params, function() { alert('Configuration saved') });
        return false;
    });
    
});


    
function processModuleData(data) {
    $('#moduleDescription').html(data.description);
    $("#adminFields").html('');
    $.each(data, function(section, sectionData) {
        $.each(sectionData.fields, function(key, data) {
            data.section = section;
            $("#adminFields").append(createFormFieldListItem(key, data));
        });
    });
    
}
    
