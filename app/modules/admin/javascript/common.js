/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

var localizedStrings = {}

$(document).ready(function() {
    getLocalizedString(['BUTTON_ADD','BUTTON_EDIT','BUTTON_DONE','BUTTON_REMOVE','CONFIG_SAVED','ACTION_RUNNING','ACTION_SUCCESSFUL','ADMIN_SECTION_REMOVE_PROMPT','ADMIN_SECTION_ADD_PROMPT']);            
    $('#message').hide();
});

function getLocalizedString(key) {
    if (typeof key=='string' && typeof localizedStrings[key] != 'undefined') {
        return localizedStrings[key];
    }

    makeAPICall('GET', 'admin','getlocalizedstring', { 'v':1, 'key':key}, function(response) {
        $.each(response, function(k,v) {   
            localizedStrings[k] = v;
        });

        if (typeof key=='string' && typeof localizedStrings[key] != 'undefined') {
            return localizedStrings[key];
        }
    });
}


function createFormFieldListItems(key, fieldData) {
    var items = [createFormFieldListItem(key,fieldData)];
    
    return items;
}

function showIfCheck(element, items, value) {
    var show = false;
    var val = $(element).val();
    if (element.type=='checkbox') {
        var val = element.checked ? val : '0';
    }
    if ($.isArray(value)) {
        show = ($.inArray(val, value) != -1);
    } else if (value=='*') {
        show = val.length>0;
    } else {
        show = val == value;
    }

    $(items)[show?'show':'hide']();
    if (show) {
        $(items).find(':input').removeAttr('disabled');
    } else {
        $(items).find(':input').attr('disabled', 'true');
    }
}

function createFormSectionListItems(section, sectionData) {
    var items = [];
    var sectionItems = {};
    var fieldgroups = {}
        
    switch (sectionData.sectiontype)
    {
        case 'fields':
            $.each(sectionData.fields, function(key, data) {
                data.section = section;
                var _items = createFormFieldListItems(key, data);
                sectionItems[key] = _items;
                
                if (data.fieldgroup) {
                    if (sectionData.fieldgroups && typeof sectionData.fieldgroups[data.fieldgroup] != 'undefined') {
                        var groupdata = sectionData.fieldgroups[data.fieldgroup];
                        if (!fieldgroups[data.fieldgroup]) {
                            var fieldgroup = $('<fieldset />').attr('id','fieldgroup_' + data.fieldgroup);
                            fieldgroups[data.fieldgroup] = $('<div class="fieldgroup" />');
                            if (groupdata.label) {
                                var legend = $('<div class="fieldgroup-legend">'+ groupdata.label + '</div>');
                                
                                if (groupdata.collapsable || groupdata.collapsed) {
                                    fieldgroup.addClass('collapsable')
                                    legend.click(function() {
                                        fieldgroup.toggleClass('collapsed');
                                        fieldgroups[data.fieldgroup].slideToggle();
                                    });
                                }
                                fieldgroup.append(legend);
                                if (groupdata.description) {
                                    fieldgroups[data.fieldgroup].append($('<div class="fieldgroup-description">'+groupdata.description+'</div>'));
                                }
                            }
                            fieldgroup.append(fieldgroups[data.fieldgroup]);
                            if (groupdata.collapsed) {
                                fieldgroup.addClass('collapsed');
                                fieldgroups[data.fieldgroup].hide();
                            }
                            $.merge(items, fieldgroup);
                        }
                        
                        fieldgroups[data.fieldgroup].append(_items);
                    } else {
                        alert("Fieldgroup " + data.fieldgroup + " not defined.");
                    }
                } else {
                    $.merge(items, _items);
                }
                if (data.showIf && data.showIf[0] in sectionData.fields) {
                    $(sectionItems[data.showIf[0]]).find('.changeElement').change(function() {
                        showIfCheck(this, _items, data.showIf[1]);
                    });
                    showIfCheck($(sectionItems[data.showIf[0]]).find('.changeElement').get(0), _items, data.showIf[1]);
                }
            });
            break;
        case 'section':
            items.push(createFormSectionList(section, sectionData));
            break;
        default:
            //this represents an error in the admin recipe. Should never happen
            alert('Section type ' + sectionData.sectiontype + ' not handled for section ' + section);
            
    }
    
    return items;
}

