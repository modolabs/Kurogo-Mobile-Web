    
$(document).ready(function() {
    if (moduleID) {
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
    $.each(data, function(i, sectionData) {
        $.each(sectionData.fields, function(i, data) {
            $("#adminFields").append(createFormFieldListItem(data));
        });
    });
    
}
    
