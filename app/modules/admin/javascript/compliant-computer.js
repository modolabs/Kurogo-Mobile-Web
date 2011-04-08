$(document).ready(function() {
    $('#message').hide();
});

function createFormFieldListItems(key, fieldData) {
    var items = [createFormFieldListItem(key,fieldData)];
    
    if (fieldData.fieldValueGroups) {
    
        $.each(fieldData.fieldValueGroups, function(k,v) {
            $.each(v.fields, function(field, fd) {
                items.push(createFormFieldListItem(field, fd));
            })
        });
    }
    
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
    
    switch (sectionData.sectiontype)
    {
        case 'fields':
            $.each(sectionData.fields, function(key, data) {
                data.section = section;
                var _items = createFormFieldListItems(key, data);
                sectionItems[key] = _items;
                $.merge(items, _items);
                if (data.showIf && data.showIf[0] in sectionData.fields) {
                    $(sectionItems[data.showIf[0]]).find('.changeElement').change(function() {
                        showIfCheck(this, _items, data.showIf[1]);
                    });
                    showIfCheck($(sectionItems[data.showIf[0]]).find('.changeElement').get(0), _items, data.showIf[1]);
                }
            });
            break;
        case 'section':
            items.push(createFormSectionTable(section, sectionData));
            break;
        default:
            alert('Section type ' + sectionData.sectiontype + ' not handled for section ' + section);
            
    }
    
    return items;
}

function createFormFieldListItem(key, fieldData) {
    var listClass='';
    switch (fieldData.type) {
        case 'checkbox':
        case 'inversecheckbox':
            listClass='checkitem';
            break;
        case 'paragraph':
            listClass='tallfield';
            break;
        case 'label':
            listClass='labelfield';
            break;
    }

    var li = $('<li>').attr('class', listClass);

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
    var re;
    
    switch (fieldData.type) {
    
        case 'file':
            var prefixKey = key + '_prefix';
            if (re = key.match(/(.*)\[(.*)\]/)) {
                prefixKey = re[1] + '[' + re[2] + '_prefix]';
            }
        
            parent.append(createSelectBox(fileListTypes(), fieldData.constant).addClass('filePrefix').attr('name', prefixKey).attr('section',section));
            parent.append($('<input/>').attr('type','text').attr('name', key).attr('section', section).attr('value', fieldData.value).addClass('fileData').addClass(inputClass).attr('id',id));
            break;
        case 'number':
            var input = $('<input/>').attr('type','text').attr('name', key).attr('section', section).attr('value', fieldData.value).addClass(inputClass).attr('id',id);
            if ('placeholder' in fieldData) {
                input.attr('placeholder', fieldData.placeholder)
            }
            parent.append(input);
            break;
        case 'password':
        case 'text':
            var input = $('<input/>').attr('type',fieldData.type).attr('name', key).attr('section', section).attr('value', fieldData.value).addClass(inputClass).attr('id',id);
            if ('placeholder' in fieldData) {
                input.attr('placeholder', fieldData.placeholder)
            }
            parent.append(input);
            break;
        case 'inversecheckbox':
            parent.append($('<input/>').attr('type','hidden').attr('name', key).attr('section', section).attr('value', '1'));
            parent.append($('<input/>').attr('type','checkbox').attr('name', key).attr('section', section).attr('value', '0').addClass('changeElement').addClass(inputClass).attr('checked', !parseInt(fieldData.value) ? 'checked':'').attr('id',id));
            break;
        case 'checkbox':
            parent.append($('<input/>').attr('type','hidden').attr('name', key).attr('section', section).attr('value', '0'));
            parent.append($('<input/>').attr('type',fieldData.type).attr('name', key).attr('section', section).attr('value', '1').addClass('changeElement').addClass(inputClass).attr('checked', parseInt(fieldData.value) ? 'checked':'').attr('id',id));
            break;
        case 'radio':
            $.each(fieldData.options, function(value,label) {
                parent.append($('<input/>').attr('type',fieldData.type).attr('name', key).attr('section', section).attr('value', value).addClass(inputClass).addClass('changeElement').attr('checked', fieldData.value==value));
                parent.append(label);
            });
            break;
        case 'select':
            var options = 'options' in fieldData ? fieldData.options : [];
            parent.append(createSelectBox(options, fieldData.value).attr('name',key).attr('section', section).addClass('changeElement').addClass(inputClass).attr('id',id));
            break;
        case 'paragraph':
            parent.append($('<textarea>'+(fieldData.value ? fieldData.value : '')+'</textarea>').attr('name',key).attr('rows','8').attr('section', section).addClass(inputClass).attr('id',id));
            break;
        case 'label':
            parent.append('<span class="labeltext">'+fieldData.value+'</span>');
            break;
        default:
            alert("Don't know how to handle field of type '" + fieldData.type + "' for key '" + key +"'");
            break;
    }
}

function stopSectionEditing(titleField) {
    if (titleField) {
        $('tr.editing .sectiontitle').html($('.editrow.editing input[name*="['+titleField+']"]').val());
    }
    $('.editrow.editing').hide();
    $('.editing').removeClass('editing');
    
}

