    
$(document).ready(function() {
    if (typeof moduleID != 'undefined') {
        makeAPICall('admin','getmoduledata', { 'v':1,'module':moduleID}, processModuleData);
    }
    
    $('#adminForm').submit(function(e) {
        alert("You didn't think I actually got it all done, did you?");
        return false;
    });
    
});


    
function processModuleData(data) {
    $('#moduleDescription').html(data.description);
    $("#adminFields").html('');
    $.each(data, function(section, sectionData) {
        $.each(sectionData.fields, function(i, data) {
            data.section = section;
            $("#adminFields").append(createFormFieldListItem(data));
        });
    });
    
}
    
