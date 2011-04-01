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


function createFormSectionListItems(section, sectionData) {
    var items = [];
    
    if (sectionData.fields) {
        $.each(sectionData.fields, function(key, data) {
            data.section = section;
            $.merge(items, createFormFieldListItems(key, data));
        });
    } else if (sectionData.tablefields) {
        $.merge(items, [createFormTable(section, sectionData)]);
    } else if (sectionData.sectionfields) {
        $.merge(items, [createFormSectionTable(section, sectionData)]);
    }
    
    return items;
}

function createFormFieldListItem(key, fieldData) {
    var listClass='';
    switch (fieldData.type) {
        case 'checkbox':
            listClass='checkitem';
            break;
        case 'paragraph':
            listClass='tallfield';
            break;
    }

    var section = typeof fieldData.section == 'undefined' ? '' : fieldData.section;
    var id = typeof fieldData.id == 'undefined' ? null : fieldData.id;
    var li = $('<li>').attr('class', listClass);

    if (fieldData.label) {
        li.append('<label>' + fieldData.label + '</label>');
    }
    
    fieldData.value = 'value' in fieldData ? fieldData.value : ('default' in fieldData ? fieldData['default'] : '');
    
    switch (fieldData.type) {
    
        case 'time':
            li.append($('<input/>').attr('type','text').attr('name', key).attr('section', section).attr('value', fieldData.value).attr('class','timeData').attr('id',id));
            li.append('seconds');
            break;
        case 'file':
            li.append(createSelectBox(fileListTypes(), fieldData.constant).attr('class','filePrefix').attr('name', key+'_prefix').attr('section',section));
            li.append($('<input/>').attr('type','text').attr('name', key).attr('section', section).attr('value', fieldData.value).attr('class','fileData').attr('id',id));
            break;
        case 'number':
            var input = $('<input/>').attr('type','text').attr('name', key).attr('section', section).attr('value', fieldData.value).attr('id',id);
            if ('placeholder' in fieldData) {
                input.attr('placeholder', fieldData.placeholder)
            }
            li.append(input);
            break;
        case 'password':
        case 'text':
            var input = $('<input/>').attr('type',fieldData.type).attr('name', key).attr('section', section).attr('value', fieldData.value).attr('id',id);
            if ('placeholder' in fieldData) {
                input.attr('placeholder', fieldData.placeholder)
            }
            li.append(input);
            break;
        case 'checkbox':
            li.append($('<input/>').attr('type','hidden').attr('name', key).attr('section', section).attr('value', '0'));
            li.append($('<input/>').attr('type',fieldData.type).attr('name', key).attr('section', section).attr('value', '1').attr('checked', parseInt(fieldData.value) ? 'checked':'').attr('id',id));
            break;
        case 'select':
            var options = 'options' in fieldData ? fieldData.options : [];
            li.append(createSelectBox(options, fieldData.value).attr('name',key).attr('section', section).attr('id',id));
            break;
        case 'paragraph':
            li.append($('<textarea>'+(fieldData.value ? fieldData.value : '')+'</textarea>').attr('name',key).attr('rows','5').attr('section', section).attr('id',id));
            break;
        case 'label':
            li.append('<span class="labeltext">'+fieldData.value+'</span>');
            break;
    }

    if (fieldData.description) {
        li.append('<span class="helptext">' + fieldData.description + '</span>');
    }

    return li;
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
    row.append($('<td class="sectiontitle">' + title + '</td>'));

    var rowbuttons = $('<td class="rowbuttons" />');

    rowbuttons.append($('<a href="" class="textbutton edit">Edit</span>').click(function() {
        stopSectionEditing(titleField);
        $(this).parents('tr').addClass('editing');
        $(this).parents('tr').next('.editrow').addClass('editing').show();
        return false;
    }));

    if (data.sectiondelete) {
        rowbuttons.append($('<a href="" class="textbutton delete">Remove</span>').click(function() {
            if (confirm("Do you want to remove this item? Removal will occur immediately and cannot be undone.")) {
                if ($(this).parents('tr').hasClass('notsaved')) {
                    $(this).parents('tr').next('.editrow').remove();
                    $(this).parents('tr').remove();
                    return false;
                }
                
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
                        break;
                    case 'module':
                        params.module = moduleID,
                        break;
                }
                
                var button = this;
                
                makeAPICall('GET', 'admin','removeconfigsection', params, function() {
                    $(button).parents('tr').next('.editrow').remove();
                    $(button).parents('tr').remove();
                });

            }
            return false;
        }));
        
    }
    row.append(rowbuttons);
    rows.push(row);
    
    var row = $('<tr class="editrow" />');
    var cell = $('<td colspan="2" />');

    var list = $('<ul class="formfields" />');
    $.each(data.sectionfields, function(field, _fieldData) {
        var fieldData = jQuery.extend(true, {}, _fieldData);
        if (typeof sectionData[field] != 'undefined') {
            fieldData.value = sectionData[field];
        }
        if (field=='section') {
            fieldData.value = sectionID;
        }
        fieldData.section = section;
        field = sectionID +'['+field+']';
        list.append(createFormFieldListItem(field, fieldData));
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
    return rows;
}

function createFormSectionTable(section, data) {
    //create main list item
    var li = $('<li>').attr('class', 'tallfield');
    if (data.label) {
        li.append('<label>' + data.label + '</label>');
    }
    
    //table
    var table = $('<table />').attr('id', section).attr('class','subtable');
    var body = $('<tbody>');

    //go through each item in the sections array
    $.each(data.sections, function(sectionID, sectionData) {
        $.each(createSectionTableRow(section, data, sectionID, sectionData), function(i,row) {
            body.append(row);
        });
    });
    
    table.append(body);
    li.append(table);
    
    //add the "Add" button if specified
    if (data.sectionaddnew) {
        var div = $('<div class="tablebuttons" />');
        div.append($('<a href="" class="textbutton add">Add</span>').click(function() {
            stopSectionEditing();
            var sectionID;
            if (data.sectionindex =='numeric') {
                sectionID = data.sections.length;
            } else {
                if (!(sectionID = prompt("Enter id of new section"))) {
                    return false;
                }
            }
            
            var sectionData = { 'TITLE':'New Item'}
            $.each(createSectionTableRow(section, data, sectionID, sectionData), function(i,row) {
                body.append(row);
                row.addClass('notsaved').addClass('editing');
                if (row.hasClass('editrow')) {
                    row.show();
                }
            });

            return false;
        }));
        li.append(div);
    }

    //add the description if specified
    if (data.description) {
        li.append('<span class="helptext">' + data.description + '</span>');
    }

    return li;
}

function createFormTable(section, data) {

    var li = $('<li>').attr('class', 'tallfield');
    if (data.label) {
        li.append('<label>' + data.label + '</label>');
    }
    
    var table = $('<table />').attr('id', section).attr('class','subtable');
    var head = '<thead><tr>';
    var fields = [];
    $.each(data.tablefields, function(key, fieldData) {
        fields.push(key);
        head+='<th>' + fieldData.heading + '</th>';
    });
    table.append(head + '</thead');

    var body = $('<tbody>');
    $.each(data.tablerows, function(rowKey, rowValues) {
        var row = $('<tr />'); 
        $.each(fields, function(j, key) {
            var cell = $('<td />');
            if (key=='section') {
                var value = rowKey;
            } else {
                var value = typeof rowValues[key] != 'undefined' ? rowValues[key] : '';
            }
            switch (data.tablefields[key].type) {
                case 'label':
                    cell.append(value);
                    break;
                case 'text':
                    var inputClass = typeof data.tablefields[key]['class'] !='undefined' ? data.tablefields[key]['class'] :'';
                    var name = rowKey + '[' + key + ']';
                    cell.append($('<input />').attr('type','text').attr('name',name).attr('value',value).attr('class',inputClass).attr('section',section));
                    break;
            }
            row.append(cell);
        });
        body.append(row);
    });
    
    table.append(body);
    li.append(table);

    if (data.description) {
        li.append('<span class="helptext">' + data.description + '</span>');
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