function createSectionTableRow(section, data, sectionID, sectionData) {
    var rows = [];
    var row = $('<tr />'); 
    
    //use TITLE if present
    var titleField = 'sectiontitlefield' in data ? data.sectiontitlefield : 'TITLE';
    
    var title = titleField in sectionData ? sectionData[titleField] : sectionID;
    
    if (data.sectiontable) {

        $.each(data.fields, function(field, _fieldData) {
            var cell = $('<td />');
            var fieldData = jQuery.extend(true, {}, _fieldData);
    
            if (typeof sectionData[field] != 'undefined') {
                if ($.isArray(sectionData[field])) {
                    fieldData.constant = sectionData[field][0];
                    fieldData.value = sectionData[field][1];
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
    
        row.append($('<td>' + sectionID + '</td>'));
        row.append($('<td class="sectiontitle">' + title + '</td>'));
    
        var rowbuttons = $('<td class="rowbuttons" />');
    
        rowbuttons.append($('<a href="" class="textbutton edit">Edit</a>').click(function() {
            stopSectionEditing(titleField);
            $(this).parents('tr').addClass('editing');
            $(this).parents('tr').next('.editrow').addClass('editing').show();
            return false;
        }));
    }

    if (data.sectiondelete) {
        rowbuttons.append($('<a href="" class="textbutton delete">Remove</a>').click(function() {
            if ($(this).parents('tr').hasClass('notsaved')) {
                reloadSection();
                return false;
            }
            
            if (confirm("Do you want to remove this item? Removal will occur immediately and cannot be undone.")) {

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
    row.append(rowbuttons);
    rows.push(row);
    
    if (!data.sectiontable) {
    
        var row = $('<tr class="editrow" />');
        var cell = $('<td colspan="3" />');
    
        var list = $('<ul class="formfields" />');
        var items = [];
        $.each(data.fields, function(field, _fieldData) {
            var fieldData = jQuery.extend(true, {}, _fieldData);
    
            if (typeof sectionData[field] != 'undefined') {
                if ($.isArray(sectionData[field])) {
                    fieldData.constant = sectionData[field][0];
                    fieldData.value = sectionData[field][1];
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
            list.append(item);
            
            if (fieldData.showIf && fieldData.showIf[0] in data.fields) {
                $(items[fieldData.showIf[0]]).find('.changeElement').change(function() {
                    showIfCheck(this, item, fieldData.showIf[1]);
                });
                showIfCheck($(items[fieldData.showIf[0]]).find('.changeElement'), item, fieldData.showIf[1]);
            }
        });
        cell.append(list);
        var div = $('<div class="rowbuttons" />');
        div.append($('<a href="" class="textbutton save">Done</a>').click(function() {
            stopSectionEditing(titleField);
            return false;
        }));
        cell.append(div);
        row.hide();
        row.append(cell);
        rows.push(row);
    }
    return rows;
}

function createFormSectionTable(section, data) {
    //create main list item
    var li = $('<li>').attr('class', 'tallfield');
    
    //table
    var table = $('<table />').attr('id', section).addClass('subtable');
    if (data.sectiontable) {
        var head = '<thead><tr>';
        $.each(data.fields, function(key, fieldData) {
            head+='<th>' + fieldData.label + '</th>';
        });
        if (data.sectiondelete) {
            head+='<th></th>';
        }
        table.append(head + '</thead');
    }

    var body = $('<tbody>');

    //go through each item in the sections array
    $.each(data.sections, function(sectionID, sectionData) {
        $.each(createSectionTableRow(section, data, sectionID, sectionData), function(i,row) {
            body.append(row);
        });
    });
    
    table.append(body);
    li.append(table);

    if (data.sections.length==0 && data.sectionsnone) {
        li.append('<div class="sectionsnone">' + data.sectionsnone + '</div>');
    }
    
    //add the "Add" button if specified
    if (data.sectionaddnew) {
        var div = $('<div class="tablebuttons" />');
        div.append($('<a href="" class="textbutton add">Add</span>').click(function() {
            stopSectionEditing();
            var sectionID;
            if (data.sectionindex =='numeric') {
                sectionID = data.sections.length;
            } else {
                var sectionaddprompt = 'sectionaddprompt' in data ? data.sectionaddprompt : 'Enter id of new section';
                if (!(sectionID = prompt(sectionaddprompt))) {
                    return false;
                }
            }
            
            var sectionData = {  }
            $.each(createSectionTableRow(section, data, sectionID, sectionData), function(i,row) {
                body.append(row);
                row.addClass('notsaved');
                if (!data.sectiontable) {
                    $(".sectionsnone").hide();
                    row.addClass('editing');
                    if (row.hasClass('editrow')) {
                        row.show();
                    }
                }
            });

            return false;
        }));
        li.append(div);
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
    return {'':'-','FULL_URL_BASE':'FULL_URL_BASE','LOG_DIR':'LOG_DIR','LIB_DIR':'LIB_DIR','CACHE_DIR':'CACHE_DIR','DATA_DIR':'DATA_DIR','SITE_DIR':'SITE_DIR','ROOT_DIR':'ROOT_DIR'};
}

function showMessage(message, error) {
    if (error) {
        $('#message').addClass('error');
    } else {
        $('#message').removeClass('error');
    }
    $('#message').html(message).slideDown('fast').delay(3000).slideUp('slow');
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
            alert("Error: " + textStatus);
        }
    });
}