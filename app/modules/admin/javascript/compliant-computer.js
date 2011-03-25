function createFormFieldListItems(key, fieldData) {
    var items = [createFormFieldListItem(key,fieldData)];
    
    if (fieldData.fieldValueGroups) {
    
        $.each(fieldData.fieldValueGroups, function(k,v) {
            $.each(v.fields, function(field, fd) {
                items.push(createFormFieldListItem(field, fd));
            })
        });

        /*
        select.change(function() {
            $(selectDiv).find('.selectGroup').hide();
            $(selectDiv).find('div[selectValue="'+$(this).val()+'"]').show();
        }).change();
        */
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
    
    fieldData.value = 'value' in fieldData ? fieldData.value : '';
    
    switch (fieldData.type) {
    
        case 'time':
            li.append($('<input/>').attr('type','text').attr('name', key).attr('section', section).attr('value', fieldData.value).attr('class','timeData').attr('id',id));
            li.append('seconds');
            break;
        case 'file':
            li.append(createSelectBox(fileListTypes(), fieldData.constant).attr('class','filePrefix').attr('name', key+'_filePrefix').attr('section',section));
            li.append($('<input/>').attr('type','text').attr('name', key).attr('section', section).attr('value', fieldData.value).attr('class','fileData').attr('id',id));
            break;
        case 'number':
            li.append($('<input/>').attr('type','text').attr('name', key).attr('section', section).attr('value', fieldData.value).attr('id',id));
            break;
        case 'password':
        case 'text':
            li.append($('<input/>').attr('type',fieldData.type).attr('name', key).attr('section', section).attr('value', fieldData.value).attr('id',id));
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
            li.append(fieldData.value);
            break;
    }

    if (fieldData.description) {
        li.append('<span class="helptext">' + fieldData.description + '</span>');
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