$(document).ready(function() {
    makeAPICall('admin','getsectiondata', { 'v':1,'type':'site', 'section':adminSection}, processAdminSectionData)
});

function processAdminSectionData(data) {
    $('#sectionTitle').html(data.title);
    var items = [];
    $.each(data.fields, function(i, data) {
        $("#adminFields").append(createFormFieldListItem(data));
    });
}
    
