/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

var adminType='site';
var KUROGO_LOCAL_VERSION;
var KUROGO_CURRENT_VERSION;
$(document).ready(function() {

    getLocalizedString(['KUROGO_VERSION_TITLE']);
    reloadSection();    
    
    $('#adminForm').submit(function(e) {
        var form = this;
        var params = { 'v':1, 'type':'site', 'section':adminSection, 'data':{}}
        var re;

        params.data[adminSection] = {};
        
        var data = {};
        var uploads = false;
        
        $('#adminForm [section]').map(function() {
            if ( !this.disabled &&  (this.type !='checkbox' || this.checked) && (this.type !='file')) {

                if (re = $(this).attr('name').match(/(.*)\[(.*)\]/)) {
                    if (typeof data[re[1]]=='undefined') {
                        data[re[1]] = {}
                    }
                    data[re[1]][re[2]] = $(this).val();                    
                } else {
                    data[$(this).attr('name')] = $(this).val();
                }
            }

            if (this.type == 'file' && this.value) {
                uploads = true;
            }
        });

        if (adminSubsection) {
            params.subsection = adminSubsection;
            params.data[adminSection][adminSubsection] = data;
        } else {
            params.data[adminSection] = data;
        }
        
        if (uploads) {
            $('#uploadFrame').remove();
            var iframeName = 'uploadFrame';
            var iframe = $('<iframe id="uploadFrame" name="' + iframeName + '" style="position:absolute;top:-9999px" />').appendTo('body');
            var oldaction = form.action;
            var oldtarget = form.target;

            iframe.load(function() {
                form.action = oldaction;
                form.target = oldtarget;
                try {
                    var data = $.parseJSON($(this).contents().text());
                    if (data.error) {
                        showMessage(data.error.message, true, 0);
                        return;
                    }
                    
                    makeAPICall('POST','admin','setconfigdata', params, function() { 
                        showMessage(getLocalizedString('CONFIG_SAVED'));
                        reloadSection();
                    });
                } catch (e) {
                    showMessage("Error uploading file", true, 0);
                    return;
                }
            });
            
            form.action = URL_BASE + 'rest/admin/upload';
            form.target = iframeName;
            showMessage('Uploading...', false, 0);
            return true;
        } else {
            makeAPICall('POST','admin','setconfigdata', params, function() { 
                showMessage(getLocalizedString('CONFIG_SAVED'));
                reloadSection();
            });
        
            e.preventDefault();        
            return false;
        }
        
    });
    
    $('nav a[section]').click(function(e) {
        var section = $(this).attr('section');
        if (adminSection != section) {
            adminSection = section;
            adminSubsection = '';
            $('nav ul li a[class=current]').attr('class','');
            $(this).attr('class','current');
            reloadSection();
        }
        return false;
    });
});

function checkVersion() {
    makeAPICall('GET', 'admin','checkversion', { 'v':1}, processCheckVersion);
}

function processCheckVersion(data) {
    KUROGO_LOCAL_VERSION = data.local;
    KUROGO_CURRENT_VERSION = data.current;
    
    var li = $('<div />');
    li.append('<label>'+ getLocalizedString('KUROGO_VERSION_TITLE') +'</label>');
    li.append('<div class="infotext'+ (!data.uptodate ? ' error':'')+'">' + data.message + '</div>');

    $('#adminFields').append(li);
}

function reloadSection() {
    makeAPICall('GET','admin','getconfigdata', { 'v':1,'type':'site','section':adminSection}, processAdminSectionData);
}

function selectSubsection(subsection, subsectionData) {
    $('#sectionDescription').html(subsectionData.description);
    $("#adminFields").html('');
    $.each(createFormSectionListItems(subsectionData.section, subsectionData), function(k,element) {
        $("#adminFields").append(element);
    });
    adminSubsection = subsection;
}

function processAdminSectionData(data) {
    $('#sectionTitle').html(data.title);
    $('#sectionDescription').html(data.description);
    $('#section').val(data.section);
    $("#adminFields").html('');
    $("#adminSections").html("");
    if (data.sectiontype=='sections') {
        $("#adminSections").html('').show();
        var first = true;
        var sectionCount = 0;
        $.each(data.sections, function(subsection, subsectionData) {
            sectionCount++;
            var li = $('<li />').append('<a href="?section='+adminSection+'&subsection='+subsection+'">'+subsectionData.title+'</a>').addClass(subsectionData.type).click(function() {
                $('#adminSections .selected').removeClass('selected');
                $(this).addClass('selected');
                selectSubsection(subsection, subsectionData);
                return false;
            });
            if ((!adminSubsection && first) || (adminSubsection==subsection)) {
                li.addClass('selected');
                selectSubsection(subsection, subsectionData);
                first = false;
            }
            $("#adminSections").append(li);
        });
        if (sectionCount<2) {
            $("#adminSections").hide();
        }
    } else {
        $("#adminSections").hide();
        $.each(createFormSectionListItems(data.section, data), function(k,element) {
            $("#adminFields").append(element);
        });
    }
    if (adminSection=='setup') {
        checkVersion();
    }
}
    