function createFormFieldListItem(key, fieldData) {
    var listClass='formfield';
    switch (fieldData.type) {
        case 'checkbox':
        case 'inversecheckbox':
            listClass+=' checkitem';
            break;
        case 'paragraph':
        case 'textarea':
            listClass+=' tallfield';
            break;
        case 'label':
            listClass+=' labelfield';
            break;
    }

    var li = $('<div>').attr('class', listClass);

    if (fieldData.label) {
        li.append('<label>' + fieldData.label + '</label>');
    }
    
    appendFormField(li, key, fieldData);

    if (fieldData.description) {
        li.append('<span class="helptext">' + fieldData.description + '</span>');
    }

    //return a dom element
    return li.get(0);
}

function appendFormField(parent, key, fieldData) {
    fieldData.value = 'value' in fieldData ? fieldData.value : ('default' in fieldData ? fieldData['default'] : '');
    var section = typeof fieldData.section == 'undefined' ? null : fieldData.section;
    var inputClass = typeof fieldData['class'] == 'undefined' ? '' : fieldData['class'];
    var id = typeof fieldData.id == 'undefined' ? null : fieldData.id;
    var disabled = typeof fieldData.enabled == 'undefined' ? '' : (fieldData.enabled ? '' : 'disabled');
    var re;
    
    switch (fieldData.type) {
    
        case 'file':
            var prefixKey = key + '_prefix';
            if (re = key.match(/(.*)\[(.*)\]/)) {
                prefixKey = re[1] + '[' + re[2] + '_prefix]';
            }
        
            parent.append(createSelectBox(fileListTypes(), fieldData.constant).addClass('filePrefix').attr('name', prefixKey).attr('section',section).attr('disabled',disabled));
            parent.append($('<input/>').attr('type','text').attr('name', key).attr('section', section).attr('value', fieldData.value).addClass('fileData').addClass(inputClass).attr('id',id).attr('disabled',disabled));
            break;
        case 'number':
            var input = $('<input/>').attr('type','text').attr('name', key).attr('section', section).attr('value', fieldData.value).addClass(inputClass).attr('id',id).attr('disabled',disabled);
            if ('placeholder' in fieldData) {
                input.attr('placeholder', fieldData.placeholder)
            }
            parent.append(input);
            break;
        case 'password':
        case 'text':
            var input = $('<input/>').attr('type',fieldData.type).attr('name', key).attr('section', section).attr('value', fieldData.value).addClass(inputClass).attr('id',id).attr('disabled',disabled);
            if ('placeholder' in fieldData) {
                input.attr('placeholder', fieldData.placeholder)
            }
            parent.append(input);
            break;
        case 'inversecheckbox':
            parent.append($('<input/>').attr('type','hidden').attr('name', key).attr('section', section).attr('value', '1'));
            parent.append($('<input/>').attr('type','checkbox').attr('name', key).attr('section', section).attr('value', '0').addClass('changeElement').addClass(inputClass).attr('checked', !parseInt(fieldData.value) ? 'checked':'').attr('id',id).attr('disabled',disabled));
            break;
        case 'checkbox':
            if (fieldData.value.length==0 && fieldData.placeholder) {
                fieldData.value = fieldData.placeholder;
            }

            parent.append($('<input/>').attr('type','hidden').attr('name', key).attr('section', section).attr('value', '0'));
            parent.append($('<input/>').attr('type',fieldData.type).attr('name', key).attr('section', section).attr('value', '1').addClass('changeElement').addClass(inputClass).attr('checked', parseInt(fieldData.value) ? 'checked':'').attr('id',id).attr('disabled',disabled));
            break;
        case 'radio':
            $.each(fieldData.options, function(value,label) {
                parent.append($('<input/>').attr('type',fieldData.type).attr('name', key).attr('section', section).attr('value', value).addClass(inputClass).addClass('changeElement').attr('checked', fieldData.value==value).attr('disabled',disabled));
                parent.append(label);
            });
            break;
        case 'select':
            var options = 'options' in fieldData ? fieldData.options : [];
            if (!fieldData.value && 'placeholder' in fieldData) {
                fieldData.value = fieldData.placeholder;
            }
            parent.append(createSelectBox(options, fieldData.value).attr('name',key).attr('section', section).addClass('changeElement').addClass(inputClass).attr('id',id).attr('disabled',disabled));
            break;
        case 'paragraph':
        case 'textarea':
            if (fieldData.rows) {
                var rows = fieldData.rows;
            } else {
                var rows = fieldData.type == 'textarea' ? 4: 8;
            }
            parent.append($('<textarea>'+(fieldData.value ? fieldData.value : '')+'</textarea>').attr('name',key).attr('rows',rows).attr('section', section).addClass(inputClass).attr('id',id).attr('disabled',disabled));
            break;
        case 'label':
            parent.append('<span class="labeltext">'+fieldData.value+'</span>').attr('disabled',disabled);
            break;
        case 'link':
            if (fieldData.value) {
                parent.append('<a href="'+ fieldData.value +'">'+fieldData.value+'</a>');
            }
            break;
        
        case 'hidden':
            parent.append($('<input/>').attr('type',fieldData.type).attr('name', key).attr('section', section).attr('value', fieldData.value).addClass(inputClass).attr('id',id)).addClass('hidden');
            break;
            
        case 'action':
            if (fieldData.dynamicParams) {
                if (typeof fieldData.params == 'undefined') {
                    fieldData.params = {}
                }

                for (param in fieldData.dynamicParams) {
                    switch (fieldData.dynamicParams[param])
                    {
                        case 'moduleID':
                            fieldData.params.module = moduleID;
                            break;
                    }
                }
            }
        
            parent.append($('<a class="formbutton"">').append($('<div>').html(fieldData.value).attr('disabled',disabled)).click(function() {
                showMessage(fieldData.runningMessage ? fieldData.runningMessage : getLocalizedString('ACTION_RUNNING'), false, true);
                makeAPICall('GET','admin',fieldData.action, fieldData.params, function() { 
                    showMessage(fieldData.message ? fieldData.message : getLocalizedString('ACTION_SUCCESSFUL'));
                });
            }));
            break;
        case 'upload':
            var input = $('<input/>').attr('type','file').attr('name', key).attr('section', section).addClass(inputClass).attr('id',id).attr('disabled',disabled);
            parent.append(input);
            break;
        default:
            //this represents an error in the admin recipe. Should never happen
            alert("Don't know how to handle field of type '" + fieldData.type + "' for key '" + key +"'");
            break;
    }
}

