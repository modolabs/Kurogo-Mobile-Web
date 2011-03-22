    
$(document).ready(function() {
    makeAPICall('admin','getsectiondata', { 'v':1,'type':'site', 'section':adminSection}, processAdminSectionData);
    
    $('#adminForm').submit(function(e) {
        alert("You didn't think I actually got it all done, did you?");
        return false;
    });
    
    $('nav ul li a').click(function(e) {
        var href = $(this).attr('href');
        if (href.indexOf('?section')>-1) {
            var qs = href.substr(href.indexOf('?')+1);
            var params = qs.split('&');
            for (key in params) {
                var param = params[key].split('=');
                if (param[0]=='section') {
                    if (adminSection != param[1]) {
                        adminSection = param[1];
                        $('nav ul li a[class=current]').attr('class','');
                        $(this).attr('class','current');
                        makeAPICall('admin','getsectiondata', { 'v':1,'type':'site', 'section':adminSection}, processAdminSectionData);
                    }
                }
            }

            return false;
        } else {
        }
    });
});

function processAdminSectionData(data) {
    $('#sectionTitle').html(data.title);
    $('#sectionDescription').html(data.description);
    var items = [];
    $("#adminFields").html('');
    $.each(data.fields, function(i, data) {
        $("#adminFields").append(createFormFieldListItem(data));
    });
}
    
