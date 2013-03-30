/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

var adminType='module';    
$(document).ready(function() {
    $('#adminCancel').click(function(e) {
        window.location.reload();
    });
    
    if (typeof moduleID != 'undefined') {
       reloadSection();
    
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
            
            $('#adminForm .sectionorder').map(function() { 
                if (!('sectionorder' in params)) {
                    params.sectionorder = {}
                }
                if (re = $(this).attr('name').match(/sectionorder\[(.*?)\]/)) {
                    if (!(re[1] in params.sectionorder)) {
                        params.sectionorder[re[1]] = [];
                    }
                    params.sectionorder[re[1]].push($(this).val());
                }
            });

            makeAPICall('POST','admin','setconfigdata', params, function() { 
                showMessage(getLocalizedString('CONFIG_SAVED'));
                reloadSection();

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

        $('#addNewModule').click(function(e) {
            e.preventDefault();
            var params = { 'v':1 }
            
            params.newModule = {
                title: $('#newModuleTitle').val(),
                id: $('#newModuleID').val(),
                config: $('#newModuleConfig').val(),
                disabled: $('#newModuleDisabled').checked ? 1 : 0,
                secure: $('#newModuleSecure').checked ? 1 : 0,
                search: $('#newModuleSearch').checked ? 1 : 0
            }
            
            makeAPICall('POST','admin','addNewModule', params, function() { window.location = 'modules?module=' + params.newModule.config});
            return false;
        });
        
        $('.removeModule').click(function(e) {
            e.preventDefault();
            var re;
            var params = { 'v':1 }
            if (re = e.currentTarget.id.match(/removeModule_(.*)/)) {
                params.configModule = re[1];
                if (confirm('Are you sure you want to remove all configuration for ' + re[1] + '? This will make this module completely unavailable.')) {
                    makeAPICall('POST','admin','removeModule', params, function() { window.location.reload()});
                }
            }
        });
    
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
  
            makeAPICall('POST','admin','setmodulelayout', params, function() { showMessage(getLocalizedString('CONFIG_SAVED')) });
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

            makeAPICall('POST','admin','setconfigdata', params, function() { showMessage(getLocalizedString('CONFIG_SAVED')) });
            return false;
        });
    }
    
});

function updateModuleLayoutSections() {
    $('.springboard input').each(function(i) { $(this).attr('section', $(this).parents('.springboard').attr('section')) });
}

function selectSection(section) {
    adminSection=section;
    reloadSection();
}

function reloadSection() {
    makeAPICall('GET', 'admin','getconfigsections', { 'v':1,'type':'module','module':moduleID}, processModuleSections);
    makeAPICall('GET', 'admin','getconfigdata', { 'v':1,'type':'module','module':moduleID,'section':adminSection}, processModuleData); 
}

function processModuleSections(data) {
    $("#adminSections").html('');
    $.each(data, function(section, sectionData) {
        var li = $('<li />').append('<a href="?module='+moduleID+'&section='+section+'">'+sectionData.title+'</a>').addClass(sectionData.type).click(function() {
            $('#adminSections .selected').removeClass('selected');
            $(this).addClass('selected');
            selectSection(section);
            return false;
        });
        if (section==adminSection) {
            li.addClass('selected');
        }
        $("#adminSections").append(li);
    });
}
    
function processModuleData(data) {
    $('#moduleDescription').html(data.description);
    $("#adminFields").html('');
    $.each(createFormSectionListItems(data.section, data), function(k,element) {
        $("#adminFields").append(element);
    });
}
    