function stopSectionEditing(titleField) {
    if (titleField) {
        $('.editing .sectiontitle').html($('.editing .editrow input[name*="['+titleField+']"]').val());
    }
    $('.editing').removeClass('editing');
}

function createSectionListRow(section, data, sectionID, sectionData) {
    var row;
    
    //use TITLE if present
    var titleField = 'sectiontitlefield' in data ? data.sectiontitlefield : 'TITLE';
    
    var title = titleField in sectionData ? sectionData[titleField] : '';
    
    if (data.sectiontable) {

        row = $('<tr />').attr('sectionID',sectionID);
        if (data.sectionreorder) {
            row.append($('<td />').addClass('handle'));
        }
        $.each(data.fields, function(field, _fieldData) {
            var cell = $('<td />');
            var fieldData = jQuery.extend(true, {}, _fieldData);
    
            if (typeof sectionData[field] != 'undefined') {
                if ($.isArray(sectionData[field])) {
                    if (fieldData.type=='file') {
                        fieldData.constant = sectionData[field][0];
                        fieldData.value = sectionData[field][1];
                    } else {
                        fieldData.value = sectionData[field][2];
                    }
                } else {
                    fieldData.value = sectionData[field];
                }
            }
            if (field=='section') {
                fieldData.value = sectionID;
            }
            fieldData.section = section;
            fieldName = sectionID +'['+field+']';
            appendFormField(cell, fieldName, fieldData);
            row.append(cell);
        });
        var rowbuttons = $('<td class="rowbuttons" />');
    
    } else {
    
        var row = $('<li />').attr('sectionID',sectionID).addClass('formfield');
        var listhead = $('<div class="edithead" />');
        row.append(listhead);

        if (data.sectionreorder) {
            listhead.append($('<div />').addClass('handle'));
        }

        if (data.sectionindex =='string') {
            listhead.append($('<span class="sectionid" />').html(sectionID));
        }

        listhead.append($('<span class="sectiontitle" />').html(title));
    
        var rowbuttons = $('<div class="rowbuttons" />');
    
        rowbuttons.append($('<a href="" class="textbutton edit">'+ getLocalizedString('BUTTON_EDIT') + '</a>').click(function() {
            stopSectionEditing(titleField);
            $(this).closest('.formfield').addClass('editing');
            return false;
        }));
    }

    rowbuttons.append($("<input />").attr('type','hidden').addClass('sectionorder').attr('name','sectionorder['+section+'][]').attr('value',sectionID));

    if (data.sectiondelete) {
        rowbuttons.append($('<a href="" class="textbutton delete">'+ getLocalizedString('BUTTON_REMOVE') +'</a>').click(function() {
            if ($(this).closest('.formfield').hasClass('notsaved')) {
                reloadSection();
                return false;
            }
            
            if (confirm(getLocalizedString('ADMIN_SECTION_REMOVE_PROMPT'))) {

                params = {
                    v: '1',
                    type: adminType,
                    section: section,
                    key: sectionID
                }
                
                switch (adminType) 
                {
                    case 'site':
                        params.section = adminSection;
                        if (adminSubsection) {
                            params.subsection = adminSubsection;
                        }
                        break;
                    case 'module':
                        params.module = moduleID;
                        break;
                }
                
                var button = this;
                
                makeAPICall('GET', 'admin','removeconfigsection', params, function() {
                   reloadSection();
                });

            }
            return false;
        }));
        
    }
    
    if (data.sectiontable) {
        row.append(rowbuttons);
    } else {
        listhead.append(rowbuttons);
        var editrow = $('<div class="editrow" />');
        var list = $('<div class="formfields" />');
        var items = [];
        var fieldgroups = {}
        
        $.each(data.fields, function(field, _fieldData) {
            var fieldData = jQuery.extend(true, {}, _fieldData);
    
            if (typeof sectionData[field] != 'undefined') {
                if ($.isArray(sectionData[field])) {
                    if (fieldData.type=='file') {
                        fieldData.constant = sectionData[field][0];
                        fieldData.value = sectionData[field][1];
                    } else {
                        fieldData.value = sectionData[field][2];
                    }
                } else {
                    fieldData.value = sectionData[field];
                }
            }
            if (field=='section') {
                fieldData.value = sectionID;
            }
            fieldData.section = section;
            fieldName = sectionID +'['+field+']';
            var item = createFormFieldListItem(fieldName, fieldData);
            items[field] = item;
            
            if (fieldData.fieldgroup) {
                var groupname = fieldData.fieldgroup;
                if (data.fieldgroups && typeof data.fieldgroups[groupname] != 'undefined') {
                    var groupdata = data.fieldgroups[groupname];
                    if (!fieldgroups[groupname]) {
                        var fieldgroup = $('<fieldset />');
                        fieldgroups[groupname] = $('<div class="fieldgroup" />');
                        if (groupdata.label) {
                            var legend = $('<div class="fieldgroup-legend">'+ groupdata.label + '</div>');
                            if (groupdata.collapsable || groupdata.collapsed) {
                                fieldgroup.addClass('collapsable')
                                legend.click(function() {
                                    fieldgroup.toggleClass('collapsed');
                                    fieldgroups[fieldData.fieldgroup].slideToggle();
                                });
                            }

                            fieldgroup.append(legend);
                            if (groupdata.description) {
                                fieldgroups[groupname].append($('<div class="fieldgroup-description">'+groupdata.description+'</div>'));
                            }
                        }
                        fieldgroup.append(fieldgroups[groupname]);
                        if (groupdata.collapsed) {
                            fieldgroup.addClass('collapsed');
                            fieldgroups[groupname].hide();
                        }
                        list.append(fieldgroup);
                    }
                    
                    fieldgroups[groupname].append(item);
                } else {
                    alert("Fieldgroup " + groupname + " not defined.");
                }
            }  else {
                list.append(item);
            }
            
            if (fieldData.showIf && fieldData.showIf[0] in data.fields) {
                $(items[fieldData.showIf[0]]).find('.changeElement').change(function() {
                    showIfCheck(this, item, fieldData.showIf[1]);
                });
                showIfCheck($(items[fieldData.showIf[0]]).find('.changeElement'), item, fieldData.showIf[1]);
            }
        });
        editrow.append(list);
        var div = $('<div class="rowbuttons" />');
        div.append($('<a href="" class="textbutton save">'+getLocalizedString('BUTTON_DONE')+'</a>').click(function() {
            stopSectionEditing(titleField);
            return false;
        }));
        editrow.append(div);
        row.append(editrow);
        
    }
    return row;
}

