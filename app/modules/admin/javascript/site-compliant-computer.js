    
$(document).ready(function() {
    makeAPICall('admin','getsitedata', { 'v':1,'section':adminSection}, processAdminSectionData);
    
    $('#adminForm').submit(function(e) {
        alert("You didn't think I actually got it all done, did you?");
        return false;
    });
    
    $('nav a[section]').click(function(e) {
        var section = $(this).attr('section');
        if (adminSection != section) {
            adminSection = section;
            $('nav ul li a[class=current]').attr('class','');
            $(this).attr('class','current');
            makeAPICall('admin','getsitedata', { 'v':1,'section':adminSection}, processAdminSectionData);
        }
        return false;
    });
});

function processAdminSectionData(data) {
    $('#sectionTitle').html(data.title);
    $('#sectionDescription').html(data.description);
    $("#adminFields").html('');
    $.each(data.fields, function(i, data) {
        $("#adminFields").append(createFormFieldListItem(data));
    });
}
    
