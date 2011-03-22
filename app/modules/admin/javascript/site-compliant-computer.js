$(document).ready(function() {
    makeAPICall('admin','getsectiondata', { 'v':1,'type':'site', 'section':adminSection}, processAdminSectionData)
    
    $('#adminForm').submit(function(e) {
        alert("You didn't think I actually got it all done, did you?");
        return false;
    });
});

function processAdminSectionData(data) {
    $('#sectionTitle').html(data.title);
    $('#sectionDescription').html(data.description);
    var items = [];
    $.each(data.fields, function(i, data) {
        $("#adminFields").append(createFormFieldListItem(data));
    });
}
    