function createFormSectionList(section, data) {
    //create main list item
    var li = $('<div>').attr('class', 'tallfield');
    
    if (data.sectiontable) {
        var table = $('<table />').attr('id', section).addClass('subtable');
        var head = '<thead><tr>';
        if (data.sectionreorder) {
            head+='<th />';
        }
        $.each(data.fields, function(key, fieldData) {
            head+='<th>' + fieldData.label + '</th>';
        });
        if (data.sectiondelete) {
            head+='<th></th>';
        }
        table.append(head + '</thead');
        var body = $('<tbody>');
        table.append(body);
        li.append(table);
    } else {
        var body = $('<ul>').addClass('sublist').addClass(data.sectionindex);
        li.append(body);
    }

    //go through each item in the sections array
    $.each(data.sections, function(sectionID, sectionData) {
        $.each(createSectionListRow(section, data, sectionID, sectionData), function(i,row) {
            body.append(row);
        });
    });
    
    if (data.sections.length==0 && data.sectionsnone) {
        li.append('<div class="sectionsnone">' + data.sectionsnone + '</div>');
    }
    
    //add the "Add" button if specified
    if (data.sectionaddnew) {
        var div = $('<div class="tablebuttons" />');
        div.append($('<a href="" class="textbutton add">'+getLocalizedString('BUTTON_ADD') +'</span>').click(function() {
            stopSectionEditing();
            var sectionID;
            if (data.sectionindex =='numeric') {
                sectionID = data.sections.length;
            } else {
                var sectionaddprompt = 'sectionaddprompt' in data ? data.sectionaddprompt : getLocalizedString('ADMIN_SECTION_ADD_PROMPT');
                if (!(sectionID = prompt(sectionaddprompt))) {
                    return false;
                }
            }
            
            var sectionData = { }
            var row = createSectionListRow(section, data, sectionID, sectionData);
            body.append(row);
            row.addClass('notsaved');
            if (!data.sectiontable) {
                $(".sectionsnone").hide();
                row.addClass('editing');
            }

            return false;
        }));
        li.append(div);
    }
    
    if (data.sectionreorder) {
        body.sortable({
            opacity: 0.6,
            handle: '.handle'
        });
    }
    

    return li;
}

