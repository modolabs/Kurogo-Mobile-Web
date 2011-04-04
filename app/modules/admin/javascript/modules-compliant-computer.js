var adminType='module';    
$(document).ready(function() {
    $('#adminCancel').click(function(e) {
        window.location.reload();
    });
    
    if (typeof moduleID != 'undefined') {
        makeAPICall('GET', 'admin','getconfigsections', { 'v':1,'type':'module','module':moduleID}, processModuleSections);
        makeAPICall('GET', 'admin','getconfigdata', { 'v':1,'type':'module','module':moduleID,'section':adminSection}, processModuleData); 
    
        $('#adminForm').submit(function(e) {
            var params = { 'v':1, 'type':'module', 'module':moduleID, 'data':{}};
            var re;
            
            $('#adminForm [section]').map(function() { 
                var section = $(this).attr('section');
                if (typeof params.data[section] == 'undefined') {
                    params.data[section] = {};
                }

                if ( !this.disabled &&  (this.type !='checkbox' || this.checked)) {
                    if (re = $(this).attr('name').match(/(.*)\[(.*)\]/)) {
                        if (typeof params.data[section][re[1]]=='undefined') {
                            params.data[section][re[1]] = {}
                        }
                        params.data[section][re[1]][re[2]] = $(this).val();                    
                    } else {
                        params.data[section][$(this).attr('name')] = $(this).val();
                    }
                }
            });
                        
            makeAPICall('POST','admin','setconfigdata', params, function() { showMessage('Configuration saved'); 
               makeAPICall('GET', 'admin','getconfigdata', { 'v':1,'type':'module','module':moduleID,'section':adminSection}, processModuleData); 
            });
            return false;
        });
    } else {
        //overview
        $('#homescreen_layout .springboard').sortable({
            connectWith: ".springboard",
            opacity: 0.6,
            update: function() {
                updateModuleLayoutSections();
            }
        }).disableSelection();
        
        
        $('#adminForm.homescreen').submit(function(e) {
            var params = { 'v':1, 'data':{}};
            var re;
            
            $('#adminForm input[section]').map(function() { 
                var section = $(this).attr('section');
                if (section) {
                    if (typeof params.data[section] == 'undefined') {
                        params.data[section] = {};
                    }
    
                    params.data[section][$(this).attr('name')] = $(this).val();
                }
            });
  
            makeAPICall('POST','admin','setmodulelayout', params, function() { showMessage('Configuration saved') });
            return false;
        });
        
        $('#adminForm.overview').submit(function(e) {
            var params = { 'v':1, 'type':'module', 'section':'overview', 'data':{}};
            var re;
            var formValues = {};
            $.each($(this).serializeArray(), function(index,value) {
                if (re = value.name.match(/(.*)\[(.*)\]/)) {
                    if (typeof params.data[re[1]]=='undefined') {
                        params.data[re[1]] = {}
                    }
                    params.data[re[1]][re[2]] = $(this).val();                    
                }
            });

            makeAPICall('POST','admin','setconfigdata', params, function() { showMessage('Configuration saved') });
            return false;
        });
    }
    
});

function updateModuleLayoutSections() {
    $('.springboard input').each(function(i) { $(this).attr('section', $(this).parents('.springboard').attr('section')) });
}

function processModuleSections(data) {
    $("#moduleSections").html('');
    $.each(data, function(section, sectionTitle) {
        var li = $('<li />').append('<a href="?module='+moduleID+'&section='+section+'">'+sectionTitle+'</a>').click(function() {
            $('#moduleSections .selected').removeClass('selected');
            adminSection=section;
            $(this).addClass('selected');
            makeAPICall('GET', 'admin','getconfigdata', { 'v':1,'type':'module','module':moduleID,'section':adminSection}, processModuleData); 
            return false;
        });
        if (section==adminSection) {
            li.addClass('selected');
        }
        $("#moduleSections").append(li);
    });
}
    
function processModuleData(data) {
    $('#moduleDescription').html(data.description);
    $("#adminFields").html('');
    $.each(createFormSectionListItems(data.section, data), function(k,element) {
        $("#adminFields").append(element);
    });
}
    