function createSelectBox(options, selected) {
    var select = $('<select>');
    $.each(options, function(k,v) {
        select.append($("<option>" + v + "</option>").attr('value', k).attr('selected', selected==k ? 'selected' :''));
    });
    return select;
}

function fileListTypes() {  
    return {'':'-','FULL_URL_BASE':'FULL_URL_BASE','LOG_DIR':'LOG_DIR','LIB_DIR':'LIB_DIR','CACHE_DIR':'CACHE_DIR','DATA_DIR':'DATA_DIR','SHARED_DATA_DIR':'SHARED_DATA_DIR','SHARED_DIR':'SHARED_DIR','SITE_DIR':'SITE_DIR','ROOT_DIR':'ROOT_DIR'};
}

function showMessage(message, error, keep) {
    if (error) {
        $('#message').addClass('errormessage');
    } else {
        $('#message').removeClass('errormessage');
    }

    $('#message').html(message).slideDown('fast');
    
    if (!error && !keep) {
        $('#message').delay(3000).slideUp('slow');
    }
}

function makeAPICall(type, module, command, data, callback) {
    var url = URL_BASE + 'rest/' + module + '/' + command;
    $.ajax({
        type: type,
        url: url,
        data: data, 
        dataType: 'json',
        success: function(data, textStatus, jqXHR) {
            if (data.error) {
                alert(data.error.message);
               return;
            }
                    
            if (callback) {
                callback(data.response);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
        }
    });
}
